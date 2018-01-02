<?php

namespace Differ\tests\GetdiffTest;

use PHPUnit\Framework\TestCase;
use function \Differ\Gendiff\genDiff;
use function \Differ\Gendiff\getDiffAst;
use function \Differ\Gendiff\parseAst;

final class GendiffTest extends TestCase
{
    private $result = <<<DOC
{
    host: hexlet.io
  + timeout: 20
  - timeout: 50
  - proxy: 123.234.53.22
  + verbose: true
}
DOC;
    private $ast = [
        [
            'name' => 'host',
            'value' => 'hexlet.io',
            'type' => 'not changed'
        ],
        [
            'name' => 'timeout',
            'value' => 50,
            'type' => 'changed',
            'newValue' => 20
        ],
        [
            'name' => 'proxy',
            'value' => '123.234.53.22',
            'type' => 'deleted',
        ],
        [
            'name' => 'verbose',
            'value' => true,
            'type' => 'added'
        ]
    ];
    public function testGenDiff()
    {

        $path1 = __DIR__ . '/fixtures/before.json';
        $path2 = __DIR__ . '/fixtures/after.json';
        $this->assertEquals($this->result, genDiff('pretty', $path1, $path2));
    }

    public function testGetDiffAst()
    {
        $data1 = [
            "host" => "hexlet.io",
            "timeout" => 50,
            "proxy" => '123.234.53.22'
        ];
        $data2 = [
            "timeout" => 20,
            "verbose" => true,
            "host" => "hexlet.io"
        ];
        $this->assertEquals($this->ast, getDiffAst($data1, $data2));
    }
    public function testParseAst()
    {
        $this->assertEquals($this->result, parseAst($this->ast));
    }
}
