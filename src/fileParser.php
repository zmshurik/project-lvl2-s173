<?php

namespace Differ\fileParser;

function parse($path)
{
    return json_decode(file_get_contents($path), true);
}
