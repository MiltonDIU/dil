<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyKey;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'username', 'email', 'password', 'is_admin', 'blocked',
    'last_login_at', 'legacy_id', 'legacy_source',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasLegacyKey, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_admin' => 'boolean',
            'blocked' => 'boolean',
            /*
             * Laravel's `hashed` cast is a no-op on an already-hashed value, so
             * the two legacy Joomla bcrypt hashes ($2y$, 60 chars) survive the
             * import untouched and both admins keep their existing passwords.
             */
            'password' => 'hashed',
        ];
    }

    /**
     * Gate the Filament panel. Only non-blocked admins get in.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin && ! $this->blocked;
    }
}
