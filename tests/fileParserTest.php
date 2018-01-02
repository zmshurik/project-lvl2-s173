<?php

namespace Differ\tests\fileParserTest;

use PHPUnit\Framework\TestCase;
use function \Differ\fileParser\parse;

final class FileParserTest extends TestCase
{
    public function testFileParser()
    {
        $expected = [
            "timeout" => 20,
            "verbose" => true,
            "host" => "hexlet.io"
        ];
        $path = __DIR__ . '/files/after.json';
        $this->assertEquals($expected, parse($path));
    }
}