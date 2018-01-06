<?php

namespace Differ\tests\GetdiffTest;

use PHPUnit\Framework\TestCase;
use function \Differ\Render\renderToPretty;
use function Differ\Render\renderToPlain;

final class RenderTest extends TestCase
{
    private $simpleAst;
    private $complexAst;

    public function setUp()
    {
        $this->simpleAst = [
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
        $this->complexAst = [
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
    }

    public function testRenderToPretty()
    {
        $simple = <<<DOC
{
    host: hexlet.io
  + timeout: 20
  - timeout: 50
  - proxy: 123.234.53.22
  + verbose: true
}
DOC;
        $complex = <<<DOC
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
        $simpleOutput = implode(PHP_EOL, renderToPretty($this->simpleAst));
        $this->assertEquals($simple, $simpleOutput);
        $nestedOutput = implode(PHP_EOL, renderToPretty($this->complexAst));
        $this->assertEquals($complex, $nestedOutput);
    }

    public function testRenderToPlain()
    {
        $output = <<<DOC
Property 'common.setting2' was removed
Property 'common.setting6' was removed
Property 'common.setting4' was added with value: 'blah blah'
Property 'common.setting5' was added with value: 'complex value'
Property 'group1.baz' was changed. From 'bas' to 'bars'
Property 'group2' was removed
Property 'group3' was added with value: 'complex value'
DOC;
        $nestedOutput = implode(PHP_EOL, renderToPlain($this->complexAst));
        $this->assertEquals($output, $nestedOutput);
    }
}
