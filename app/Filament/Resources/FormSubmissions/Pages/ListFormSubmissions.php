<?php

namespace App\Filament\Resources\FormSubmissions\Pages;

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use App\Models\FormSubmission;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFormSubmissions extends ListRecords
{
    protected static string $resource = FormSubmissionResource::class;

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All')->badge(FormSubmission::count()),
        ];

        foreach (FormSubmission::STATUSES as $status => $label) {
            $count = FormSubmission::where('status', $status)->count();

            $tabs[$status] = Tab::make($label)
                ->badge($count)
                ->badgeColor($status === 'new' ? 'warning' : 'gray')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', $status));
        }

        return $tabs;
    }
}
