<?php

declare(strict_types=1);

test('панель и мастерская обращаются к данным через прикладные сценарии', function (): void {
    foreach ([app_path('Filament/Pages/Overview.php'), app_path('Http/Controllers/DevController.php')] as $source) {
        foreach (['Facades\\DB', '::query(', '::firstOrCreate(', '->validate(', 'Models\\Calendar', 'Models\\TelegramUser'] as $bypass) {
            expect(file_get_contents($source))->not->toContain($bypass);
        }
    }
});
