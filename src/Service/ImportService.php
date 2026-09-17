<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Import;
use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use ControleOnline\Repository\ImportRepository;
use ControleOnline\Service\Imports\ImportProcessorResolver;
use ControleOnline\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImportService
{
    /**
     * Minutos após os quais um import em "processing" é considerado
     * estagnado e elegível a reprocessamento pelo worker.
     */
    public const STALE_PROCESSING_MINUTES = 15;

    private const FORBIDDEN_EXTENSIONS = ['*', '*.*', '', '.', '.*'];

    private const EXTENSIONS_BY_TYPE = [
        'csv' => ['csv'],
        'product' => ['csv'],
        'people' => ['csv'],
        'client' => ['csv'],
        'provider' => ['csv'],
        'prospect' => ['csv'],
        'invoice_tax' => ['xml', 'zip'],
        'xml' => ['xml', 'zip'],
    ];

    public function __construct(
        private ImportRepository $repository,
        private EntityManagerInterface $entityManager,
        private ImportProcessorResolver $resolver,
        private StatusService $statusService
    ) {}

    /**
     * @deprecated Prefer getImportsToProcess() — mantido por compatibilidade.
     */
    public function getAllOpenImports(int $limit)
    {
        $status = $this->statusService->discoveryStatus(
            'open',
            'open',
            'integration'
        );

        return $this->repository->getImportsByStatus($status, $limit);
    }

    /**
     * @return Import[]
     */
    public function getImportsToProcess(int $limit): array
    {
        $openStatus = $this->statusService->discoveryStatus(
            'open',
            'open',
            'integration'
        );

        $processingStatus = $this->statusService->discoveryStatus(
            'pending',
            'processing',
            'integration'
        );

        $staleBefore = new \DateTime(sprintf(
            '-%d minutes',
            self::STALE_PROCESSING_MINUTES
        ));

        return $this->repository->getImportsToProcess(
            $openStatus,
            $processingStatus,
            $staleBefore,
            $limit
        );
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

    /**
     * @return list<string>
     */
    public function allowedExtensionsForType(string $importType): array
    {
        $type = strtolower(trim($importType));
        $extensions = self::EXTENSIONS_BY_TYPE[$type] ?? ['csv'];
        $clean = [];

        foreach ($extensions as $extension) {
            $normalized = strtolower(ltrim((string) $extension, '.'));
            if ($normalized === '' || in_array($normalized, self::FORBIDDEN_EXTENSIONS, true) || str_contains($normalized, '*')) {
                continue;
            }
            $clean[] = $normalized;
        }

        if ($clean === []) {
            throw new BadRequestHttpException('Tipo de importacao sem allowlist valida. Importar *.* nao e permitido.');
        }

        return array_values(array_unique($clean));
    }

    public function createCsvImport(
        File $file,
        ?People $people,
        string $importType
    ): Import {
        return $this->createImport($file, $people, $importType, 'csv');
    }

    public function createImport(
        File $file,
        ?People $people,
        string $importType,
        string $fileFormat
    ): Import {
        $allowed = $this->allowedExtensionsForType($importType);
        $format = strtolower(ltrim($fileFormat, '.'));

        if (!in_array($format, $allowed, true)) {
            throw new BadRequestHttpException(sprintf(
                'Formato %s nao permitido para %s.',
                $format,
                $importType
            ));
        }

        $status = $this->statusService->discoveryStatus(
            'open',
            'open',
            'integration'
        );

        $import = new Import();
        $import->setImportType($importType);
        $import->setFileFormat($format);
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
