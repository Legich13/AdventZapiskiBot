<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withCache(__DIR__.'/vendor/.cache/advent-quality/rector')
    ->withParallel(timeoutSeconds: 600)
    ->withPhpSets()
    ->withSkip([
        NewMethodCallWithoutParenthesesRector::class,
        StringClassNameToClassConstantRector::class => [
            __DIR__.'/config/scramble.php',
        ],
    ]);
