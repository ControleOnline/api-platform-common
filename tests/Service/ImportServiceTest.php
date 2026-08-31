<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\Import;
use ControleOnline\Entity\Status;
use ControleOnline\Repository\ImportRepository;
use ControleOnline\Service\ImportService;
use ControleOnline\Service\Imports\ImportProcessorInterface;
use ControleOnline\Service\Imports\ImportProcessorResolver;
use ControleOnline\Service\StatusService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ImportServiceTest extends TestCase
{
    public function testGetImportsToProcessDelegatesOpenAndStaleProcessing(): void
    {
        $open = $this->createStatusStub();
        $processing = $this->createStatusStub();
        $expected = [new Import()];

        $repository = $this->createMock(ImportRepository::class);
        $repository
            ->expects($this->once())
            ->method('getImportsToProcess')
            ->with(
                $this->identicalTo($open),
                $this->identicalTo($processing),
                $this->callback(function (\DateTimeInterface $staleBefore) {
                    $now = new \DateTime();
                    $diffMinutes = ($now->getTimestamp() - $staleBefore->getTimestamp()) / 60;
                    return $diffMinutes >= ImportService::STALE_PROCESSING_MINUTES - 1
                        && $diffMinutes <= ImportService::STALE_PROCESSING_MINUTES + 1;
                }),
                50
            )
            ->willReturn($expected);

        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->expects($this->exactly(2))
            ->method('discoveryStatus')
            ->willReturnCallback(function (string $real, string $color, string $ctx) use ($open, $processing) {
                if ($real === 'open' && $color === 'open') {
                    return $open;
                }
                if ($real === 'pending' && $color === 'processing') {
                    return $processing;
                }
                $this->fail("Unexpected discoveryStatus($real, $color, $ctx)");
            });

        $service = new ImportService(
            $repository,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ImportProcessorResolver::class),
            $statusService
        );

        $result = $service->getImportsToProcess(50);
        $this->assertSame($expected, $result);
    }

    public function testExecuteImportMarksDoneOnSuccess(): void
    {
        $processing = $this->createStatusStub();
        $done = $this->createStatusStub();

        $import = new Import();
        $import->setImportType('product');

        $processor = $this->createMock(ImportProcessorInterface::class);
        $processor->expects($this->once())->method('process')->with($import);

        $resolver = $this->createMock(ImportProcessorResolver::class);
        $resolver->method('resolve')->with('product')->willReturn($processor);

        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->method('discoveryStatus')
            ->willReturnCallback(function (string $real, string $color) use ($processing, $done) {
                if ($color === 'processing') {
                    return $processing;
                }
                if ($color === 'done') {
                    return $done;
                }
                $this->fail("Unexpected status $color");
            });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->atLeast(2))->method('persist')->with($import);
        $em->expects($this->atLeast(2))->method('flush');

        $service = new ImportService(
            $this->createMock(ImportRepository::class),
            $em,
            $resolver,
            $statusService
        );

        $service->executeImport($import);
        $this->assertSame($done, $import->getStatus());
    }

    public function testExecuteImportMarksErrorAndRethrowsOnFailure(): void
    {
        $processing = $this->createStatusStub();
        $error = $this->createStatusStub();

        $import = new Import();
        $import->setImportType('product');

        $processor = $this->createMock(ImportProcessorInterface::class);
        $processor
            ->method('process')
            ->willThrowException(new \RuntimeException('linha quebrada'));

        $resolver = $this->createMock(ImportProcessorResolver::class);
        $resolver->method('resolve')->willReturn($processor);

        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->method('discoveryStatus')
            ->willReturnCallback(function (string $real, string $color) use ($processing, $error) {
                if ($color === 'processing') {
                    return $processing;
                }
                if ($color === 'error') {
                    return $error;
                }
                $this->fail("Unexpected status $color");
            });

        $em = $this->createMock(EntityManagerInterface::class);

        $service = new ImportService(
            $this->createMock(ImportRepository::class),
            $em,
            $resolver,
            $statusService
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('linha quebrada');

        try {
            $service->executeImport($import);
        } finally {
            $this->assertSame($error, $import->getStatus());
            $this->assertSame('linha quebrada', $import->getFeedback());
        }
    }

    private function createStatusStub(): Status
    {
        return $this->getMockBuilder(Status::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
