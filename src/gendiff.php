<?php

namespace Differ\Gendiff;

use function Differ\FileParser\parse;

function getDiffAst($data1, $data2)
{
    $getType = function ($key) use ($data1, $data2) {
        if (array_key_exists($key, $data1) && array_key_exists($key, $data2)) {
            return $data1[$key] == $data2[$key] ? 'not changed' : 'changed';
        }
        return array_key_exists($key, $data1) ? 'deleted' : 'added';
    };
    $typeMap = [
        'not changed' => function ($key) use ($data1) {
            return ['name' => $key, 'value' => $data1[$key], 'type' => 'not changed'];
        },
        'changed' => function ($key) use ($data1, $data2) {
            return ['name' => $key, 'value' => $data1[$key], 'type' => 'changed', 'newValue' => $data2[$key]];
        },
        'deleted' => function ($key) use ($data1) {
            return ['name' => $key, 'value' => $data1[$key], 'type' => 'deleted'];
        },
        'added' => function ($key) use ($data2) {
            return ['name' => $key, 'value' => $data2[$key], 'type' => 'added'];
        }
    ];
    $unionKeys = \Funct\Collection\union(array_keys($data1), array_keys($data2));
    $result = array_map(function ($key) use ($typeMap, $getType) {
        $type = $getType($key);
        $getItem = $typeMap[$type];
        return $getItem($key);
    }, $unionKeys);
    return array_values($result);
}

function parseAst($ast)
{
    $signMap = [
        'not changed' => ' ',
        'changed' => '-',
        'deleted' => '-',
        'added' => '+'
    ];
    $toBoolStr = function ($value) {
        return $value ? 'true' : 'false';
    };
    $parts = array_reduce($ast, function ($acc, $item) use ($signMap, $toBoolStr) {
        extract($item);
        $sign = $signMap[$type];
        if ($type == 'changed') {
            $acc[] = "+ $name: " . (is_bool($newValue) ? $toBoolStr($newValue) : $newValue);
        }
        $acc[] = "$sign $name: " . (is_bool($value) ? $toBoolStr($value) : $value);
        return $acc;
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
