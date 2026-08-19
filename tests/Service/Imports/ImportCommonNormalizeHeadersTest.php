<?php

namespace ControleOnline\Tests\Service\Imports;

use ControleOnline\Entity\Import;
use ControleOnline\Service\Imports\ImportCommon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ImportCommonNormalizeHeadersTest extends TestCase
{
    public function testNormalizeHeadersStripsTrailingAsteriskFromRequiredMarkers(): void
    {
        $processor = new class extends ImportCommon {
            public function getType(): string
            {
                return 'test';
            }

            public function process(Import $import): void
            {
            }

            public function getExampleCsv(): array
            {
                return [];
            }
        };

        $method = new ReflectionMethod(ImportCommon::class, 'normalizeHeaders');
        $method->setAccessible(true);

        $normalized = $method->invoke($processor, [
            "\xEF\xBB\xBFcategory_name*",
            ' product_name* ',
            'product_sku',
            'group_required',
        ]);

        $this->assertSame(
            ['category_name', 'product_name', 'product_sku', 'group_required'],
            $normalized
        );
    }
}
