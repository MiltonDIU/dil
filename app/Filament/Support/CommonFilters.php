<?php

namespace App\Filament\Support;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class CommonFilters
{
    /**
     * Date-range filter on a timestamp column, with an indicator so the user
     * can see at a glance that a filter is narrowing their results.
     */
    public static function dateRange(string $column = 'created_at', ?string $label = null): Filter
    {
        $label ??= str($column)->headline()->toString();

        return Filter::make($column)
            ->label($label)
            ->schema([
                DatePicker::make('from')->label("{$label} from"),
                DatePicker::make('until')->label("{$label} until"),
            ])
            ->query(fn (Builder $query, array $data) => $query
                ->when($data['from'] ?? null, fn (Builder $q, $v) => $q->whereDate($column, '>=', $v))
                ->when($data['until'] ?? null, fn (Builder $q, $v) => $q->whereDate($column, '<=', $v)))
            ->indicateUsing(function (array $data) use ($label): array {
                $indicators = [];

                if ($data['from'] ?? null) {
                    $indicators[] = "{$label} from ".Carbon::parse($data['from'])->toFormattedDateString();
                }

                if ($data['until'] ?? null) {
                    $indicators[] = "{$label} until ".Carbon::parse($data['until'])->toFormattedDateString();
                }

                return $indicators;
            });
    }

    /**
     * A SelectFilter whose options come from the database, never hardcoded —
     * so a new value appearing in the data appears in the filter too.
     */
    public static function fromColumn(string $model, string $column, ?string $label = null): SelectFilter
    {
        return SelectFilter::make($column)
            ->label($label ?? str($column)->headline()->toString())
            ->options(fn () => $model::query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->orderBy($column)
                ->pluck($column, $column))
            ->multiple()
            ->searchable()
            ->preload();
    }
}
