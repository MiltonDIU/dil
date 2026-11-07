<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Support\CommonFilters;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),

            TextInput::make('username')->maxLength(255)->unique(ignoreRecord: true),

            TextInput::make('email')->email()->required()->maxLength(255)
                ->unique(ignoreRecord: true),

            /*
             * Blank on edit means "leave the password alone" — the two migrated
             * Joomla admins keep their existing bcrypt hashes untouched.
             */
            TextInput::make('password')
                ->password()->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText('Leave blank to keep the current password.'),

            Toggle::make('is_admin')
                ->label('Can access the admin panel')
                ->helperText('Users without this cannot sign in here at all.'),

            Toggle::make('blocked')
                ->helperText('Blocks sign-in without deleting the account.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium')
                    ->description(fn (User $r) => $r->username),
                TextColumn::make('email')->searchable()->sortable()->copyable(),
                IconColumn::make('is_admin')->label('Admin')->boolean()->sortable(),
                IconColumn::make('blocked')->boolean()->sortable()
                    ->trueColor('danger')->falseColor('gray'),
                TextColumn::make('last_login_at')->dateTime('j M Y')->sortable()
                    ->placeholder('never')->toggleable(),
                TextColumn::make('legacy_source')->badge()->label('Migrated')
                    ->placeholder('created here')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_admin')->label('Admin access'),
                TernaryFilter::make('blocked'),
                CommonFilters::dateRange('created_at', 'Created'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Deleting yourself locks you out of the panel.
                    DeleteBulkAction::make()
                        ->before(fn (Collection $records) => $records
                            ->reject(fn (Model $r) => $r->is(auth()->user()))),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
