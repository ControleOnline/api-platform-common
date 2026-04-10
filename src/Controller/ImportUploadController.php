<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\File;
use ControleOnline\Entity\Import;
use ControleOnline\Entity\People;
use ControleOnline\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ImportUploadController extends AbstractController
{

    private EntityManagerInterface $em;

    public function __construct(
        private StatusService $statusService,
        EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

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


        $file = new File();

        $file->setFileName($uploadedFile->getClientOriginalName());
        $file->setExtension('csv');
        $file->setFileType('import');
        $file->setContext('import');
        $file->setContent(file_get_contents($uploadedFile->getPathname()));

        $people =   $this->em->getRepository(People::class)->find(
            str_replace('/\D/', '', $peopleId)
        );

        if ($people) {
            $file->setPeople($people);
        }

        $this->em->persist($file);

        $status = $this->statusService->discoveryStatus(
            'open',
            'open',
            'integration'
        );

        $import = new Import();

        $import->setImportType($importType);
        $import->setFileFormat('csv');
        $import->setFile($file);
        $import->setStatus($status);

        if ($people) {
            $import->setPeople($people);
        }

        $this->em->persist($import);

        $this->em->flush();

        $data = [
            'id' => $import->getId(),
            'importType' => $import->getImportType(),
            'fileName' => $import->getFile()->getFileName(),
            'status' => $import->getStatus()->getStatus(),
        ];

        return new Response(json_encode($data), 200, ['Content-Type' => 'application/json']);
    }
}
