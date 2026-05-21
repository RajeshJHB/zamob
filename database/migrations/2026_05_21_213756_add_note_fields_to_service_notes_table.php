<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_notes', function (Blueprint $table) {
            $table->unsignedInteger('note_number')->nullable()->after('contact_id');
            $table->foreignId('note_type_id')->nullable()->after('note_number')->constrained('note_types');
            $table->string('status', 16)->default('open')->after('note_type_id');
            $table->string('staff', 255)->default('')->after('created_by');
        });

        $defaultTypeId = DB::table('note_types')->orderBy('sort_order')->value('id');
        $notes = DB::table('service_notes')->orderBy('id')->get(['id', 'created_by']);
        $number = 0;

        foreach ($notes as $note) {
            $number++;
            $staff = '';
            if ($note->created_by) {
                $email = DB::table('users')->where('id', $note->created_by)->value('email');
                $staff = $email ? (string) $email : '';
            }

            DB::table('service_notes')->where('id', $note->id)->update([
                'note_number' => $number,
                'note_type_id' => $defaultTypeId,
                'status' => 'open',
                'staff' => $staff,
            ]);
        }

        Schema::table('service_notes', function (Blueprint $table) {
            $table->unsignedInteger('note_number')->nullable(false)->unique()->change();
            $table->foreignId('note_type_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('service_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('note_type_id');
            $table->dropColumn(['note_number', 'status', 'staff']);
        });
    }
};
