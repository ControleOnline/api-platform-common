<?php

namespace ControleOnline\Tests\Service\Imports;

use ControleOnline\Entity\Import;
use ControleOnline\Service\Imports\ImportCommon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ImportCommonDelimiterTest extends TestCase
{
    private function detect(string $content): string
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

        $method = new ReflectionMethod(ImportCommon::class, 'detectDelimiter');
        $method->setAccessible(true);

        return $method->invoke($processor, $content);
    }

    public function testDetectsSemicolonFromExcelPtBr(): void
    {
        $this->assertSame(';', $this->detect("\xEF\xBB\xBF\r\nEmpresa;CNPJ;Telefones\r\nACME;123;\"11999999999,1188888888\"\r\n"));
    }

    public function testDefaultsToComma(): void
    {
        $this->assertSame(',', $this->detect("Empresa,CNPJ,Telefones\nACME,123,\"11 9999;1188\"\n"));
        $this->assertSame(',', $this->detect(''));
    }
}
