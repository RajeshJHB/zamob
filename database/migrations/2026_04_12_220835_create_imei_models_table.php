<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imei_models')) {
            return;
        }

        Schema::create('imei_models', function (Blueprint $table) {
            $table->id();
            $table->text('model');
            $table->timestamp('date_added')->useCurrent();
            $table->text('serial');
            $table->text('make');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_models');
    }
};
