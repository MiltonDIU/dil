<?php

namespace App\Filament\Resources\Courses\Schemas;

use App\Filament\Support\SeoFields;
use App\Models\Course;
use App\Support\Text;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RelationshipRepeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CourseForm
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
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('The web address: /courses/your-slug'),

                    Textarea::make('summary')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull()
                        ->helperText('One or two sentences shown on the courses listing.'),

                    RichEditor::make('curriculum_html')
                        ->label('Course content')
                        ->columnSpanFull()
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('media/courses')
                        ->helperText('What visitors see. Leave blank to fall back to the original imported text.'),
                ])->columns(2),

                Tab::make('Lesson breakdown')->schema([
                    Section::make('Lessons')
                        ->description('Shown as a numbered accordion on the course page. Drag to reorder.')
                        ->schema([
                            RelationshipRepeater::make('lessons')
                                ->relationship('lessons')
                                ->orderColumn('ordering')
                                ->reorderable()
                                ->collapsible()
                                ->cloneable()
                                ->defaultItems(0)
                                ->itemLabel(fn (array $state) => filled($state['duration_label'] ?? null)
                                    ? $state['duration_label'].': '.($state['title'] ?? '')
                                    : ($state['title'] ?? 'New lesson'))
                                ->schema([
                                    TextInput::make('title')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Introduction & Basics'),

                                    TextInput::make('duration_label')
                                        ->label('When / duration')
                                        ->maxLength(100)
                                        ->placeholder('Weeks 1–2')
                                        ->helperText('Optional badge, e.g. "Weeks 1–2" or "Lesson 5".'),

                                    RichEditor::make('body')
                                        ->label('Topics covered')
                                        ->columnSpanFull()
                                        ->helperText('Grammar points, vocabulary themes, activities…'),

                                    Toggle::make('published')
                                        ->default(true)
                                        ->helperText('Unpublished lessons are hidden from the website.'),
                                ])
                                ->columns(2),
                        ]),
                ]),

                Tab::make('Structure & fees')->schema([
                    Section::make('Structure')->schema([
                        Select::make('language')
                            ->options(fn () => Course::query()
                                ->whereNotNull('language')->distinct()
                                ->orderBy('language')->pluck('language', 'language'))
                            ->searchable()
                            ->native(false),

                        Select::make('cefr_levels')
                            ->label('CEFR levels')
                            ->multiple()
                            ->options([
                                'A1' => 'A1 — Beginner', 'A2' => 'A2 — Elementary',
                                'B1' => 'B1 — Intermediate', 'B2' => 'B2 — Upper intermediate',
                                'C1' => 'C1 — Advanced', 'C2' => 'C2 — Proficient',
                            ])
                            ->helperText('Leave empty for courses that do not use CEFR (e.g. TOPIK, HSK).'),

                        TextInput::make('duration_months')->numeric()->minValue(0)->suffix('months'),
                        TextInput::make('classes_per_week')->numeric()->minValue(0),
                        TextInput::make('total_classes')->numeric()->minValue(0),
                        TextInput::make('class_duration_minutes')->numeric()->minValue(0)->suffix('minutes'),
                    ])->columns(3),

                    Section::make('Fees')
                        ->description('Leave a tier blank if it does not apply — blank tiers are hidden on the website.')
                        ->schema([
                            TextInput::make('fee_regular')->label('Regular')->numeric()->prefix('Tk.'),
                            TextInput::make('fee_daffodil_family')->label('Daffodil family')->numeric()->prefix('Tk.'),
                            TextInput::make('fee_outside')->label('Outside students')->numeric()->prefix('Tk.'),
                            TextInput::make('fee_note')->label('Fee note')->columnSpanFull()->maxLength(255),
                        ])->columns(3),
                ]),

                Tab::make('Publishing')->schema([
                    Toggle::make('published')->helperText('Unpublished courses are hidden from the website.'),
                    Toggle::make('featured')->label('Feature on homepage'),
                    DateTimePicker::make('published_at')->label('Publish date')->seconds(false),
                    TextInput::make('ordering')->numeric()->default(0)
                        ->helperText('Lower numbers appear first. You can also drag rows on the list page.'),

                    FileUpload::make('featured_image')
                        ->image()
                        ->disk('public')
                        ->directory('media/courses')
                        ->imageEditor()
                        ->columnSpanFull(),

                    TextInput::make('featured_image_alt')
                        ->label('Image description (alt text)')
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->helperText('Describes the image for screen readers and search engines.'),
                ])->columns(2),

                Tab::make('SEO')->schema([SeoFields::section()]),

                Tab::make('Original import')->schema([
                    Textarea::make('body_raw')
                        ->label('Original Joomla content')
                        ->rows(18)
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Read-only. Kept permanently so nothing from the old site is ever lost.'),
                ]),
            ]),
        ]);
    }
}
