<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WebsiteProject extends Model
{
    protected $fillable = [
        'project_id',
        'is_published',
        'show_on_home',
        'sort_order',
        'title',
        'subtitle',
        'location',
        'status_label',
        'excerpt',
        'description',
        'cover_path',
        'gallery',
        'features',
        'cta_label',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_on_home' => 'boolean',
            'sort_order' => 'integer',
            'gallery' => 'array',
            'features' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereHas('project', fn (Builder $q) => $q->listed());
    }

    public function scopeOnHome(Builder $query): Builder
    {
        return $query->where('show_on_home', true);
    }

    public function displayTitle(): string
    {
        $title = trim((string) ($this->title ?? ''));
        if ($title !== '') {
            return $title;
        }

        return (string) ($this->project?->name ?? 'مشروع');
    }

    public function coverUrl(): ?string
    {
        $path = $this->cover_path;
        if (! is_string($path) || $path === '') {
            return null;
        }

        return $this->publicMediaUrl($path);
    }

    /** @return list<string> */
    public function galleryUrls(): array
    {
        $gallery = is_array($this->gallery) ? $this->gallery : [];
        $urls = [];
        foreach ($gallery as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            $urls[] = $this->publicMediaUrl($path);
        }

        return $urls;
    }

    /**
     * Root-relative URL so images work on any host/port (8000, 8007, domain...).
     */
    private function publicMediaUrl(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $base = '';
        try {
            if (app()->bound('request') && request()) {
                $base = rtrim((string) request()->getBasePath(), '/');
            }
        } catch (\Throwable) {
            $base = '';
        }

        return $base.'/storage/'.$path;
    }

    public function purgeFiles(): void
    {
        $disk = Storage::disk('public');
        if (is_string($this->cover_path) && $this->cover_path !== '') {
            $disk->delete($this->cover_path);
        }
        foreach (is_array($this->gallery) ? $this->gallery : [] as $path) {
            if (is_string($path) && $path !== '') {
                $disk->delete($path);
            }
        }
        $disk->deleteDirectory('website/projects/'.$this->project_id);
    }
}
