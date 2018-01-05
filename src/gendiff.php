<?php

namespace Differ\Gendiff;

use function Differ\Parser\parse;
use function Funct\Collection\flatten;

define('NOT_FIRST_CALL', false);

function getNestedTree($data)
{
    $nestedTree = array_map(function ($key) use ($data) {
        $value = is_array($data[$key]) ? getNestedTree($data[$key]) : $data[$key];
        return ['name' => $key, 'value' => $value, 'type' => 'nested'];
    }, array_keys($data));
    return array_values($nestedTree);
}

function getDiffAst($data1, $data2)
{
    $typeMap = [
        'with children' => [
            'getItem' => function ($key) use ($data1, $data2) {
                $children = getDiffAst($data1[$key], $data2[$key]);
                return ['name' => $key, 'type' => 'with children', 'children' => $children];
            },
            'isNeedType' => function ($key) use ($data1, $data2) {
                return array_key_exists($key, $data1) &&
                array_key_exists($key, $data2) &&
                (is_array($data1[$key]) && is_array($data2[$key]));
            }
        ],
        'not changed' => [
            'getItem' => function ($key) use ($data1) {
                return ['name' => $key, 'oldValue' => $data1[$key], 'type' => 'not changed'];
            },
            'isNeedType' => function ($key) use ($data1, $data2) {
                return array_key_exists($key, $data1) &&
                array_key_exists($key, $data2) &&
                $data1[$key] == $data2[$key] &&
                !(is_array($data1[$key]) && is_array($data2[$key]));
            }
        ],
        'changed' => [
            'getItem' => function ($key) use ($data1, $data2) {
                return [
                    'name' => $key, 'oldValue' => $data1[$key], 'type' => 'changed', 'newValue' => $data2[$key]];
            },
            'isNeedType' => function ($key) use ($data1, $data2) {
                return array_key_exists($key, $data1) &&
                 array_key_exists($key, $data2) &&
                  $data1[$key] != $data2[$key] &&
                  !(is_array($data1[$key]) && is_array($data2[$key]));
            }
        ],
        'deleted' => [
            'getItem' => function ($key) use ($data1) {
                $value = is_array($data1[$key]) ? getNestedTree($data1[$key]) : $data1[$key];
                return ['name' => $key, 'oldValue' => $value, 'type' => 'deleted'];
            },
            'isNeedType' => function ($key) use ($data1, $data2) {
                return array_key_exists($key, $data1) && !array_key_exists($key, $data2);
            }
        ],
        'added' => [
            'getItem' => function ($key) use ($data2) {
                $value = is_array($data2[$key]) ? getNestedTree($data2[$key]) : $data2[$key];
                return ['name' => $key, 'newValue' => $value, 'type' => 'added'];
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

function renderAst($ast, $isFirstCall = true)
{
    $toBoolStr = function ($value) {
        return $value ? 'true' : 'false';
    };
    $typeMap = [
        'not changed' => function ($astItem) use ($toBoolStr) {
            extract($astItem);
            return ["  $name: " . (is_bool($oldValue) ? $toBoolStr($oldValue) : $oldValue)];
        },
        'changed' => function ($astItem) use ($toBoolStr) {
            extract($astItem);
            return [
                "+ $name: " . (is_bool($newValue) ? $toBoolStr($newValue) : $newValue),
                "- $name: " . (is_bool($oldValue) ? $toBoolStr($oldValue) : $oldValue)
            ];
        },
        'deleted' => function ($astItem) use ($toBoolStr) {
            extract($astItem);
            if (is_array($oldValue)) {
                $value = renderAst($oldValue, NOT_FIRST_CALL);
                $result = "- $name: " . implode(PHP_EOL, $value);
                return explode(PHP_EOL, $result);
            }
            return ["- $name: " . (is_bool($oldValue) ? $toBoolStr($oldValue) : $oldValue)];
        },
        'added' => function ($astItem) use ($toBoolStr) {
            extract($astItem);
            if (is_array($newValue)) {
                $value = renderAst($newValue, NOT_FIRST_CALL);
                $result = "+ $name: " . implode(PHP_EOL, $value);
                return explode(PHP_EOL, $result);
            }
            return ["+ $name: " . (is_bool($newValue) ? $toBoolStr($newValue) : $newValue)];
        },
        'with children' => function ($astItem) use ($toBoolStr) {
            extract($astItem);
            $value = renderAst($children, NOT_FIRST_CALL);
            $result = "  $name: " . implode(PHP_EOL, $value);
            return explode(PHP_EOL, $result);
        },
        'nested' => function ($astItem) use ($toBoolStr) {
            extract($astItem);
            if (is_array($value)) {
                $value = renderAst($value, NOT_FIRST_CALL);
                $result = "  $name: " . implode(PHP_EOL, $value);
                return explode(PHP_EOL, $result);
            }
            return ["  $name: " . (is_bool($value) ? $toBoolStr($value) : $value)];
        }
    ];
    $parts = array_reduce($ast, function ($acc, $item) use ($typeMap) {
        $getNewItems = $typeMap[$item['type']];
        $newAcc = array_merge($acc, $getNewItems($item));
        return $newAcc;
    }, []);
    $withCurlyBrace = [
        '{' ,
        array_map(function ($str) use ($isFirstCall) {
            $space = $isFirstCall ? '  ' : '    ';
            return $space . $str;
        }, $parts),
        ($isFirstCall ? '}' : '  }')
    ];
    $result = flatten($withCurlyBrace);
    return $result;
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
    $output = implode(PHP_EOL, renderAst($diffAst));
    return $output;
}
