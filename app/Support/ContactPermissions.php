<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\ServiceNote;
use App\Models\User;
use Illuminate\Support\Carbon;

final class ContactPermissions
{
    public static function canDeleteContact(User $user, Contact $contact): bool
    {
        if ($user->canDeleteImeiReferenceData()) {
            return true;
        }

        return self::isToday($contact->created_at);
    }

    public static function canEditContact(User $user, Contact $contact): bool
    {
        return true;
    }

    public static function canEditServiceNote(User $user, ServiceNote $note): bool
    {
        if ($user->canEditServiceNoteAlways()) {
            return true;
        }

        return self::isToday($note->created_at);
    }

    public static function canDeleteServiceNote(User $user, ServiceNote $note): bool
    {
        if (! $user->canDeleteImeiReferenceData()) {
            return false;
        }

        return $note->isClosed();
    }

    private static function isToday(?Carbon $timestamp): bool
    {
        if ($timestamp === null) {
            return false;
        }

        return $timestamp->isToday();
    }
}
