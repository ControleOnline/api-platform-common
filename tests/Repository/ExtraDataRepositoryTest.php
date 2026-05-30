<?php

namespace ControleOnline\Tests\Repository;

use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\ExtraFields;
use ControleOnline\Repository\ExtraDataRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ExtraDataRepositoryTest extends TestCase
{
    public function testUpsertValuePersistsSourceForMarketplaceWrites(): void
    {
        $extraFields = $this->createExtraFields(44);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::once())
            ->method('fetchAssociative')
            ->with(
                self::callback(static function (string $sql): bool {
                    return str_contains($sql, 'FROM extra_data')
                        && str_contains($sql, 'FOR UPDATE');
                }),
                self::callback(static function (array $parameters): bool {
                    return (int) ($parameters['extra_fields_id'] ?? 0) === 44
                        && (string) ($parameters['entity_id'] ?? '') === '71670'
                        && ($parameters['entity_name'] ?? '') === 'Order';
                })
            )
            ->willReturn(false);
        $connection->expects(self::once())
            ->method('executeStatement')
            ->willReturnCallback(static function (string $sql, array $parameters): int {
                TestCase::assertStringContainsString('INSERT INTO extra_data', $sql);
                TestCase::assertSame([
                    'extra_fields_id' => 44,
                    'entity_id' => '71670',
                    'entity_name' => 'Order',
                    'data_value' => 'abc',
                    'source' => 'Food99',
                ], $parameters);

                return 1;
            });
        $connection->expects(self::once())
            ->method('commit');
        $connection->expects(self::never())
            ->method('rollBack');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(ExtraData::class)
            ->willReturn(new ClassMetadata(ExtraData::class));
        $entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->with(ExtraData::class)->willReturn($entityManager);
        $managerRegistry->method('getManager')->willReturn($entityManager);

        $repository = new ExtraDataRepository($managerRegistry);

        $repository->upsertValue($extraFields, 'Order', 71670, 'abc', 'Food99');

        self::assertTrue(true);
    }

    public function testUpsertValueRecoversFromDuplicateInsertWithoutClosingEntityManager(): void
    {
        $extraFields = $this->createExtraFields(44);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('beginTransaction');
        $connection->expects(self::exactly(2))
            ->method('fetchAssociative')
            ->willReturnOnConsecutiveCalls(
                false,
                [
                    'id' => 99,
                    'data_value' => 'abc',
                    'source' => null,
                ]
            );
        $connection->expects(self::exactly(2))
            ->method('executeStatement')
            ->willReturnCallback(static function (string $sql, array $parameters) use (&$callCount): int {
                $callCount = ($callCount ?? 0) + 1;

                if ($callCount === 1) {
                    TestCase::assertStringContainsString('INSERT INTO extra_data', $sql);
                    TestCase::assertSame([
                        'extra_fields_id' => 44,
                        'entity_id' => '71670',
                        'entity_name' => 'Order',
                        'data_value' => 'abc',
                        'source' => 'Food99',
                    ], $parameters);

                    throw new UniqueConstraintViolationException(
                        new \Doctrine\DBAL\Driver\PDO\Exception('duplicate', '23000', 1062, null),
                        null
                    );
                }

                TestCase::assertStringContainsString('UPDATE extra_data SET data_value = :data_value, source = :source', $sql);
                TestCase::assertSame([
                    'id' => 99,
                    'data_value' => 'abc',
                    'source' => 'Food99',
                ], $parameters);

                return 1;
            });
        $connection->expects(self::once())
            ->method('commit');
        $connection->expects(self::never())
            ->method('rollBack');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with(ExtraData::class)
            ->willReturn(new ClassMetadata(ExtraData::class));
        $entityManager->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->with(ExtraData::class)->willReturn($entityManager);
        $managerRegistry->method('getManager')->willReturn($entityManager);

        $repository = new ExtraDataRepository($managerRegistry);

        $repository->upsertValue($extraFields, 'Order', 71670, 'abc', 'Food99');

        self::assertTrue(true);
    }

    private function createExtraFields(int $id): ExtraFields
    {
        $extraFields = new ExtraFields();
        $extraFields->setName('code');
        $extraFields->setContext('Food99');
        $extraFields->setType('text');
        $extraFields->setRequired(false);
        $extraFields->setConfigs('{}');

        $property = new \ReflectionProperty(ExtraFields::class, 'id');
        $property->setAccessible(true);
        $property->setValue($extraFields, $id);

        return $extraFields;
    }
}
