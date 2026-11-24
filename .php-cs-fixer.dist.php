<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = new Finder()
    ->in([
        __DIR__ . '/assets',
        __DIR__ . '/commands',
        __DIR__ . '/config',
        __DIR__ . '/controllers',
        __DIR__ . '/integrations',
        __DIR__ . '/migrations',
        __DIR__ . '/models',
        __DIR__ . '/services',
        __DIR__ . '/tests',
        __DIR__ . '/views',
        __DIR__ . '/widgets',
    ])
    ->notPath('__autocomplete.php')
    ->append([
        __DIR__ . '/web/index.php',
        __DIR__ . '/yii',
        __FILE__,
    ]);

return new Config()
    ->setRiskyAllowed(false)
    ->setRules([
        '@PER-CS' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => null,
            'import_functions' => null,
        ],
        'new_expression_parentheses' => ['use_parentheses' => false],
        'no_unused_imports' => true,
        'ordered_imports' => true,
        'single_line_empty_body' => true,
    ])
    ->setCacheFile(__DIR__ . '/runtime/cache/php-cs-fixer.cache')
    ->setFinder($finder);
