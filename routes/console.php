<?php

use App\Services\CashboxLedgerService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cashbox:rebuild', function (CashboxLedgerService $ledger): void {
    $ledger->rebuildFromAccountingRecords();
    $this->info('تمت مزامنة حركات الصندوق مع التحصيل والمصروفات ومقدمات البيع.');
})->purpose('Rebuild treasury ledger from revenues, expenses, and sale down payments');
