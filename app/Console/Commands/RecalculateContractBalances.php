<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Revenue;
use Illuminate\Console\Command;

class RecalculateContractBalances extends Command
{
    protected $signature = 'contracts:recalculate
        {--project= : Limit to a project id}
        {--apply : Actually write the corrected values (default is preview/dry-run only)}';

    protected $description = 'Recompute paid_amount/remaining_amount for all contracts from approved sale down payments + approved revenue installments. Fixes contracts whose remaining_amount never accounted for the down payment (bug in syncContractForSale ordering).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $projectId = $this->option('project');

        $query = Contract::query()->withoutProjectScope()->with('sale:id,down_payment,approval_status');
        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', (int) $projectId);
        }

        $affected = 0;
        $totalDelta = 0.0;
        $rows = [];

        $query->orderBy('id')->chunkById(200, function ($contracts) use (&$affected, &$totalDelta, &$rows, $apply): void {
            foreach ($contracts as $contract) {
                $paidFromRevenues = (float) Revenue::query()
                    ->withoutProjectScope()
                    ->where('contract_id', $contract->id)
                    ->where('approval_status', 'approved')
                    ->sum('amount');

                $downPayment = (($contract->sale?->approval_status ?? 'approved') === 'approved')
                    ? round((float) ($contract->sale?->down_payment ?? 0), 5)
                    : 0.0;

                $correctPaid = round($downPayment + $paidFromRevenues, 5);
                $correctRemaining = round(max(0, (float) $contract->total_price - $correctPaid), 5);

                $currentRemaining = round((float) $contract->remaining_amount, 5);
                $delta = round($correctRemaining - $currentRemaining, 5);

                if (abs($delta) >= 0.00001) {
                    $affected++;
                    $totalDelta += $delta;
                    if (count($rows) < 30) {
                        $rows[] = [
                            $contract->id,
                            number_format($currentRemaining, 2),
                            number_format($correctRemaining, 2),
                            number_format($delta, 2),
                        ];
                    }

                    if ($apply) {
                        $contract->update([
                            'paid_amount' => $correctPaid,
                            'remaining_amount' => $correctRemaining,
                        ]);
                    }
                }
            }
        });

        if ($rows !== []) {
            $this->table(['contract_id', 'remaining (current)', 'remaining (correct)', 'delta'], $rows);
        }

        $this->info(sprintf(
            '%s %d contract(s) with wrong remaining_amount. Total correction: %s.',
            $apply ? 'Fixed' : 'Found',
            $affected,
            number_format($totalDelta, 2)
        ));

        if (! $apply && $affected > 0) {
            $this->warn('This was a dry run — nothing was written. Re-run with --apply to persist the fix.');
        }

        return self::SUCCESS;
    }
}
