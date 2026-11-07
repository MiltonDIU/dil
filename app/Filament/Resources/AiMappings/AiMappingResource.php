<?php

namespace App\Filament\Resources\AiMappings;

use App\Filament\Resources\AiMappings\Pages\EditAiMapping;
use App\Filament\Resources\AiMappings\Pages\ListAiMappings;
use App\Filament\Support\CommonFilters;
use App\Models\AiMapping;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * Audit trail for every AI decision.
 *
 * Marking a row Reviewed pins it: `ai:*` commands look here before calling the
 * API and never overwrite a reviewed mapping, so a human correction survives
 * every future re-run.
 */
class AiMappingResource extends Resource
{
    protected static ?string $model = AiMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'AI decisions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Input')->schema([
                Textarea::make('input')->rows(6)->disabled()->dehydrated(false)->columnSpanFull(),
            ]),

            Section::make('AI output')->schema([
                Textarea::make('output_pretty')
                    ->label('Result')
                    ->rows(14)->columnSpanFull()
                    ->formatStateUsing(fn (?AiMapping $record) => json_encode(
                        $record?->output_json ?? [],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    ))
                    ->dehydrateStateUsing(fn (?string $state) => json_decode((string) $state, true))
                    ->helperText('Edit to correct the AI. Then mark it reviewed so re-runs keep your change.'),

                Toggle::make('reviewed')
                    ->label('Reviewed and approved')
                    ->helperText('Locks this decision — future AI runs will not overwrite it.'),

                Textarea::make('review_notes')->rows(2)->columnSpanFull(),
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
                TextColumn::make('type')->badge()->searchable()->sortable(),
                TextColumn::make('input')->limit(60)->searchable()->wrap(),
                TextColumn::make('model')->badge()->toggleable(),
                IconColumn::make('reviewed')->boolean()->sortable()
                    ->trueColor('success')->falseColor('gray'),
                TextColumn::make('reviewer.name')->label('Reviewed by')
                    ->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->dateTime('j M Y H:i')->sortable(),
            ])
            ->filters([
                CommonFilters::fromColumn(AiMapping::class, 'type'),
                TernaryFilter::make('reviewed'),
                CommonFilters::dateRange('created_at', 'Created'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_reviewed')
                        ->label('Mark reviewed')
                        ->icon('heroicon-o-check-badge')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update([
                            'reviewed' => true,
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiMappings::route('/'),
            'edit' => EditAiMapping::route('/{record}/edit'),
        ];
    }
}
