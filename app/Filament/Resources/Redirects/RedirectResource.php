<?php

namespace App\Filament\Resources\Redirects;

use App\Filament\Resources\Redirects\Pages\CreateRedirect;
use App\Filament\Resources\Redirects\Pages\EditRedirect;
use App\Filament\Resources\Redirects\Pages\ListRedirects;
use App\Filament\Support\CommonFilters;
use App\Models\Redirect;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * The 301 map that carries the old site's search ranking across the cutover.
 * Editable, so staff can add redirects post-launch without a deploy.
 */
class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('source')
                ->required()->maxLength(500)->columnSpanFull()
                ->helperText('The old address, e.g. /index.php/courses/french-language')
                ->rules(['starts_with:/']),

            TextInput::make('target')
                ->required()->maxLength(500)->columnSpanFull()
                ->helperText('Where to send visitors, e.g. /courses/french-language'),

            Select::make('status_code')
                ->options([301 => '301 — Permanent', 302 => '302 — Temporary'])
                ->default(301)->required(),

            Toggle::make('published')->default(true),

            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->columns([
                TextColumn::make('source')->searchable()->sortable()->limit(50)->wrap(),
                TextColumn::make('target')->searchable()->sortable()->limit(40),
                TextColumn::make('status_code')->badge()->sortable()->toggleable(),
                TextColumn::make('hits')->numeric()->sortable(),
                TextColumn::make('last_hit_at')->dateTime('j M Y H:i')->sortable()
                    ->placeholder('never')->toggleable(),
                IconColumn::make('published')->boolean()->sortable(),
                TextColumn::make('notes')->limit(30)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('published'),
                TernaryFilter::make('used')
                    ->label('Has been used')
                    ->queries(
                        true: fn ($q) => $q->where('hits', '>', 0),
                        false: fn ($q) => $q->where('hits', 0),
                        blank: fn ($q) => $q,
                    ),
                CommonFilters::dateRange('last_hit_at', 'Last used'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->icon('heroicon-o-check')->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('disable')
                        ->icon('heroicon-o-x-mark')->color('gray')->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['published' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRedirects::route('/'),
            'create' => CreateRedirect::route('/create'),
            'edit' => EditRedirect::route('/{record}/edit'),
        ];
    }
}
