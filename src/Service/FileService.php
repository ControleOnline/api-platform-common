<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;


class FileService
{

    public function __construct(
        private  EntityManagerInterface $manager,
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
}
