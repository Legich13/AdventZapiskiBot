<?php

declare(strict_types=1);

namespace App\Studio\Actions;

use App\Studio\Infrastructure\Persistence\PreviewStaffRepository;
use Illuminate\Contracts\Session\Session;

final readonly class SelectPreviewProfileAction
{
    public function __construct(private PreviewStaffRepository $profiles) {}

    public function execute(Session $session, int $id, string $time): void
    {
        abort_unless($this->profiles->profileExists($id), 409, 'Сначала запустите make dev-demo.');
        $session->regenerate();
        $session->put(['telegram_id' => $id, 'dev_time' => $time]);
    }
}
