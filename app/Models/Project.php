<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
        'is_draft',
        'contract_template_path',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_draft' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Project $project): void {
            $project->purgeContractTemplateFiles();
        });
    }

    public function hasContractTemplate(): bool
    {
        $path = $this->contract_template_path;

        return is_string($path) && $path !== '' && Storage::disk('local')->exists($path);
    }

    /** مسار التخزين النسبي لقرص local (مثل ‎project-contract-templates/1/template.docx‎). */
    public static function contractTemplateRelativePath(int $projectId): string
    {
        return 'project-contract-templates/'.$projectId.'/template.docx';
    }

    public function purgeContractTemplateFiles(): void
    {
        Storage::disk('local')->deleteDirectory('project-contract-templates/'.$this->id);
    }

    /** مشاريع تظهر في الشريط الجانبي والتنقل (ليست مسودة). */
    public function scopeListed($query)
    {
        return $query->where('is_active', true)->where('is_draft', false);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /** مطلوب لربط المسارات المقيّدة `{project}/revenues/{revenue}` (scopeBindings). */
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function shareholders(): HasMany
    {
        return $this->hasMany(Shareholder::class);
    }

    public function facings(): HasMany
    {
        return $this->hasMany(Facing::class);
    }

    public function lands(): HasMany
    {
        return $this->hasMany(Land::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(Debt::class);
    }
}
