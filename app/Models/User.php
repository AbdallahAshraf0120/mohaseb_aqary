<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var list<string>|null */
    protected ?array $permissionSlugCache = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'extra_permissions',
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
            'extra_permissions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $user->permissionSlugCache = null;
        });
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? '') === 'admin';
    }

    /**
     * @return list<string>
     */
    public function directPermissionSlugs(): array
    {
        if ($this->isAdmin()) {
            return ['*'];
        }

        return $this->permissionSlugCache ??= RolePermission::query()
            ->where('role', (string) ($this->role ?? ''))
            ->pluck('permission_slug')
            ->all();
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $extras = array_values(array_unique(array_filter($this->extra_permissions ?? [])));
        if (in_array($slug, $extras, true)) {
            return true;
        }
        if (str_ends_with($slug, '.view')) {
            $manage = substr($slug, 0, -5).'.manage';
            if (in_array($manage, $extras, true)) {
                return true;
            }
        }

        $granted = $this->directPermissionSlugs();
        if (in_array($slug, $granted, true)) {
            return true;
        }
        if (str_ends_with($slug, '.view')) {
            $manage = substr($slug, 0, -5).'.manage';

            return in_array($manage, $granted, true);
        }

        return false;
    }
}
