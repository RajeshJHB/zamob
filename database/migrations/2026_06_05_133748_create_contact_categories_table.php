<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $categories = [
            ['name' => 'Customer', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Supplier', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Emergency', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('contact_categories')->insert($categories);
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_categories');
    }
};
