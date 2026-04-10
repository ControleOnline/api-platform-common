<?php

namespace ControleOnline\Command;

use ControleOnline\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Lock\LockFactory;
use ControleOnline\Service\DatabaseSwitchService;
use ControleOnline\Service\DomainService;
use ControleOnline\Service\ImportService;
use ControleOnline\Service\LoggerService;
use ControleOnline\Service\SkyNetService;
use Throwable;

class ImportCommand extends DefaultCommand
{
    public function __construct(
        LockFactory $lockFactory,
        DatabaseSwitchService $databaseSwitchService,
        LoggerService $loggerService,
        SkyNetService $skyNetService,
        private ImportService $importService,
        private EntityManagerInterface $entityManager,
        private StatusService $statusService
    ) {

        $this->lockFactory = $lockFactory;
        $this->databaseSwitchService = $databaseSwitchService;
        $this->loggerService = $loggerService;
        $this->skyNetService = $skyNetService;

        parent::__construct('import:start');
    }

    protected function configure(): void
    {
        $this->setDescription('Processa a fila de importações pendentes');
    }

    protected function runCommand(): int
    {
        if (!$this->lock->acquire()) {
            $this->addLog('Outro processo ainda está em execução. Ignorando...');
            return Command::SUCCESS;
        }

        $this->addLog('Iniciando processamento da fila de importações...');

        $imports = $this->importService->getAllOpenImports(50);

        foreach ($imports as $import) {

            try {

                $this->addLog(sprintf(
                    'Processando import ID: %d - type: %s',
                    $import->getId(),
                    $import->getImportType()
                ));

                $this->importService->executeImport($import);

            } catch (Throwable $e) {

                $statusError = $this->statusService->discoveryStatus(
                    'pending',
                    'error',
                    'integration'
                );

                $this->addLog(sprintf(
                    '<error>Erro ao processar import ID: %d. Erro: %s</error>',
                    $import->getId(),
                    $e->getMessage()
                ));

                $this->addLog($e->getFile());
                $this->addLog($e->getLine());

                $import->setStatus($statusError);

                $this->entityManager->persist($import);
                $this->entityManager->flush();
            }
        }

        $this->addLog('Processamento da fila de importações concluído.');

        return Command::SUCCESS;
    }
}