<?php
namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Service\{SystemLogWriter, SystemLogConfigService};
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SystemLogWriterBatchTest extends TestCase
{
    public function testAllAuditRowsKeepTheirPayloadAndIdentifiersWithOneDatabaseStatement(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(fn ($name) => $name);
        $connection->expects(self::never())->method('insert');
        $connection->expects(self::once())->method('executeStatement')->with(
            self::callback(fn ($sql) => str_starts_with($sql, 'INSERT INTO log ') && substr_count($sql, '(?, ?, ?, ?, ?, ?)') === 34),
            self::callback(function ($values): bool {
                self::assertCount(204, $values);
                foreach (array_chunk($values, 6) as $i => $row) {
                    self::assertSame('entity', $row[0]);
                    self::assertSame('insert', $row[1]);
                    self::assertSame('Integration', $row[2]);
                    self::assertSame(['device' => $i + 1], json_decode($row[3], true));
                    self::assertSame($i + 1, $row[4]);
                    self::assertNull($row[5]);
                }
                return true;
            }))->willReturn(34);
        $policies = $this->createMock(SystemLogConfigService::class);
        $policies->expects(self::exactly(34))->method('shouldPersist')->with('entity', null)->willReturn(true);
        $writer = new SystemLogWriter($connection, $this->createStub(TokenStorageInterface::class), $policies);
        $records = array_map(fn ($id) => ['type' => ' ENTITY ', 'action' => ' INSERT ', 'class' => 'Integration',
            'row' => $id, 'payload' => ['device' => $id]], range(1, 34));
        self::assertSame(34, $writer->writeMany($records));
    }

    public function testDisabledPoliciesDoNotWriteAndChannelIsPreservedInAcceptedPayload(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(fn ($name) => $name);
        $connection->expects(self::never())->method('insert');
        $connection->expects(self::once())->method('executeStatement')->with(self::anything(),
            ['generic', 'info', null, '{"message":"test","channel":"frontend-debug"}', null, null])->willReturn(1);
        $policies = $this->createMock(SystemLogConfigService::class);
        $policies->method('shouldPersist')->willReturnCallback(fn ($type, $channel) => $channel === 'frontend-debug');
        $writer = new SystemLogWriter($connection, $this->createStub(TokenStorageInterface::class), $policies);
        self::assertSame(1, $writer->writeMany([
            ['type' => 'entity', 'action' => 'insert'],
            ['type' => 'generic', 'action' => 'info', 'channel' => ' frontend-debug ', 'payload' => ['message' => 'test']],
        ]));
        self::assertSame(0, $writer->writeMany([]));
        self::assertSame(0, $writer->writeMany([['type' => 'entity', 'action' => 'insert']]));
    }

    public function testEntityListenerPreservesGeneratedIdsAndUserInOneBatch(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(fn ($name) => $name);
        $connection->expects(self::never())->method('insert');
        $connection->expects(self::once())->method('executeStatement')->with(self::anything(),
            self::callback(function ($values): bool {
                self::assertCount(12, $values);
                foreach (array_chunk($values, 6) as $i => $row) {
                    self::assertSame('entity', $row[0]);
                    self::assertSame('insert', $row[1]);
                    self::assertSame($i + 101, $row[4]);
                    self::assertSame(7, $row[5]);
                    self::assertSame($i + 101, json_decode($row[3], true)['id']);
                }
                return true;
            }))->willReturn(2);
        $policy = $this->createStub(SystemLogConfigService::class);
        $policy->method('shouldPersist')->willReturn(true);
        $user = $this->createStub(\ControleOnline\Entity\User::class);
        $user->method('getId')->willReturn(7);
        $token = $this->createStub(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $storage = $this->createStub(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);
        $writer = new SystemLogWriter($connection, $storage, $policy);
        $listener = new \ControleOnline\Listener\LogListener($writer);
        $rows = [new \ControleOnline\Entity\Integration(), new \ControleOnline\Entity\Integration()];
        $metadata = new \Doctrine\ORM\Mapping\ClassMetadata(\ControleOnline\Entity\Integration::class);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->initializeReflection(new \Doctrine\Persistence\Mapping\RuntimeReflectionService());
        $metadata->wakeupReflection(new \Doctrine\Persistence\Mapping\RuntimeReflectionService());
        $uow = $this->createStub(\Doctrine\ORM\UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn($rows);
        $manager = $this->createStub(\Doctrine\ORM\EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($uow);
        $manager->method('getClassMetadata')->willReturn($metadata);
        $listener->onFlush(new \Doctrine\ORM\Event\OnFlushEventArgs($manager));
        foreach ($rows as $i => $row) (new \ReflectionProperty($row, 'id'))->setValue($row, $i + 101);
        $listener->postFlush(new \Doctrine\ORM\Event\PostFlushEventArgs($manager));
        $listener->postFlush(new \Doctrine\ORM\Event\PostFlushEventArgs($manager));
    }

    public function testLargeBatchKeepsEveryRowAcrossBoundedStatements(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(fn ($name) => $name);
        $ids = [];
        $connection->expects(self::exactly(3))->method('executeStatement')
            ->willReturnCallback(function ($sql, $values) use (&$ids) {
                self::assertLessThanOrEqual(600, count($values));
                foreach (array_chunk($values, 6) as $row) $ids[] = $row[4];
                return intdiv(count($values), 6);
            });
        $policy = $this->createStub(SystemLogConfigService::class);
        $policy->method('shouldPersist')->willReturn(true);
        $writer = new SystemLogWriter($connection, $this->createStub(TokenStorageInterface::class), $policy);
        self::assertSame(201, $writer->writeMany(array_map(fn ($id) =>
            ['type' => 'entity', 'action' => 'insert', 'row' => $id], range(1, 201))));
        self::assertSame(range(1, 201), $ids);
    }

    public function testSingleWriteRetainsItsExistingReturnAndRowContract(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('insert')->with('log',
            ['type' => 'generic', 'action' => 'info', 'class' => null,
             'object' => '{"channel":"channel-a"}', 'row' => null, 'user_id' => null])->willReturn(1);
        $policy = $this->createStub(SystemLogConfigService::class);
        $policy->method('shouldPersist')->willReturnCallback(fn ($type) => $type === 'generic');
        $writer = new SystemLogWriter($connection, $this->createStub(TokenStorageInterface::class), $policy);
        self::assertTrue($writer->write('', '', null, null, ['channel' => 'channel-a']));
        self::assertFalse($writer->write('entity', 'insert'));
    }

    public function testDatabaseFailureIsNotSilenced(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeStatement')->willThrowException(new \RuntimeException('audit unavailable'));
        $policies = $this->createStub(SystemLogConfigService::class);
        $policies->method('shouldPersist')->willReturn(true);
        $writer = new SystemLogWriter($connection, $this->createStub(TokenStorageInterface::class), $policies);
        $this->expectExceptionMessage('audit unavailable');
        $writer->writeMany([['type' => 'entity', 'action' => 'insert']]);
    }
}
