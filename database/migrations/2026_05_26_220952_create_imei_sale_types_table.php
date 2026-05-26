<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('imei_sale_types')) {
            return;
        }

        Schema::create('imei_sale_types', function (Blueprint $table) {
            $table->id();
            $table->string('sale_type')->unique();
        });

        DB::table('imei_sale_types')->insert([
            ['sale_type' => 'None'],
            ['sale_type' => 'Cash'],
            ['sale_type' => 'Voda_Sale'],
            ['sale_type' => 'Easy20wn'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('imei_sale_types');
    }
};
