<?php

namespace App\Services;

use App\Models\Client;
use App\Support\Phone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * عميل واحد لكل شخص داخل المشروع:
 * - نفس الرقم القومي ⇒ نفس العميل.
 * - نفس الموبايل + نفس الاسم (بعد توحيد الكتابة) ⇒ نفس العميل.
 */
class ClientDirectory
{
    public static function normalizeName(?string $name): string
    {
        $n = trim((string) $name);
        $n = preg_replace('/[\x{064B}-\x{0652}\x{0640}]/u', '', $n) ?? $n; // تشكيل وتطويل
        $n = strtr($n, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي']);
        $n = preg_replace('/\s+/u', ' ', $n) ?? $n;

        return mb_strtolower($n);
    }

    public static function normalizeNationalId(?string $id): ?string
    {
        $d = preg_replace('/\D+/', '', strtr((string) $id, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));

        return ($d === null || strlen($d) < 6) ? null : $d;
    }

    /** يدور على عميل موجود في المشروع الحالي. */
    public function find(?string $name, ?string $phone, ?string $nationalId): ?Client
    {
        $nid = self::normalizeNationalId($nationalId);
        $phoneKey = Phone::key($phone);
        $nameKey = self::normalizeName($name);

        $candidates = Client::query()
            ->where(function ($w) use ($nationalId, $phone, $phoneKey): void {
                if ($nationalId) {
                    $w->orWhere('national_id', trim($nationalId));
                }
                if ($phone) {
                    $w->orWhere('phone', trim($phone));
                }
                if ($phoneKey) {
                    $w->orWhere('phone', 'like', '%'.substr($phoneKey, -9));
                }
            })
            ->orderBy('id')
            ->get();

        if ($nid !== null) {
            $byNid = $candidates->first(fn (Client $c) => self::normalizeNationalId($c->national_id) === $nid);
            if ($byNid) {
                return $byNid;
            }
        }
        if ($phoneKey !== null) {
            return $candidates->first(function (Client $c) use ($phoneKey, $nameKey, $nid): bool {
                if (Phone::key($c->phone) !== $phoneKey || self::normalizeName($c->name) !== $nameKey) {
                    return false;
                }
                // لو الاتنين ليهم رقم قومي ومختلفين ⇒ شخصين مختلفين
                $other = self::normalizeNationalId($c->national_id);

                return $nid === null || $other === null || $other === $nid;
            });
        }

        return null;
    }

    /** يرجّع العميل الموجود (ويكمّل بياناته الناقصة) أو ينشئ واحد جديد. */
    public function findOrCreate(array $data): Client
    {
        $existing = $this->find($data['name'] ?? null, $data['phone'] ?? null, $data['national_id'] ?? null);
        if ($existing) {
            $fill = [];
            foreach (['email', 'national_id'] as $f) {
                if (blank($existing->{$f}) && filled($data[$f] ?? null)) {
                    $fill[$f] = $data[$f];
                }
            }
            if ($fill !== []) {
                $existing->update($fill);
            }

            return $existing;
        }

        return Client::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'national_id' => $data['national_id'] ?? null,
        ]);
    }

    /**
     * مجموعات العملاء المكررين في المشروع الحالي.
     *
     * @return Collection<int, Collection<int, Client>>
     */
    public function duplicateGroups(): Collection
    {
        $clients = Client::query()->withCount('sales')->orderBy('id')->get();
        $parent = [];
        $find = function (int $x) use (&$parent, &$find): int {
            return $parent[$x] === $x ? $x : ($parent[$x] = $find($parent[$x]));
        };
        foreach ($clients as $c) {
            $parent[$c->id] = $c->id;
        }
        $index = ['nid' => [], 'pn' => []];
        foreach ($clients as $c) {
            $keys = [];
            if ($nid = self::normalizeNationalId($c->national_id)) {
                $keys[] = ['nid', $nid];
            }
            if ($pk = Phone::key($c->phone)) {
                $keys[] = ['pn', $pk.'|'.self::normalizeName($c->name)];
            }
            foreach ($keys as [$type, $key]) {
                if (isset($index[$type][$key])) {
                    $parent[$find($c->id)] = $find($index[$type][$key]);
                } else {
                    $index[$type][$key] = $c->id;
                }
            }
        }

        return $clients->groupBy(fn (Client $c) => $find($c->id))
            ->filter(fn (Collection $g) => $g->count() > 1)
            ->map(fn (Collection $g): Collection => $g->values())
            // لو فيه رقمين قوميين مختلفين في نفس المجموعة، مش بنقترح دمجهم
            ->filter(function (Collection $g): bool {
                $nids = $g->map(fn (Client $c) => self::normalizeNationalId($c->national_id))->filter()->unique();

                return $nids->count() <= 1;
            })
            ->values();
    }

    /** دمج عملاء في عميل واحد (الأقدم): نقل البيعات والعقود والتحصيلات والذمم ثم مسح المكرر. */
    public function merge(Client $keep, Collection $others): int
    {
        return DB::transaction(function () use ($keep, $others): int {
            $ids = $others->pluck('id')->reject(fn ($id) => (int) $id === (int) $keep->id)->values()->all();
            if ($ids === []) {
                return 0;
            }
            foreach (['sales', 'contracts', 'revenues', 'debts'] as $table) {
                if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'client_id')) {
                    DB::table($table)->whereIn('client_id', $ids)->update(['client_id' => $keep->id]);
                }
            }
            $fill = [];
            foreach ($others as $o) {
                foreach (['email', 'national_id'] as $f) {
                    if (blank($keep->{$f}) && blank($fill[$f] ?? null) && filled($o->{$f})) {
                        $fill[$f] = $o->{$f};
                    }
                }
            }
            if ($fill !== []) {
                $keep->update($fill);
            }
            Client::query()->whereIn('id', $ids)->delete();

            return count($ids);
        });
    }
}
