<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Whether this user may enter the operator panel.
     *
     * Filament only honours panel access when the user model implements
     * `FilamentUser`. Without it, access is granted solely because the app runs
     * in the `local` environment, so the moment `APP_ENV` is anything else the
     * entire panel returns 403 for every operator.
     *
     * This project has no role or permission model yet, so every authenticated
     * user counts as an operator. Add the real check here (role, tenant, invite
     * flag) before this reaches production.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
