<?php
namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Entity\File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

class FileUploadController
{

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function upload(Request $request): Response
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        if (!$file) {
            throw new BadRequestHttpException('No file provided');
        }

        $content = file_get_contents($file->getPathname()); 
        $fileType = $file->getClientMimeType();
        $originalFilename = $file->getClientOriginalName();

        $fileEntity = new File();
        $fileEntity->setContent($content);
        $fileEntity->setFileType($fileType);

        $this->em->persist($fileEntity);
        $this->em->flush();

        return new Response('File uploaded successfully', Response::HTTP_CREATED);
    }
}
