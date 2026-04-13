<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imei_locations')) {
            return;
        }

        Schema::create('imei_locations', function (Blueprint $table) {
            $table->id();
            $table->text('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_locations');
    }
};
