<?php

declare(strict_types=1);

namespace App\Telegram\Services;

use App\Telegram\Contracts\WidgetRepository;
use App\Telegram\DTO\WidgetInboxItemDTO;
use Illuminate\Support\Collection;

final readonly class WidgetInboxService
{
    public function __construct(private WidgetRepository $messages) {}

    /** @return Collection<int, WidgetInboxItemDTO> */
    public function forViewer(?int $viewerId): Collection
    {
        return $this->messages->recentForViewer($viewerId ?? 0);
    }

    public function previewHtml(int $viewerId, int $messageId): string
    {
        $html = $this->messages->previewForViewer($viewerId, $messageId);
        if ($html === null) {
            abort($this->messages->exists($messageId) ? 403 : 404);
        }

        return $html;
    }
}
