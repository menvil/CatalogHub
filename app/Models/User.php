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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['site_id', 'name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
/**
 * @property UserRole $role
 */
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
        return $this->userRole() === UserRole::SuperAdmin;
    }

    public function isCentralAdmin(): bool
    {
        return $this->userRole() === UserRole::CentralAdmin;
    }

    public function isCatalogEditor(): bool
    {
        return $this->userRole() === UserRole::CatalogEditor;
    }

    public function canManageImports(): bool
    {
        return $this->hasCatalogHubPermission('imports.manage');
    }

    public function isSiteAdmin(): bool
    {
        return $this->userRole() === UserRole::SiteAdmin;
    }

    public function isTranslator(): bool
    {
        return $this->userRole() === UserRole::Translator;
    }

    public function isModerator(): bool
    {
        return $this->userRole() === UserRole::Moderator;
    }

    public function hasCatalogHubPermission(string $permission): bool
    {
        return app(PermissionMatrix::class)->allows($this->userRole(), $permission);
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    private function userRole(): UserRole
    {
        $role = $this->getAttribute('role');

        if ($role instanceof UserRole) {
            return $role;
        }

        if (is_string($role)) {
            return UserRole::from($role);
        }

        return UserRole::default();
    }
}
