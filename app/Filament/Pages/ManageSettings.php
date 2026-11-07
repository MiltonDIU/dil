<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * One screen for everything that used to be hardcoded in Joomla's
 * configuration.php or the Helix Ultimate template params.
 *
 * The form is built from the `settings` rows themselves, so adding a setting
 * is a data change, not a code change.
 */
class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Appearance';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Site settings';

    protected string $view = 'filament.pages.manage-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    private const GROUP_LABELS = [
        'identity' => 'Identity',
        'contact' => 'Contact',
        'social' => 'Social links',
        'theme' => 'Colours',
        'seo' => 'SEO',
        'features' => 'Features',
        'payments' => 'Payments',
    ];

    public function mount(): void
    {
        $this->form->fill(
            Setting::query()->pluck('value', 'key')->all()
        );
    }

    public function form(Schema $schema): Schema
    {
        $groups = Setting::query()->orderBy('ordering')->get()->groupBy('group');

        $tabs = [];

        foreach (self::GROUP_LABELS as $group => $label) {
            if (! $groups->has($group)) {
                continue;
            }

            $tabs[] = Tab::make($label)->schema([
                Section::make()
                    ->schema($groups[$group]->map(fn (Setting $s) => $this->field($s))->all())
                    ->columns($group === 'theme' ? 3 : 1),
            ]);
        }

        return $schema
            ->components([Tabs::make()->tabs($tabs)->columnSpanFull()])
            ->statePath('data');
    }

    /**
     * Map a setting's `type` to the right control.
     */
    private function field(Setting $setting)
    {
        $field = match ($setting->type) {
            'textarea' => Textarea::make($setting->key)->rows(4),
            // Raw markup pasted verbatim (e.g. a map iframe) — a WYSIWYG
            // would strip it, so this stays a plain textarea.
            'html' => Textarea::make($setting->key)->rows(6),
            'richtext' => RichEditor::make($setting->key)
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory('media/site'),
            'boolean' => Toggle::make($setting->key),
            'color' => ColorPicker::make($setting->key),
            'number' => TextInput::make($setting->key)->numeric(),
            'email' => TextInput::make($setting->key)->email(),
            'url' => TextInput::make($setting->key)->url()
                ->helperText($setting->description ?: 'Leave blank to hide this link.'),
            'image' => FileUpload::make($setting->key)
                ->image()->disk('public')->directory('media/site')->imageEditor(),
            default => TextInput::make($setting->key)->maxLength(500),
        };

        return $field
            ->label($setting->label)
            ->helperText($setting->description ?: null);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $values = $this->form->getState();

        foreach ($values as $key => $value) {
            Setting::where('key', $key)->update([
                'value' => is_bool($value) ? ($value ? '1' : '0') : $value,
            ]);
        }

        Setting::flushCache();

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->body('Changes are live on the website immediately.')
            ->send();
    }
}
