<?php

declare(strict_types=1);

test('все Pest test descriptions написаны по-русски', function (): void {
    $testsPath = dirname(__DIR__, 2);
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testsPath, FilesystemIterator::SKIP_DOTS),
    );

    $violations = [];

    foreach ($files as $file) {
        if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname()) ?: '';

        preg_match_all(
            '/\b(?:test|it|describe)\(\s*([\'"])(.*?)\1/s',
            $content,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );

        foreach ($matches as $match) {
            $title = stripcslashes($match[2][0]);

            if (preg_match('/\p{Cyrillic}/u', $title) === 1) {
                continue;
            }

            $line = substr_count(substr($content, 0, $match[0][1]), "\n") + 1;
            $relativePath = str_replace(dirname(__DIR__, 3).'/', '', $file->getPathname());

            $violations[] = "{$relativePath}:{$line} => {$title}";
        }
    }

    expect($violations)->toBe([]);
});
