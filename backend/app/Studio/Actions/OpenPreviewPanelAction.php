<?php

declare(strict_types=1);

namespace App\Studio\Actions;

use App\Studio\Infrastructure\Persistence\PreviewStaffRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class OpenPreviewPanelAction
{
    public function __construct(private PreviewStaffRepository $staff) {}

    public function execute(): void
    {
        Auth::guard('web')->loginUsingId(DB::transaction($this->staff->loginId(...)));
    }
}
