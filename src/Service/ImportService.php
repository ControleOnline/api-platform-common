<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Import;
use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Repository\ImportRepository;
use ControleOnline\Service\Imports\ImportProcessorResolver;
use ControleOnline\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;

class ImportService
{

    public function __construct(
        private ImportRepository $repository,
        private EntityManagerInterface $entityManager,
        private ImportProcessorResolver $resolver,
        private StatusService $statusService
    ) {}

    public function getAllOpenImports(int $limit)
    {
        $status = $this->statusService->discoveryStatus(
            'open',
            'open',
            'integration'
        );

        return $this->repository->getImportsByStatus($status, $limit);
    }

    public function executeImport(Import $import): void
    {
        $processor = $this->resolver->resolve($import->getImportType());

        $statusProcessing = $this->statusService->discoveryStatus(
            'pending',
            'processing',
            'integration'
        );

        $import->setStatus($statusProcessing);

        $this->entityManager->persist($import);
        $this->entityManager->flush();

        try {

            $processor->process($import);

            $statusDone = $this->statusService->discoveryStatus(
                'pending',
                'done',
                'integration'
            );

            $import->setStatus($statusDone);
        } catch (\Throwable $e) {

            $statusError = $this->statusService->discoveryStatus(
                'pending',
                'error',
                'integration'
            );

            $import->setStatus($statusError);
            $import->setFeedback($e->getMessage());

            throw $e;
        }

        $this->entityManager->persist($import);
        $this->entityManager->flush();
    }

    public function getExampleCsv(string $type): array
    {
        $processor = $this->resolver->resolve($type);

        return $processor->getExampleCsv();
    }

    public function createCsvImport(
        File $file,
        ?People $people,
        string $importType
    ): Import {
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

        if ($people instanceof People) {
            $import->setPeople($people);
        }

        $this->entityManager->persist($import);
        $this->entityManager->flush();

        return $import;
    }
}
