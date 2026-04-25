<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Service\DatabaseLogger;
use ControleOnline\Service\SystemLogWriter;
use PHPUnit\Framework\TestCase;

class DatabaseLoggerTest extends TestCase
{
    public function testPersistsGenericLogsWithoutEntityReference(): void
    {
        $logger = $this->createLogger($this->createWriterExpectation(function (
            string $type,
            string $action,
            ?string $class,
            ?int $row,
            array $payload,
            ?string $channel
        ): void {
            self::assertSame('generic', $type);
            self::assertSame('warning', $action);
            self::assertNull($class);
            self::assertNull($row);
            self::assertSame('integration', $channel);
            self::assertSame('integration', $payload['channel']);
            self::assertSame('warning', $payload['level']);
            self::assertSame('Worker started', $payload['message']);
            self::assertSame(99, $payload['context']['job_id']);
        }));

        $logger->warning('Worker started', [
            'job_id' => 99,
        ]);
    }

    public function testPersistsEntityLogsWhenContextIncludesEntityReference(): void
    {
        $entity = new \ControleOnline\Entity\DatabaseLoggerTestEntity(55);
        $logger = $this->createLogger($this->createWriterExpectation(function (
            string $type,
            string $action,
            ?string $class,
            ?int $row,
            array $payload,
            ?string $channel
        ) use ($entity): void {
            self::assertSame('entity', $type);
            self::assertSame('error', $action);
            self::assertSame(\ControleOnline\Entity\DatabaseLoggerTestEntity::class, $class);
            self::assertSame(55, $row);
            self::assertSame('orders', $channel);
            self::assertSame('orders', $payload['channel']);
            self::assertSame('error', $payload['level']);
            self::assertSame('Order action failed', $payload['message']);
            self::assertSame(
                sprintf('Entity:%s#55', \ControleOnline\Entity\DatabaseLoggerTestEntity::class),
                $payload['context']['logEntity']
            );
            self::assertSame('timeout', $payload['context']['reason']);
        }), 'orders');

        $logger->error('Order action failed', [
            'logEntity' => $entity,
            'reason' => 'timeout',
        ]);
    }

    public function testPersistsEntityLogsWhenContextInfersEntityFromIdentifierKey(): void
    {
        $logger = $this->createLogger($this->createWriterExpectation(function (
            string $type,
            string $action,
            ?string $class,
            ?int $row,
            array $payload
        ): void {
            self::assertSame('entity', $type);
            self::assertSame('info', $action);
            self::assertSame(\ControleOnline\Entity\Order::class, $class);
            self::assertSame(44, $row);
            self::assertSame(44, $payload['context']['local_order_id']);
            self::assertSame('synced', $payload['context']['status']);
        }));

        $logger->info('Order sync finished', [
            'local_order_id' => 44,
            'status' => 'synced',
        ]);
    }

    public function testPersistsEntityLogsWhenContextInfersEntityFromCamelCaseKey(): void
    {
        $logger = $this->createLogger($this->createWriterExpectation(function (
            string $type,
            string $action,
            ?string $class,
            ?int $row,
            array $payload
        ): void {
            self::assertSame('entity', $type);
            self::assertSame('notice', $action);
            self::assertSame(\ControleOnline\Entity\OrderProductQueue::class, $class);
            self::assertSame(18, $row);
            self::assertSame(18, $payload['context']['orderProductQueue']);
            self::assertSame('print-requested', $payload['context']['event']);
        }));

        $logger->notice('Queue print requested', [
            'orderProductQueue' => 18,
            'event' => 'print-requested',
        ]);
    }

    private function createLogger(SystemLogWriter $writer, string $channel = 'integration'): DatabaseLogger
    {
        return new DatabaseLogger($writer, $channel);
    }

    private function createWriterExpectation(callable $assertion): SystemLogWriter
    {
        $writer = $this->createMock(SystemLogWriter::class);
        $writer
            ->expects(self::once())
            ->method('write')
            ->willReturnCallback(function (
                string $type,
                string $action,
                ?string $class,
                ?int $row,
                array $payload,
                ?string $channel = null
            ) use ($assertion): bool {
                $assertion($type, $action, $class, $row, $payload, $channel);

                return true;
            });

        return $writer;
    }
}

namespace ControleOnline\Entity;

class DatabaseLoggerTestEntity
{
    public function __construct(private int $id) {}

    public function getId(): int
    {
        return $this->id;
    }
}
