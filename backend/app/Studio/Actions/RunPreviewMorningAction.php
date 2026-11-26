<?php

declare(strict_types=1);

namespace App\Studio\Actions;

use App\Telegram\Actions\DispatchMorningRemindersAction;
use Illuminate\Support\Facades\Config;

final readonly class RunPreviewMorningAction
{
    public function __construct(private DispatchMorningRemindersAction $dispatch) {}

    public function execute(int $viewer): void
    {
        $previous = Config::string('queue.default');
        Config::set('queue.default', 'sync');
        try {
            $this->dispatch->execute($viewer);
        } finally {
            Config::set('queue.default', $previous);
        }
    }
}
