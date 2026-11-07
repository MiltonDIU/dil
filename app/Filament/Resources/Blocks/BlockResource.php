<?php

namespace App\Filament\Resources\Blocks;

use App\Filament\Resources\Blocks\Pages\CreateBlock;
use App\Filament\Resources\Blocks\Pages\EditBlock;
use App\Filament\Resources\Blocks\Pages\ListBlocks;
use App\Models\Block;
use App\Models\Course;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

/**
 * Homepage sections, replacing SP Page Builder.
 *
 * Each block keeps the SPPB JSON it was built from in `raw_layout`, which is
 * shown read-only on the edit screen and never overwritten.
 */
class BlockResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Homepage blocks';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Block')->schema([
                Select::make('type')
                    ->required()->live()
                    ->options(Block::TYPES)
                    ->helperText('Determines how this section is displayed.'),

                Select::make('group')
                    ->required()->default('home')
                    ->options(['home' => 'Homepage'])
                    ->helperText('Which page this block appears on.'),

                TextInput::make('title')->maxLength(255),
                TextInput::make('subtitle')->maxLength(255),
            ])->columns(2),

            Section::make('Content')->schema([
                RichEditor::make('content')
                    ->visible(fn (Get $get) => in_array($get('type'), ['rich_text', 'cta', 'course_spotlight'], true))
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('media/blocks'),

                Textarea::make('data.embed')
                    ->label('Embed code')
                    ->rows(5)->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === 'map')
                    ->helperText('Paste the full <iframe> from Google Maps.'),

                TextInput::make('data.url')
                    ->label('Video URL')
                    ->url()->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === 'video')
                    ->helperText('A YouTube watch or share link.'),

                Repeater::make('data.items')
                    ->label('Links')
                    ->visible(fn (Get $get) => $get('type') === 'link_list')
                    ->columnSpanFull()
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['label'] ?? 'Link')
                    ->schema([
                        TextInput::make('label')->required()->maxLength(120),
                        // The legacy lists were plain <li> text with no hrefs,
                        // so these import with an empty URL rather than a
                        // fabricated one.
                        TextInput::make('url')->maxLength(255)
                            ->helperText('Leave blank to show the label as plain text.'),
                    ])->columns(2),

                Repeater::make('data.cards')
                    ->label('Cards')
                    ->visible(fn (Get $get) => $get('type') === 'feature_cards')
                    ->columnSpanFull()
                    ->reorderable()->collapsible()
                    ->itemLabel(fn (array $state) => $state['title'] ?? 'Card')
                    ->schema([
                        TextInput::make('title')->maxLength(120),
                        TextInput::make('icon')->maxLength(60)
                            ->helperText('Font Awesome class, e.g. "fas fa-bahai".'),
                        Textarea::make('body')->rows(3)->columnSpanFull(),
                        TextInput::make('link_text')->maxLength(60),
                        TextInput::make('link_url')->maxLength(255)
                            ->helperText('Leave blank to hide the button.'),
                    ])->columns(2),

                Select::make('data.course_ids')
                    ->label('Courses to feature')
                    ->multiple()->searchable()->columnSpanFull()
                    ->options(fn () => Course::query()->orderBy('language')->orderBy('title')->pluck('title', 'id'))
                    ->visible(fn (Get $get) => $get('type') === 'course_spotlight')
                    ->helperText('Shown in the order selected. Fee and duration come from each course.'),

                Repeater::make('data.images')
                    ->label('Photos')
                    ->visible(fn (Get $get) => $get('type') === 'gallery')
                    ->columnSpanFull()
                    ->reorderable()->collapsible()
                    ->itemLabel(fn (array $state) => $state['caption'] ?? 'Photo')
                    ->schema([
                        FileUpload::make('image')->image()->required()
                            ->disk('public')->directory('media/gallery'),
                        TextInput::make('caption')->maxLength(80),
                        TextInput::make('alt')->label('Image description (alt text)')->maxLength(255),
                    ])->columns(2),

                Textarea::make('content')
                    ->label('HTML')
                    ->rows(10)->columnSpanFull()
                    ->visible(fn (Get $get) => $get('type') === 'html'),
            ]),

            Section::make('Appearance & publishing')->schema([
                FileUpload::make('image')->image()->disk('public')->directory('media/blocks')
                    ->imageEditor(),
                TextInput::make('image_alt')->label('Image description (alt text)')->maxLength(255),
                TextInput::make('link_text')->maxLength(60),
                TextInput::make('link_url')->maxLength(255),
                Select::make('background')->options([
                    'white' => 'White', 'light' => 'Light grey',
                    'dark' => 'Dark', 'brand' => 'Brand colour',
                ])->placeholder('Default'),
                TextInput::make('ordering')->numeric()->default(0),
                Toggle::make('full_width'),
                Toggle::make('published')->default(true),
            ])->columns(2),

            Section::make('Original import')
                ->collapsed()
                ->schema([
                    Textarea::make('raw_layout')
                        ->label('Original SP Page Builder data')
                        ->rows(12)->disabled()->dehydrated(false)
                        ->helperText('Read-only. Kept permanently so the original layout is never lost.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('ordering')
            ->defaultSort('ordering')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->columns([
                TextColumn::make('ordering')->label('#')->sortable()->toggleable(),
                TextColumn::make('title')->searchable()->sortable()
                    ->placeholder('(untitled)')->weight('medium'),
                TextColumn::make('type')->badge()->sortable()
                    ->formatStateUsing(fn (Block $r) => $r->typeLabel()),
                TextColumn::make('group')->badge()->sortable()->toggleable(),
                IconColumn::make('published')->boolean()->sortable(),
                IconColumn::make('full_width')->boolean()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->options(Block::TYPES)->multiple(),
                SelectFilter::make('group')->options(['home' => 'Homepage']),
                TernaryFilter::make('published'),
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

    public static function getPages(): array
    {
        return [
            'index' => ListBlocks::route('/'),
            'create' => CreateBlock::route('/create'),
            'edit' => EditBlock::route('/{record}/edit'),
        ];
    }
}
