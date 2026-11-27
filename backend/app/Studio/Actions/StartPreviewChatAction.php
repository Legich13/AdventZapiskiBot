<?php

declare(strict_types=1);

namespace App\Studio\Actions;

use App\Studio\DemoProfiles;
use App\Telegram\Actions\HandleTelegramUpdateAction;
use App\Telegram\DTO\StartUpdateDTO;

final readonly class StartPreviewChatAction
{
    public function __construct(private HandleTelegramUpdateAction $handler) {}

    public function execute(int $viewer): void
    {
        // Отрицательный ID отличает локальную команду от обновлений Telegram.
        $retry = $this->handler->execute(new StartUpdateDTO(-random_int(1, PHP_INT_MAX), $viewer, DemoProfiles::PEOPLE[$viewer]));
        abort_if($retry !== null, 503, 'Повторите запуск через несколько секунд.');
    }
}
