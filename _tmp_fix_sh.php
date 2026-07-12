<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ShareholderController;
use App\Models\Project;
use App\Models\Shareholder;
use App\Models\ShareholderLedgerEntry;
use App\Support\CurrentProject;
use Illuminate\Http\Request;

$project = Project::query()->orderBy('id')->first();
app(CurrentProject::class)->force((int) $project->id);
session(['current_project_id' => (int) $project->id]);

$rows = Shareholder::query()->withSum([
    'ledgerEntries as ledger_credit_sum' => fn ($q) => $q->where('direction', ShareholderLedgerEntry::DIRECTION_CREDIT),
], 'amount')->limit(1)->get();
echo 'withSum OK count='.$rows->count().PHP_EOL;

$view = app(ShareholderController::class)->index($project, Request::create('/'.$project->id.'/shareholders', 'GET'));
echo 'index OK '.$view->name().PHP_EOL;
