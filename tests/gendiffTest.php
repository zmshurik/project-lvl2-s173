<?php

namespace Differ\tests\GetdiffTest;

use PHPUnit\Framework\TestCase;
use function \Differ\Gendiff\genDiff;
use function \Differ\Gendiff\getDiffAst;

final class GendiffTest extends TestCase
{
    public function testGenDiff()
    {
        $output = <<<DOC
{
    host: hexlet.io
  + timeout: 20
  - timeout: 50
  - proxy: 123.234.53.22
  + verbose: true
}
DOC;
        $path1 = __DIR__ . '/fixtures/before.json';
        $path2 = __DIR__ . '/fixtures/after.json';
        $this->assertEquals($output, genDiff('pretty', $path1, $path2));
    }

    public function testGenDiffWithNested()
    {
        $outputPretty = <<<DOC
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
        $outputPlain = <<<DOC
Property 'common.setting2' was removed
Property 'common.setting6' was removed
Property 'common.setting4' was added with value: 'blah blah'
Property 'common.setting5' was added with value: 'complex value'
Property 'group1.baz' was changed. From 'bas' to 'bars'
Property 'group2' was removed
Property 'group3' was added with value: 'complex value'
DOC;
        $path1 = __DIR__ . '/fixtures/beforeComplex.json';
        $path2 = __DIR__ . '/fixtures/afterComplex.json';
        $this->assertEquals($outputPretty, genDiff('pretty', $path1, $path2));
        $this->assertEquals($outputPlain, genDiff('plain', $path1, $path2));
    }

    public function testGetDiffAst()
    {
        $ast =  [
            [
                'name' => 'host',
                'oldValue' => 'hexlet.io',
                'type' => 'not changed',
                'children' => []
            ],
            [
                'name' => 'timeout',
                'oldValue' => 50,
                'type' => 'changed',
                'newValue' => 20,
                'children' => []
            ],
            [
                'name' => 'proxy',
                'oldValue' => '123.234.53.22',
                'type' => 'deleted',
                'children' => []
            ],
            [
                'name' => 'verbose',
                'newValue' => true,
                'type' => 'added',
                'children' => []
            ]
        ];
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
        $this->assertEquals($ast, getDiffAst($data1, $data2));
    }

    public function testComplexAstGeneration()
    {
        $ast = [
            [
                'name' => 'common',
                'type' => 'need check in deep',
                'children' => [
                    [
                        'name' => 'setting1',
                        'oldValue' => 'Value 1',
                        'type' => 'not changed',
                        'children' => []
                    ],
                    [
                        'name' => 'setting2',
                        'oldValue' => 200,
                        'type' => 'deleted',
                        'children' => []
                    ],
                    [
                        'name' => 'setting3',
                        'oldValue' => true,
                        'type' => 'not changed',
                        'children' => []
                    ],
                    [
                        'name' => 'setting6',
                        'oldValue' => ['key' => 'value'],
                        'type' => 'deleted',
                        'children' => [
                            [
                                'name' => 'key',
                                'value' => 'value',
                                'type' =>'nested',
                                'children' => []
                            ]
                        ]
                    ],
                    [
                        'name' => 'setting4',
                        'newValue' => 'blah blah',
                        'type' => 'added',
                        'children' => []
                    ],
                    [
                        'name' => 'setting5',
                        'newValue' => ['key5' => 'value5'],
                        'type' => 'added',
                        'children' => [
                            [
                                'name' => 'key5',
                                'value' => 'value5',
                                'type' => 'nested',
                                'children' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'group1',
                'type' => 'need check in deep',
                'children' => [
                    [
                        'name' => 'baz',
                        'oldValue' => 'bas',
                        'type' => 'changed',
                        'newValue' => 'bars',
                        'children' => []
                    ],
                    [
                        'name' => 'foo',
                        'oldValue' => 'bar',
                        'type' => 'not changed',
                        'children' => []
                    ]
                ]
            ],
            [
                'name' => 'group2',
                'oldValue' => ['abc' => 12345],
                'type' => 'deleted',
                'children' => [
                    [
                        'name' => 'abc',
                        'value' => 12345,
                        'type' => 'nested',
                        'children' => []
                    ]
                ]
            ],
            [
                'name' => 'group3',
                'newValue' => ['fee' => 100500],
                'type' => 'added',
                'children' => [
                    [
                        'name' => 'fee',
                        'value' => 100500,
                        'type' => 'nested',
                        'children' => []
                    ]
                ]
            ]
        ];
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
        $this->assertEquals($ast, getDiffAst($data1, $data2));
    }
}
