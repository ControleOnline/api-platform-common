<?php

namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Entity\Order;
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

    private function createUser(int $id, string $username): User
    {
        $user = new User();
        $user->setUsername($username);

        $property = new \ReflectionProperty(User::class, 'id');
        $property->setAccessible(true);
        $property->setValue($user, $id);

        return $user;
    }
}
