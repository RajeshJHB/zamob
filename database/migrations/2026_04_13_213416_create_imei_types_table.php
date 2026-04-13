<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imei_types')) {
            return;
        }

        Schema::create('imei_types', function (Blueprint $table) {
            $table->id();
            $table->text('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_types');
    }
};
