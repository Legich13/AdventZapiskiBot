<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Telegram\Actions\DispatchMorningRemindersAction;
use Illuminate\Console\Command;

final class DispatchMorning extends Command
{
    #[\Override]
    protected $signature = 'calendar:morning';

    #[\Override]
    protected $description = 'Поставить в очередь утренние подарки за сегодня';

    public function handle(DispatchMorningRemindersAction $reminders): int
    {
        $this->info('Поставлено в очередь: '.$reminders->execute());

        return self::SUCCESS;
    }
}
