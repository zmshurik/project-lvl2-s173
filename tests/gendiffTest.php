<?php

namespace Differ\tests\GetdiffTest;

use PHPUnit\Framework\TestCase;
use function \Differ\Gendiff\genDiff;
use function \Differ\Gendiff\getDiffAst;
use function \Differ\Gendiff\renderAst;

final class GendiffTest extends TestCase
{
    private $simpleResult;
    private $complexResult;
    private $simpleAst;
    private $complexAst;

    public function setUp()
    {
        $this->simpleResult = <<<DOC
{
    host: hexlet.io
  + timeout: 20
  - timeout: 50
  - proxy: 123.234.53.22
  + verbose: true
}
DOC;

        $this->complexResult = <<<DOC
{
    common: {
        setting1: Value 1
      - setting2: 200
        setting3: true
      - setting6: {
            key: value
        }
      + setting4: blah blah
      + setting5: {
            key5: value5
        }
    }
    group1: {
      + baz: bars
      - baz: bas
        foo: bar
    }
  - group2: {
        abc: 12345
    }
  + group3: {
        fee: 100500
    }
}
DOC;

        $this->simpleAst = [
            [
                'name' => 'host',
                'oldValue' => 'hexlet.io',
                'type' => 'not changed',
                'isGroup' => false
            ],
            [
                'name' => 'timeout',
                'oldValue' => 50,
                'type' => 'changed',
                'newValue' => 20,
                'isGroup' => false
            ],
            [
                'name' => 'proxy',
                'oldValue' => '123.234.53.22',
                'type' => 'deleted',
                'isGroup' => false
            ],
            [
                'name' => 'verbose',
                'newValue' => true,
                'type' => 'added',
                'isGroup' => false
            ]
        ];
        $this->complexAst = [
            [
                'name' => 'common',
                'type' => 'with children',
                'isGroup' => true,
                'children' => [
                    [
                        'name' => 'setting1',
                        'oldValue' => 'Value 1',
                        'type' => 'not changed',
                        'isGroup' => false
                    ],
                    [
                        'name' => 'setting2',
                        'oldValue' => 200,
                        'type' => 'deleted',
                        'isGroup' => false
                    ],
                    [
                        'name' => 'setting3',
                        'oldValue' => true,
                        'type' => 'not changed',
                        'isGroup' => false
                    ],
                    [
                        'name' => 'setting6',
                        'oldValue' => [
                            [
                                'name' => 'key',
                                'value' => 'value',
                                'type' =>'nested'
                            ]
                        ],
                        'type' => 'deleted',
                        'isGroup' => true

                    ],
                    [
                        'name' => 'setting4',
                        'newValue' => 'blah blah',
                        'type' => 'added',
                        'isGroup' => false
                    ],
                    [
                        'name' => 'setting5',
                        'newValue' => [
                            [
                                'name' => 'key5',
                                'value' => 'value5',
                                'type' => 'nested'
                            ]
                        ],
                        'type' => 'added',
                        'isGroup' => true
                    ]
                ]
            ],
            [
                'name' => 'group1',
                'type' => 'with children',
                'isGroup' => true,
                'children' => [
                    [
                        'name' => 'baz',
                        'oldValue' => 'bas',
                        'type' => 'changed',
                        'newValue' => 'bars',
                        'isGroup' => false
                    ],
                    [
                        'name' => 'foo',
                        'oldValue' => 'bar',
                        'type' => 'not changed',
                        'isGroup' => false
                    ]
                ]
            ],
            [
                'name' => 'group2',
                'oldValue' => [
                    [
                        'name' => 'abc',
                        'value' => 12345,
                        'type' => 'nested'
                    ]
                ],
                'type' => 'deleted',
                'isGroup' => true
            ],
            [
                'name' => 'group3',
                'newValue' => [
                    [
                        'name' => 'fee',
                        'value' => 100500,
                        'type' => 'nested'
                    ]
                ],
                'type' => 'added',
                'isGroup' => true
            ]
        ];
    }

    public function testGenDiff()
    {

        $path1 = __DIR__ . '/fixtures/before.json';
        $path2 = __DIR__ . '/fixtures/after.json';
        $this->assertEquals($this->simpleResult, genDiff('pretty', $path1, $path2));
    }

    public function testGenDifWithNested()
    {
        $path1 = __DIR__ . '/fixtures/beforeComplex.json';
        $path2 = __DIR__ . '/fixtures/afterComplex.json';
        $this->assertEquals($this->complexResult, genDiff('pretty', $path1, $path2));
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

    public function testComplexAstGeneration()
    {
        $data1 = [
            "common" => [
                'setting1' => 'Value 1',
                'setting2' => 200,
                'setting3' => true,
                'setting6' => [
                    'key' => 'value'
                ],
            ],
            "group1" => [
                'baz' => 'bas',
                'foo' => 'bar'
            ],
            "group2" => [
                'abc' => 12345
            ]
        ];
        $data2 = [
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
        $this->assertEquals($this->complexAst, getDiffAst($data1, $data2));
    }
    public function testRenderAst()
    {
        $simpleOutput = implode(PHP_EOL, renderAst($this->simpleAst));
        $this->assertEquals($this->simpleResult, $simpleOutput);
        $nestedOutput = implode(PHP_EOL, renderAst($this->complexAst));
        $this->assertEquals($this->complexResult, $nestedOutput);
    }
}
