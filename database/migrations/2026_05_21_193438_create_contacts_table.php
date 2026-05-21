<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('');
            $table->string('first_name')->default('');
            $table->string('surname')->default('');
            $table->string('telephone_1')->default('');
            $table->string('telephone_2')->default('');
            $table->string('email_address')->default('');
            $table->text('physical_address')->nullable();
            $table->foreignId('related_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
