<?php

namespace ControleOnline\Tests\Controller;

use ControleOnline\Controller\GetPublicShopCategoriesAction;
use ControleOnline\Controller\GetPublicShopCategoryAction;
use ControleOnline\Service\PublicCategoryProjectionGuard;
use ControleOnline\Service\PublicShopCategoryService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PublicShopCategoryProjectionTest extends TestCase
{
    #[DataProvider('clientProjectionProvider')]
    public function testCollectionRouteRejectsClientProjection(array $query): void
    {
        $service = $this->createMock(PublicShopCategoryService::class);
        $service->expects(self::never())->method('getCollection');
        $controller = new GetPublicShopCategoriesAction(
            $service,
            new PublicCategoryProjectionGuard()
        );

        $this->expectException(BadRequestHttpException::class);
        $controller(new Request($query));
    }

    #[DataProvider('clientProjectionProvider')]
    public function testItemRouteRejectsClientProjection(array $query): void
    {
        $service = $this->createMock(PublicShopCategoryService::class);
        $service->expects(self::never())->method('getItem');
        $controller = new GetPublicShopCategoryAction(
            $service,
            new PublicCategoryProjectionGuard()
        );

        $this->expectException(BadRequestHttpException::class);
        $controller(new Request($query), 12);
    }

    public static function clientProjectionProvider(): iterable
    {
        yield 'categoryIds' => [['categoryIds' => ['12', '15', '99']]];
        yield 'ids alias' => [['ids' => '12,15,99']];
    }
}
