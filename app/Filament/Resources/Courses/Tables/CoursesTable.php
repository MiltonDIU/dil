<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Filament\Support\CommonFilters;
use App\Models\Course;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CoursesTable
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
                // ImageColumn reads the *_url accessor, never the raw column.
                ImageColumn::make('featured_image_url')
                    ->label('Image')
                    ->height(40)
                    ->defaultImageUrl(null)
                    ->toggleable(),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Course $r) => $r->slug),

                TextColumn::make('language')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('cefr_levels')
                    ->label('Levels')
                    ->badge()
                    ->separator(',')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('fee_regular')
                    ->label('Fee')
                    ->money('BDT', 100)
                    ->sortable()
                    ->placeholder('not stated')
                    ->description(fn (Course $r) => $r->fee_daffodil_family
                        ? 'family '.$r->formatFee('daffodil')
                        : null),

                TextColumn::make('duration_months')
                    ->label('Months')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('total_classes')
                    ->label('Classes')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('published')->boolean()->sortable(),

                IconColumn::make('featured')->boolean()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Surfaces the courses whose legacy body was empty, so they do
                // not quietly ship as blank pages.
                IconColumn::make('has_content')
                    ->label('Has content')
                    ->boolean()
                    ->state(fn (Course $r) => ! $r->isEmpty())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->falseColor('warning'),

                IconColumn::make('ai_normalized')
                    ->label('AI')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                CommonFilters::fromColumn(Course::class, 'language'),
                TernaryFilter::make('published'),
                TernaryFilter::make('featured'),
                TernaryFilter::make('ai_normalized')->label('AI normalized'),
                CommonFilters::dateRange('created_at', 'Created'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['published' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('unpublish')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['published' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
