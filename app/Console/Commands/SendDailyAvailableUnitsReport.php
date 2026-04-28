<?php

namespace App\Console\Commands;

use App\Mail\DailyAvailableUnitsReportMail;
use App\Models\Project;
use App\Models\Property;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyAvailableUnitsReport extends Command
{
    protected $signature = 'reports:daily-available-units {--project= : Project id to send only} {--force : Send even if current time does not match settings}';

    protected $description = 'Send daily available units report (unsold units) to selected accounts per project.';

    public function handle(): int
    {
        $projectIdOpt = $this->option('project');
        $force = (bool) $this->option('force');
        $projects = Project::query()->orderBy('id');
        if ($projectIdOpt !== null && $projectIdOpt !== '') {
            $projects->whereKey((int) $projectIdOpt);
        }

        $reportDate = now()->toDateString();
        $nowTime = now()->format('H:i');
        $sent = 0;

        foreach ($projects->get() as $project) {
            $setting = Setting::query()
                ->withoutProjectScope()
                ->where('project_id', $project->id)
                ->first();

            $enabled = (bool) data_get($setting?->meta, 'daily_available_units_report_enabled', false);
            $at = (string) data_get($setting?->meta, 'daily_available_units_report_time', '08:00');
            if (! $force) {
                if (! $enabled) {
                    continue;
                }
                if ($nowTime !== $at) {
                    continue;
                }
            }

            $recipientIds = collect(data_get($setting?->meta, 'daily_available_units_report_recipients', []))
                ->map(fn ($v) => (int) $v)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            if ($recipientIds === []) {
                continue;
            }

            $recipients = User::query()
                ->whereIn('id', $recipientIds)
                ->whereNotNull('email')
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($recipients === []) {
                continue;
            }

            $rows = $this->buildRowsForProject($project);

            Mail::to($recipients)->send(new DailyAvailableUnitsReportMail(
                project: $project,
                reportDate: $reportDate,
                rows: $rows,
            ));

            $sent++;
        }

        $this->info("Sent daily available units report for {$sent} project(s).");

        return self::SUCCESS;
    }

    /**
     * @return list<array{property_name:string, total_units:int, sold_units:int, available_units:int}>
     */
    private function buildRowsForProject(Project $project): array
    {
        $properties = Property::query()
            ->withoutProjectScope()
            ->where('project_id', $project->id)
            ->select('id', 'name', 'total_apartments', 'floors_count', 'apartments_per_floor', 'ground_floor_shops_count', 'has_mezzanine', 'mezzanine_apartments_count')
            ->orderBy('name')
            ->get();

        $soldByProperty = Sale::query()
            ->withoutProjectScope()
            ->where('project_id', $project->id)
            ->where('approval_status', 'approved')
            ->selectRaw('property_id, count(*) as sold_count')
            ->groupBy('property_id')
            ->pluck('sold_count', 'property_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $rows = [];
        foreach ($properties as $p) {
            $total = (int) ($p->total_apartments ?? 0);
            if ($total <= 0) {
                $floors = max(0, (int) ($p->floors_count ?? 0));
                $perFloor = max(0, (int) ($p->apartments_per_floor ?? 0));
                $shops = max(0, (int) ($p->ground_floor_shops_count ?? 0));
                $mezz = ((bool) ($p->has_mezzanine ?? false)) ? max(0, (int) ($p->mezzanine_apartments_count ?? 0)) : 0;
                $total = ($floors * $perFloor) + $shops + $mezz;
            }

            $sold = (int) ($soldByProperty[$p->id] ?? 0);
            $available = max(0, $total - $sold);
            if ($available <= 0) {
                continue;
            }

            $rows[] = [
                'property_name' => (string) $p->name,
                'total_units' => $total,
                'sold_units' => $sold,
                'available_units' => $available,
            ];
        }

        return $rows;
    }
}

