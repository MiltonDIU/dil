<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Support\Text;
use BackedEnum;
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
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (?string $state, callable $set, string $operation) {
                    if ($operation === 'create' && filled($state)) {
                        $set('slug', Text::slug($state, 'category'));
                    }
                }),

            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),

            Select::make('parent_id')
                ->label('Parent category')
                ->relationship('parent', 'name', fn ($query, ?Category $record) => $record
                    ? $query->whereKeyNot($record->getKey())
                    : $query)
                ->searchable()->preload()
                ->helperText('Leave blank for a top-level category.'),

            TextInput::make('ordering')->numeric()->default(0),

            Textarea::make('description')->rows(3)->columnSpanFull(),

            Toggle::make('published')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordering')
            ->defaultSort('ordering')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium'),
                TextColumn::make('slug')->searchable()->toggleable(),
                TextColumn::make('parent.name')->badge()->placeholder('top level')->toggleable(),
                TextColumn::make('posts_count')->counts('posts')->label('News')->sortable(),
                IconColumn::make('published')->boolean()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([TernaryFilter::make('published')])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
