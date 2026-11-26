<?php

declare(strict_types=1);

namespace App\Admin\Actions;

use App\Admin\Infrastructure\Persistence\StaffAccessRepository;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class AssignStaffRoleAction
{
    public function __construct(private StaffAccessRepository $repository) {}

    public function execute(string $email, string $role): void
    {
        if ($role === 'local_preview') {
            throw new DomainException('Роль локального предпросмотра назначает мастерская.');
        }
        DB::transaction(fn () => $this->repository->replaceStaffRole(strtolower(trim($email)), $role), 3);
    }
}
