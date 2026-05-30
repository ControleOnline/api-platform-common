<?php

namespace ControleOnline\Common\Tests\Listener;

use ControleOnline\Event\EntityChangedEvent;
use ControleOnline\Listener\DefaultEventListener;
use ControleOnline\Service\ExtraDataService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DefaultEventListenerTest extends TestCase
{
    public function testPreUpdateDispatchesRealPreviousEntityState(): void
    {
        $oldInformations = ['order_action' => ['name' => 'confirm']];
        $newInformations = ['order_action' => ['name' => 'ready']];
        $entity = new DefaultEventListenerProbe(42, 'Food99', $newInformations);

        $extraDataService = $this->createMock(ExtraDataService::class);
        $extraDataService
            ->expects(self::once())
            ->method('persist')
            ->with($entity);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(function (EntityChangedEvent $event) use ($entity, $oldInformations, $newInformations) {
                self::assertSame('preUpdate', $event->getPhase());
                self::assertSame($entity, $event->getEntity());
                self::assertNotSame($entity, $event->getOldEntity());
                self::assertSame('Food99', $event->getOldEntity()->getApp());
                self::assertSame($oldInformations, $event->getOldEntity()->getOtherInformations());
                self::assertSame($newInformations, $event->getEntity()->getOtherInformations());

                return true;
            }))
            ->willReturnArgument(0);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('has')
            ->willReturn(false);

        $listener = new DefaultEventListener(
            $this->createStub(EntityManagerInterface::class),
            $extraDataService,
            $dispatcher,
            $container,
        );

        $changeSet = [
            'otherInformations' => [$oldInformations, $newInformations],
        ];

        $listener->preUpdate(new PreUpdateEventArgs(
            $entity,
            $this->createStub(EntityManagerInterface::class),
            $changeSet,
        ));
    }
}

final class DefaultEventListenerProbe
{
    public function __construct(
        private int $id,
        private string $app,
        private array $otherInformations,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getApp(): string
    {
        return $this->app;
    }

    public function getOtherInformations(): array
    {
        return $this->otherInformations;
    }
}
