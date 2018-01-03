<?php

namespace Differ\Parser;

function parse($content)
{
    return json_decode($content, true);
}
