<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_notes')) {
            return;
        }

        if (! Schema::hasColumn('service_notes', 'primary_service_note_id')) {
            Schema::table('service_notes', function (Blueprint $table) {
                $table->foreignId('primary_service_note_id')
                    ->nullable()
                    ->after('contact_id')
                    ->constrained('service_notes')
                    ->nullOnDelete();
            });
        }

        if (! $this->indexExists('service_notes', 'service_notes_contact_primary_index')) {
            Schema::table('service_notes', function (Blueprint $table) {
                $table->index(['contact_id', 'primary_service_note_id'], 'service_notes_contact_primary_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_notes')) {
            return;
        }

        if ($this->indexExists('service_notes', 'service_notes_contact_primary_index')) {
            Schema::table('service_notes', function (Blueprint $table) {
                $table->dropIndex('service_notes_contact_primary_index');
            });
        }

        if (Schema::hasColumn('service_notes', 'primary_service_note_id')) {
            Schema::table('service_notes', function (Blueprint $table) {
                $table->dropForeign(['primary_service_note_id']);
                $table->dropColumn('primary_service_note_id');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $definition) {
            if (($definition['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
