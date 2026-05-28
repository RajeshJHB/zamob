<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('imei_models')) {
            return;
        }

        if (Schema::hasColumn('imei_models', 'item_code')) {
            return;
        }

        Schema::table('imei_models', function (Blueprint $table) {
            $table->text('item_code')->default('')->after('make');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('imei_models')) {
            return;
        }

        if (! Schema::hasColumn('imei_models', 'item_code')) {
            return;
        }

        Schema::table('imei_models', function (Blueprint $table) {
            $table->dropColumn('item_code');
        });
    }
};
