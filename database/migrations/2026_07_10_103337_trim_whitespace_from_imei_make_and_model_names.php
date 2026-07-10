<?php

use App\Support\ImeiReferenceText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imei_make')) {
            $this->dedupeAndTrimMakes();
        }

        if (Schema::hasTable('imei_models')) {
            DB::table('imei_models')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $make = ImeiReferenceText::normalize((string) $row->make);
                        $model = ImeiReferenceText::normalize((string) $row->model);

                        if ($make === (string) $row->make && $model === (string) $row->model) {
                            continue;
                        }

                        DB::table('imei_models')
                            ->where('id', $row->id)
                            ->update([
                                'make' => $make,
                                'model' => $model,
                            ]);
                    }
                });
        }

        if (Schema::hasTable('imei')) {
            DB::table('imei')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $make = ImeiReferenceText::normalize((string) $row->make);
                        $model = ImeiReferenceText::normalize((string) $row->model);

                        if ($make === (string) $row->make && $model === (string) $row->model) {
                            continue;
                        }

                        DB::table('imei')
                            ->where('id', $row->id)
                            ->update([
                                'make' => $make,
                                'model' => $model,
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Irreversible data cleanup.
    }

    private function dedupeAndTrimMakes(): void
    {
        $rows = DB::table('imei_make')->orderBy('id')->get(['id', 'make']);
        $keptByNormalized = [];

        foreach ($rows as $row) {
            $normalized = ImeiReferenceText::normalize((string) $row->make);

            if ($normalized === '') {
                DB::table('imei_make')->where('id', $row->id)->delete();

                continue;
            }

            if (! array_key_exists($normalized, $keptByNormalized)) {
                $keptByNormalized[$normalized] = (int) $row->id;

                if ($normalized !== (string) $row->make) {
                    DB::table('imei_make')
                        ->where('id', $row->id)
                        ->update(['make' => $normalized]);
                }

                continue;
            }

            DB::table('imei_make')->where('id', $row->id)->delete();
        }
    }
};
