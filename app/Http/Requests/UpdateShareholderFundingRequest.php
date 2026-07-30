<?php

namespace App\Http\Requests;

use App\Models\LandParcel;
use App\Models\LandParcelShareholder;
use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateShareholderFundingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(['project', 'land'])],
            'target_id' => ['required', 'integer', 'min:1'],
            'share_percentage' => ['required', 'numeric', 'min:0.00001', 'max:100'],
            'total_investment' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['target_type', 'target_id', 'share_percentage'])) {
                return;
            }

            $shareholder = $this->route('shareholder');
            if (! $shareholder instanceof Shareholder) {
                return;
            }

            $type = (string) $this->input('target_type');
            $targetId = (int) $this->input('target_id');
            $percentage = round((float) $this->input('share_percentage'), 5);

            if ($type === 'project') {
                $project = Project::query()->find($targetId);
                if ($project === null) {
                    $validator->errors()->add('target_id', 'المشروع غير موجود.');

                    return;
                }

                $membership = ProjectShareholder::query()
                    ->where('shareholder_id', (int) $shareholder->id)
                    ->where('project_id', $targetId)
                    ->first();
                if ($membership === null) {
                    $validator->errors()->add('target_id', 'هذا المساهم غير مرتبط بهذا المشروع.');

                    return;
                }

                $pctColumn = Schema::hasColumn('project_shareholder', 'planned_percentage')
                    ? 'planned_percentage'
                    : 'share_percentage';
                $othersPct = round((float) ProjectShareholder::query()
                    ->where('project_id', $targetId)
                    ->where('shareholder_id', '!=', (int) $shareholder->id)
                    ->sum($pctColumn), 5);

                if (round($othersPct + $percentage, 5) > 100.00001) {
                    $remaining = max(0, round(100 - $othersPct, 5));
                    $validator->errors()->add(
                        'share_percentage',
                        "مجموع نسب المساهمين يتجاوز 100٪. الحد الأقصى لهذا المساهم: {$remaining}٪."
                    );
                }

                return;
            }

            $parcel = LandParcel::query()->find($targetId);
            if ($parcel === null) {
                $validator->errors()->add('target_id', 'الأرض غير موجودة.');

                return;
            }

            $membership = LandParcelShareholder::query()
                ->where('shareholder_id', (int) $shareholder->id)
                ->where('land_parcel_id', $targetId)
                ->first();
            if ($membership === null) {
                $validator->errors()->add('target_id', 'هذا المساهم غير مرتبط بهذه الأرض.');

                return;
            }

            $capital = round((float) ($parcel->planned_capital ?? $parcel->purchase_price ?? 0), 5);
            if ($capital <= 0) {
                $validator->errors()->add('share_percentage', 'يجب تعيين سعر شراء للأرض أولاً.');

                return;
            }

            $pctColumn = Schema::hasColumn('land_parcel_shareholder', 'planned_percentage')
                ? 'planned_percentage'
                : 'share_percentage';
            $othersPct = round((float) LandParcelShareholder::query()
                ->where('land_parcel_id', $targetId)
                ->where('shareholder_id', '!=', (int) $shareholder->id)
                ->sum($pctColumn), 5);

            if (round($othersPct + $percentage, 5) > 100.00001) {
                $remaining = max(0, round(100 - $othersPct, 5));
                $validator->errors()->add(
                    'share_percentage',
                    "مجموع نسب المساهمين يتجاوز 100٪. الحد الأقصى لهذا المساهم: {$remaining}٪."
                );
            }
        });
    }
}
