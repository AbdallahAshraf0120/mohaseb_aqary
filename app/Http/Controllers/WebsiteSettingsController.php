<?php

namespace App\Http\Controllers;

use App\Models\WebsiteSetting;
use App\Support\WebsiteContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebsiteSettingsController extends Controller
{
    /** @var list<string> */
    private const SECTIONS = ['general', 'home', 'about', 'trust', 'contact'];

    public function index(): View
    {
        $site = WebsiteContent::all();

        return view('website.index', [
            'title' => 'إدارة الموقع | Mohaseb Aqary',
            'pageTitle' => 'إدارة الموقع الإلكتروني',
            'site' => $site,
            'sections' => $this->sectionMeta(),
        ]);
    }

    public function edit(string $section): View
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $setting = WebsiteSetting::current();
        $defaults = WebsiteContent::defaults();
        $meta = $this->sectionMeta()[$section];

        return view('website.edit', [
            'title' => $meta['title'].' | إدارة الموقع',
            'pageTitle' => $meta['title'],
            'section' => $section,
            'meta' => $meta,
            'setting' => $setting,
            'defaults' => $defaults,
            'sections' => $this->sectionMeta(),
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $setting = WebsiteSetting::current();
        $data = $this->validateSection($request, $section);
        $setting->fill($data)->save();
        WebsiteContent::forget();

        return redirect()
            ->route('website.edit', $section)
            ->with('success', 'تم حفظ إعدادات الموقع بنجاح.');
    }

    public function updateBrand(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:120'],
        ]);

        $setting = WebsiteSetting::current();
        $setting->fill($data)->save();
        WebsiteContent::forget();

        return redirect()
            ->route('website.index')
            ->with('success', 'تم تحديث اسم البراند على الموقع.');
    }

    /** @return array<string, array{title:string,desc:string,icon:string}> */
    private function sectionMeta(): array
    {
        return [
            'projects' => [
                'title' => 'مشاريع الموقع',
                'desc' => 'اختيار المشاريع الظاهرة، الصور، الوصف، الترتيب، ونشرها على الموقع.',
                'icon' => 'fa-building',
                'route' => 'website.projects.index',
            ],
            'general' => [
                'title' => 'الهوية والتواصل',
                'desc' => 'اسم الشركة، الشعار النصي، الهاتف، واتساب، البريد، والعنوان.',
                'icon' => 'fa-id-card',
            ],
            'home' => [
                'title' => 'الصفحة الرئيسية',
                'desc' => 'نصوص الـ Hero، الشريط المتحرك، عناوين المشاريع، ودعوة التواصل.',
                'icon' => 'fa-house',
            ],
            'about' => [
                'title' => 'من نحن',
                'desc' => 'مقدمة الصفحة ورؤية الشركة.',
                'icon' => 'fa-building',
            ],
            'trust' => [
                'title' => 'لماذا نحن والخدمات',
                'desc' => 'نقاط الثقة وقائمة الخدمات المعروضة.',
                'icon' => 'fa-shield-halved',
            ],
            'contact' => [
                'title' => 'صفحة التواصل',
                'desc' => 'عنوان الصفحة، المقدمة، ورسالة النجاح بعد الإرسال.',
                'icon' => 'fa-envelope-open-text',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function validateSection(Request $request, string $section): array
    {
        return match ($section) {
            'general' => $request->validate([
                'brand' => ['required', 'string', 'max:120'],
                'tagline' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'whatsapp' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'is_published' => ['nullable', 'boolean'],
            ]) + ['is_published' => $request->boolean('is_published')],
            'home' => (function () use ($request): array {
                $data = $request->validate([
                    'hero_eyebrow' => ['nullable', 'string', 'max:120'],
                    'hero_lead' => ['nullable', 'string', 'max:1000'],
                    'hero_cta_primary' => ['nullable', 'string', 'max:80'],
                    'hero_cta_secondary' => ['nullable', 'string', 'max:80'],
                    'marquee_text' => ['nullable', 'string', 'max:2000'],
                    'home_projects_title' => ['nullable', 'string', 'max:120'],
                    'home_projects_subtitle' => ['nullable', 'string', 'max:500'],
                    'home_projects_limit' => ['required', 'integer', 'min:1', 'max:24'],
                    'cta_title' => ['nullable', 'string', 'max:160'],
                    'cta_text' => ['nullable', 'string', 'max:500'],
                    'cta_button' => ['nullable', 'string', 'max:80'],
                ]);

                $items = preg_split('/\r\n|\r|\n/', (string) ($data['marquee_text'] ?? '')) ?: [];
                $data['marquee_items'] = array_values(array_filter(array_map('trim', $items), fn ($v) => $v !== ''));
                unset($data['marquee_text']);

                return $data;
            })(),
            'about' => $request->validate([
                'about_title' => ['nullable', 'string', 'max:120'],
                'about_intro' => ['nullable', 'string', 'max:2000'],
                'about_vision_title' => ['nullable', 'string', 'max:120'],
                'about_vision' => ['nullable', 'string', 'max:2000'],
            ]),
            'trust' => $this->validatePoints($request),
            'contact' => $request->validate([
                'contact_title' => ['nullable', 'string', 'max:120'],
                'contact_intro' => ['nullable', 'string', 'max:1000'],
                'contact_success' => ['nullable', 'string', 'max:255'],
            ]),
            default => [],
        };
    }

    /** @return array{trust_title:?string,trust_subtitle:?string,trust_points:list<array{title:string,text:string}>,services:list<array{title:string,text:string}>} */
    private function validatePoints(Request $request): array
    {
        $data = $request->validate([
            'trust_title' => ['nullable', 'string', 'max:120'],
            'trust_subtitle' => ['nullable', 'string', 'max:500'],
            'trust_points' => ['nullable', 'array', 'max:8'],
            'trust_points.*.title' => ['nullable', 'string', 'max:120'],
            'trust_points.*.text' => ['nullable', 'string', 'max:500'],
            'services' => ['nullable', 'array', 'max:8'],
            'services.*.title' => ['nullable', 'string', 'max:120'],
            'services.*.text' => ['nullable', 'string', 'max:500'],
        ]);

        $data['trust_points'] = $this->cleanPairs($data['trust_points'] ?? []);
        $data['services'] = $this->cleanPairs($data['services'] ?? []);

        return $data;
    }

    /**
     * @param  array<int, array{title?:string,text?:string}>  $rows
     * @return list<array{title:string,text:string}>
     */
    private function cleanPairs(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $text = trim((string) ($row['text'] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }
            $out[] = ['title' => $title !== '' ? $title : '—', 'text' => $text];
        }

        return $out;
    }
}
