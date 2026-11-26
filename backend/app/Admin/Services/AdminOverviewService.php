<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\DTO\CalendarOverviewRowDTO;
use App\Admin\DTO\MorningDeliveryRowDTO;
use App\Admin\DTO\TelegramPersonDTO;
use App\Admin\Infrastructure\Persistence\AdminOverviewRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class AdminOverviewService
{
    public function __construct(private AdminOverviewRepository $repository) {}

    /** @return array{people: LengthAwarePaginator<int,TelegramPersonDTO>, calendars: LengthAwarePaginator<int,CalendarOverviewRowDTO>, deliveries: LengthAwarePaginator<int,MorningDeliveryRowDTO>} */
    public function tables(string $search): array
    {
        $term = ltrim(mb_strtolower(trim($search)), '@');

        return ['people' => $this->repository->people($term), 'calendars' => $this->repository->calendars($term), 'deliveries' => $this->repository->deliveries($term)];
    }
}
