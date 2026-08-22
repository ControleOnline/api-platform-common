<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Service\PublicCategoryProjectionGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PublicCategoryProjectionGuardTest extends TestCase
{
    public function testRequestWithoutClientProjectionRemainsCompatible(): void
    {
        (new PublicCategoryProjectionGuard())->rejectClientProjection(new Request([
            'company' => '/people/3',
        ]));

        self::addToAssertionCount(1);
    }

    #[DataProvider('clientProjectionProvider')]
    public function testClientProjectionCannotBecomePublicationAuthority(array $query): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('cannot define the published category projection');

        (new PublicCategoryProjectionGuard())->rejectClientProjection(new Request($query));
    }

    public static function clientProjectionProvider(): iterable
    {
        yield 'categoryIds with same-tenant unpublished id' => [
            ['categoryIds' => ['12', '15', '99']],
        ];
        yield 'ids alias with same-tenant unpublished id' => [
            ['ids' => '12,15,99'],
        ];
        yield 'explicit empty categoryIds is still client-controlled' => [
            ['categoryIds' => ''],
        ];
    }
}
