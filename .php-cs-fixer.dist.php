<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/examples',
        __DIR__ . '/tests',
        __DIR__ . '/scripts/ci',
    ])
    ->append([__FILE__])
    ->exclude(['vendor']);

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS3x0' => true,
        // The 3.1 verifier checks intent and final-case termination.
        'no_break_comment' => false,
    ])
    ->setFinder($finder)
    ->setUsingCache(false);
