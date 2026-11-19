<?php

namespace App\Console\Commands;

use App\Mail\InstallmentRemindersMail;
use App\Models\Project;
use App\Models\Revenue;
use App\Models\Setting;
use App\Models\User;
use App\Services\InstallmentDueService;
use App\Support\CurrentProject;
use App\Support\Phone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * إيميل يومي بالأقساط المتأخرة واللي مستحقة قريب + الشيكات اللي ميعادها جه.
 * بيتبعت لنفس مستلمي «تقرير الوحدات المتاحة» في إعدادات المشروع.
 */
class SendInstallmentReminders extends Command
{
    protected $signature = 'installments:remind {--project= : مشروع واحد بس} {--force : ابعت حتى لو اتبعت النهارده أو التذكير متوقف}';

    protected $description = 'Email a daily digest of overdue / upcoming installments and due cheques to the project recipients.';

    public function handle(InstallmentDueService $service): int
    {
        $force = (bool) $this->option('force');
        $today = now()->startOfDay();
        $sent = 0;

        $projects = Project::query()->orderBy('id');
        if ($this->option('project')) {
            $projects->whereKey((int) $this->option('project'));
        }

        foreach ($projects->get() as $project) {
            $setting = Setting::query()->withoutProjectScope()->where('project_id', $project->id)->first();
            $meta = $setting?->meta ?? [];
            if (! $force) {
                if (! data_get($meta, 'installment_reminders_enabled', false)) {
                    continue;
                }
                if (data_get($meta, 'installment_reminders_last_sent_date') === $today->toDateString()) {
                    continue;
                }
            }
            $recipients = User::query()
                ->whereIn('id', collect(data_get($meta, 'daily_available_units_report_recipients', []))->map(fn ($v) => (int) $v)->all())
                ->whereNotNull('email')->pluck('email')->filter()->unique()->values()->all();
            if ($recipients === []) {
                continue;
            }

            app(CurrentProject::class)->force((int) $project->id);
            $daysBefore = max(0, min(30, (int) data_get($meta, 'installment_reminders_days_before', 3)));
            $rows = $service->dueRows((int) $project->id, $today->copy()->addDays($daysBefore)->endOfDay());
            $map = fn (array $r) => [
                'client' => $r['client']?->name ?? '—',
                'phone' => $r['client']?->phone ?? '',
                'unit' => ($r['property']?->name ?? '').' — '.$r['sale']->unitLabel(),
                'due_date' => $r['due_date']->toDateString(),
                'balance' => $r['balance'],
                'days_late' => $r['days_late'],
                'wa' => Phone::whatsappUrl($r['client']?->phone),
            ];
            $overdue = $rows->where('days_late', '>', 0)->map($map)->values()->all();
            $upcoming = $rows->where('days_late', 0)->map($map)->values()->all();
            $cheques = Revenue::query()->withoutGlobalScope('project')->where('project_id', $project->id)
                ->where('approval_status', '!=', 'rejected')->unclearedCheques()
                ->whereDate('cheque_due_date', '<=', $today->copy()->addDays($daysBefore))
                ->with(['client' => fn ($c) => $c->withoutGlobalScopes()])->orderBy('cheque_due_date')->get()
                ->map(fn (Revenue $c) => [
                    'number' => $c->cheque_number, 'bank' => $c->cheque_bank, 'client' => $c->client?->name,
                    'due_date' => optional($c->cheque_due_date)->toDateString(), 'amount' => (float) $c->amount,
                ])->all();

            if ($overdue === [] && $upcoming === [] && $cheques === [] && ! $force) {
                continue;
            }

            Mail::to($recipients)->send(new InstallmentRemindersMail($project, $today->toDateString(), $overdue, $upcoming, $cheques, $daysBefore));
            if ($setting) {
                $meta['installment_reminders_last_sent_date'] = $today->toDateString();
                $setting->update(['meta' => $meta]);
            }
            $sent++;
        }
        app(CurrentProject::class)->clearForce();

        $this->info("Sent installment reminders for {$sent} project(s).");

        return self::SUCCESS;
    }
}
