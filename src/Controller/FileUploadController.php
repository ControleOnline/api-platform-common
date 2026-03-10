<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Import;
use ControleOnline\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\StatusService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileUploadController
{

    public function __construct(
        private EntityManagerInterface $em,
        private HydratorService $hydratorService,
        private FileService $fileService,
        private StatusService $statusService
    ) {}

    public function __invoke(Request $request): Response
    {

        try {

            $file = $request->files->get('file');
            $people_id = $request->request->get('people');
            $context = $request->request->get('context');

            if (!$file) {
                throw new BadRequestHttpException('No file provided');
            }

            $content = file_get_contents($file->getPathname());
            $fileType = explode('/', $file->getClientMimeType());
            $originalFilename = $file->getClientOriginalName();

            $people = null;

            if ($people_id) {
                $people = $this->em->getRepository(People::class)->find($people_id);
            }

            $fileEntity = $this->fileService->addFile(
                $people,
                $content,
                $context,
                $originalFilename,
                $fileType[0],
                $fileType[1]
            );

            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

            if ($extension === 'csv') {

                $import = new Import();

                $import->setFile($fileEntity);
                $import->setPeople($people);
                $import->setFileFormat('csv');
                $import->setImportType($context ?: 'people');
                $import->setStatus(
                    $this->statusService->discoveryStatus(
                        'open',
                        'open',
                        'integration'
                    )
                );
                $this->em->persist($import);
                $this->em->flush();
            }

            return new JsonResponse(
                $this->hydratorService->data($fileEntity, 'file:read'),
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {

            return new JsonResponse(
                $this->hydratorService->error($e)
            );
        }
    }
}
