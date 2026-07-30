<?php

namespace App\Support;

class LandInstallmentPlanBuilder
{
    /**
     * @return array{
     *     payment_type: string,
     *     down_payment: float,
     *     installment_months: int|null,
     *     installment_schedule: string|null,
     *     installment_start_date: string|null,
     *     installment_plan: array<string, mixed>|null
     * }
     */
    public static function build(
        string $paymentType,
        float $totalPrice,
        mixed $downPaymentInput,
        mixed $monthsInput,
        mixed $scheduleInput,
        mixed $startDateInput,
    ): array {
        $isCash = $paymentType === 'cash';
        $downPaymentValue = $downPaymentInput === null || $downPaymentInput === ''
            ? ($isCash ? $totalPrice : 0.0)
            : (float) $downPaymentInput;
        $downPaymentValue = round(max(0, min($downPaymentValue, $totalPrice)), 5);

        if ($isCash) {
            return [
                'payment_type' => 'cash',
                'down_payment' => $downPaymentValue > 0 ? $downPaymentValue : $totalPrice,
                'installment_months' => null,
                'installment_schedule' => null,
                'installment_start_date' => null,
                'installment_plan' => null,
            ];
        }

        $schedule = in_array($scheduleInput, ['monthly', 'quarterly', 'semiannual'], true)
            ? (string) $scheduleInput
            : 'monthly';
        $intervalMonths = match ($schedule) {
            'quarterly' => 3,
            'semiannual' => 6,
            default => 1,
        };
        $months = max(1, (int) ($monthsInput ?: 1));
        $remaining = max(0, round($totalPrice - $downPaymentValue, 5));
        $installmentsCount = max(1, (int) ceil($months / $intervalMonths));
        $installmentAmount = $installmentsCount > 0 && $remaining > 0
            ? round($remaining / $installmentsCount, 5)
            : 0.0;
        $startDate = filled($startDateInput) ? (string) $startDateInput : null;

        return [
            'payment_type' => 'installment',
            'down_payment' => $downPaymentValue,
            'installment_months' => $months,
            'installment_schedule' => $schedule,
            'installment_start_date' => $startDate,
            'installment_plan' => [
                'schedule_type' => $schedule,
                'interval_months' => $intervalMonths,
                'installments_count' => $installmentsCount,
                'remaining_amount' => $remaining,
                'secondary_payments' => [],
                'secondary_payments_total' => 0,
                'installment_base_for_schedule' => $remaining,
                'installment_amount' => $installmentAmount,
                'monthly_installment' => $installmentAmount,
            ],
        ];
    }
}
