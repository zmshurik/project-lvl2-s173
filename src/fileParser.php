<?php

namespace Differ\FileParser;

function parse($content)
{
    return json_decode($content, true);
}
