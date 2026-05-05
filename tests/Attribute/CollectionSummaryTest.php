<?php

namespace ControleOnline\Tests\Attribute;

use ControleOnline\Attribute\CollectionSummary;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CollectionSummaryTest extends TestCase
{
    public function testAllowsCustomResolverWithoutAggregateOperation(): void
    {
        $attribute = new CollectionSummary(
            name: 'pricing',
            parameter: 'summary',
            parameterValue: 'pricing',
            groups: ['product:read', ' product:details '],
            resolver: 'App\\PricingResolver'
        );

        self::assertSame([], $attribute->getOperations());
        self::assertSame('summary', $attribute->getParameter());
        self::assertSame(['pricing'], $attribute->getParameterValues());
        self::assertSame(['product:read', 'product:details'], $attribute->getGroups());
        self::assertSame('App\\PricingResolver', $attribute->getResolver());
    }

    public function testRequiresOperationOrResolver(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CollectionSummary();
    }
}
