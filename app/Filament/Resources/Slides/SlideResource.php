<?php

namespace App\Filament\Resources\Slides;

use App\Filament\Resources\Slides\Pages\CreateSlide;
use App\Filament\Resources\Slides\Pages\EditSlide;
use App\Filament\Resources\Slides\Pages\ListSlides;
use App\Models\Slide;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class SlideResource extends Resource
{
    protected static ?string $model = Slide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Slider';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Image')->schema([
                /*
                 * disk-relative storage that round-trips with the image_url
                 * accessor. No formatStateUsing/dehydrateStateUsing prefix
                 * hacks — they break the upload preview.
                 */
                FileUpload::make('image')
                    ->image()->required()
                    ->disk('public')->directory('media/slides')
                    ->imageEditor()
                    ->columnSpanFull(),

                TextInput::make('image_alt')
                    ->label('Image description (alt text)')
                    ->maxLength(255)->columnSpanFull()
                    ->helperText('Describes the image for screen readers and search engines.'),
            ]),

            Section::make('Caption & link')->schema([
                TextInput::make('title')->maxLength(255),
                TextInput::make('subtitle')->maxLength(255),
                Textarea::make('caption')->rows(2)->columnSpanFull(),
                TextInput::make('link_text')->maxLength(60),
                TextInput::make('link_url')->maxLength(255)
                    ->helperText('Leave blank to hide the button.'),
                Select::make('target')->options(['_self' => 'Same tab', '_blank' => 'New tab'])
                    ->default('_self'),
            ])->columns(2),

            Section::make('Publishing')->schema([
                Select::make('group')->required()->default('home')
                    ->options(['home' => 'Homepage'])
                    ->helperText('Which slider this slide belongs to.'),
                TextInput::make('ordering')->numeric()->default(0),
                Toggle::make('published')->default(true),
            ])->columns(3),
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
                ImageColumn::make('image_url')->label('Image')->height(48),
                TextColumn::make('title')->searchable()->sortable()->placeholder('(no caption)'),
                TextColumn::make('image_alt')->label('Alt text')->limit(45)
                    ->placeholder('missing')
                    ->color(fn (Slide $r) => blank($r->image_alt) ? 'warning' : null)
                    ->toggleable(),
                TextColumn::make('group')->badge()->sortable()->toggleable(),
                TextColumn::make('link_url')->limit(30)->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('published')->boolean()->sortable(),
                IconColumn::make('ai_alt_generated')->label('AI alt')->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group')->options(['home' => 'Homepage']),
                TernaryFilter::make('published'),
                TernaryFilter::make('ai_alt_generated')->label('AI-generated alt text'),
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
            'index' => ListSlides::route('/'),
            'create' => CreateSlide::route('/create'),
            'edit' => EditSlide::route('/{record}/edit'),
        ];
    }
}
