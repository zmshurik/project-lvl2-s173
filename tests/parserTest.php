<?php

namespace Differ\tests\ParserTest;

use PHPUnit\Framework\TestCase;
use function \Differ\Parser\parse;

final class FileParserTest extends TestCase
{
    public function testParser()
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
    public function testParserNested()
    {
        $expected = [
            "common" => [
                'setting1' => 'Value 1',
                'setting3' => true,
                'setting4' => 'blah blah',
                'setting5' => [
                    'key5' => 'value5'
                ],
            ],
            "group1" => [
                'foo' => 'bar',
                'baz' => 'bars'
            ],
            "group3" => [
                'fee' => 100500
            ]
        ];
        $jsonPath = __DIR__ . '/fixtures/afterComplex.json';
        $jsonContent = file_get_contents($jsonPath);
        $this->assertEquals($expected, parse($jsonContent, 'json'));
        $ymlPath = __DIR__ . '/fixtures/afterComplex.yml';
        $ymlContent = file_get_contents($ymlPath);
        $this->assertEquals($expected, parse($ymlContent, 'yml'));
    }
}
