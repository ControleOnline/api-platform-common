<?php

namespace ControleOnline\Controller;

use Symfony\Component\HttpFoundation\Response;
use ControleOnline\Entity\File;
use ControleOnline\Service\HydratorService;
use ControleOnline\Service\FileService;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class FileConvertController
{

    public function __construct(
        private HydratorService $hydratorService,
        private FileService $fileService
    ) {}

    public function __invoke(File $data): Response
    {

        try {
            $this->fileService->convertHtmlFileToPdf($data);
            return new JsonResponse($this->hydratorService->data($data, 'file:read'), Response::HTTP_OK);
        } catch (Exception $e) {
            return new JsonResponse($this->hydratorService->error($e));
        }
    }
}
