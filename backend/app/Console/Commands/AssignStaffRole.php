<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Admin\Actions\AssignStaffRoleAction;
use DomainException;
use Illuminate\Console\Command;
use Override;

final class AssignStaffRole extends Command
{
    #[Override]
    protected $signature = 'advent:staff-role {email : Почта существующего сотрудника} {role : Название существующей роли}';

    #[Override]
    protected $description = 'Заменить роли сотрудника одной указанной ролью, сохранив пароль';

    public function handle(AssignStaffRoleAction $action): int
    {
        try {
            $action->execute($this->argument('email'), $this->argument('role'));
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Роль сотрудника обновлена.');

        return self::SUCCESS;
    }
}
