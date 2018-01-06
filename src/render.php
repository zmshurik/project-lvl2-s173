<?php

namespace Differ\Render;

use function Funct\Collection\flatten;

define('NOT_FIRST_CALL', false);

function toBoolStr($value)
{
    return $value ? 'true' : 'false';
}

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

function renderToPlain($ast, $parents = '')
{
    $needNodes = array_filter($ast, function ($node) {
        return $node['type'] != 'not changed';
    });
    $typeMap = [
        'deleted' => function ($astItem) use ($parents) {
            $fullPropertyName = $parents . $astItem['name'];
            return ["Property '$fullPropertyName' was removed"];
        },
        'added' => function ($astItem) use ($parents) {
            $fullPropertyName = $parents . $astItem['name'];
            $value = is_array($astItem['newValue']) ? 'complex value' : $astItem['newValue'];
            return ["Property '$fullPropertyName' was added with value: '" .
             (is_bool($value) ? toBoolStr($value) : $value) . "'"];
        },
        'changed' => function ($astItem) use ($parents) {
            $fullPropertyName = $parents . $astItem['name'];
            return ["Property '$fullPropertyName' was changed. From '" .
             (is_bool($astItem['oldValue']) ? toBoolStr($astItem['oldValue']) : $astItem['oldValue']) . "' to '" .
             (is_bool($astItem['newValue']) ? toBoolStr($astItem['newValue']) : $astItem['newValue']) . "'"];
        },
        'need check in deep' => function ($astItem) use ($parents) {
            $nextParents = $parents . $astItem['name'] . '.';
            return renderToPlain($astItem['children'], $nextParents);
        }
    ];
    return array_reduce($needNodes, function ($acc, $item) use ($typeMap) {
        $getNewItems = $typeMap[$item['type']];
        $newAcc = array_merge($acc, $getNewItems($item));
        return $newAcc;
    }, []);
}

function render($ast, $format)
{
    $formatMap = [
        'pretty' => function ($ast) {
            return implode(PHP_EOL, renderToPretty($ast));
        },
        'plain' => function ($ast) {
            return implode(PHP_EOL, renderToPlain($ast));
        }
    ];
    $render = $formatMap[$format];
    return $render($ast);
}
