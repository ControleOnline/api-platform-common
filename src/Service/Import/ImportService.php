<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Import;
use ControleOnline\Repository\ImportRepository;
use ControleOnline\Service\Import\ImportProcessorResolver;
use Doctrine\ORM\EntityManagerInterface;

class ImportService
{

    public function __construct(
        private ImportRepository $repository,
        private EntityManagerInterface $entityManager,
        private ImportProcessorResolver $resolver,
        private StatusService $statusService
    ) {}

    public function getAllOpenImports(int $limit): array
    {
        return $this->repository->getOpenImports($limit);
    }

    public function executeImport(Import $import): void
    {
        $processor = $this->resolver->resolve($import->getImportType());

        $statusProcessing = $this->statusService->discoveryStatus(
            'pending',
            'processing',
            'import'
        );

        $import->setStatus($statusProcessing);

        $this->entityManager->persist($import);
        $this->entityManager->flush();

        try {

            $processor->process($import);

            $statusDone = $this->statusService->discoveryStatus(
                'pending',
                'done',
                'import'
            );

            $import->setStatus($statusDone);
        } catch (\Throwable $e) {

            $statusError = $this->statusService->discoveryStatus(
                'pending',
                'error',
                'import'
            );

            $import->setStatus($statusError);
            $import->setFeedback($e->getMessage());

            throw $e;
        }

        $this->entityManager->persist($import);
        $this->entityManager->flush();
    }

    public function getExampleCsv(string $type): string
    {
        $processor = $this->resolver->resolve($type);

        return $processor->getExampleCsv();
    }
}
