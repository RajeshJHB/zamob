<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $types = [
            ['name' => 'Consult', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Upgrade', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'New Deal', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Repair', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('note_types')->insert($types);
    }

    public function down(): void
    {
        Schema::dropIfExists('note_types');
    }
};
