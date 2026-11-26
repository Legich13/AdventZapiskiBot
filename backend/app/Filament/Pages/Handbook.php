<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Documentation\ProjectHandbook;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

final class Handbook extends Page
{
    use HasPageShield;

    #[\Override]
    protected static ?string $slug = 'docs';

    #[\Override]
    protected static ?string $title = 'Документация';

    #[\Override]
    protected string $view = 'filament.pages.handbook';

    #[Url]
    public string $document = 'backend/domain-models.md';

    /** @return array{chapters:array<string,string>,html:string} */
    protected function getViewData(): array
    {
        $handbook = app(ProjectHandbook::class);

        return ['chapters' => $handbook->chapters(), 'html' => $handbook->html($this->document)];
    }
}
