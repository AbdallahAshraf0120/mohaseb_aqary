<?php

namespace App\Http\Requests;

use App\Models\LandParcel;
use App\Models\LandParcelShareholder;
use App\Models\Project;
use App\Models\ProjectShareholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreShareholderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $landsReady = Schema::hasTable('land_parcels') && Schema::hasTable('land_parcel_shareholder');

        return [
            'name' => ['required', 'string', 'max:255'],
            'link_type' => ['required', 'in:project,land'],
            'project_id' => [
                Rule::requiredIf(fn () => $this->input('link_type') === 'project'),
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(fn ($q) => $q->where('is_draft', false)),
            ],
            'land_parcel_id' => array_filter([
                Rule::requiredIf(fn () => $this->input('link_type') === 'land'),
                'nullable',
                'integer',
                $landsReady ? Rule::exists('land_parcels', 'id') : 'integer',
            ]),
            'total_investment' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['link_type', 'project_id', 'land_parcel_id', 'total_investment'])) {
                return;
            }

            $linkType = (string) $this->input('link_type');
            $investment = round((float) $this->input('total_investment'), 2);

            if ($linkType === 'project') {
                $this->validateProjectFunding($validator, $investment);

                return;
            }

            if ($linkType === 'land') {
                $this->validateLandFunding($validator, $investment);
            }
        });
    }

    private function validateProjectFunding(Validator $validator, float $investment): void
    {
        $project = Project::query()->find((int) $this->input('project_id'));
        if ($project === null) {
            return;
        }

        $projectCapital = round((float) ($project->planned_capital ?? $project->capital ?? 0), 2);
        if ($projectCapital <= 0) {
            $validator->errors()->add(
                'project_id',
                'يجب تعيين رأس مال المشروع أولاً من صفحة المشاريع قبل إضافة مساهمين.'
            );

            return;
        }

        $existingInvestment = round((float) ProjectShareholder::query()
            ->where('project_id', (int) $project->id)
            ->sum(Schema::hasColumn('project_shareholder', 'planned_investment') ? 'planned_investment' : 'total_investment'), 2);

        if (round($existingInvestment + $investment, 2) > $projectCapital) {
            $remaining = max(0, round($projectCapital - $existingInvestment, 2));
            $validator->errors()->add(
                'total_investment',
                "مجموع تمويلات المساهمين يتجاوز رأس مال المشروع ({$projectCapital} ج.م). المتبقي المتاح: {$remaining} ج.م."
            );
        }
    }

    private function validateLandFunding(Validator $validator, float $investment): void
    {
        if (! Schema::hasTable('land_parcels') || ! Schema::hasTable('land_parcel_shareholder')) {
            $validator->errors()->add('land_parcel_id', 'ميزة الأراضي غير مفعّلة. شغّل migrate على السيرفر.');

            return;
        }

        $parcel = LandParcel::query()->find((int) $this->input('land_parcel_id'));
        if ($parcel === null) {
            return;
        }

        $capital = round((float) ($parcel->planned_capital ?? $parcel->purchase_price ?? 0), 2);
        if ($capital <= 0) {
            $validator->errors()->add(
                'land_parcel_id',
                'يجب تعيين سعر شراء للأرض أولاً (يُستخدم كأساس لحساب نسبة المساهمة).'
            );

            return;
        }

        $sumColumn = Schema::hasColumn('land_parcel_shareholder', 'planned_investment')
            ? 'planned_investment'
            : 'total_investment';

        $existingInvestment = round((float) LandParcelShareholder::query()
            ->where('land_parcel_id', (int) $parcel->id)
            ->sum($sumColumn), 2);

        if (round($existingInvestment + $investment, 2) > $capital) {
            $remaining = max(0, round($capital - $existingInvestment, 2));
            $validator->errors()->add(
                'total_investment',
                "مجموع تمويلات المساهمين يتجاوز سعر شراء الأرض ({$capital} ج.م). المتبقي: {$remaining} ج.م."
            );
        }
    }
}
