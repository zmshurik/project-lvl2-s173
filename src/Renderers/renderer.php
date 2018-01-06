<?php

namespace Differ\Renderers\Renderer;

use function Differ\Renderers\ToPlain\renderToPlain;
use function Differ\Renderers\ToPretty\renderToPretty;

function toBoolStr($value)
{
    return $value ? 'true' : 'false';
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
