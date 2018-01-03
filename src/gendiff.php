<?php

namespace Differ\Gendiff;

use function Differ\Parser\parse;

function getDiffAst($data1, $data2)
{
    $getItem = function ($key) use ($data1, $data2) {
        if (array_key_exists($key, $data1) && array_key_exists($key, $data2)) {
            return $data1[$key] == $data2[$key] ?
                ['name' => $key, 'oldValue' => $data1[$key], 'type' => 'not changed'] :
                ['name' => $key, 'oldValue' => $data1[$key], 'type' => 'changed', 'newValue' => $data2[$key]];
        }
        return array_key_exists($key, $data1) ?
            ['name' => $key, 'oldValue' => $data1[$key], 'type' => 'deleted'] :
            ['name' => $key, 'newValue' => $data2[$key], 'type' => 'added'];
    };
    // $typeMap = [
    //     'not changed' => [
    //         function ($key) use ($data1) {
    //             return ['name' => $key, 'value' => $data1[$key], 'type' => 'not changed'];
    //         },
    //         function ($key) use ($data1, $data2) {
    //          return array_key_exists($key, $data1) && array_key_exists($key, $data2) && $data1[$key] == $data2[$key];
    //         }
    //     ]
    //     'changed' => function ($key) use ($data1, $data2) {
    //         return ['name' => $key, 'value' => $data1[$key], 'type' => 'changed', 'newValue' => $data2[$key]];
    //     },
    //     'deleted' => function ($key) use ($data1) {
    //         return ['name' => $key, 'value' => $data1[$key], 'type' => 'deleted'];
    //     },
    //     'added' => function ($key) use ($data2) {
    //         return ['name' => $key, 'value' => $data2[$key], 'type' => 'added'];
    //     }
    // ];
    $unionKeys = \Funct\Collection\union(array_keys($data1), array_keys($data2));
    $result = array_map(function ($key) use ($getItem) {
        return $getItem($key);
    }, $unionKeys);
    return array_values($result);
}

function parseAst($ast)
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
            return ["- $name: " . (is_bool($oldValue) ? $toBoolStr($oldValue) : $oldValue)];
        },
        'added' => function ($astItem) use ($toBoolStr) {
            extract($astItem);
            return ["+ $name: " . (is_bool($newValue) ? $toBoolStr($newValue) : $newValue)];
        }
    ];
    $parts = array_reduce($ast, function ($acc, $item) use ($typeMap) {
        $getNewItems = $typeMap[$item['type']];
        $newAcc = array_merge($acc, $getNewItems($item));
        return $newAcc;
    }, ['{']);
    return implode(PHP_EOL . '  ', $parts) . PHP_EOL . '}';
}

function genDiff($format, $file1, $file2)
{
    $content1 = file_get_contents($file1);
    $content2 = file_get_contents($file2);
    $data1 = parse($content1);
    $data2 = parse($content2);
    $diffAst = getDiffAst($data1, $data2);
    return parseAst($diffAst);
}
