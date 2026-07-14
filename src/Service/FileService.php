<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleMedia;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class FileService
{

  public function __construct(
    private EntityManagerInterface $manager,
    private DomainService $domainService,
    private PdfService $pdfService

  ) {}


  public function getFileUrl(People $people): ?array
  {
    if ($people->getImage() instanceof File)
      return $this->buildFileUrl($people->getImage());

    return null;
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
      'url'    => '/files/' . $file->getId() . '/download'
    ];
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
