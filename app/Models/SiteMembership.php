<?php

namespace App\Models;

use App\Enums\SiteMembershipRole;
use Database\Factories\SiteMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'site_id', 'role', 'is_active'])]
final class SiteMembership extends Model
{
    /** @use HasFactory<SiteMembershipFactory> */
    use HasFactory;

    protected $table = 'site_user_memberships';

    protected static function newFactory(): SiteMembershipFactory
    {
        return SiteMembershipFactory::new();
    }

    protected function casts(): array
    {
        return [
            'role' => SiteMembershipRole::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Site, $this> */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function roleEnum(): SiteMembershipRole
    {
        $role = $this->getAttribute('role');

        return $role instanceof SiteMembershipRole ? $role : SiteMembershipRole::from((string) $role);
    }
}
