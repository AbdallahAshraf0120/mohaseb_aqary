<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Facing;
use App\Models\Project;
use App\Models\Property;
use App\Models\Revenue;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Shareholder;
use App\Models\User;
use App\Services\CashboxLedgerService;
use App\Support\CurrentProject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        $projectSpecs = [
            ['code' => 'default', 'name' => 'المشروع الافتراضي'],
            ['code' => 'palm-towers', 'name' => 'أبراج النخيل'],
            ['code' => 'north-compound', 'name' => 'كمبوند الشمال'],
            ['code' => 'capital-r2', 'name' => 'العاصمة الإدارية — المرحلة 2'],
            ['code' => 'west-plaza', 'name' => 'ويست بلازا التجاري'],
        ];

        $counts = [
            'areas' => 32,
            'shareholders' => 42,
            'properties' => 110,
            'clients' => 480,
            'sales' => 750,
            'expenses' => 320,
        ];

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
            'المنيل',
            'مصر الجديدة',
        ];

        foreach ($projectSpecs as $spec) {
            $project = Project::query()->firstOrCreate(
                ['code' => $spec['code']],
                ['name' => $spec['name'], 'is_active' => true, 'is_draft' => false]
            );

            app(CurrentProject::class)->force((int) $project->id);

            try {
                $this->seedHeavyDemoForProject($project, $admin, $counts, $areaNames);
                app(CashboxLedgerService::class)->rebuildFromAccountingRecords();
            } finally {
                app(CurrentProject::class)->force(null);
            }
        }
    }

    /**
     * @param  array{areas: int, shareholders: int, properties: int, clients: int, sales: int, expenses: int}  $counts
     * @param  array<int, string>  $areaNames
     */
    private function seedHeavyDemoForProject(Project $project, User $admin, array $counts, array $areaNames): void
    {
        $pid = (int) $project->id;
        Facing::seedDefaultsForProject($pid);
        $slug = $project->code ?? (string) $pid;

        $areas = collect(range(1, $counts['areas']))->map(function (int $i) use ($areaNames, $pid, $slug) {
            $base = $areaNames[($i - 1) % count($areaNames)];

            return Area::query()->firstOrCreate(
                ['project_id' => $pid, 'name' => $base.' — '.$slug.' — '.$i]
            );
        });

        $shareholders = collect(range(1, $counts['shareholders']))->map(function (int $i) use ($pid, $slug) {
            $share = fake()->numberBetween(3, 22);

            return Shareholder::query()->firstOrCreate(
                ['project_id' => $pid, 'name' => "مساهم {$slug}-{$i}"],
                [
                    'share_percentage' => $share,
                    'total_investment' => fake()->numberBetween(400_000, 12_000_000),
                    'profit_amount' => fake()->numberBetween(40_000, 1_200_000),
                ]
            );
        });

        $properties = collect(range(1, $counts['properties']))->map(function (int $i) use ($areas, $shareholders, $admin, $pid, $project) {
            $floors = fake()->numberBetween(6, 22);
            $apartmentsPerFloor = fake()->numberBetween(2, 7);
            $groundFloorShops = fake()->numberBetween(0, 8);
            $hasMezzanine = fake()->boolean(60);
            $mezzanineApartments = $hasMezzanine ? fake()->numberBetween(1, 6) : 0;
            $modelsCount = fake()->numberBetween(2, 5);

            $models = collect(range(1, $modelsCount))->map(function (int $modelIndex) {
                return [
                    'model_name' => chr(64 + $modelIndex),
                    'area' => fake()->numberBetween(75, 240),
                    'rooms_count' => fake()->numberBetween(1, 5),
                    'bathrooms_count' => fake()->numberBetween(1, 3),
                    'view_type' => fake()->randomElement(['normal', 'facade', 'corner']),
                ];
            })->values()->all();

            $selectedShareholders = $shareholders->random(fake()->numberBetween(2, 5))->values();
            $basePercentages = $selectedShareholders->map(fn () => fake()->numberBetween(8, 55));
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
                ['project_id' => $pid, 'name' => "عقار {$i} — {$project->name}"],
                [
                    'area_id' => $area->id,
                    'property_type' => fake()->randomElement(['سكني', 'تجاري', 'إداري', 'مختلط']),
                    'floors_count' => $floors,
                    'apartments_per_floor' => $apartmentsPerFloor,
                    'ground_floor_shops_count' => $groundFloorShops,
                    'has_mezzanine' => $hasMezzanine,
                    'mezzanine_apartments_count' => $mezzanineApartments,
                    'total_apartments' => ($floors * $apartmentsPerFloor) + $mezzanineApartments,
                    'shareholder_allocations' => $allocations,
                    'apartment_models' => $models,
                    'location' => $area->name,
                    'price' => 0,
                    'status' => fake()->randomElement(['available', 'available', 'available', 'sold']),
                    'owner_id' => $admin->id,
                ]
            );
        });

        $clients = collect(range(1, $counts['clients']))->map(function (int $i) use ($pid) {
            $phone = '010'.str_pad((string) (($pid * 1_000_000) + $i), 8, '0', STR_PAD_LEFT);

            return Client::query()->updateOrCreate(
                ['project_id' => $pid, 'phone' => $phone],
                [
                    'name' => fake()->name(),
                    'email' => "c{$pid}_{$i}@demo.example.com",
                    'national_id' => str_pad((string) (200_000_000_000_00 + ($pid * 1_000_000) + $i), 14, '0', STR_PAD_LEFT),
                ]
            );
        });

        $unitSlots = collect();
        foreach ($properties as $property) {
            $minFloor = (int) ($property->ground_floor_shops_count ?? 0) > 0 ? 0 : 1;
            $maxFloor = max(1, (int) $property->floors_count) + ((bool) ($property->has_mezzanine ?? false) ? 1 : 0);
            $modelNames = collect($property->apartment_models ?? [])->pluck('model_name')->filter()->values();
            if ($modelNames->isEmpty()) {
                $modelNames = collect(['A']);
            }
            $mezzSet = collect($property->mezzanine_floors ?? [])
                ->pluck('floor_number')
                ->map(static fn ($v) => (int) $v)
                ->filter(static fn (int $v) => $v >= 1)
                ->unique();
            if ($mezzSet->isEmpty() && ($property->has_mezzanine ?? false)) {
                $mezzSet = collect([$maxFloor]);
            }
            for ($floor = $minFloor; $floor <= $maxFloor; $floor++) {
                foreach ($modelNames as $modelName) {
                    $unitSlots->push([
                        'property' => $property,
                        'floor_number' => $floor,
                        'apartment_model' => (string) $modelName,
                        'is_mezzanine' => false,
                    ]);
                    if ($mezzSet->contains($floor)) {
                        $unitSlots->push([
                            'property' => $property,
                            'floor_number' => $floor,
                            'apartment_model' => (string) $modelName,
                            'is_mezzanine' => true,
                        ]);
                    }
                }
            }
        }

        $unitSlots = $unitSlots->shuffle()->take($counts['sales'])->values();

        $sales = $unitSlots->map(function (array $slot, int $i) use ($clients, $pid) {
            $property = $slot['property'];
            $salePrice = fake()->numberBetween(650_000, 6_500_000);
            $paymentType = fake()->randomElement(['installment', 'installment', 'installment', 'cash']);
            $downPayment = $paymentType === 'cash'
                ? $salePrice
                : fake()->numberBetween((int) ($salePrice * 0.2), (int) ($salePrice * 0.72));
            $months = $paymentType === 'cash' ? null : fake()->randomElement([12, 18, 24, 30, 36, 42, 48, 60]);
            $scheduleType = $paymentType === 'cash' ? null : fake()->randomElement(['monthly', 'quarterly', 'semiannual']);
            $intervalMonths = match ($scheduleType) {
                'quarterly' => 3,
                'semiannual' => 6,
                default => 1,
            };
            $remaining = max(0, $salePrice - $downPayment);
            $installmentsCount = ($months && $paymentType !== 'cash') ? max(1, (int) ceil($months / $intervalMonths)) : 0;
            $saleDate = Carbon::now()->subDays(fake()->numberBetween(1, 900))->toDateString();
            $secondaryRows = [];
            $secondaryTotal = 0.0;
            if ($paymentType !== 'cash' && fake()->boolean(25) && $remaining > 120_000) {
                $amt = round(min($remaining * 0.12, (float) fake()->numberBetween(30_000, 150_000)), 2);
                if ($amt >= 5000 && $amt < $remaining - 50_000) {
                    $secondaryRows[] = [
                        'label' => 'دفعة تشطيب',
                        'amount' => $amt,
                        'due_date' => Carbon::parse($saleDate)->addMonths(fake()->numberBetween(2, 8))->toDateString(),
                    ];
                    $secondaryTotal = $amt;
                }
            }
            $secondaryTotal = round($secondaryTotal, 2);
            $baseForSchedule = max(0, round($remaining - $secondaryTotal, 2));
            $installmentAmount = ($installmentsCount && $baseForSchedule > 0) ? round($baseForSchedule / $installmentsCount, 2) : 0;

            return Sale::query()->updateOrCreate(
                [
                    'project_id' => $pid,
                    'property_id' => $property->id,
                    'floor_number' => $slot['floor_number'],
                    'apartment_model' => $slot['apartment_model'],
                    'is_mezzanine' => $slot['is_mezzanine'],
                ],
                [
                    'client_id' => $clients->random()->id,
                    'sale_price' => $salePrice,
                    'payment_type' => $paymentType,
                    'down_payment' => $downPayment,
                    'installment_months' => $months,
                    'installment_start_date' => $paymentType === 'cash' ? null : Carbon::parse($saleDate)->addMonth()->toDateString(),
                    'installment_plan' => $paymentType === 'cash'
                        ? null
                        : [
                            'schedule_type' => $scheduleType,
                            'interval_months' => $intervalMonths,
                            'installments_count' => $installmentsCount,
                            'remaining_amount' => $remaining,
                            'secondary_payments' => $secondaryRows,
                            'secondary_payments_total' => $secondaryTotal,
                            'installment_base_for_schedule' => $baseForSchedule,
                            'installment_amount' => $installmentAmount,
                            'monthly_installment' => $installmentAmount,
                        ],
                    'sale_date' => $saleDate,
                    'notes' => "بيع تجريبي {$pid}/".($i + 1),
                    'broker_name' => fake()->name(),
                ]
            );
        });

        /** @var Collection<int, Contract> $contracts */
        $contracts = $sales->map(function (Sale $sale) use ($pid) {
            $endDate = $sale->installment_months
                ? Carbon::parse($sale->sale_date)->addMonths((int) $sale->installment_months)->toDateString()
                : Carbon::parse($sale->sale_date)->addYear()->toDateString();

            return Contract::query()->updateOrCreate(
                ['sale_id' => $sale->id],
                [
                    'project_id' => $pid,
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

        $contracts->each(function (Contract $contract) use ($pid): void {
            $paymentsCount = fake()->numberBetween(1, 5);
            $remaining = (float) $contract->remaining_amount;

            for ($i = 1; $i <= $paymentsCount && $remaining > 0; $i++) {
                $amount = $i === $paymentsCount
                    ? $remaining
                    : min($remaining, (float) fake()->numberBetween(15_000, 220_000));
                $remaining -= $amount;

                Revenue::query()->firstOrCreate(
                    [
                        'project_id' => $pid,
                        'contract_id' => $contract->id,
                        'paid_at' => Carbon::parse($contract->start_date)->addMonths($i)->toDateString(),
                        'amount' => $amount,
                    ],
                    [
                        'sale_id' => $contract->sale_id,
                        'client_id' => $contract->client_id,
                        'category' => 'قسط بيع',
                        'source' => "قسط {$i}",
                        'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'wallet', 'check']),
                        'notes' => 'تحصيل تلقائي — سيدر',
                    ]
                );
            }
        });

        $contracts->each(function (Contract $contract): void {
            $paid = (float) Revenue::query()->where('contract_id', $contract->id)->sum('amount');
            $contract->update([
                'paid_amount' => $paid,
                'remaining_amount' => max(0, (float) $contract->total_price - $paid),
            ]);
        });

        collect(range(1, $counts['expenses']))->each(function (int $i) use ($pid, $slug): void {
            Expense::query()->firstOrCreate(
                ['project_id' => $pid, 'description' => "مصروف {$slug} — {$i}"],
                [
                    'amount' => fake()->numberBetween(3_000, 180_000),
                    'category' => fake()->randomElement(['تشغيل', 'تسويق', 'رواتب', 'صيانة', 'مرافق', 'تصاريح', 'عمولات']),
                ]
            );
        });

        $carrierClientId = Client::query()->where('project_id', $pid)->orderBy('id')->value('id');
        $suppliers = [
            'مؤسسة مواد البناء',
            'شركة حديد وصلب',
            'مقاول تشطيبات',
            'مورد خرسانة جاهزة',
            'مكتب استشارات هندسية',
            'محل كهرباء وسباكة',
            'مورد ألوميتال',
            'شركة نقل ورفع',
        ];

        collect(range(1, 8))->each(function (int $i) use ($pid, $slug, $carrierClientId, $suppliers): void {
            $total = (float) fake()->numberBetween(80_000, 3_500_000);
            $paidRatio = fake()->randomFloat(2, 0, 0.9);
            $paid = round($total * $paidRatio, 2);
            $remaining = round(max(0, $total - $paid), 2);
            $creditor = $suppliers[($i - 1) % count($suppliers)];

            $purchaseDescription = "شراء للمشروع — {$slug} — بند {$i} (لم يُسدَّد بالكامل بعد)";
            $clientId = Schema::getConnection()->getDriverName() === 'mysql'
                ? null
                : $carrierClientId;

            Debt::query()->firstOrCreate(
                [
                    'project_id' => $pid,
                    'purchase_description' => $purchaseDescription,
                ],
                [
                    'client_id' => $clientId,
                    'creditor_name' => $creditor,
                    'total_amount' => $total,
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'status' => $remaining > 0.01 ? 'open' : 'closed',
                ]
            );
        });

        Setting::query()->firstOrCreate(
            ['project_id' => $pid],
            [
                'company_name' => $project->name,
                'currency' => 'EGP',
                'meta' => [
                    'seeded' => true,
                    'project_code' => $slug,
                    'counts' => $counts,
                ],
            ]
        );
    }
}
