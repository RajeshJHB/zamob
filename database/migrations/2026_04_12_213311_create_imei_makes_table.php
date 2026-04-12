<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imei_make')) {
            return;
        }

        Schema::create('imei_make', function (Blueprint $table) {
            $table->id();
            $table->text('make');
            $table->timestamp('date_added')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_make');
    }
};
