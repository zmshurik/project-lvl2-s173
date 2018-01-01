<?php

namespace Differ\RunParser;

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
}
