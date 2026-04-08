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
use App\Models\TreasuryTransaction;
use App\Models\User;
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
        $admin = User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $areas = collect([
            'مدينة نصر',
            'التجمع الخامس',
            'الشيخ زايد',
            'العاصمة الإدارية',
        ])->map(fn (string $name) => Area::query()->firstOrCreate(['name' => $name]));

        $shareholders = collect([
            ['name' => 'أحمد علي', 'share_percentage' => 40, 'total_investment' => 2000000, 'profit_amount' => 120000],
            ['name' => 'محمد علي', 'share_percentage' => 30, 'total_investment' => 1500000, 'profit_amount' => 90000],
            ['name' => 'محمود علي', 'share_percentage' => 30, 'total_investment' => 1500000, 'profit_amount' => 80000],
        ])->map(fn (array $data) => Shareholder::query()->firstOrCreate(['name' => $data['name']], $data));

        $properties = collect([
            [
                'name' => 'برج النخبة 1',
                'area_id' => $areas[0]->id,
                'property_type' => 'سكني',
                'floors_count' => 12,
                'apartments_per_floor' => 4,
                'total_apartments' => 48,
                'shareholder_allocations' => $shareholders->map(fn ($s) => [
                    'shareholder_id' => $s->id,
                    'shareholder_name' => $s->name,
                    'percentage' => (float) $s->share_percentage,
                ])->values()->all(),
                'apartment_models' => [
                    ['model_name' => 'A', 'area' => 120],
                    ['model_name' => 'B', 'area' => 140],
                ],
                'location' => $areas[0]->name,
                'price' => 0,
                'status' => 'available',
                'owner_id' => $admin->id,
            ],
            [
                'name' => 'برج النخبة 2',
                'area_id' => $areas[1]->id,
                'property_type' => 'مختلط',
                'floors_count' => 10,
                'apartments_per_floor' => 3,
                'total_apartments' => 30,
                'shareholder_allocations' => $shareholders->map(fn ($s) => [
                    'shareholder_id' => $s->id,
                    'shareholder_name' => $s->name,
                    'percentage' => (float) $s->share_percentage,
                ])->values()->all(),
                'apartment_models' => [
                    ['model_name' => 'A', 'area' => 110],
                    ['model_name' => 'C', 'area' => 160],
                ],
                'location' => $areas[1]->name,
                'price' => 0,
                'status' => 'available',
                'owner_id' => $admin->id,
            ],
        ])->map(fn (array $data) => Property::query()->firstOrCreate(['name' => $data['name']], $data));

        $clients = collect([
            ['name' => 'محمد السيد', 'phone' => '01000000001', 'email' => 'client1@example.com', 'national_id' => '30101010101011'],
            ['name' => 'منة الله علي', 'phone' => '01000000002', 'email' => 'client2@example.com', 'national_id' => '30202020202022'],
            ['name' => 'عبدالله حسن', 'phone' => '01000000003', 'email' => 'client3@example.com', 'national_id' => '30303030303033'],
        ])->map(fn (array $data) => Client::query()->firstOrCreate(['phone' => $data['phone']], $data));

        $sales = collect([
            [
                'property_id' => $properties[0]->id,
                'client_id' => $clients[0]->id,
                'floor_number' => 3,
                'apartment_model' => 'A',
                'sale_price' => 1650000,
                'payment_type' => 'installment',
                'down_payment' => 800000,
                'installment_months' => 24,
                'installment_start_date' => Carbon::now()->subMonths(2)->toDateString(),
                'installment_plan' => ['remaining_amount' => 850000, 'monthly_installment' => 35416.67],
                'sale_date' => Carbon::now()->subMonths(3)->toDateString(),
                'notes' => 'بيع تجريبي 1',
            ],
            [
                'property_id' => $properties[1]->id,
                'client_id' => $clients[1]->id,
                'floor_number' => 5,
                'apartment_model' => 'C',
                'sale_price' => 2150000,
                'payment_type' => 'installment',
                'down_payment' => 1050000,
                'installment_months' => 30,
                'installment_start_date' => Carbon::now()->subMonths(1)->toDateString(),
                'installment_plan' => ['remaining_amount' => 1100000, 'monthly_installment' => 36666.67],
                'sale_date' => Carbon::now()->subMonths(2)->toDateString(),
                'notes' => 'بيع تجريبي 2',
            ],
        ])->map(fn (array $data) => Sale::query()->firstOrCreate([
            'property_id' => $data['property_id'],
            'client_id' => $data['client_id'],
            'sale_date' => $data['sale_date'],
        ], $data));

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
                    'paid_amount' => $sale->down_payment,
                    'remaining_amount' => max(0, (float) $sale->sale_price - (float) $sale->down_payment),
                ]
            );
        });

        $revenues = collect([
            [
                'contract_id' => $contracts[0]->id,
                'sale_id' => $sales[0]->id,
                'client_id' => $clients[0]->id,
                'amount' => 150000,
                'category' => 'قسط بيع',
                'source' => 'قسط أول',
                'paid_at' => Carbon::now()->subMonths(1)->toDateString(),
                'payment_method' => 'cash',
                'notes' => 'تحصيل قسط شهري',
            ],
            [
                'contract_id' => $contracts[1]->id,
                'sale_id' => $sales[1]->id,
                'client_id' => $clients[1]->id,
                'amount' => 120000,
                'category' => 'قسط بيع',
                'source' => 'قسط أول',
                'paid_at' => Carbon::now()->toDateString(),
                'payment_method' => 'bank_transfer',
                'notes' => 'تحصيل تحويل بنكي',
            ],
        ])->map(fn (array $data) => Revenue::query()->firstOrCreate([
            'contract_id' => $data['contract_id'],
            'amount' => $data['amount'],
            'paid_at' => $data['paid_at'],
        ], $data));

        $contracts->each(function (Contract $contract): void {
            $paid = (float) Revenue::query()->where('contract_id', $contract->id)->sum('amount');
            $contract->update([
                'paid_amount' => $paid,
                'remaining_amount' => max(0, (float) $contract->total_price - $paid),
            ]);
        });

        collect([
            ['amount' => 380000, 'category' => 'تشغيل موقع', 'description' => 'مصروفات تشغيل'],
            ['amount' => 300000, 'category' => 'تسويق', 'description' => 'حملة تسويقية'],
        ])->each(fn (array $data) => Expense::query()->firstOrCreate($data));

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
            'meta' => ['seeded' => true],
        ]);

        TreasuryTransaction::query()->firstOrCreate([
            'type' => 'revenue',
            'amount' => (float) $revenues->sum('amount'),
            'description' => 'قبض تلقائي من التحصيلات',
        ]);

        TreasuryTransaction::query()->firstOrCreate([
            'type' => 'expense',
            'amount' => (float) Expense::query()->sum('amount'),
            'description' => 'صرف تلقائي من المصروفات',
        ]);
    }
}
