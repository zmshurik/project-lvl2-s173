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
        $jsonPath = __DIR__ . '/fixtures/after.json';
        $jsonContent = file_get_contents($jsonPath);
        $this->assertEquals($expected, parse($jsonContent, 'json'));
        $ymlPath = __DIR__ . '/fixtures/after.yml';
        $ymlContent = file_get_contents($ymlPath);
        $this->assertEquals($expected, parse($ymlContent, 'yml'));
    }
}
