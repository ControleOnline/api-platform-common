<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Service\DatabaseLogger;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DatabaseLoggerTest extends TestCase
{
    public function testPersistsGenericLogsWithoutEntityReference(): void
    {
        $logger = $this->createLogger($this->createConnectionExpectation(function (array $data): void {
            self::assertSame('generic', $data['type']);
            self::assertSame('warning', $data['action']);
            self::assertNull($data['class']);
            self::assertNull($data['row']);
            self::assertSame(21, $data['user_id']);

            $payload = json_decode($data['object'], true, 512, JSON_THROW_ON_ERROR);
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
        $logger = $this->createLogger($this->createConnectionExpectation(function (array $data) use ($entity): void {
            self::assertSame('entity', $data['type']);
            self::assertSame('error', $data['action']);
            self::assertSame(\ControleOnline\Entity\DatabaseLoggerTestEntity::class, $data['class']);
            self::assertSame(55, $data['row']);
            self::assertSame(21, $data['user_id']);

            $payload = json_decode($data['object'], true, 512, JSON_THROW_ON_ERROR);
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
        $logger = $this->createLogger($this->createConnectionExpectation(function (array $data): void {
            self::assertSame('entity', $data['type']);
            self::assertSame('info', $data['action']);
            self::assertSame(\ControleOnline\Entity\Order::class, $data['class']);
            self::assertSame(44, $data['row']);

            $payload = json_decode($data['object'], true, 512, JSON_THROW_ON_ERROR);
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
        $logger = $this->createLogger($this->createConnectionExpectation(function (array $data): void {
            self::assertSame('entity', $data['type']);
            self::assertSame('notice', $data['action']);
            self::assertSame(\ControleOnline\Entity\OrderProductQueue::class, $data['class']);
            self::assertSame(18, $data['row']);

            $payload = json_decode($data['object'], true, 512, JSON_THROW_ON_ERROR);
            self::assertSame(18, $payload['context']['orderProductQueue']);
            self::assertSame('print-requested', $payload['context']['event']);
        }));

        $logger->notice('Queue print requested', [
            'orderProductQueue' => 18,
            'event' => 'print-requested',
        ]);
    }

    private function createLogger(Connection $connection, string $channel = 'integration'): DatabaseLogger
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(new DatabaseLoggerTestUser(21));

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        return new DatabaseLogger($connection, $tokenStorage, $channel);
    }

    private function createConnectionExpectation(callable $assertion): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with('log', self::callback(function (array $data) use ($assertion): bool {
                $assertion($data);

                return true;
            }))
            ->willReturn(1);

        return $connection;
    }
}

class DatabaseLoggerTestUser implements UserInterface
{
    public function __construct(private int $id) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return 'database-logger-test-user';
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
