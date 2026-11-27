<?php

declare(strict_types=1);

namespace App\Http\Resources\Calendar;

use App\Calendar\DTO\OpenedGiftDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @property-read OpenedGiftDTO $resource */
final class OpenedGiftResource extends JsonResource
{
    #[Override]
    public static $wrap = null;

    public function __construct(OpenedGiftDTO $gift)
    {
        parent::__construct($gift);
    }

    /** @return array{text:string,first:bool} */
    #[Override]
    public function toArray(Request $request): array
    {
        return ['text' => $this->resource->text, 'first' => $this->resource->first];
    }
}
