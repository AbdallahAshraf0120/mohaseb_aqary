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
            'share_percentage' => ['required', 'numeric', 'min:0.00001', 'max:100'],
            'total_investment' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['link_type', 'project_id', 'land_parcel_id', 'share_percentage'])) {
                return;
            }

            $linkType = (string) $this->input('link_type');
            $percentage = round((float) $this->input('share_percentage'), 5);

            if ($linkType === 'project') {
                $this->validateProjectPercentage($validator, $percentage);

                return;
            }

            if ($linkType === 'land') {
                $this->validateLandPercentage($validator, $percentage);
            }
        });
    }

    private function validateProjectPercentage(Validator $validator, float $percentage): void
    {
        $project = Project::query()->find((int) $this->input('project_id'));
        if ($project === null) {
            return;
        }

        $pctColumn = Schema::hasColumn('project_shareholder', 'planned_percentage')
            ? 'planned_percentage'
            : 'share_percentage';

        $existingPct = round((float) ProjectShareholder::query()
            ->where('project_id', (int) $project->id)
            ->sum($pctColumn), 5);

        if (round($existingPct + $percentage, 5) > 100.00001) {
            $remaining = max(0, round(100 - $existingPct, 5));
            $validator->errors()->add(
                'share_percentage',
                "مجموع نسب المساهمين يتجاوز 100٪. المتبقي المتاح: {$remaining}٪."
            );
        }
    }

    private function validateLandPercentage(Validator $validator, float $percentage): void
    {
        if (! Schema::hasTable('land_parcels') || ! Schema::hasTable('land_parcel_shareholder')) {
            $validator->errors()->add('land_parcel_id', 'ميزة الأراضي غير مفعّلة. شغّل migrate على السيرفر.');

            return;
        }

        $parcel = LandParcel::query()->find((int) $this->input('land_parcel_id'));
        if ($parcel === null) {
            return;
        }

        $capital = round((float) ($parcel->planned_capital ?? $parcel->purchase_price ?? 0), 5);
        if ($capital <= 0) {
            $validator->errors()->add(
                'land_parcel_id',
                'يجب تعيين سعر شراء للأرض أولاً (يُستخدم كأساس لحساب نسبة المساهمة).'
            );

            return;
        }

        $pctColumn = Schema::hasColumn('land_parcel_shareholder', 'planned_percentage')
            ? 'planned_percentage'
            : 'share_percentage';

        $existingPct = round((float) LandParcelShareholder::query()
            ->where('land_parcel_id', (int) $parcel->id)
            ->sum($pctColumn), 5);

        if (round($existingPct + $percentage, 5) > 100.00001) {
            $remaining = max(0, round(100 - $existingPct, 5));
            $validator->errors()->add(
                'share_percentage',
                "مجموع نسب المساهمين يتجاوز 100٪. المتبقي المتاح: {$remaining}٪."
            );
        }
    }
}
