<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\FileService;
use ControleOnline\Service\ImportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ImportUploadController extends AbstractController
{
    public function __construct(
        private FileService $fileService,
        private ImportService $importService
    ) {}

    public function __invoke(Request $request): Response
    {

        $importType = $request->request->get('importType');
        $peopleId = $request->request->get('people');


        $uploadedFile = $request->files->get('file');

        if (!$peopleId) {
            throw new BadRequestHttpException('people is required');
        }
        if (!$importType) {
            throw new BadRequestHttpException('importType is required');
        }


        if (!$uploadedFile) {
            throw new BadRequestHttpException('CSV file is required');
        }

        $extension = strtolower($uploadedFile->getClientOriginalExtension());

        if ($extension !== 'csv') {
            throw new BadRequestHttpException('Only CSV files are allowed');
        }

        $people = $this->fileService->resolvePeopleReference($peopleId);
        $file = $this->fileService->addUploadedFile($uploadedFile, $people, 'import');
        $import = $this->importService->createCsvImport($file, $people, $importType);

        $data = [
            'id' => $import->getId(),
            'importType' => $import->getImportType(),
            'fileName' => $import->getFile()->getFileName(),
            'status' => $import->getStatus()->getStatus(),
        ];

        return new JsonResponse($data, 200);
    }
}
