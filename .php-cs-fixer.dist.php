<?php

use KennedyOsaze\PhpCsFixerConfig\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests'])
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->name('*.php');

return Config::create($finder);
