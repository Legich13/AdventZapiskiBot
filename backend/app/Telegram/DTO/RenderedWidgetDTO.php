<?php

declare(strict_types=1);

namespace App\Telegram\DTO;

final readonly class RenderedWidgetDTO
{
    public function __construct(public string $html, public string $caption) {}
}
