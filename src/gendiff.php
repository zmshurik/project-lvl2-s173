<?php

namespace Differ\Gendiff;

use function Differ\Parser\parse;

function getDiffAst($data1, $data2)
{
    $typeMap = [
        'not changed' => [
            'getItem' => function ($key) use ($data1) {
                return ['name' => $key, 'oldValue' => $data1[$key], 'type' => 'not changed'];
            },
            'isNeedType' => function ($key) use ($data1, $data2) {
                return array_key_exists($key, $data1) && array_key_exists($key, $data2) && $data1[$key] == $data2[$key];
            }
        ],
        'changed' => [
            'getItem' => function ($key) use ($data1, $data2) {
                return ['name' => $key, 'oldValue' => $data1[$key], 'type' => 'changed', 'newValue' => $data2[$key]];
            },
            'isNeedType' => function ($key) use ($data1, $data2) {
                return array_key_exists($key, $data1) && array_key_exists($key, $data2) && $data1[$key] != $data2[$key];
            }
        ],
        'deleted' => [
            'getItem' => function ($key) use ($data1) {
                return ['name' => $key, 'oldValue' => $data1[$key], 'type' => 'deleted'];
            },
            'isNeedType' => function ($key) use ($data1, $data2) {
                return array_key_exists($key, $data1) && !array_key_exists($key, $data2);
            }
        ],
        'added' => [
            'getItem' => function ($key) use ($data2) {
                return ['name' => $key, 'newValue' => $data2[$key], 'type' => 'added'];
            },
            'isNeedType' => function ($key) use ($data1, $data2) {
                return !array_key_exists($key, $data1) && array_key_exists($key, $data2);
            }
        ]
    ];
    $unionKeys = \Funct\Collection\union(array_keys($data1), array_keys($data2));
    $result = array_map(function ($key) use ($typeMap) {
        list($typeFunctions) = array_values(array_filter($typeMap, function ($current) use ($key) {
            $isNeedType = $current['isNeedType'];
            return $isNeedType($key);
        }));
        $getItem = $typeFunctions['getItem'];
        return $getItem($key);
    }, $unionKeys);
    return array_values($result);
}

function renderAst($ast)
{
    $typeMap = [
        'not changed' => function ($astItem) {
            extract($astItem);
            return ["  $name" => $oldValue];
        },
        'changed' => function ($astItem) {
            extract($astItem);
            return [
                "+ $name" => $newValue,
                "- $name" => $oldValue
            ];
        },
        'deleted' => function ($astItem) {
            extract($astItem);
            return ["- $name" => $oldValue];
        },
        'added' => function ($astItem) {
            extract($astItem);
            return ["+ $name" => $newValue];
        }
    ];
    $parts = array_reduce($ast, function ($acc, $item) use ($typeMap) {
        $getNewItems = $typeMap[$item['type']];
        $newAcc = array_merge($acc, $getNewItems($item));
        return $newAcc;
    }, []);
    return json_encode($parts, JSON_PRETTY_PRINT);
}

function genDiff($format, $file1, $file2)
{
    $content1 = file_get_contents($file1);
    $content2 = file_get_contents($file2);
    $fileType1 = pathinfo($file1, PATHINFO_EXTENSION);
    $fileType2 = pathinfo($file2, PATHINFO_EXTENSION);
    $data1 = parse($content1, $fileType1);
    $data2 = parse($content2, $fileType2);
    $diffAst = getDiffAst($data1, $data2);
    return renderAst($diffAst);
}
