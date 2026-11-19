<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ترقيم مسلسل لكل مشروع (إيصالات …) بقفل على الصف عشان مفيش رقمين يتكرروا.
 */
class DocumentNumber
{
    public static function next(?int $projectId, string $type = 'receipt'): ?int
    {
        if (! Schema::hasTable('document_sequences')) {
            return null;
        }

        return DB::transaction(function () use ($projectId, $type): int {
            $row = DB::table('document_sequences')
                ->where('project_id', $projectId)
                ->where('type', $type)
                ->lockForUpdate()
                ->first();
            if ($row === null) {
                DB::table('document_sequences')->insert([
                    'project_id' => $projectId, 'type' => $type, 'last_number' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                return 1;
            }
            $next = (int) $row->last_number + 1;
            DB::table('document_sequences')->where('id', $row->id)->update(['last_number' => $next, 'updated_at' => now()]);

            return $next;
        });
    }

    public static function format(?int $number, string $prefix = 'RC'): string
    {
        return $number ? $prefix.'-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT) : '—';
    }
}
