<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use ControleOnline\Service\HydratorService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileUploadController
{

    public function __construct(
        private EntityManagerInterface $em,
        private HydratorService $hydratorService
    ) {}

    public function __invoke(Request $request): Response
    {

        try {
            $file = $request->files->get('file');
            $people_id = $request->request->get('people');

            if (!$file) {
                throw new BadRequestHttpException('No file provided');
            }

            $content = file_get_contents($file->getPathname());
            $fileType = $file->getClientMimeType();
            $originalFilename = $file->getClientOriginalName();

            $fileEntity = new File();
            $fileEntity->setContent($content);
            $fileEntity->setFileType($fileType);
            $fileEntity->setPeople(
                $this->em->getRepository(People::class)->find($people_id)
            );
            $this->em->persist($fileEntity);
            $this->em->flush();
            return new JsonResponse($this->hydratorService->data($fileEntity, 'file_read'), Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e));
        }
    }
}
