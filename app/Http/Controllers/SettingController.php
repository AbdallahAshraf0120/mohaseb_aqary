<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Support\CurrentProject;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    public function edit(): View
    {
        $projectId = app(CurrentProject::class)->id();
        $setting = Setting::query()->firstOrCreate(
            ['project_id' => $projectId],
            [
                'company_name' => 'Real Estate Demo',
                'currency' => 'EGP',
                'meta' => [],
            ]
        );

        return view('settings.edit', [
            'title' => 'الإعدادات | Mohaseb Aqary',
            'pageTitle' => 'الإعدادات',
            'setting' => $setting,
            'users' => User::query()->select('id', 'name', 'email', 'role')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:20'],
            'daily_available_units_report_enabled' => ['nullable', 'in:0,1'],
            'daily_available_units_report_time' => ['nullable', 'date_format:H:i'],
            'daily_available_units_report_repeat_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'daily_available_units_report_recipients' => ['nullable', 'array'],
            'daily_available_units_report_recipients.*' => ['integer', 'exists:users,id'],
        ]);

        $setting = Setting::query()->firstOrFail();
        $recipients = collect($data['daily_available_units_report_recipients'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $meta = $setting->meta ?? [];
        $meta['daily_available_units_report_enabled'] = $request->boolean('daily_available_units_report_enabled');
        $meta['daily_available_units_report_time'] = $data['daily_available_units_report_time'] ?? data_get($meta, 'daily_available_units_report_time', '08:00');
        $meta['daily_available_units_report_repeat_minutes'] = (int) ($data['daily_available_units_report_repeat_minutes'] ?? data_get($meta, 'daily_available_units_report_repeat_minutes', 0));
        $meta['daily_available_units_report_recipients'] = $recipients;

        unset($data['daily_available_units_report_recipients']);
        unset($data['daily_available_units_report_enabled']);
        unset($data['daily_available_units_report_time']);
        unset($data['daily_available_units_report_repeat_minutes']);
        $setting->update(array_merge($data, ['meta' => $meta]));

        return redirect()->route('settings.edit')->with('success', 'تم تحديث الإعدادات بنجاح.');
    }

    public function sendAvailableUnitsReportNow(Request $request): RedirectResponse
    {
        $projectId = app(CurrentProject::class)->id();
        if ($projectId === null) {
            return redirect()->route('settings.edit')->with('error', 'تعذّر تحديد المشروع الحالي.');
        }

        Artisan::call('reports:daily-available-units', [
            '--project' => (int) $projectId,
            '--force' => true,
        ]);

        return redirect()->route('settings.edit')->with('success', 'تم إرسال التقرير الآن (إن وُجد مستلمون).');
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
            'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
            'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'contracts.index'],
            'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
            'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'revenues.index'],
            'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'expenses.index'],
            'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'cashbox.index'],
            'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'remaining.index'],
            'debts' => ['label' => 'ذمم دائنة', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
