<?php

declare(strict_types=1);

namespace App\Studio\Actions;

use App\Calendar\Actions\ClaimCalendarsAction;
use App\Studio\DemoProfiles;
use App\Telegram\Actions\SendWidgetAction;

final readonly class StartPreviewChatAction
{
    public function __construct(private ClaimCalendarsAction $claim, private SendWidgetAction $widgets) {}

    public function execute(int $viewer): bool
    {
        $calendars = $this->claim->execute($viewer, DemoProfiles::PEOPLE[$viewer]);
        foreach ($calendars as $calendar) {
            $this->widgets->execute($viewer, $calendar);
        }

        return $calendars !== [];
    }
}
