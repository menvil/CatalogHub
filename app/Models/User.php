<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Support\PermissionMatrix;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'role' => UserRole::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isCentralAdmin(): bool
    {
        return $this->role === UserRole::CentralAdmin;
    }

    public function isCatalogEditor(): bool
    {
        return $this->role === UserRole::CatalogEditor;
    }

    public function isSiteAdmin(): bool
    {
        return $this->role === UserRole::SiteAdmin;
    }

    public function isTranslator(): bool
    {
        return $this->role === UserRole::Translator;
    }

    public function isModerator(): bool
    {
        return $this->role === UserRole::Moderator;
    }

    public function hasCatalogHubPermission(string $permission): bool
    {
        return app(PermissionMatrix::class)->allows($this->role, $permission);
    }
}
