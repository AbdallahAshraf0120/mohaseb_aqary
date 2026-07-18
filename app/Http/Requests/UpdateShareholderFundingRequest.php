<?php

namespace App\Http\Requests;

use App\Models\LandParcel;
use App\Models\LandParcelShareholder;
use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
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
            'total_investment' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['target_type', 'target_id', 'total_investment'])) {
                return;
            }

            $shareholder = $this->route('shareholder');
            if (! $shareholder instanceof Shareholder) {
                return;
            }

            $type = (string) $this->input('target_type');
            $targetId = (int) $this->input('target_id');
            $investment = round((float) $this->input('total_investment'), 2);

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

                $projectCapital = round((float) $project->capital, 2);
                if ($projectCapital <= 0) {
                    $validator->errors()->add('total_investment', 'يجب تعيين رأس مال المشروع أولاً.');

                    return;
                }

                $othersInvestment = round((float) ProjectShareholder::query()
                    ->where('project_id', $targetId)
                    ->where('shareholder_id', '!=', (int) $shareholder->id)
                    ->sum('total_investment'), 2);

                if (round($othersInvestment + $investment, 2) > $projectCapital) {
                    $remaining = max(0, round($projectCapital - $othersInvestment, 2));
                    $validator->errors()->add(
                        'total_investment',
                        "مجموع تمويلات المساهمين يتجاوز رأس مال المشروع. الحد الأقصى لهذا المساهم: {$remaining} ج.م."
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

            $capital = round((float) $parcel->purchase_price, 2);
            if ($capital <= 0) {
                $validator->errors()->add('total_investment', 'يجب تعيين سعر شراء للأرض أولاً.');

                return;
            }

            $othersInvestment = round((float) LandParcelShareholder::query()
                ->where('land_parcel_id', $targetId)
                ->where('shareholder_id', '!=', (int) $shareholder->id)
                ->sum('total_investment'), 2);

            if (round($othersInvestment + $investment, 2) > $capital) {
                $remaining = max(0, round($capital - $othersInvestment, 2));
                $validator->errors()->add(
                    'total_investment',
                    "مجموع تمويلات المساهمين يتجاوز سعر شراء الأرض. الحد الأقصى لهذا المساهم: {$remaining} ج.م."
                );
            }
        });
    }
}
