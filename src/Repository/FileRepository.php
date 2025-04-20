<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    public function addFile(People $people, string  $content, string $context, string $fileName, string $fileType, string $extension): File
    {

        $file = new File();
        $file->setContext($context);
        $file->setContent($content);
        $file->setFileName($fileName);
        $file->setFileType($fileType);
        $file->setExtension($extension);
        $file->setPeople($people);
        $this->em->persist($file);
        $this->em->flush();

        return $file;
    }
}
