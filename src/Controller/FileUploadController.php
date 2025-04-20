<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\HydratorService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileUploadController
{

    public function __construct(
        private EntityManagerInterface $em,
        private HydratorService $hydratorService,
        private FileService $fileService
    ) {}

    public function __invoke(Request $request): Response
    {

        try {
            $file = $request->files->get('file');
            $people_id = $request->request->get('people');
            $context = $request->request->get('context');
            //$file_id = $request->request->get('id');


            if (!$file) {
                throw new BadRequestHttpException('No file provided');
            }

            $content = file_get_contents($file->getPathname());
            $fileType = explode('/', $file->getClientMimeType());
            $originalFilename = $file->getClientOriginalName();

            //if ($file_id)
            //    $fileEntity = $this->em->getRepository(File::class)->find($file_id);
            //if (!$fileEntity)
            $people = $this->em->getRepository(People::class)->find($people_id);

            $fileEntity = $this->fileService->addFile($people, $content, $context, $originalFilename, $fileType[0], $fileType[1]);

            return new JsonResponse($this->hydratorService->data($fileEntity, 'file:read'), Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e));
        }
    }
}
