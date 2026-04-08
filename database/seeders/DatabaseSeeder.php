<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Property;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Shareholder;
use App\Models\User;
use App\Services\CashboxLedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $areasCount = 20;
        $shareholdersCount = 25;
        $propertiesCount = 80;
        $clientsCount = 300;
        $salesCount = 500;
        $expensesCount = 200;

        $admin = User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $areaNames = [
            'مدينة نصر',
            'التجمع الخامس',
            'الشيخ زايد',
            'العاصمة الإدارية',
            'المعادي',
            'الشروق',
            'العبور',
            'حدائق أكتوبر',
            'أكتوبر',
            'الرحاب',
        ];
        $areas = collect(range(1, $areasCount))->map(function (int $i) use ($areaNames) {
            $base = $areaNames[($i - 1) % count($areaNames)];
            return Area::query()->firstOrCreate(['name' => $base . ' - ' . $i]);
        });

        $shareholders = collect(range(1, $shareholdersCount))->map(function (int $i) {
            $share = fake()->numberBetween(3, 20);
            return Shareholder::query()->firstOrCreate(
                ['name' => "مساهم {$i}"],
                [
                    'share_percentage' => $share,
                    'total_investment' => fake()->numberBetween(500000, 8000000),
                    'profit_amount' => fake()->numberBetween(50000, 900000),
                ]
            );
        });

        $properties = collect(range(1, $propertiesCount))->map(function (int $i) use ($areas, $shareholders, $admin) {
            $floors = fake()->numberBetween(6, 20);
            $apartmentsPerFloor = fake()->numberBetween(2, 6);
            $modelsCount = fake()->numberBetween(2, 4);

            $models = collect(range(1, $modelsCount))->map(function (int $modelIndex) {
                return [
                    'model_name' => chr(64 + $modelIndex),
                    'area' => fake()->numberBetween(85, 220),
                ];
            })->values()->all();

            $selectedShareholders = $shareholders->random(fake()->numberBetween(2, 4))->values();
            $basePercentages = $selectedShareholders->map(fn () => fake()->numberBetween(10, 60));
            $total = max(1, $basePercentages->sum());
            $allocations = $selectedShareholders->map(function ($s, int $idx) use ($basePercentages, $total) {
                return [
                    'shareholder_id' => $s->id,
                    'shareholder_name' => $s->name,
                    'percentage' => round(($basePercentages[$idx] / $total) * 100, 2),
                ];
            })->values()->all();

            $area = $areas->random();

            return Property::query()->firstOrCreate(
                ['name' => "مشروع {$i}"],
                [
                    'area_id' => $area->id,
                    'property_type' => fake()->randomElement(['سكني', 'تجاري', 'إداري', 'مختلط']),
                    'floors_count' => $floors,
                    'apartments_per_floor' => $apartmentsPerFloor,
                    'total_apartments' => $floors * $apartmentsPerFloor,
                    'shareholder_allocations' => $allocations,
                    'apartment_models' => $models,
                    'location' => $area->name,
                    'price' => 0,
                    'status' => fake()->randomElement(['available', 'available', 'available', 'sold']),
                    'owner_id' => $admin->id,
                ]
            );
        });

        $clients = collect(range(1, $clientsCount))->map(function (int $i) {
            $phone = '010' . str_pad((string) $i, 8, '0', STR_PAD_LEFT);

            return Client::query()->updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => "عميل {$i}",
                    'email' => "client{$i}@example.com",
                    'national_id' => str_pad((string) (30000000000000 + $i), 14, '0', STR_PAD_LEFT),
                ]
            );
        });

        $sales = collect(range(1, $salesCount))->map(function (int $i) use ($properties, $clients) {
            $property = $properties->random();
            $models = collect($property->apartment_models ?? []);
            $model = $models->isNotEmpty() ? $models->random() : ['model_name' => 'A'];

            $salePrice = fake()->numberBetween(700000, 5000000);
            $paymentType = fake()->randomElement(['installment', 'installment', 'cash']);
            $downPayment = $paymentType === 'cash'
                ? $salePrice
                : fake()->numberBetween((int) ($salePrice * 0.25), (int) ($salePrice * 0.7));
            $months = $paymentType === 'cash' ? null : fake()->randomElement([12, 18, 24, 30, 36, 48]);
            $remaining = max(0, $salePrice - $downPayment);
            $monthly = ($months && $remaining > 0) ? round($remaining / $months, 2) : 0;
            $saleDate = Carbon::now()->subDays(fake()->numberBetween(1, 720))->toDateString();

            return Sale::query()->firstOrCreate(
                ['property_id' => $property->id, 'client_id' => $clients->random()->id, 'sale_date' => $saleDate, 'floor_number' => fake()->numberBetween(1, max(1, (int) $property->floors_count))],
                [
                    'apartment_model' => (string) ($model['model_name'] ?? 'A'),
                    'sale_price' => $salePrice,
                    'payment_type' => $paymentType,
                    'down_payment' => $downPayment,
                    'installment_months' => $months,
                    'installment_start_date' => $paymentType === 'cash' ? null : Carbon::parse($saleDate)->addMonth()->toDateString(),
                    'installment_plan' => $paymentType === 'cash'
                        ? null
                        : ['remaining_amount' => $remaining, 'monthly_installment' => $monthly],
                    'notes' => "بيع تلقائي رقم {$i}",
                ]
            );
        });

        $contracts = $sales->map(function (Sale $sale) {
            $endDate = $sale->installment_months
                ? Carbon::parse($sale->sale_date)->addMonths((int) $sale->installment_months)->toDateString()
                : Carbon::parse($sale->sale_date)->addYear()->toDateString();

            return Contract::query()->updateOrCreate(
                ['sale_id' => $sale->id],
                [
                    'client_id' => $sale->client_id,
                    'property_id' => $sale->property_id,
                    'start_date' => $sale->sale_date,
                    'end_date' => $endDate,
                    'total_price' => $sale->sale_price,
                    'paid_amount' => (float) $sale->down_payment,
                    'remaining_amount' => max(0, (float) $sale->sale_price - (float) $sale->down_payment),
                ]
            );
        });

        $revenues = collect();
        $contracts->each(function (Contract $contract) use (&$revenues): void {
            $paymentsCount = fake()->numberBetween(1, 4);
            $remaining = (float) $contract->remaining_amount;

            for ($i = 1; $i <= $paymentsCount && $remaining > 0; $i++) {
                $amount = $i === $paymentsCount
                    ? $remaining
                    : min($remaining, (float) fake()->numberBetween(20000, 180000));
                $remaining -= $amount;

                $revenue = Revenue::query()->firstOrCreate(
                    [
                        'contract_id' => $contract->id,
                        'paid_at' => Carbon::parse($contract->start_date)->addMonths($i)->toDateString(),
                        'amount' => $amount,
                    ],
                    [
                        'sale_id' => $contract->sale_id,
                        'client_id' => $contract->client_id,
                        'category' => 'قسط بيع',
                        'source' => "قسط {$i}",
                        'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'wallet']),
                        'notes' => 'تحصيل تلقائي من السيدر',
                    ]
                );

                $revenues->push($revenue);
            }
        });

        $contracts->each(function (Contract $contract): void {
            $paid = (float) Revenue::query()->where('contract_id', $contract->id)->sum('amount');
            $contract->update([
                'paid_amount' => $paid,
                'remaining_amount' => max(0, (float) $contract->total_price - $paid),
            ]);
        });

        collect(range(1, $expensesCount))->each(function (int $i): void {
            Expense::query()->firstOrCreate(
                ['description' => "مصروف رقم {$i}"],
                [
                    'amount' => fake()->numberBetween(5000, 120000),
                    'category' => fake()->randomElement(['تشغيل', 'تسويق', 'رواتب', 'صيانة', 'مرافق']),
                ]
            );
        });

        $contracts->each(function (Contract $contract): void {
            Debt::query()->updateOrCreate(
                ['client_id' => $contract->client_id],
                [
                    'total_amount' => $contract->total_price,
                    'paid_amount' => $contract->paid_amount,
                    'remaining_amount' => $contract->remaining_amount,
                    'status' => $contract->remaining_amount > 0 ? 'open' : 'closed',
                ]
            );
        });

        Setting::query()->firstOrCreate([], [
            'company_name' => 'Mohaseb Aqary',
            'currency' => 'EGP',
            'meta' => [
                'seeded' => true,
                'counts' => [
                    'areas' => $areasCount,
                    'shareholders' => $shareholdersCount,
                    'properties' => $propertiesCount,
                    'clients' => $clientsCount,
                    'sales' => $salesCount,
                    'expenses' => $expensesCount,
                ],
            ],
        ]);

        app(CashboxLedgerService::class)->rebuildFromAccountingRecords();
    }
}
