<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يرفع دقة الأعمدة العشرية إلى 5 خانات بعد العلامة في كل الجداول المالية.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }

        $database = Schema::getConnection()->getDatabaseName();

        $columns = DB::select(
            'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND DATA_TYPE = \'decimal\'
               AND NUMERIC_SCALE = 2
               AND TABLE_NAME NOT LIKE \'%migrations%\'',
            [$database]
        );

        foreach ($columns as $col) {
            $table = $col->TABLE_NAME;
            $column = $col->COLUMN_NAME;
            $nullable = strtoupper((string) $col->IS_NULLABLE) === 'YES' ? 'NULL' : 'NOT NULL';

            // نسب مئوية: دقة أصغر قبل العلامة تكفي
            $isPercent = str_contains($column, 'percentage')
                || str_contains($column, 'percent')
                || $column === 'share_percentage';

            $type = $isPercent ? 'DECIMAL(12,5)' : 'DECIMAL(18,5)';

            $defaultSql = '';
            if ($col->COLUMN_DEFAULT !== null) {
                $defaultSql = ' DEFAULT '.(is_numeric($col->COLUMN_DEFAULT)
                    ? $col->COLUMN_DEFAULT
                    : DB::getPdo()->quote((string) $col->COLUMN_DEFAULT));
            } elseif ($nullable === 'NOT NULL') {
                $defaultSql = ' DEFAULT 0';
            }

            $comment = trim((string) ($col->COLUMN_COMMENT ?? ''));
            $commentSql = $comment !== '' ? ' COMMENT '.DB::getPdo()->quote($comment) : '';

            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `%s` %s %s%s%s',
                $table,
                $column,
                $type,
                $nullable,
                $defaultSql,
                $commentSql
            ));
        }
    }

    public function down(): void
    {
        // لا نرجع تلقائيًا لـ 2 خانات لتفادي فقدان الدقة.
    }
};
