<?php

namespace App\Http\Requests;

use App\Models\LandParcel;
use App\Models\LandParcelShareholder;
use App\Models\Shareholder;
use Illuminate\Foundation\Http\FormRequest;
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
            'total_investment' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['land_parcel_id', 'total_investment'])) {
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

            $capital = round((float) $parcel->purchase_price, 2);
            if ($capital <= 0) {
                $validator->errors()->add(
                    'land_parcel_id',
                    'يجب تعيين سعر شراء للأرض أولاً (يُستخدم كأساس لحساب نسبة المساهمة).'
                );

                return;
            }

            $investment = round((float) $this->input('total_investment'), 2);
            $existingInvestment = round((float) LandParcelShareholder::query()
                ->where('land_parcel_id', $parcelId)
                ->sum('total_investment'), 2);

            if (round($existingInvestment + $investment, 2) > $capital) {
                $remaining = max(0, round($capital - $existingInvestment, 2));
                $validator->errors()->add(
                    'total_investment',
                    "مجموع تمويلات المساهمين يتجاوز سعر شراء الأرض ({$capital} ج.م). المتبقي: {$remaining} ج.م."
                );
            }
        });
    }
}
