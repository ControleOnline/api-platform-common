<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\UploadedFile;


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
   * Scope File collection/item queries to companies the current user can access.
   * File.people is the ownership/company pointer (app-community#296).
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
    if (empty($companies)) {
      $queryBuilder->andWhere('1 = 0');
      return;
    }

    $queryBuilder->andWhere(sprintf('%s.people IN(:fileSecurityCompanies)', $rootAlias));
    $queryBuilder->setParameter('fileSecurityCompanies', $companies);

    $request = $this->requestStack->getCurrentRequest();
    if ($people = $request?->query->get('people', null)) {
      $peopleId = (int) preg_replace('/\D+/', '', (string) $people);
      if ($peopleId > 0) {
        $queryBuilder->andWhere(sprintf('%s.people = :fileSecurityPeople', $rootAlias));
        $queryBuilder->setParameter('fileSecurityPeople', $peopleId);
      }
    }
  }


  public function getFileUrl(People $people): ?array
  {
    if ($people->getImage() instanceof File)
      return [
        'id'     => $people->getImage()->getId(),
        'domain' => $this->domainService->getMainDomain(),
        'url'    => '/files/' . $people->getImage()->getId() . '/download'
      ];

    return null;
  }

  public function addFile(?People $people, string  $content, string $context, ?string $fileName = null, ?string $fileType = null, ?string $extension = null): File
  {
    return $this->manager->getRepository(File::class)->addFile($people, $content, $context, $fileName, $fileType, $extension);
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
    $content = file_get_contents($uploadedFile->getPathname());
    $mimeType = explode('/', (string) $uploadedFile->getClientMimeType());

    return $this->addFile(
      $people,
      $content,
      (string) ($context ?: ''),
      $uploadedFile->getClientOriginalName(),
      $mimeType[0] ?? null,
      $mimeType[1] ?? strtolower($uploadedFile->getClientOriginalExtension())
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
