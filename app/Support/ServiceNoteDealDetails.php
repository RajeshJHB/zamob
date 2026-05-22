<?php

namespace App\Support;

use App\Models\ServiceNote;

final class ServiceNoteDealDetails
{
    public static function format(ServiceNote $note): string
    {
        $heading = trim((string) $note->heading);
        $body = trim((string) $note->body);

        if ($heading !== '' && $body !== '') {
            return $heading."\n\n".$body;
        }

        if ($heading !== '') {
            return $heading;
        }

        return $body;
    }
}
