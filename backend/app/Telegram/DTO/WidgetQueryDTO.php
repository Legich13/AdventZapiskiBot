<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final readonly class WidgetQueryDTO
{
    public function __construct(public string $queryId, public int $viewerId, public ?int $telegramMessageId, public string $command) {}
}
