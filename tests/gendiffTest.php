<?php

namespace Differ\tests\GetdiffTest;

use PHPUnit\Framework\TestCase;
use function \Differ\Gendiff\genDiff;

final class GendiffTest extends TestCase
{
    public function testGenDiff()
    {
        $expected = <<<DOC
{
    host: hexlet.io
  + timeout: 20
  - timeout: 50
  - proxy: 123.234.53.22
  + verbose: true
}
DOC;
        $path1 = __DIR__ . '/files/before.json';
        $path2 = __DIR__ . '/files/after.json';
        $this->assertEquals($expected, genDiff('pretty', $path1, $path2));
    }
}
