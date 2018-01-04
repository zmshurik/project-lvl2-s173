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
        $this->simpleResult = json_encode([
            "  host" => 'hexlet.io',
            "+ timeout" => 20,
            "- timeout" => 50,
            "- proxy" => '123.234.53.22',
            "+ verbose" => true
        ], JSON_PRETTY_PRINT);

        $this->complexResult = json_encode([
            "  common" => [
                "  setting1" => "Value 1",
                "- setting2" => 200,
                "  setting3" => true,
                "- setting6" => [
                    "  key" => "value"
                ],
                "+ setting4" => "blah blah",
                "+ setting5" => [
                    "  key5" => "value5"
                ]
            ],
            "  group1" => [
                "- baz" => "bas",
                "+ baz" => "bars",
                "+ foo" => "bar"
            ],
            "- group2" => [
                "  abc" => "12345"
            ],
            "+ group3" => [
                "  fee"=> "100500"
            ]
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
        $this->complexAst = [
            [
                'name' => 'common',
                'type' => 'not changed',
                'children' => [
                    [
                        'name' => 'setting1',
                        'oldValue' => 'Value 1',
                        'type' => 'not changed'
                    ],
                    [
                        'name' => 'setting2',
                        'oldValue' => 200,
                        'type' => 'deleted'
                    ],
                    [
                        'name' => 'setting1',
                        'oldValue' => true,
                        'type' => 'not changed'
                    ],
                    [
                        'name' => 'setting6',
                        'type' => 'deleted',
                        'children' => [
                            [
                                'name' => 'key',
                                'oldValue' => 'value',
                                'type' => 'not changed'
                            ]
                        ]
                    ],
                    [
                        'name' => 'setting4',
                        'newValue' => 'blah blah',
                        'type' => 'added'
                    ],
                    [
                        'name' => 'settings5',
                        'type' => 'added',
                        'children' => [
                            [
                                'name' => 'key5',
                                'oldValue' => 'value5',
                                'type' => 'not changed'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'group1',
                'type' => 'not chaged',
                'cildren' => [
                    [
                        'name' => 'baz',
                        'oldValue' => 'bas',
                        'type' => 'changed',
                        'newValue' => 'bars'
                    ],
                    [
                        'name' => 'foo',
                        'oldValue' => 'bar',
                        'type' => 'not changed'
                    ]
                ]
            ],
            [
                'name' => 'group2',
                'type' => 'not changed',
                'children' => [
                    [
                        'name' => 'abc',
                        'oldValue' => 12345,
                        'type' => 'not changed'
                    ]
                ]
            ],
            [
                'name' => 'group3',
                'type' => 'not changed',
                'children' => [
                    [
                        'name' => 'fee',
                        'oldValue' => 100500,
                        'type' => 'not changed'
                    ]
                ]
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
        $this->assertEquals($this->simpleResult, renderAst($this->simpleAst));
    }
}
