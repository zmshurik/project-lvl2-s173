<?php

namespace Differ\tests\ParserTest;

use PHPUnit\Framework\TestCase;
use function \Differ\Parser\parse;

final class FileParserTest extends TestCase
{
    public function testFileParser()
    {
        $expected = [
            "timeout" => 20,
            "verbose" => true,
            "host" => "hexlet.io"
        ];
        $path = __DIR__ . '/fixtures/after.json';
        $content = file_get_contents($path);
        $this->assertEquals($expected, parse($content));
    }
}
