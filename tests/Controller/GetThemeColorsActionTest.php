<?php

namespace ControleOnline\Common\Tests\Controller;

use ControleOnline\Controller\GetThemeColorsAction;
use ControleOnline\Entity\PeopleDomain;
use ControleOnline\Entity\Theme;
use ControleOnline\Service\DomainService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GetThemeColorsActionTest extends TestCase
{
    public function testReturnsCssVariablesForDomainTheme(): void
    {
        $theme = (new Theme())->setColors([
            'primary' => '#4090D0',
            'buttonBackground' => '#4090D0',
        ]);
        $peopleDomain = (new PeopleDomain())
            ->setDomain('pos.controleonline.com')
            ->setTheme($theme);

        $response = $this->createAction($peopleDomain)(new Request());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/css', $response->headers->get('Content-Type'));
        self::assertStringContainsString('--primary: #4090D0;', $response->getContent());
        self::assertStringContainsString('--q-buttonBackground: #4090D0;', $response->getContent());
    }

    public function testReturnsNotFoundWhenDomainDoesNotExist(): void
    {
        $response = $this->createAction(null)(new Request());

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    public function testReturnsNotFoundWhenDomainHasNoTheme(): void
    {
        $peopleDomain = (new PeopleDomain())->setDomain('crm.controleonline.com');

        $response = $this->createAction($peopleDomain)(new Request());

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    private function createAction(?PeopleDomain $peopleDomain): GetThemeColorsAction
    {
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['domain' => 'crm.controleonline.com'])
            ->willReturn($peopleDomain);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->with(PeopleDomain::class)
            ->willReturn($repository);

        $domainService = $this->createMock(DomainService::class);
        $domainService
            ->method('getDomain')
            ->willReturn('crm.controleonline.com');

        return new GetThemeColorsAction($entityManager, $domainService);
    }
}
