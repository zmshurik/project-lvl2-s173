<?php

namespace Differ\FileParser;

function parse($path)
{
    return json_decode(file_get_contents($path), true);
}
