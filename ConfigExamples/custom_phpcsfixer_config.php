<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(['.'])
    ->exclude([
        'vendor',
    ]);

return (new Config())
    ->setFinder($finder)
    ->setCacheFile('Examples/.php-cs-fixer.cache');
