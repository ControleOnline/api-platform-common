<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;


class FileService
{

  public function __construct(
    private EntityManagerInterface $manager,
    private DomainService $domainService

  ) {}


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

  public function addFile(People $people, string  $content, string $context, string $fileName, string $fileType, string $extension): File
  {
    return $this->manager->getRepository(File::class)->addFile($people, $content, $context, $fileName, $fileType, $extension);
  }

  public function removeFile(File $file)
  {
    $this->manager->remove($file);
    $this->manager->flush();
  }
}
