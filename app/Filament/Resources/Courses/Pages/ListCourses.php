<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use App\Models\Course;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Course::count()),

            'published' => Tab::make('Published')
                ->badge(Course::where('published', true)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('published', true)),

            'draft' => Tab::make('Draft')
                ->badge(Course::where('published', false)->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('published', false)),

            // The four courses whose legacy article had an empty body. They
            // are live on the menu, so they need an editor's attention.
            'needs_content' => Tab::make('Needs content')
                ->badge(Course::whereNull('curriculum_html')
                    ->where(fn ($q) => $q->whereNull('body')->orWhere('body', ''))
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q
                    ->whereNull('curriculum_html')
                    ->where(fn ($q) => $q->whereNull('body')->orWhere('body', ''))),

            'no_fee' => Tab::make('Fee missing')
                ->badge(Course::whereNull('fee_regular')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('fee_regular')),
        ];
    }
}
