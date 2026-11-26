<?php

declare(strict_types=1);

namespace App\Admin\Infrastructure\Persistence;

use App\Admin\DTO\CalendarOverviewRowDTO;
use App\Admin\DTO\MorningDeliveryRowDTO;
use App\Admin\DTO\TelegramPersonDTO;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use stdClass;

/** Проекции для сотрудников. Записки и HTML сообщений не извлекаются. */
final class AdminOverviewRepository
{
    private function matchingPeople(string $term): Builder
    {
        return DB::table('telegram_users')->when($term !== '', static function (Builder $query) use ($term): void {
            $query->where('username', 'like', '%'.$term.'%');
            if (ctype_digit($term)) {
                $query->orWhereRaw('CAST(id AS TEXT) = ?', [$term]);
            }
        });
    }

    /** @return LengthAwarePaginator<int, TelegramPersonDTO> */
    public function people(string $term): LengthAwarePaginator
    {
        return $this->page($this->matchingPeople($term)->select('id', 'username')->orderByDesc('id'), 'users',
            static function (stdClass $row): TelegramPersonDTO {
                /** @var stdClass&object{id:int,username:?string} $row */
                return new TelegramPersonDTO($row->id, $row->username);
            });
    }

    /** @return LengthAwarePaginator<int, CalendarOverviewRowDTO> */
    public function calendars(string $term): LengthAwarePaginator
    {
        $opened = DB::table('gift_days')->select('calendar_id')
            ->selectRaw('count(*) AS opened_count, max(opened_at) AS last_opened')->whereNotNull('opened_at')->groupBy('calendar_id');
        $rows = DB::table('calendars as c')->join('telegram_users as a', 'a.id', '=', 'c.author_id')
            ->leftJoin('telegram_users as r', 'r.id', '=', 'c.recipient_id')
            ->leftJoinSub($opened, 'o', 'o.calendar_id', '=', 'c.id')
            ->select('c.month', 'c.author_id', 'c.recipient_id', 'c.recipient_username', 'c.is_self', 'c.published_at', 'c.claimed_at',
                'a.username as author_name', 'r.username as recipient_name', 'o.opened_count', 'o.last_opened')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $people = $this->matchingPeople($term)->select('id');
                $query->whereIn('c.author_id', clone $people)->orWhereIn('c.recipient_id', $people)
                    ->orWhere('c.recipient_username', 'like', '%'.$term.'%');
            })->orderByDesc('c.id');

        return $this->page($rows, 'calendars', static function (stdClass $row): CalendarOverviewRowDTO {
            /** @var stdClass&object{month:string,author_name:?string,author_id:int,recipient_id:?int,recipient_username:?string,recipient_name:?string,is_self:bool,published_at:?string,claimed_at:?string,opened_count:?int,last_opened:?string} $row */
            return new CalendarOverviewRowDTO($row->month, $row->author_name ?? (string) $row->author_id,
                $row->is_self ? 'Себе' : '@'.$row->recipient_username, $row->recipient_name ?? ($row->recipient_id !== null ? (string) $row->recipient_id : null),
                $row->published_at, $row->claimed_at, $row->opened_count ?? 0, $row->last_opened);
        });
    }

    /** @return LengthAwarePaginator<int, MorningDeliveryRowDTO> */
    public function deliveries(string $term): LengthAwarePaginator
    {
        $rows = DB::table('morning_deliveries')->select('gift_date', 'recipient_id', 'status')
            ->when($term !== '', fn (Builder $query) => $query->whereIn('recipient_id', $this->matchingPeople($term)->select('id')))
            ->orderByDesc('id');

        return $this->page($rows, 'deliveries', static function (stdClass $row): MorningDeliveryRowDTO {
            /** @var stdClass&object{gift_date:string,recipient_id:int,status:string} $row */
            return new MorningDeliveryRowDTO($row->gift_date, $row->recipient_id, $row->status);
        });
    }

    /**
     * @template T of object
     *
     * @param  Closure(stdClass):T  $project
     * @return LengthAwarePaginator<int,T>
     */
    private function page(Builder $rows, string $pageName, Closure $project): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, stdClass> $page */
        $page = $rows->paginate(15, ['*'], $pageName);

        return new LengthAwarePaginator($page->getCollection()->map($project), $page->total(), $page->perPage(),
            $page->currentPage(), $page->getOptions());
    }
}
