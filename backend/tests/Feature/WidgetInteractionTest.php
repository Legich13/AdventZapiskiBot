<?php

declare(strict_types=1);

use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\DTO\WidgetStateDTO;
use App\Telegram\Services\WidgetRenderer;

use function Pest\Laravel\mock;

use Symfony\Component\Process\Process;

test('первое доступное нажатие открывает подарок после задержки обновления карточки', function (?string $giftDate, string $stateResult): void {
    $state = new WidgetStateDTO('2026-12', [7 => 1], 31);
    mock(WidgetRepository::class)->shouldReceive('stateForRecipient')->once()->andReturn($state);
    $html = app(WidgetRenderer::class)->render(1, 2, $giftDate)->html;
    $process = new Process(['node', base_path('tests/Fixtures/widgets/first-tap.cjs'), $stateResult]);
    $process->setInput($html);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
})->with([
    'календарь, состояние загружено' => [null, 'success'],
    'календарь, ошибка сети' => [null, 'failure'],
    'утренний подарок, состояние загружено' => ['2026-12-07', 'success'],
    'утренний подарок, ошибка сети' => ['2026-12-07', 'failure'],
]);

test('локальный мост сообщает о занятом запросе и освобождается после ошибки', function (): void {
    $html = view('preview-widget', ['html' => '', 'message' => 7])->render();
    $process = new Process(['node', base_path('tests/Fixtures/widgets/preview-requests.cjs')]);
    $process->setInput($html);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
});
