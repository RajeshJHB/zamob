<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_notes', function (Blueprint $table) {
            $table->foreignId('primary_service_note_id')
                ->nullable()
                ->after('contact_id')
                ->constrained('service_notes')
                ->nullOnDelete();

            $table->index(['contact_id', 'primary_service_note_id'], 'service_notes_contact_primary_index');
        });
    }

    public function down(): void
    {
        Schema::table('service_notes', function (Blueprint $table) {
            $table->dropForeign(['primary_service_note_id']);
            $table->dropIndex('service_notes_contact_primary_index');
            $table->dropColumn('primary_service_note_id');
        });
    }
};
