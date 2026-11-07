<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Filament\Support\CommonFilters;
use App\Models\Post;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->columns([
                ImageColumn::make('featured_image_url')
                    ->label('Image')->height(40)->toggleable(),

                TextColumn::make('title')
                    ->searchable()->sortable()->weight('medium')
                    ->description(fn (Post $r) => '/news/'.$r->slug),

                TextColumn::make('category.name')
                    ->badge()->searchable()->sortable()->toggleable()->placeholder('—'),

                IconColumn::make('published')->boolean()->sortable(),
                IconColumn::make('featured')->boolean()->sortable()->toggleable(),

                TextColumn::make('published_at')->dateTime('j M Y')->sortable()->placeholder('—'),

                TextColumn::make('updated_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->multiple()->searchable()->preload(),
                TernaryFilter::make('published'),
                TernaryFilter::make('featured'),
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
            ])
            ->emptyStateHeading('No news yet')
            ->emptyStateDescription('Both legacy news articles were in the Joomla trash, so they were imported unpublished.');
    }
}
