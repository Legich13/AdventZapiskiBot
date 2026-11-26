<?php

declare(strict_types=1);

namespace App\Calendar\Services;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\EditorPageDTO;
use App\Policies\CalendarPolicy;
use App\Support\GiftClock;

final readonly class EditorPageService
{
    public function __construct(private CalendarRepository $repository, private CalendarPolicy $policy) {}

    public function forViewer(?int $viewerId, ?int $selection): EditorPageDTO
    {
        $viewer = $viewerId === null ? null : $this->repository->identity($viewerId);
        $selected = null;
        if ($viewer !== null && $selection !== null) {
            $selected = $this->repository->calendar($selection);
            abort_if($selected === null, 404);
            abort_unless($this->policy->manage($viewer->id, $selected), 403);
        }

        return new EditorPageDTO(
            $viewer, $viewer === null ? [] : $this->repository->authored($viewer->id), $selected,
            $selected === null ? [] : $this->repository->notes($selected->id), GiftClock::now('Europe/Moscow')->toDateString(),
        );
    }
}
