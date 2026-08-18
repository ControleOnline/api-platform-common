<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Service\CategoryProjectionRequestParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CategoryProjectionRequestParserTest extends TestCase
{
    public function testMissingProjectionRemainsCompatibleAndExplicitEmptyStaysEmpty(): void
    {
        $parser = new CategoryProjectionRequestParser();

        self::assertNull($parser->parse(new Request()));
        self::assertSame([], $parser->parse(new Request(['categoryIds' => ''])));
    }

    public function testProjectionAcceptsArrayOrCommaSeparatedIdsDeterministically(): void
    {
        $parser = new CategoryProjectionRequestParser();

        self::assertSame(
            [4, 2],
            $parser->parse(new Request(['categoryIds' => ['4', '/categories/2', '4']]))
        );
        self::assertSame(
            [9, 3],
            $parser->parse(new Request(['ids' => '9, /categories/3, 9']))
        );
    }
}
