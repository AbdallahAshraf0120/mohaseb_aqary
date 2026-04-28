<?php

use App\Services\CashboxLedgerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cashbox:rebuild', function (CashboxLedgerService $ledger): void {
    $ledger->rebuildFromAccountingRecords();
    $this->info('تمت مزامنة حركات الصندوق مع التحصيل والمصروفات ومقدمات البيع.');
})->purpose('Rebuild treasury ledger from revenues, expenses, and sale down payments');

Schedule::command('reports:daily-available-units')
    ->everyMinute()
    ->timezone(config('app.timezone', 'Africa/Cairo'))
    ->name('reports:daily-available-units');

Artisan::command('permissions:audit-routes', function (): int {
    /** @var array<string, string> $map */
    $map = config('route-permissions', []);
    $namedRoutes = collect(app('router')->getRoutes())
        ->map(fn ($r) => $r->getName())
        ->filter()
        ->unique()
        ->values()
        ->all();

    $missing = array_values(array_diff($namedRoutes, array_keys($map)));
    sort($missing);

    if ($missing === []) {
        $this->info('OK: كل الـ routes المسماة لها permission mapping.');
        return self::SUCCESS;
    }

    $this->error('Routes بدون permission mapping:');
    foreach ($missing as $name) {
        $this->line('- '.$name);
    }

    $this->newLine();
    $this->line('اقتراح لإضافتها (copy/paste):');
    foreach ($missing as $name) {
        $this->line("    '{$name}' => '{$name}',");
    }

    return self::FAILURE;
})->purpose('List named routes missing route-permissions mapping');
