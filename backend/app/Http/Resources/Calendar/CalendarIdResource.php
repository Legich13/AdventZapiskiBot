<?php

declare(strict_types=1);

namespace App\Http\Resources\Calendar;

use App\Calendar\DTO\CalendarWriteResultDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @property-read CalendarWriteResultDTO $resource */
final class CalendarIdResource extends JsonResource
{
    #[Override]
    public static $wrap = null;

    public function __construct(CalendarWriteResultDTO $result)
    {
        parent::__construct($result);
    }

    /** @return array{id:int} */
    #[Override]
    public function toArray(Request $request): array
    {
        return ['id' => $this->resource->id];
    }

    #[Override]
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode($this->resource->created ? 201 : 200);
    }
}
