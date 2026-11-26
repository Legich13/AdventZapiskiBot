<?php

declare(strict_types=1);

return [
    'api_path' => '',
    'info' => ['version' => '1.0.0', 'description' => 'Авторизация Mini App, публикация календаря и получение событий Telegram.'],
    'renderer' => 'scalar',
    'middleware' => ['web', 'can:viewApiDocs'],
    'ui' => ['title' => 'Декабрь чудес · API'],
    'renderers' => ['scalar' => [
        'view' => 'scramble::scalar', 'cdn' => 'https://cdn.jsdelivr.net/npm/@scalar/api-reference@1.65.1/dist/browser/standalone.js',
        'credentials' => 'same-origin', 'theme' => 'laravel', 'proxyUrl' => null, 'showDeveloperTools' => 'never', 'agent' => ['disabled' => true],
    ]],
];
