<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\EditMedia;
use App\Filament\Resources\Media\Pages\ListMedia;
use App\Filament\Support\CommonFilters;
use App\Models\Media;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Media library';

    public static function canCreate(): bool
    {
        return false;   // files arrive via uploads on each resource
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->maxLength(255),
            TextInput::make('alt')->label('Alt text')->maxLength(255),
            TextInput::make('path')->disabled()->dehydrated(false)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('path')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->columns([
                ImageColumn::make('url')->label('')->height(40),
                TextColumn::make('filename')->searchable()->sortable()->weight('medium')
                    ->description(fn (Media $r) => $r->folder),
                TextColumn::make('extension')->badge()->sortable()->toggleable(),
                TextColumn::make('human_size')->label('Size')->sortable('size'),
                TextColumn::make('dimensions')
                    ->label('Dimensions')
                    ->state(fn (Media $r) => $r->width ? "{$r->width}×{$r->height}" : '—')
                    ->toggleable(),
                TextColumn::make('alt')->limit(40)->placeholder('—')->toggleable(),

                // Catches files deleted from disk outside the app.
                IconColumn::make('on_disk')
                    ->label('On disk')
                    ->boolean()
                    ->state(fn (Media $r) => $r->exists())
                    ->falseColor('danger'),
            ])
            ->filters([
                CommonFilters::fromColumn(Media::class, 'folder'),
                CommonFilters::fromColumn(Media::class, 'extension', 'File type'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
