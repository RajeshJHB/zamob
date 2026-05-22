<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('app_settings')->where('key', 'browse_list_limit')->exists()) {
            return;
        }

        $now = now();

        DB::table('app_settings')->insert([
            'key' => 'browse_list_limit',
            'value' => '200',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('app_settings')->where('key', 'browse_list_limit')->delete();
    }
};
