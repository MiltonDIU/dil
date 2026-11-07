<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Support\SeoFields;
use App\Support\Text;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->tabs([

                Tab::make('Content')->schema([
                    TextInput::make('title')
                        ->required()->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $set, string $operation) {
                            if ($operation === 'create' && filled($state)) {
                                $set('slug', Text::slug($state, 'news'));
                            }
                        }),

                    TextInput::make('slug')
                        ->required()->maxLength(255)->unique(ignoreRecord: true)
                        ->helperText('The web address: /news/your-slug'),

                    Select::make('category_id')
                        ->relationship('category', 'name')
                        ->searchable()->preload(),

                    Textarea::make('excerpt')->rows(2)->maxLength(500)->columnSpanFull(),

                    RichEditor::make('body')
                        ->label('Article')
                        ->columnSpanFull()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('media/news'),
                ])->columns(2),

                Tab::make('Publishing')->schema([
                    Section::make()->schema([
                        Toggle::make('published'),
                        Toggle::make('featured'),
                        DateTimePicker::make('published_at')->seconds(false),
                    ])->columns(3),

                    FileUpload::make('featured_image')
                        ->image()->disk('public')->directory('media/news')
                        ->imageEditor()->columnSpanFull(),

                    TextInput::make('featured_image_alt')
                        ->label('Image description (alt text)')
                        ->maxLength(255)->columnSpanFull(),
                ]),

                Tab::make('SEO')->schema([SeoFields::section()]),

                Tab::make('Original import')->schema([
                    Textarea::make('body_raw')
                        ->label('Original Joomla content')
                        ->rows(18)->disabled()->dehydrated(false)
                        ->helperText('Read-only. Kept permanently so nothing from the old site is ever lost.'),
                ]),
            ]),
        ]);
    }
}
