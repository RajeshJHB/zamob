<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_notes') || ! Schema::hasColumn('service_notes', 'body')) {
            return;
        }

        Schema::table('service_notes', function (Blueprint $table) {
            $table->string('body', 2000)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_notes') || ! Schema::hasColumn('service_notes', 'body')) {
            return;
        }

        Schema::table('service_notes', function (Blueprint $table) {
            $table->string('body', 2000)->nullable(false)->change();
        });
    }
};
