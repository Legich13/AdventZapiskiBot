<?php

declare(strict_types=1);

namespace App\Telegram\Actions;

use App\Calendar\Actions\OpenGiftAction;
use App\Support\GiftClock;
use App\Telegram\Contracts\WidgetRepository;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final readonly class AnswerWidgetAction
{
    public function __construct(private WidgetRepository $messages, private OpenGiftAction $openGift) {}

    /** Строка result для нативного TelegramWidget.request и локального моста. */
    public function execute(int $viewerId, int $messageId, string $command): string
    {
        $operation = $command === 'state' ? 'state' : 'open';
        $answer = RateLimiter::attempt('widget-query:'.$operation.':'.$viewerId, 5, fn () => $this->answer($viewerId, $messageId, $command), 5);

        return json_encode($answer ?: ['error' => 'Слишком много нажатий. Попробуйте через несколько секунд.'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return array<string, string|int|bool|array<int,int>> */
    private function answer(int $viewerId, int $messageId, string $command): array
    {
        $target = $this->messages->targetForRecipient($viewerId, $messageId);
        if ($target === null) {
            return ['error' => 'Этот подарок адресован другому человеку.'];
        }
        if ($command === 'state') {
            $state = $this->messages->stateForRecipient($viewerId, $target->calendarId, GiftClock::now('Europe/Moscow')->toDateString());

            return $state === null ? ['error' => 'Этот подарок пока недоступен.'] : ['month' => $state->month, 'days' => $state->days, 'total' => $state->total];
        }
        if (! preg_match('/^open:([1-9]|[12]\d|3[01])$/D', $command, $match)) {
            return ['error' => 'Выберите день в календаре.'];
        }
        $date = $target->month.'-'.str_pad($match[1], 2, '0', STR_PAD_LEFT);
        if ($target->giftDate !== null && $date !== $target->giftDate) {
            return ['error' => 'Выберите дату этой карточки.'];
        }
        try {
            $gift = $this->openGift->execute($viewerId, $target->calendarId, $date);

            return ['text' => $gift->text, 'first' => $gift->first];
        } catch (HttpExceptionInterface) {
            return ['error' => 'Этот подарок пока недоступен.'];
        }
    }
}
