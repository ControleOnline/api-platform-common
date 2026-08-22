<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleMedia;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

class FileService
{
  public function __construct(
    private EntityManagerInterface $manager,
    private DomainService $domainService,
    private PdfService $pdfService,
    private PeopleService $peopleService,
    private RequestStack $requestStack,
  ) {}

  /**
   * File reads are tenant-scoped by the owning People record.
   *
   * The upload library accepts context/people query parameters from the client,
   * so those values must only narrow the set of companies already granted by
   * PeopleService::getMyCompanies(); they must never expand visibility.
   */
  public function securityFilter(
    QueryBuilder $queryBuilder,
    $resourceClass = null,
    $applyTo = null,
    $rootAlias = null
  ): void {
    $rootAlias ??= $queryBuilder->getRootAliases()[0] ?? null;
    if (!$rootAlias) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }

    $companies = $this->peopleService->getMyCompanies();
    if ($companies === []) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }

    $queryBuilder->andWhere(sprintf('%s.people IN(:fileSecurityCompanies)', $rootAlias));
    $queryBuilder->setParameter('fileSecurityCompanies', $companies);

    $request = $this->requestStack->getCurrentRequest();
    $requestedPeople = $request?->query->get('people');
    if ($requestedPeople === null || $requestedPeople === '') {
      return;
    }

    $peopleId = (int) preg_replace('/\D+/', '', (string) $requestedPeople);
    if ($peopleId <= 0) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }

    $queryBuilder->andWhere(sprintf('%s.people = :fileSecurityPeople', $rootAlias));
    $queryBuilder->setParameter('fileSecurityPeople', $peopleId);
  }

  public function getFileUrl(People $people): ?array
  {
    $mediaType = $people->getPeopleType() === 'F' ? 'avatar' : 'logo';

    return $this->getPeopleMediaFileUrl($people, $mediaType);
  }

  public function getPeopleMediaFileUrl(People $people, string $mediaType): ?array
  {
    $resolvedMediaType = strtolower(trim($mediaType));
    if ($resolvedMediaType === '') {
      return null;
    }

    $peopleMedia = $this->manager
      ->getRepository(PeopleMedia::class)
      ->createQueryBuilder('peopleMedia')
      ->innerJoin('peopleMedia.mediaType', 'mediaType')
      ->andWhere('peopleMedia.people = :people')
      ->andWhere('mediaType.type = :type')
      ->setParameter('people', $people)
      ->setParameter('type', $resolvedMediaType)
      ->orderBy('peopleMedia.id', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

    if (!$peopleMedia instanceof PeopleMedia || !$peopleMedia->getFile() instanceof File) {
      return null;
    }

    return $this->buildFileUrl($peopleMedia->getFile());
  }

  public function buildFileUrl(?File $file): ?array
  {
    if (!$file instanceof File) {
      return null;
    }

    return [
      'id'     => $file->getId(),
      'domain' => $this->domainService->getMainDomain(),
      'url'    => '/files/' . $file->getId() . '/download',
      'fileType' => $file->getFileType(),
      'public' => $file->isPublic()
    ];
  }

  public function addFile(?People $people, string $content, string $context, ?string $fileName = null, ?string $fileType = null, ?string $extension = null, bool $public = false): File
  {
    return $this->manager->getRepository(File::class)->addFile($people, $content, $context, $fileName, $fileType, $extension, $public);
  }

  public function resolvePeopleReference(mixed $peopleReference): ?People
  {
    $peopleId = (int) preg_replace('/\D+/', '', (string) $peopleReference);
    if ($peopleId <= 0) {
      return null;
    }

    return $this->manager->getRepository(People::class)->find($peopleId);
  }

  public function addUploadedFile(
    UploadedFile $uploadedFile,
    ?People $people = null,
    ?string $context = null
  ): File {
    if (!$uploadedFile->isValid()) {
      throw new \InvalidArgumentException(
        'Upload invalido: ' . $uploadedFile->getErrorMessage()
      );
    }

    $pathname = (string) $uploadedFile->getPathname();
    if ($pathname === '' || !is_readable($pathname)) {
      throw new \InvalidArgumentException(
        'Arquivo temporario de upload ausente ou ilegivel.'
      );
    }

    $content = file_get_contents($pathname);
    if ($content === false || $content === '') {
      throw new \InvalidArgumentException('Conteudo do arquivo vazio ou ilegivel.');
    }

    // Prefer client-provided MIME; avoid FileinfoMimeTypeGuesser on empty paths.
    $clientMime = (string) $uploadedFile->getClientMimeType();
    if ($clientMime === '' || !str_contains($clientMime, '/')) {
      $ext = strtolower((string) $uploadedFile->getClientOriginalExtension());
      $clientMime = match ($ext) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
        'csv' => 'text/csv',
        default => 'application/octet-stream',
      };
    }
    $mimeType = explode('/', $clientMime, 2);
    $fileType = $mimeType[0] ?? 'application';
    $extension = $mimeType[1] ?? strtolower($uploadedFile->getClientOriginalExtension() ?: 'bin');
    $resolvedContext = (string) ($context ?: '');

    // Images used as people_media / avatar must be publicly downloadable:
    // browser <Image> cannot send Authorization on web → otherwise 403.
    // GetFileDataAction allows public image downloads without ROLE_HUMAN.
    $isImage = strtolower((string) $fileType) === 'image';
    $peopleMediaContext = in_array(
      strtolower($resolvedContext),
      ['people_media', 'avatar', 'logo', 'people'],
      true
    );
    $public = $isImage && $peopleMediaContext;

    return $this->addFile(
      $people,
      $content,
      $resolvedContext,
      $uploadedFile->getClientOriginalName(),
      $fileType,
      $extension,
      $public
    );
  }

  public function convertHtmlFileToPdf(File $file): File
  {
    if ($file->getFileType() !== 'text' || $file->getExtension() !== 'html') {
      return $file;
    }

    $file->setFileType('application');
    $file->setExtension('pdf');
    $file->setContent($this->pdfService->convertHtmlToPdf($file->getContent()));

    $this->manager->persist($file);
    $this->manager->flush();

    return $file;
  }

  public function removeFile(File $file)
  {
    $this->manager->remove($file);
    $this->manager->flush();
  }
}
