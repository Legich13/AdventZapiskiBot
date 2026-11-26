<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Admin\Services\AdminOverviewService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Livewire\WithPagination;

final class Overview extends Page
{
    use HasPageShield;
    use WithPagination;

    #[\Override]
    protected static ?string $slug = '/';

    #[\Override]
    protected static ?string $title = 'Обзор календарей';

    #[\Override]
    protected string $view = 'filament.pages.overview';

    public string $search = '';

    public function updatedSearch(): void
    {
        foreach (['users', 'calendars', 'deliveries'] as $name) {
            $this->resetPage($name);
        }
    }

    protected function getViewData(): array
    {
        return app(AdminOverviewService::class)->tables($this->search);
    }
}
