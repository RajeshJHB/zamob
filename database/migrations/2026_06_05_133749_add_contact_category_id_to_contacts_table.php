<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contacts', 'contact_category_id')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->unsignedBigInteger('contact_category_id')
                    ->nullable()
                    ->after('related_contact_id');
            });
        }

        if (! $this->foreignKeyExists('contacts', 'contacts_contact_category_id_foreign')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->foreign('contact_category_id')
                    ->references('id')
                    ->on('contact_categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->foreignKeyExists('contacts', 'contacts_contact_category_id_foreign')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropForeign(['contact_category_id']);
            });
        }

        if (Schema::hasColumn('contacts', 'contact_category_id')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropColumn('contact_category_id');
            });
        }
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$database, $table, $constraintName, 'FOREIGN KEY'],
        );

        return $result !== null;
    }
};
