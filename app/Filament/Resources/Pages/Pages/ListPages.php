<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $emptyBody = fn (Builder $q) => $q->where(fn ($q) => $q->whereNull('body')->orWhere('body', ''));

        return [
            'all' => Tab::make('All')->badge(Page::count()),

            'published' => Tab::make('Published')
                ->badge(Page::where('published', true)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('published', true)),

            'draft' => Tab::make('Draft')
                ->badge(Page::where('published', false)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('published', false)),

            'needs_content' => Tab::make('Needs content')
                ->badge(Page::query()->tap($emptyBody)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing($emptyBody),
        ];
    }
}
