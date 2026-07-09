<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Service\DomainService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DomainServiceTest extends TestCase
{
    public function testGetDomainUsesTheCurrentRequestEachTime(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push($this->createRequest('manager.controleonline.com'));

        $service = new DomainService(
            $this->createStub(EntityManagerInterface::class),
            $requestStack,
        );

        self::assertSame('manager.controleonline.com', $service->getDomain());

        $requestStack->pop();
        $requestStack->push($this->createRequest('admin.controleonline.com'));

        self::assertSame('admin.controleonline.com', $service->getDomain());
    }

    public function testGetPeopleDomainRefreshesWhenTheDomainChanges(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push($this->createRequest('manager.controleonline.com'));

        $managerDomain = (new PeopleDomain())->setDomain('manager.controleonline.com');
        $adminDomain = (new PeopleDomain())->setDomain('admin.controleonline.com');

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $repository
            ->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(static function (array $criteria) use ($managerDomain, $adminDomain) {
                return match ($criteria['domain'] ?? null) {
                    'manager.controleonline.com' => $managerDomain,
                    'admin.controleonline.com' => $adminDomain,
                    default => null,
                };
            });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->with(PeopleDomain::class)
            ->willReturn($repository);

        $service = new DomainService($entityManager, $requestStack);

        self::assertSame($managerDomain, $service->getPeopleDomain());

        $requestStack->pop();
        $requestStack->push($this->createRequest('admin.controleonline.com'));

        self::assertSame($adminDomain, $service->getPeopleDomain());
    }

    private function createRequest(string $domain): Request
    {
        $request = Request::create(sprintf('https://%s/', $domain));
        $request->headers->set('app-domain', $domain);

        return $request;
    }
}
