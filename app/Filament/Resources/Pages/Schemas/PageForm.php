<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Support\SeoFields;
use App\Support\Text;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->tabs([

                Tab::make('Content')->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $set, string $operation) {
                            if ($operation === 'create' && filled($state)) {
                                $set('slug', Text::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->required()->maxLength(255)->unique(ignoreRecord: true)
                        ->helperText('The web address: /your-slug'),

                    Textarea::make('excerpt')->rows(2)->maxLength(500)->columnSpanFull(),

                    RichEditor::make('body')
                        ->label('Page content')
                        ->columnSpanFull()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('media/pages'),
                ])->columns(2),

                Tab::make('Publishing')->schema([
                    Section::make()->schema([
                        Toggle::make('published'),
                        DateTimePicker::make('published_at')->seconds(false),
                        TextInput::make('ordering')->numeric()->default(0),
                    ])->columns(3),

                    FileUpload::make('featured_image')
                        ->image()->disk('public')->directory('media/pages')
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
