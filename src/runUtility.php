<?php

namespace Differ\RunUtility;

use function Differ\Gendiff\genDiff;

define(
    "HELP_MESSAGE",
    <<<DOC
Generate diff

Usage:
  gendiff (-h|--help)
  gendiff [--format <fmt>] <firstFile> <secondFile>

Options:
  -h --help                     Show this screen
  --format <fmt>                Report format [default: pretty]

DOC
);

function run()
{
    $handle = \Docopt::handle(HELP_MESSAGE);
    $format = $handle->args['--format'];
    $isFullPath = function ($path) {
        return $path[0] == DIRECTORY_SEPARATOR;
    };
    $firstFile = $handle->args['<firstFile>'];
    $secondFile = $handle->args['<secondFile>'];
    $firstPath = $isFullPath($firstFile) ? $firstFile : \getcwd() . DIRECTORY_SEPARATOR . $firstFile;
    $secondPath = $isFullPath($secondFile) ? $secondFile : \getcwd() . DIRECTORY_SEPARATOR . $secondFile;
    echo genDiff($format, $firstPath, $secondPath) . PHP_EOL;
}
