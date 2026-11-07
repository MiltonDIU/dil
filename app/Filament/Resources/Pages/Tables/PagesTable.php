<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Filament\Support\CommonFilters;
use App\Models\Page;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('ordering')
            ->defaultSort('ordering')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->columns([
                TextColumn::make('title')
                    ->searchable()->sortable()->weight('medium')
                    ->description(fn (Page $r) => '/'.$r->slug),

                TextColumn::make('excerpt')
                    ->limit(60)->searchable()->toggleable()->placeholder('—'),

                IconColumn::make('published')->boolean()->sortable(),

                /*
                 * Five legacy pages were published with a completely empty
                 * body, three of them linked from the live menu. Surfacing
                 * that here rather than on launch day.
                 */
                IconColumn::make('has_content')
                    ->label('Has content')
                    ->boolean()
                    ->state(fn (Page $r) => ! $r->isEmpty())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->falseColor('warning'),

                IconColumn::make('ai_meta_generated')
                    ->label('AI meta')->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('published_at')->dateTime('j M Y')->sortable()
                    ->toggleable()->placeholder('—'),

                TextColumn::make('updated_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('published'),
                TernaryFilter::make('ai_meta_generated')->label('AI-generated meta'),
                CommonFilters::dateRange('published_at', 'Published'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->icon('heroicon-o-eye')->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->icon('heroicon-o-eye-slash')->color('gray')->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['published' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
