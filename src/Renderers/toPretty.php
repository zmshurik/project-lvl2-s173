<?php

namespace Differ\Renderers\ToPretty;

use function Funct\Collection\flatten;
use function Differ\Renderers\Renderer\toBoolStr;

define('NOT_FIRST_CALL', false);

function renderToPretty($ast, $isFirstCall = true)
{
    $processChildren = function ($children, $name, $sign) {
        $value = renderToPretty($children, NOT_FIRST_CALL);
        $result = "$sign $name: " . implode(PHP_EOL, $value);
        return explode(PHP_EOL, $result);
    };
    $typeMap = [
        'not changed' => function ($astItem) {
            $name = $astItem['name'];
            $oldValue = $astItem['oldValue'];
            return ["  $name: " . (is_bool($oldValue) ? toBoolStr($oldValue) : $oldValue)];
        },
        'changed' => function ($astItem) {
            $name = $astItem['name'];
            $oldValue = $astItem['oldValue'];
            $newValue = $astItem['newValue'];
            return [
                "+ $name: " . (is_bool($newValue) ? toBoolStr($newValue) : $newValue),
                "- $name: " . (is_bool($oldValue) ? toBoolStr($oldValue) : $oldValue)
            ];
        },
        'deleted' => function ($astItem) use ($processChildren) {
            $name = $astItem['name'];
            $oldValue = $astItem['oldValue'];
            return is_array($oldValue) ? $processChildren($astItem['children'], $name, '-') :
                ["- $name: " . (is_bool($oldValue) ? toBoolStr($oldValue) : $oldValue)];
        },
        'added' => function ($astItem) use ($processChildren) {
            $name = $astItem['name'];
            $newValue = $astItem['newValue'];
            return is_array($newValue) ? $processChildren($astItem['children'], $name, '+') :
                ["+ $name: " . (is_bool($newValue) ? toBoolStr($newValue) : $newValue)];
        },
        'need check in deep' => function ($astItem) use ($processChildren) {
            return $processChildren($astItem['children'], $astItem['name'], ' ');
        },
        'nested' => function ($astItem) {
            $name = $astItem['name'];
            $value = $astItem['value'];
            return is_array($value) ? $processChildren($astItem['children'], $name, ' ') :
                ["  $name: " . (is_bool($value) ? toBoolStr($value) : $value)];
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
