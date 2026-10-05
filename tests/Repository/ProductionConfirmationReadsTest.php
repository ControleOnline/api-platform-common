<?php
namespace ControleOnline\Common\Tests\Repository;

use ControleOnline\Entity\{Status, DeviceConfig, People};
use ControleOnline\Repository\{StatusRepository, DeviceConfigRepository};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\{EntityManagerInterface, EntityRepository};
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, RequestStack};

final class ProductionConfirmationReadsTest extends TestCase
{
    private function repository(string $class, EntityRepository $delegate, RequestStack $stack): object
    {
        $repository = new $class($this->createStub(ManagerRegistry::class), $stack);
        $property = new \ReflectionProperty(ServiceEntityRepository::class, 'repository');
        $property->setValue($repository, $delegate);
        return $repository;
    }

    private function stack(string $path = '/orders/50/confirm', string $method = 'POST'): RequestStack
    {
        $stack = new RequestStack();
        $stack->push(Request::create($path, $method));
        return $stack;
    }

    public function testRepeatedStatusesUseOneReadButAnotherRequestAndChangedStatusReadAgain(): void
    {
        $status = (new Status())->setContext('order')->setRealStatus('open')->setStatus('preparing');
        $delegate = $this->createMock(EntityRepository::class);
        $delegate->expects(self::exactly(3))->method('findOneBy')->willReturn($status);
        $stack = $this->stack();
        $repository = $this->repository(StatusRepository::class, $delegate, $stack);
        $criteria = ['context' => 'order', 'realStatus' => 'open', 'status' => 'preparing'];
        self::assertSame($status, $repository->findOneBy($criteria));
        for ($i = 0; $i < 11; $i++) self::assertSame($status, $repository->findOneBy(array_reverse($criteria, true)));
        $stack->pop(); $stack->push(Request::create('/orders/51/confirm', 'POST'));
        self::assertSame($status, $repository->findOneBy($criteria));
        $status->setStatus('ready');
        self::assertSame($status, $repository->findOneBy($criteria));
    }

    public function testNullStatusIsNotCachedSoLaterDiscoveryCanFindIt(): void
    {
        $status = new Status();
        $delegate = $this->createMock(EntityRepository::class);
        $delegate->expects(self::exactly(2))->method('findOneBy')->willReturnOnConsecutiveCalls(null, $status);
        $repository = $this->repository(StatusRepository::class, $delegate, $this->stack());
        $criteria = ['context' => 'integration', 'realStatus' => 'open', 'status' => 'open'];
        self::assertNull($repository->findOneBy($criteria));
        self::assertSame($status, $repository->findOneBy($criteria));
    }

    public function testDeviceRecipientsArePreservedAndCompaniesAreIsolated(): void
    {
        $first = new People(); $second = new People();
        $id = new \ReflectionProperty(People::class, 'id'); $id->setValue($first, 3); $id->setValue($second, 4);
        $rows = [new DeviceConfig(), new DeviceConfig()];
        $delegate = $this->createMock(EntityRepository::class);
        $delegate->expects(self::exactly(2))->method('findBy')->willReturn($rows);
        $repository = $this->repository(DeviceConfigRepository::class, $delegate, $this->stack());
        for ($i = 0; $i < 7; $i++) self::assertSame($rows, $repository->findBy(['people' => $first]));
        self::assertSame($rows, $repository->findBy(['people' => $second]));
    }

    public function testReadsOutsideConfirmAndPaginatedQueriesKeepTheirOriginalBehavior(): void
    {
        $statusDelegate = $this->createMock(EntityRepository::class);
        $statusDelegate->expects(self::exactly(2))->method('findOneBy')->willReturn(new Status());
        $repository = $this->repository(StatusRepository::class, $statusDelegate, $this->stack('/statuses', 'GET'));
        for ($i = 0; $i < 2; $i++) $repository->findOneBy(['context' => 'order', 'realStatus' => 'open', 'status' => 'open']);
        $deviceDelegate = $this->createMock(EntityRepository::class);
        $deviceDelegate->expects(self::exactly(2))->method('findBy')->with(['people' => 3], null, 1, 0)->willReturn([]);
        $repository = $this->repository(DeviceConfigRepository::class, $deviceDelegate, $this->stack());
        for ($i = 0; $i < 2; $i++) $repository->findBy(['people' => 3], null, 1, 0);
    }
}
