<?php

declare(strict_types=1);

namespace App\Calendar\Actions;

use App\Calendar\Contracts\CalendarRepository;
use App\Calendar\DTO\TelegramIdentityDTO;
use Illuminate\Support\Facades\DB;

final readonly class RegisterTelegramUserAction
{
    public function __construct(private CalendarRepository $repository) {}

    public function execute(int $id, ?string $username): void
    {
        $identity = TelegramIdentityDTO::fromTelegram($id, $username);
        DB::transaction(fn () => $this->repository->registerIdentity($identity), 3);
    }
}
