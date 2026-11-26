<?php

declare(strict_types=1);

use App\Telegram\Infrastructure\NutgramGateway;
use GuzzleHttp\Psr7\Request;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Testing\FakeNutgram;

test('приветствие без календаря отправляет статический виджет со встроенной ссылкой редактора', function (?string $configuredUrl, string $expectedUrl): void {
    if ($configuredUrl !== null) {
        config(['services.telegram.mini_app_url' => $configuredUrl]);
    }
    $sdk = Nutgram::fake();
    app()->instance(Nutgram::class, $sdk);
    $sdk->willReceive(['message_id' => 7, 'date' => now()->timestamp, 'chat' => ['id' => 101, 'type' => 'private']]);
    $gateway = new class extends NutgramGateway
    {
        public function simulated(): bool
        {
            return false;
        }
    };

    $gateway->welcome(101);

    $sdk->assertReply('sendWidget', ['chat_id' => 101, 'caption' => '', 'allow_queries' => false])
        ->assertCalled('sendWidget', 1)
        ->assertCalled('sendMessage', 0)
        ->assertRaw(function (Request $request) use ($expectedUrl): bool {
            $data = FakeNutgram::getActualData($request);
            $html = $data['html'];
            expect($html)->toBeString();
            assert(is_string($html));
            $document = new DOMDocument;
            $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($document);

            expect($xpath->evaluate('normalize-space(//h1)'))->toBe('Маленькие чудеса каждый день')
                ->and($xpath->evaluate('normalize-space(//p)'))->toBe('Соберите календарь с тёплыми записками и сюрпризами для себя или близкого человека')
                ->and($xpath->evaluate('string(//a/@href)'))->toBe($expectedUrl)
                ->and($xpath->evaluate('normalize-space(//a)'))->toBe('Создать календарь')
                ->and($xpath->evaluate('count(//a)'))->toBe(1.0)
                ->and($html)->not->toContain('<script', 'TelegramWidget.request')
                ->and(strlen($html))->toBeLessThanOrEqual(32768)
                ->and(strlen(gzcompress($html, 9) ?: throw new RuntimeException('Ошибка zlib')))->toBeLessThanOrEqual(4096);

            return true;
        });
})->with([
    'главная Mini App по умолчанию' => [null, 'https://t.me/AdventZapiskiBot?startapp'],
    'настроенная ссылка Mini App' => ['https://t.me/AdventZapiskiBot?startapp=editor&mode=compact', 'https://t.me/AdventZapiskiBot?startapp=editor&mode=compact'],
]);
