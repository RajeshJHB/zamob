<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imei') && Schema::hasColumn('imei', 'stock_take_date')) {
            $this->renameImeiColumn('stock_take_date', 'cash_stock_type');
        }

        if (Schema::hasTable('imeifilter')) {
            $this->migrateImeiFilterParams('stock_take_date', 'cash_stock_type');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('imeifilter')) {
            $this->migrateImeiFilterParams('cash_stock_type', 'stock_take_date');
        }

        if (Schema::hasTable('imei') && Schema::hasColumn('imei', 'cash_stock_type')) {
            $this->renameImeiColumn('cash_stock_type', 'stock_take_date');
        }
    }

    private function renameImeiColumn(string $from, string $to): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            Schema::table('imei', function (Blueprint $table) use ($from, $to) {
                $table->renameColumn($from, $to);
            });

            return;
        }

        $column = collect(DB::select('SHOW COLUMNS FROM imei WHERE Field = ?', [$from]))->first();

        if ($column === null) {
            return;
        }

        $definition = $this->mysqlColumnDefinition($column);
        $previousMode = (string) (DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode ?? '');

        // Legacy `imei.date_in` uses a zero-date default; relax sql_mode so ALTER TABLE can run.
        DB::statement("SET SESSION sql_mode = ''");
        DB::statement("ALTER TABLE imei CHANGE `{$from}` `{$to}` {$definition}");
        DB::statement('SET SESSION sql_mode = ?', [$previousMode]);
    }

    private function mysqlColumnDefinition(object $column): string
    {
        $type = strtoupper((string) $column->Type);
        $null = ($column->Null ?? '') === 'YES' ? 'NULL' : 'NOT NULL';

        if ($column->Default === null) {
            return $null === 'NULL'
                ? "{$type} {$null} DEFAULT NULL"
                : "{$type} {$null}";
        }

        if ($column->Default === '') {
            return "{$type} {$null} DEFAULT ''";
        }

        $default = str_replace("'", "''", (string) $column->Default);

        return "{$type} {$null} DEFAULT '{$default}'";
    }

    private function migrateImeiFilterParams(string $from, string $to): void
    {
        foreach (DB::table('imeifilter')->orderBy('id')->get() as $row) {
            $params = json_decode((string) $row->params, true);
            if (! is_array($params)) {
                continue;
            }

            $updated = false;

            if (isset($params['columns']) && is_array($params['columns'])) {
                $params['columns'] = array_map(
                    fn (mixed $col): mixed => $col === $from ? $to : $col,
                    $params['columns'],
                );
                $updated = true;
            }

            foreach (['sort1_column', 'sort2_column', 'date_column'] as $key) {
                if (($params[$key] ?? null) === $from) {
                    $params[$key] = $to;
                    $updated = true;
                }
            }

            if ($updated) {
                DB::table('imeifilter')->where('id', $row->id)->update([
                    'params' => json_encode($params),
                ]);
            }
        }
    }
};
