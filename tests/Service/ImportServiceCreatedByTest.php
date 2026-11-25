<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\File;
use ControleOnline\Entity\Import;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Status;
use ControleOnline\Repository\ImportRepository;
use ControleOnline\Service\ImportService;
use ControleOnline\Service\Imports\ImportProcessorResolver;
use ControleOnline\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ImportServiceCreatedByTest extends TestCase
{
    public function testCreateImportRecordsUploader(): void
    {
        $statusService = $this->createMock(StatusService::class);
        $statusService->method('discoveryStatus')->willReturn($this->createStub(Status::class));

        $persisted = null;
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(function ($entity) use (&$persisted) {
            $persisted = $entity;
        });

        $service = new ImportService(
            $this->createMock(ImportRepository::class),
            $manager,
            $this->createMock(ImportProcessorResolver::class),
            $statusService
        );

        $company = new People();
        $seller = new People();
        $import = $service->createImport($this->createStub(File::class), $company, 'prospect', 'csv', $seller);

        $this->assertInstanceOf(Import::class, $persisted);
        $this->assertSame($company, $import->getPeople());
        $this->assertSame($seller, $import->getCreatedBy());
    }
}
