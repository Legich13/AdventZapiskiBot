<?php

declare(strict_types=1);

namespace App\Telegram\Contracts;

use App\Telegram\DTO\BotMessageDTO;
use App\Telegram\DTO\WidgetInboxItemDTO;
use App\Telegram\DTO\WidgetStateDTO;
use App\Telegram\DTO\WidgetTargetDTO;
use Illuminate\Support\Collection;

/** Repository для хранения сообщений с календарями и подарками. Model: App\Models\WidgetMessage. */
interface WidgetRepository
{
    /** Возвращает доступные состояния дней без содержимого записок. */
    public function stateForRecipient(int $viewerId, int $calendarId, string $today): ?WidgetStateDTO;

    /** Сохраняет привязку сообщения к исходному календарю и дню. */
    public function recordMessage(int $viewerId, ?int $telegramId, BotMessageDTO $message): int;

    /** Разрешает запрос только в контексте сообщения получателя. */
    public function targetForRecipient(int $viewerId, int $id): ?WidgetTargetDTO;

    /** Находит внутренний номер по паре личный чат и Telegram message_id. */
    public function localIdForTelegramMessage(int $viewerId, int $telegramId): ?int;

    /** @return Collection<int,WidgetInboxItemDTO> */
    public function recentForViewer(int $viewerId): Collection;

    /** Читает HTML только по подтверждённому владельцу. */
    public function previewForViewer(int $viewerId, int $messageId): ?string;

    /** Различает отсутствующее локальное сообщение и чужое. */
    public function exists(int $messageId): bool;

    /** После запуска бота снова разрешает доставку получателю. */
    public function markBotReachable(int $viewerId): void;
}
