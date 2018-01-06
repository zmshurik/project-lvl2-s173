<?php

namespace Differ\Renderers\ToPlain;

use function Differ\Renderers\Renderer\toBoolStr;

function valueToString($value)
{
    if (is_array($value)) {
        return 'complex value';
    }
    return is_bool($value) ? toBoolStr($value) : $value;
}

function renderToPlain($ast, $parents = '')
{
    $neededNodes = array_filter($ast, function ($node) {
        return $node['type'] != 'not changed';
    });
    $typeMap = [
        'deleted' => function ($astItem) use ($parents) {
            $fullPropertyName = $parents . $astItem['name'];
            return ["Property '$fullPropertyName' was removed"];
        },
        'added' => function ($astItem) use ($parents) {
            $fullPropertyName = $parents . $astItem['name'];
            $value = valueToString($astItem['newValue']);
            return ["Property '$fullPropertyName' was added with value: '$value'"];
        },
        'changed' => function ($astItem) use ($parents) {
            $fullPropertyName = $parents . $astItem['name'];
            $oldValue = valueToString($astItem['oldValue']);
            $newValue = valueToString($astItem['newValue']);
            return ["Property '$fullPropertyName' was changed. From '$oldValue' to '$newValue'"];
        },
        'need check in deep' => function ($astItem) use ($parents) {
            $nextParents = $parents . $astItem['name'] . '.';
            return renderToPlain($astItem['children'], $nextParents);
        }
    ];
    return array_reduce($neededNodes, function ($acc, $item) use ($typeMap) {
        $getNewItems = $typeMap[$item['type']];
        $newAcc = array_merge($acc, $getNewItems($item));
        return $newAcc;
    }, []);
}
