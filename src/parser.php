<?php

namespace Differ\Parser;

use Symfony\Component\Yaml\Yaml;

function parse($content, $fileType)
{
    $fileTypeMap = [
        'json' => function ($content) {
            return json_decode($content, true);
        },
        'yml' => function ($content) {
            return Yaml::parse($content);
        }
    ];
    $finalParse = $fileTypeMap[$fileType];
    return $finalParse($content);
}
