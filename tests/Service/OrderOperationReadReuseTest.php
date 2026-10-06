<?php
namespace ControleOnline\Common\Tests\Service;

use ControleOnline\Entity\{People, Status};
use ControleOnline\Service\{ConfigService, PeopleRoleService, StatusService, SystemLogConfigService};
use Doctrine\ORM\{EntityManagerInterface, EntityRepository};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, RequestStack};

final class OrderOperationReadReuseTest extends TestCase
{
    public function testStatusIsReadOncePerCriteriaAndPerRequest(): void
    {
        $status = (new Status())->setRealStatus('open')->setStatus('open')->setContext('integration');
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly(2))->method('findOneBy')->willReturn($status);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $stack = new RequestStack();
        $stack->push(Request::create('/orders/1', 'PUT'));
        $service = new StatusService($manager, $stack);
        for ($i = 0; $i < 34; $i++) self::assertSame($status, $service->discoveryStatus('open', 'open', 'integration'));
        $stack->pop();
        $stack->push(Request::create('/orders/2', 'PUT'));
        self::assertSame($status, $service->discoveryStatus('open', 'open', 'integration'));
    }

    public function testFallbackLabelIsReusedWithoutChangingDiscoveryRules(): void
    {
        $status = (new Status())->setRealStatus('open')->setStatus('Aberto')->setContext('integration');
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly(2))->method('findOneBy')->willReturnOnConsecutiveCalls(null, $status);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $stack = new RequestStack();
        $stack->push(Request::create('/orders/1', 'PUT'));
        $service = new StatusService($manager, $stack);
        self::assertSame($status, $service->discoveryStatus('open', 'open', 'integration'));
        self::assertSame($status, $service->discoveryStatus('open', 'open', 'integration'));
    }

    public function testLogPolicyIsReadOnceForAnOrderOperationAndAgainForAnotherRequest(): void
    {
        [$service, $stack] = $this->policyFixture(2);
        $stack->push(Request::create('/orders/1/add-products', 'PUT'));
        for ($i = 0; $i < 34; $i++) self::assertTrue($service->shouldPersist('generic'));
        $stack->pop();
        $stack->push(Request::create('/orders/2/add-products', 'PUT'));
        self::assertTrue($service->shouldPersist('generic'));
    }

    public function testConfigurationWritesDoNotCacheLogPolicy(): void
    {
        [$service, $stack] = $this->policyFixture(2);
        $stack->push(Request::create('/configs/1', 'PUT'));
        $service->getLogPolicy();
        $service->getLogPolicy();
    }

    private function policyFixture(int $reads): array
    {
        $roles = $this->createMock(PeopleRoleService::class);
        $roles->method('getMainCompany')->willReturn(new People());
        $configs = $this->createMock(ConfigService::class);
        $configs->expects(self::exactly($reads))->method('getConfig')->willReturn(null);
        $stack = new RequestStack();
        return [new SystemLogConfigService($configs, $roles, $stack), $stack];
    }
}
