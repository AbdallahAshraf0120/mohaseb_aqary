<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'brand',
        'tagline',
        'phone',
        'whatsapp',
        'email',
        'address',
        'hero_eyebrow',
        'hero_lead',
        'hero_cta_primary',
        'hero_cta_secondary',
        'marquee_items',
        'home_projects_title',
        'home_projects_subtitle',
        'home_projects_limit',
        'cta_title',
        'cta_text',
        'cta_button',
        'about_title',
        'about_intro',
        'about_vision_title',
        'about_vision',
        'trust_title',
        'trust_subtitle',
        'trust_points',
        'services',
        'contact_title',
        'contact_intro',
        'contact_success',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'marquee_items' => 'array',
            'trust_points' => 'array',
            'services' => 'array',
            'home_projects_limit' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'brand' => config('site.brand'),
            'tagline' => config('site.tagline'),
            'phone' => config('site.phone'),
            'whatsapp' => config('site.whatsapp'),
            'email' => config('site.email'),
            'address' => config('site.address'),
            'is_published' => true,
            'home_projects_limit' => 6,
        ]);
    }
}
