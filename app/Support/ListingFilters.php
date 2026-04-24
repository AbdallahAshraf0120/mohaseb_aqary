<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

final class ListingFilters
{
    private const MAX_Q = 200;

    public function __construct(
        public readonly string $q,
        public readonly ?Carbon $dateFrom,
        public readonly ?Carbon $dateTo,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, self::MAX_Q);
        $from = self::parseDateStart($request->query('date_from'));
        $to = self::parseDateEnd($request->query('date_to'));

        if ($from && $to && $to->lt($from)) {
            $to = $from->copy()->endOfDay();
        }

        return new self($q, $from, $to);
    }

    public function active(): bool
    {
        return $this->q !== '' || $this->dateFrom !== null || $this->dateTo !== null;
    }

    /**
     * @param  EloquentBuilder<\Illuminate\Database\Eloquent\Model>|QueryBuilder  $query
     */
    public function applyWhereDate(EloquentBuilder|QueryBuilder $query, string $column): void
    {
        if ($this->dateFrom) {
            $query->whereDate($column, '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate($column, '<=', $this->dateTo);
        }
    }

    public function likeTerm(): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $this->q);
    }

    private static function parseDateStart(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function parseDateEnd(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value)->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
