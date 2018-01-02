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
    $getItem = function ($key) use ($data1, $data2, $getType) {
        $item['name'] = $key;
        $type = $getType($key);
        $item['value'] = $type == 'added' ? $data2[$key] : $data1[$key];
        $item['type'] = $type;
        if ($type == 'changed') {
            $item['newValue'] = $data2[$key];
        }
        return $item;
    };
    $unionKeys = \Funct\Collection\union(array_keys($data1), array_keys($data2));
    return array_reduce($unionKeys, function ($acc, $key) use ($getItem) {
        $acc[] = $getItem($key);
        return $acc;
    }, []);
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
    $data1 = parse($file1);
    $data2 = parse($file2);
    $diffAst = getDiffAst($data1, $data2);
    return parseAst($diffAst);
}
