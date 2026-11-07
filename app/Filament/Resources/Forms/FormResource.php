<?php

namespace App\Filament\Resources\Forms;

use App\Filament\Resources\Forms\Pages\CreateForm;
use App\Filament\Resources\Forms\Pages\EditForm;
use App\Filament\Resources\Forms\Pages\ListForms;
use App\Models\Form as FormModel;
use App\Models\FormField;
use App\Support\Text;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RelationshipRepeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The dynamic form builder.
 *
 * Staff add, reorder and remove fields here with no deploy: the front-end
 * renderer walks `form_fields` and derives validation from each field's own
 * configuration.
 */
class FormResource extends Resource
{
    protected static ?string $model = FormModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Forms';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Form')->schema([
                TextInput::make('name')
                    ->required()->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, string $operation) {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Text::slug($state, 'form'));
                        }
                    }),

                TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),

                Textarea::make('description')->rows(2)->columnSpanFull(),

                TextInput::make('submit_label')->default('Submit')->maxLength(60),

                Textarea::make('success_message')->rows(2)
                    ->helperText('Shown after a successful submission.'),

                TagsInput::make('notify_emails')
                    ->label('Notify these addresses')
                    ->placeholder('name@example.com')
                    ->columnSpanFull()
                    ->helperText('Leave empty to disable email notifications.'),

                Toggle::make('store_submissions')->default(true)
                    ->helperText('Keep a copy of every submission in the admin.'),

                Toggle::make('published')->default(true),
            ])->columns(2),

            Section::make('Fields')
                ->description('Drag to reorder. Changes take effect immediately — no deploy needed.')
                ->schema([
                    RelationshipRepeater::make('fields')
                        ->relationship('fields')
                        ->orderColumn('ordering')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state) => ($state['label'] ?? 'New field')
                            .(($state['required'] ?? false) ? ' *' : ''))
                        ->schema([
                            TextInput::make('label')
                                ->required()->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, callable $set, Get $get) {
                                    if (blank($get('key')) && filled($state)) {
                                        $set('key', str($state)->slug('_')->toString());
                                    }
                                }),

                            TextInput::make('key')
                                ->required()->maxLength(60)
                                ->rules(['regex:/^[a-z][a-z0-9_]*$/'])
                                ->helperText('Lowercase letters, numbers and underscores. Used in exports.'),

                            Select::make('type')
                                ->required()->live()
                                ->default('text')
                                ->options(FormField::TYPES),

                            Select::make('width')
                                ->options(['full' => 'Full width', 'half' => 'Half width'])
                                ->default('full'),

                            TextInput::make('placeholder')->maxLength(255),
                            TextInput::make('help')->label('Help text')->maxLength(255),

                            KeyValue::make('options')
                                ->label('Choices')
                                ->keyLabel('Value')->valueLabel('Label')
                                ->columnSpanFull()
                                ->visible(fn (Get $get) => in_array($get('type'), ['select', 'radio', 'checkbox'], true)),

                            Select::make('options_source')
                                ->label('Or fill choices from')
                                ->options(['courses' => 'Published courses'])
                                ->placeholder('Use the list above')
                                ->columnSpanFull()
                                ->visible(fn (Get $get) => in_array($get('type'), ['select', 'radio'], true))
                                ->helperText('Choices stay in sync automatically as courses are published.'),

                            KeyValue::make('validation')
                                ->label('Validation')
                                ->keyLabel('Rule')->valueLabel('Value')
                                ->columnSpanFull()
                                ->helperText('e.g. min = 2, max = 255, mimes = pdf,docx, max_kb = 5120'),

                            Toggle::make('required'),
                            Toggle::make('published')->default(true),
                        ])
                        ->columns(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium')
                    ->description(fn (FormModel $r) => $r->slug),
                TextColumn::make('fields_count')->counts('fields')->label('Fields')->sortable(),
                TextColumn::make('submissions_count')->counts('submissions')->label('Submissions')->sortable(),
                IconColumn::make('store_submissions')->boolean()->label('Stores')->toggleable(),
                IconColumn::make('published')->boolean()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('published'),
                TernaryFilter::make('store_submissions'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}
