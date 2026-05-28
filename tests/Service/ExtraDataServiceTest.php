<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\ExtraFields;
use ControleOnline\Entity\Order;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use ControleOnline\Service\DeviceService;
use ControleOnline\Service\ExtraDataService;
use ControleOnline\Service\SkyNetService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ExtraDataServiceTest extends TestCase
{
    public function testUpsertExtraDataValueSkipsBlankValues(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())
            ->method('getRepository');
        $manager->expects(self::never())
            ->method('persist');
        $manager->expects(self::never())
            ->method('flush');

        $service = new ExtraDataService(
            $manager,
            $this->createStub(RequestStack::class),
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(DeviceService::class),
            $this->createStub(SkyNetService::class),
        );

        $service->upsertExtraDataValue('Food99', 'Order', 71670, 'code', '   ', 'text', 'Food99');

        self::assertTrue(true);
    }

    public function testUpsertExtraDataValuePersistsSourceForMarketplaceWrites(): void
    {
        $extraFields = $this->createExtraFields(44, 'code', 'Food99');

        $extraFieldsRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findOneBy'])
            ->getMock();
        $extraFieldsRepository->expects(self::once())
            ->method('findOneBy')
            ->with([
                'name' => 'code',
                'type' => 'text',
                'context' => 'Food99',
            ])
            ->willReturn($extraFields);
        $extraFieldsRepository->expects(self::never())
            ->method('find');

        $extraDataRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findOneBy'])
            ->getMock();
        $extraDataRepository->expects(self::once())
            ->method('findOneBy')
            ->with([
                'extra_fields' => $extraFields,
                'entity_name' => 'Order',
                'entity_id' => 71670,
            ])
            ->willReturn(null);
        $extraDataRepository->expects(self::never())
            ->method('find');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls($extraFieldsRepository, $extraDataRepository);
        $manager->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (object $entity) use ($extraFields): bool {
                if (!$entity instanceof ExtraData) {
                    return false;
                }

                return $entity->getExtraFields() === $extraFields
                    && $entity->getEntityName() === 'Order'
                    && (int) $entity->getEntityId() === 71670
                    && $entity->getValue() === 'abc'
                    && $entity->getSource() === 'Food99';
            }));
        $manager->expects(self::once())
            ->method('flush');

        $service = new ExtraDataService(
            $manager,
            $this->createStub(RequestStack::class),
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(DeviceService::class),
            $this->createStub(SkyNetService::class),
        );

        $service->upsertExtraDataValue('Food99', 'Order', 71670, 'code', ' abc ', 'text', 'Food99');

        self::assertTrue(true);
    }

    public function testDiscoveryUserReattachesBotUserThroughCurrentEntityManager(): void
    {
        $incomingBotUser = $this->createUser(17, 'SkyNet');
        $managedBotUser = $this->createUser(17, 'SkyNet');

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findOneBy'])
            ->getMock();
        $repository->expects(self::once())
            ->method('find')
            ->with(17)
            ->willReturn($managedBotUser);
        $repository->expects(self::never())
            ->method('findOneBy');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())
            ->method('contains')
            ->with($incomingBotUser)
            ->willReturn(false);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $security = $this->createStub(TokenStorageInterface::class);
        $security->method('getToken')->willReturn(null);

        $skyNetService = $this->createMock(SkyNetService::class);
        $skyNetService->expects(self::once())
            ->method('getBotUser')
            ->willReturn($incomingBotUser);
        $skyNetService->expects(self::never())
            ->method('discoveryBotUser');

        $service = new ExtraDataService(
            $manager,
            $this->createStub(RequestStack::class),
            $security,
            $this->createStub(DeviceService::class),
            $skyNetService,
        );

        $order = new Order();
        $service->discoveryUser($order);

        self::assertSame($managedBotUser, $order->getUser());
        self::assertNotSame($incomingBotUser, $order->getUser());
    }

    public function testGetEntityByExtraDataSupportsClassStringEntityNames(): void
    {
        $extraFields = $this->createExtraFields(44, 'code', 'Food99');
        $people = $this->createPeople(3);
        $extraData = new ExtraData();
        $extraData->setEntityId(3);
        $extraData->setEntityName('People');
        $extraData->setValue('3');
        $extraData->setExtraFields($extraFields);

        $extraFieldsRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findOneBy'])
            ->getMock();
        $extraFieldsRepository->expects(self::once())
            ->method('findOneBy')
            ->with([
                'name' => 'code',
                'type' => 'text',
                'context' => 'Food99',
            ])
            ->willReturn($extraFields);
        $extraFieldsRepository->expects(self::never())
            ->method('find');

        $extraDataRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findOneBy'])
            ->getMock();
        $extraDataRepository->expects(self::once())
            ->method('findOneBy')
            ->with([
                'extra_fields' => $extraFields,
                'entity_name' => 'People',
                'value' => '3',
            ])
            ->willReturn($extraData);
        $extraDataRepository->expects(self::never())
            ->method('find');

        $peopleRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find', 'findOneBy'])
            ->getMock();
        $peopleRepository->expects(self::once())
            ->method('find')
            ->with(3)
            ->willReturn($people);
        $peopleRepository->expects(self::never())
            ->method('findOneBy');

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::exactly(3))
            ->method('getRepository')
            ->willReturnOnConsecutiveCalls($extraFieldsRepository, $extraDataRepository, $peopleRepository);

        $service = new ExtraDataService(
            $manager,
            $this->createStub(RequestStack::class),
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(DeviceService::class),
            $this->createStub(SkyNetService::class),
        );

        $resolved = $service->getEntityByExtraData('Food99', 'code', '3', People::class);

        self::assertSame($people, $resolved);
    }

    private function createUser(int $id, string $username): User
    {
        $user = new User();
        $user->setUsername($username);

        $property = new \ReflectionProperty(User::class, 'id');
        $property->setAccessible(true);
        $property->setValue($user, $id);

        return $user;
    }

    private function createPeople(int $id): People
    {
        $people = new People();

        $property = new \ReflectionProperty(People::class, 'id');
        $property->setAccessible(true);
        $property->setValue($people, $id);

        return $people;
    }

    private function createExtraFields(int $id, string $name, string $context): ExtraFields
    {
        $extraFields = new ExtraFields();
        $extraFields->setName($name);
        $extraFields->setContext($context);
        $extraFields->setType('text');
        $extraFields->setRequired(false);
        $extraFields->setConfigs('{}');

        $property = new \ReflectionProperty(ExtraFields::class, 'id');
        $property->setAccessible(true);
        $property->setValue($extraFields, $id);

        return $extraFields;
    }
}
