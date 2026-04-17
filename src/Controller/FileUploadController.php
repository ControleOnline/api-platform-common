<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use ControleOnline\Service\FileService;
use ControleOnline\Service\ImportService;
use ControleOnline\Service\HydratorService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileUploadController
{

    public function __construct(
        private HydratorService $hydratorService,
        private FileService $fileService,
        private ImportService $importService
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

            $people = $this->fileService->resolvePeopleReference($people_id);
            $fileEntity = $this->fileService->addUploadedFile(
                $file,
                $people,
                $context
            );

            $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

            if ($extension === 'csv') {
                $this->importService->createCsvImport(
                    $fileEntity,
                    $people,
                    $context ?: 'people'
                );
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
