<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'extra_permissions'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('users')
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'إنشاء مستخدم',
                'updated' => 'تعديل مستخدم',
                'deleted' => 'حذف مستخدم',
                default => $eventName,
            });
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

    /**
     * قائمة صلاحيات مخصصة: إن وُجدت (غير فارغة) تُستبدل بها صلاحيات الدور بالكامل.
     *
     * @return list<string>
     */
    public function customPermissionSlugs(): array
    {
        return array_values(array_unique(array_filter($this->extra_permissions ?? [])));
    }

    public function usesExclusiveCustomPermissions(): bool
    {
        return $this->customPermissionSlugs() !== [];
    }

    /**
     * تحقق من صلاحية: إما المجموعة المخصصة فقط (إن وُجدت) أو صلاحيات الدور الافتراضية.
     * امتلاك *.manage يمنح تلقائياً *.view المقابلة ضمن نفس المجموعة (مخصص أو دور).
     */
    public function hasPermission(string $slug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $custom = $this->customPermissionSlugs();
        if ($custom !== []) {
            return $this->slugAllowedBySet($slug, $custom);
        }

        $granted = $this->directPermissionSlugs();

        return $this->slugAllowedBySet($slug, $granted);
    }

    /**
     * @param  list<string>  $set
     */
    private function slugAllowedBySet(string $slug, array $set): bool
    {
        if (in_array($slug, $set, true)) {
            return true;
        }
        if (str_ends_with($slug, '.view')) {
            $manage = substr($slug, 0, -5).'.manage';

            return in_array($manage, $set, true);
        }

        return false;
    }
}
