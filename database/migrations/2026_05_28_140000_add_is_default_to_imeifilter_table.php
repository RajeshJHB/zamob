<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('imeifilter')) {
            return;
        }

        if (! Schema::hasColumn('imeifilter', 'is_default')) {
            Schema::table('imeifilter', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('params');
            });
        }

        Schema::table('imeifilter', function (Blueprint $table) {
            $table->index(['user_id', 'is_default'], 'imeifilter_user_default_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('imeifilter')) {
            return;
        }

        Schema::table('imeifilter', function (Blueprint $table) {
            $table->dropIndex('imeifilter_user_default_index');
        });

        if (Schema::hasColumn('imeifilter', 'is_default')) {
            Schema::table('imeifilter', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
};
