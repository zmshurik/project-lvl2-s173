<?php

namespace Differ\tests\GetdiffTest;

use PHPUnit\Framework\TestCase;
use function \Differ\Gendiff\genDiff;
use function \Differ\Gendiff\getDiffAst;
use function \Differ\Gendiff\renderAst;

final class GendiffTest extends TestCase
{
    private $result;
    private $ast;
    
    public function setUp()
    {
        $this->simpleResult = json_encode([
            "  host" => 'hexlet.io',
            "+ timeout" => 20,
            "- timeout" => 50,
            "- proxy" => '123.234.53.22',
            "+ verbose" => true
        ], JSON_PRETTY_PRINT);
        
        $this->simpleAst = [
            [
                'name' => 'host',
                'oldValue' => 'hexlet.io',
                'type' => 'not changed'
            ],
            [
                'name' => 'timeout',
                'oldValue' => 50,
                'type' => 'changed',
                'newValue' => 20
            ],
            [
                'name' => 'proxy',
                'oldValue' => '123.234.53.22',
                'type' => 'deleted',
            ],
            [
                'name' => 'verbose',
                'newValue' => true,
                'type' => 'added'
            ]
        ];
    }

    public function testGenDiff()
    {

        $path1 = __DIR__ . '/fixtures/before.json';
        $path2 = __DIR__ . '/fixtures/after.json';
        $this->assertEquals($this->simpleResult, genDiff('pretty', $path1, $path2));
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
        $this->assertEquals($this->simpleAst, getDiffAst($data1, $data2));
    }
    public function testRenderAst()
    {
        $this->assertEquals($this->simpleResult, renderAst($this->simpleAst));
    }
}
