<?php

namespace Differ\Renderers\ToPlain;

use function Differ\Renderers\Renderer\toBoolStr;

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
