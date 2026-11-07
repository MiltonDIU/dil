<?php

namespace App\Filament\Resources\FormSubmissions;

use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Filament\Support\CommonFilters;
use App\Models\Form;
use App\Models\FormSubmission;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|UnitEnum|null $navigationGroup = 'Forms';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Submissions';

    public static function canCreate(): bool
    {
        return false;   // submissions arrive from the website, not the admin
    }

    public static function getNavigationBadge(): ?string
    {
        $new = FormSubmission::where('status', 'new')->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Submission')->schema([
                TextEntry::make('form.name')->label('Form'),
                TextEntry::make('created_at')->dateTime()->label('Received'),
                TextEntry::make('status')->badge(),
                TextEntry::make('ip')->label('IP address')->placeholder('—'),
                TextEntry::make('referer')->placeholder('—')->columnSpanFull(),
            ])->columns(3),

            Section::make('Answers')->schema([
                KeyValueEntry::make('labelled')
                    ->label('')
                    ->state(fn (FormSubmission $r) => $r->labelled())
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->toggleable(),

                TextColumn::make('form.name')->badge()->sortable()->toggleable(),

                TextColumn::make('summary')
                    ->label('From')
                    ->state(fn (FormSubmission $r) => $r->summary())
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('data', 'like', "%{$search}%")),

                TextColumn::make('preview')
                    ->label('Message')
                    ->state(fn (FormSubmission $r) => collect($r->labelled())->take(2)
                        ->map(fn ($v, $k) => "{$k}: {$v}")->implode(' · '))
                    ->limit(60)
                    ->toggleable(),

                TextColumn::make('status')->badge()->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'warning', 'replied' => 'success',
                        'spam' => 'danger', default => 'gray',
                    }),

                TextColumn::make('created_at')->dateTime('j M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('form_id')
                    ->label('Form')
                    ->options(fn () => Form::orderBy('name')->pluck('name', 'id'))
                    ->multiple()->preload(),

                SelectFilter::make('status')
                    ->options(FormSubmission::STATUSES)->multiple(),

                CommonFilters::dateRange('created_at', 'Received'),
            ])
            ->recordActions([ViewAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...collect(FormSubmission::STATUSES)->map(fn ($label, $status) => BulkAction::make("mark_{$status}")
                        ->label("Mark as {$label}")
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => $status]))
                        ->deselectRecordsAfterCompletion())->values()->all(),

                    BulkAction::make('export_csv')
                        ->label('Export CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => static::exportCsv($records))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No submissions yet');
    }

    /**
     * CSV export.
     *
     * The column set is the union of every field key present in the selected
     * rows, so a form whose fields changed over time still exports cleanly
     * instead of silently dropping older answers.
     */
    protected static function exportCsv(Collection $records): StreamedResponse
    {
        $rows = $records->map(fn (FormSubmission $r) => [
            'meta' => [
                'id' => $r->id,
                'form' => $r->form?->name,
                'status' => $r->status,
                'received' => $r->created_at?->toDateTimeString(),
                'ip' => $r->ip,
            ],
            'answers' => $r->labelled(),
        ]);

        $answerColumns = $rows->flatMap(fn ($r) => array_keys($r['answers']))->unique()->values();
        $metaColumns = ['id', 'form', 'status', 'received', 'ip'];

        $filename = 'submissions-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $metaColumns, $answerColumns) {
            $out = fopen('php://output', 'w');

            // BOM so Excel opens Bengali and other UTF-8 text correctly.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [...$metaColumns, ...$answerColumns->all()]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    ...array_map(fn ($c) => $row['meta'][$c] ?? '', $metaColumns),
                    ...$answerColumns->map(fn ($c) => $row['answers'][$c] ?? '')->all(),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
        ];
    }
}
