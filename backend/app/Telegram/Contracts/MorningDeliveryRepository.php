<?php

declare(strict_types=1);

namespace App\Telegram\Contracts;

use App\Telegram\DTO\MorningDeliveryDTO;
use Carbon\CarbonImmutable;

/** Repository для хранения попыток утренней доставки. Model: App\Models\MorningDelivery. */
interface MorningDeliveryRepository
{
    /** Восстанавливает задания после остановки планировщика или воркера. */
    public function recover(string $today, CarbonImmutable $staleBefore, ?int $viewerId): void;

    /** Резервирует дату для всех получивших календарь до отсечки. */
    public function reserveEligible(string $date, CarbonImmutable $cutoff, ?int $viewerId): void;

    /** @return iterable<int> Только готовые к постановке в очередь идентификаторы. */
    public function dueIds(string $date, ?int $viewerId): iterable;

    /** Резервирует ровно одну постановку ожидающей доставки. */
    public function reserveQueueSlot(int $id): bool;

    /** Загружает адресата и дату ожидающей отправки. */
    public function pending(int $id): ?MorningDeliveryDTO;

    /** Завершает невыполненную отправку после смены даты. */
    public function expire(int $id): void;

    /** Атомарно переводит одну готовую запись из pending в sending. */
    public function beginSending(int $id): bool;

    /** Откладывает подтверждённо не отправленную карточку. */
    public function defer(int $id, CarbonImmutable $next, bool $hasWorker, bool $sending): void;

    /** Фиксирует подтверждение Telegram вместе с локальной привязкой сообщения. */
    public function confirmSent(int $id, int $messageId): void;

    /** Сохраняет окончательный исход попытки без подтверждённого сообщения. */
    public function finishWithoutMessage(int $id, string $status): void;

    /** Исключает пользователя из рассылки после явной блокировки бота. */
    public function markBotBlocked(int $viewerId): void;
}
