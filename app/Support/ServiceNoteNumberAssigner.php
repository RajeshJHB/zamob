<?php

namespace App\Support;

use App\Models\ServiceNote;
use Illuminate\Support\Facades\DB;

final class ServiceNoteNumberAssigner
{
    public static function nextNumber(): int
    {
        return (int) DB::transaction(function (): int {
            $current = ServiceNote::query()->lockForUpdate()->max('note_number');

            return ((int) $current) + 1;
        });
    }

    public static function format(int $noteNumber): string
    {
        return 'SN-'.$noteNumber;
    }
}
