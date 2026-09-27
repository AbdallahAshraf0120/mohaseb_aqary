<?php

namespace App\Support;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class WebsiteContent
{
    public const CACHE_KEY = 'website_content_v1';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'brand' => (string) config('site.brand', 'المنارة'),
            'tagline' => (string) config('site.tagline', 'تطوير عقاري بثقة وتسليم واضح'),
            'phone' => (string) config('site.phone', ''),
            'whatsapp' => (string) config('site.whatsapp', ''),
            'email' => (string) config('site.email', ''),
            'address' => (string) config('site.address', 'مصر'),
            'hero_eyebrow' => 'تطوير عقاري معاصر',
            'hero_lead' => 'نطوّر مشاريع سكنية بعقود واضحة، ومتابعة دقيقة للوحدات والتسليم — من التخطيط حتى التسليم.',
            'hero_cta_primary' => 'تصفح المشاريع',
            'hero_cta_secondary' => 'احجز استشارة',
            'marquee_items' => [
                'تطوير عقاري',
                'عقود واضحة',
                'تسليم منظم',
                'متابعة دقيقة للوحدات',
                'شراكات موثوقة',
            ],
            'home_projects_title' => 'مشاريع مميزة',
            'home_projects_subtitle' => 'اطّلع على أحدث مشاريعنا الجارية والمكتملة بتجربة عرض سلسة وواضحة.',
            'home_projects_limit' => 6,
            'cta_title' => 'هل تبحث عن وحدتك القادمة؟',
            'cta_text' => 'اترك بياناتك وسنتواصل معك لعرض الخيارات المناسبة بسرعة واحترافية.',
            'cta_button' => 'تواصل معنا الآن',
            'about_title' => 'من نحن',
            'about_intro' => 'شركة تطوير عقاري تركز على جودة التنفيذ ووضوح التعاقد مع العملاء والمستثمرين.',
            'about_vision_title' => 'رؤيتنا',
            'about_vision' => 'أن نكون وجهة موثوقة للتطوير العقاري: مشاريع مدروسة، وعقود واضحة، وتجربة عميل من الاستفسار حتى التسليم.',
            'trust_title' => 'لماذا المنارة؟',
            'trust_subtitle' => 'نبني بثقة، ونتواصل بوضوح، ونلتزم بجداول السداد والتسليم.',
            'trust_points' => [
                ['title' => 'شفافية تعاقدية', 'text' => 'عقود واضحة ومتابعة منظمة لكل وحدة.'],
                ['title' => 'إدارة مشاريع دقيقة', 'text' => 'من الأرض والبناء حتى المبيعات والتحصيل.'],
                ['title' => 'متابعة بعد البيع', 'text' => 'فريق جاهز للإجابة والمتابعة حتى التسليم.'],
            ],
            'services' => [
                ['title' => 'تطوير مشاريع سكنية', 'text' => 'تخطيط، بناء، وتسويق وحدات بمواصفات واضحة.'],
                ['title' => 'بيع وإدارة وحدات', 'text' => 'متابعة المبيعات والعقود والتحصيل بشفافية.'],
                ['title' => 'شراكات واستثمار', 'text' => 'فرص منظمة للمستثمرين والمساهمين ضمن إطار واضح.'],
            ],
            'contact_title' => 'تواصل معنا',
            'contact_intro' => 'اترك بياناتك واهتمامك، وسنعاود الاتصال في أقرب وقت.',
            'contact_success' => 'تم استلام طلبك بنجاح. سنتواصل معك قريباً.',
            'is_published' => true,
        ];
    }

    public static function all(): object
    {
        try {
            if (! Schema::hasTable('website_settings')) {
                return (object) self::defaults();
            }
        } catch (\Throwable) {
            return (object) self::defaults();
        }

        /** @var array<string, mixed> $data */
        $data = Cache::remember(self::CACHE_KEY, 300, function (): array {
            $defaults = self::defaults();
            $row = WebsiteSetting::query()->first();
            if (! $row) {
                return $defaults;
            }

            $payload = $row->toArray();
            unset($payload['id'], $payload['created_at'], $payload['updated_at']);

            foreach ($payload as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                if (in_array($key, ['marquee_items', 'trust_points', 'services'], true) && (! is_array($value) || $value === [])) {
                    continue;
                }
                $defaults[$key] = $value;
            }

            $defaults['home_projects_limit'] = max(1, min(24, (int) ($defaults['home_projects_limit'] ?? 6)));
            $defaults['is_published'] = (bool) ($defaults['is_published'] ?? true);

            return $defaults;
        });

        return (object) $data;
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        $all = (array) self::all();

        return $all[$key] ?? $default;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
