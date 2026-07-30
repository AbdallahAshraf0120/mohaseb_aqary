<?php

namespace App\Http\Requests;

use App\Models\LandParcel;
use App\Models\LandParcelShareholder;
use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AttachShareholderLandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'land_parcel_id' => ['required', 'integer', Rule::exists('land_parcels', 'id')],
            'share_percentage' => ['required', 'numeric', 'min:0.00001', 'max:100'],
            'total_investment' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['land_parcel_id', 'share_percentage'])) {
                return;
            }

            $shareholder = $this->route('shareholder');
            if (! $shareholder instanceof Shareholder) {
                return;
            }

            $parcelId = (int) $this->input('land_parcel_id');
            $already = LandParcelShareholder::query()
                ->where('shareholder_id', (int) $shareholder->id)
                ->where('land_parcel_id', $parcelId)
                ->exists();
            if ($already) {
                $validator->errors()->add('land_parcel_id', 'هذا المساهم مرتبط بهذه الأرض بالفعل.');

                return;
            }

            $parcel = LandParcel::query()->find($parcelId);
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
            $percentage = round((float) $this->input('share_percentage'), 5);
            $existingPct = round((float) LandParcelShareholder::query()
                ->where('land_parcel_id', $parcelId)
                ->sum($pctColumn), 5);

            if (round($existingPct + $percentage, 5) > 100.00001) {
                $remaining = max(0, round(100 - $existingPct, 5));
                $validator->errors()->add(
                    'share_percentage',
                    "مجموع نسب المساهمين يتجاوز 100٪. المتبقي: {$remaining}٪."
                );
            }
        });
    }
}
