<?php

declare(strict_types=1);

use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/** Устанавливает сессию и заголовок, соответствующие одному подтверждённому запуску Mini App. */
function asMiniAppViewer(int $id): Illuminate\Foundation\Testing\TestCase
{
    $proof = hash('sha256', 'test-mini-app-launch-'.$id);

    return Pest\Laravel\withHeader('X-Mini-App-Launch', $proof)->withSession([
        'telegram_id' => $id, 'mini_app_proof' => $proof, 'mini_app_viewer' => $id,
    ]);
}
