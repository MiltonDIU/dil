<?php

namespace App\Filament\Resources\Menus\RelationManagers;

use App\Models\Course;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Menu items';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),

            Select::make('parent_id')
                ->label('Parent item')
                ->options(function (?MenuItem $record) {
                    $query = MenuItem::query()
                        ->where('menu_id', $this->getOwnerRecord()->id)
                        // Menus render 3 levels deep, so a valid parent can
                        // only be a top-level item or one of its children —
                        // never a grandchild, which would make a 4th level.
                        ->where(fn ($q) => $q->whereNull('parent_id')
                            ->orWhereHas('parent', fn ($q2) => $q2->whereNull('parent_id')));

                    if ($record) {
                        $query
                            // Can't be its own parent…
                            ->whereKeyNot($record->getKey())
                            // …and can't be one of its own children, which
                            // would create a 2-item cycle.
                            ->where(fn ($q) => $q->whereNull('parent_id')
                                ->orWhere('parent_id', '!=', $record->getKey()));
                    }

                    return $query->orderBy('ordering')->pluck('title', 'id');
                })
                ->searchable()
                ->helperText('Leave blank for a top-level item. Menus go up to 3 levels deep.'),

            Select::make('type')
                ->required()
                ->live()
                ->default('none')
                ->options([
                    'page' => 'Page',
                    'course' => 'Course',
                    'post' => 'News article',
                    'route' => 'Built-in page (home, courses, news)',
                    'url' => 'Custom link',
                    /*
                     * Six legacy items were link='#' dropdown parents. They
                     * render as non-clickable headings rather than dead links.
                     */
                    'none' => 'Dropdown heading (not clickable)',
                ])
                ->afterStateUpdated(fn (callable $set) => $set('linkable_id', null)),

            Select::make('linkable_id')
                ->label('Links to')
                ->visible(fn (Get $get) => in_array($get('type'), ['page', 'course', 'post'], true))
                ->required(fn (Get $get) => in_array($get('type'), ['page', 'course', 'post'], true))
                ->searchable()
                ->options(fn (Get $get) => match ($get('type')) {
                    'page' => Page::orderBy('title')->pluck('title', 'id'),
                    'course' => Course::orderBy('title')->pluck('title', 'id'),
                    'post' => Post::orderBy('title')->pluck('title', 'id'),
                    default => [],
                })
                ->dehydrateStateUsing(fn ($state) => $state ? (int) $state : null)
                ->afterStateHydrated(fn ($component, $state) => $component->state($state)),

            Select::make('route_name')
                ->label('Built-in page')
                ->visible(fn (Get $get) => $get('type') === 'route')
                ->required(fn (Get $get) => $get('type') === 'route')
                ->options(fn () => collect(['home' => 'Homepage', 'course.index' => 'All courses', 'news.index' => 'News'])
                    ->filter(fn ($label, $name) => Route::has($name))),

            TextInput::make('url')
                ->label('Custom link')
                ->visible(fn (Get $get) => $get('type') === 'url')
                ->required(fn (Get $get) => $get('type') === 'url')
                ->maxLength(255)
                ->helperText('A full URL (https://…) or a path starting with /'),

            Select::make('target')
                ->options(['_self' => 'Same tab', '_blank' => 'New tab'])
                ->default('_self'),

            TextInput::make('ordering')->numeric()->default(0)
                ->helperText('Lower numbers appear first. You can also drag rows.'),

            Toggle::make('published')->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('ordering')
            ->defaultSort('ordering')
            ->modifyQueryUsing(fn ($query) => $query->with('parent.parent'))
            ->columns([
                TextColumn::make('title')
                    ->searchable()->sortable()
                    // Indent children so the hierarchy is legible at a glance.
                    ->formatStateUsing(fn (MenuItem $r, string $state) => str_repeat('    ', $r->depth()).
                        ($r->parent_id ? '↳ ' : '').$state)
                    ->weight(fn (MenuItem $r) => $r->parent_id ? null : 'medium'),

                TextColumn::make('type')->badge()->sortable()
                    ->color(fn (string $state) => $state === 'none' ? 'gray' : 'primary'),

                TextColumn::make('destination')
                    ->label('Destination')
                    ->state(fn (MenuItem $r) => $r->resolvedUrl() ?? '—')
                    ->color(fn (MenuItem $r) => $r->resolvedUrl() ? null : 'gray')
                    ->toggleable(),

                IconColumn::make('content_ok')
                    ->label('Target has content')
                    ->boolean()
                    ->state(fn (MenuItem $r) => ! $r->pointsAtEmptyContent())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->falseColor('warning'),

                IconColumn::make('published')->boolean()->sortable(),
                IconColumn::make('is_home')->label('Home')->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'page' => 'Page', 'course' => 'Course', 'post' => 'News',
                    'route' => 'Built-in', 'url' => 'Custom link', 'none' => 'Dropdown heading',
                ])->multiple(),
                TernaryFilter::make('published'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
