<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')->badge(Post::count()),

            'published' => Tab::make('Published')
                ->badge(Post::where('published', true)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('published', true)),

            'draft' => Tab::make('Draft')
                ->badge(Post::where('published', false)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('published', false)),
        ];
    }
}
