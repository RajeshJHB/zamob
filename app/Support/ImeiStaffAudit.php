<?php

namespace App\Support;

final class ImeiStaffAudit
{
    public const MAX_LENGTH = 255;

    private const DELIMITER = ', ';

    /**
     * Append an email to the staff audit list if not already present (case-insensitive).
     * Preserves existing legacy text and prior emails; drops oldest entries if over {@see self::MAX_LENGTH}.
     */
    public static function appendEmail(string $existing, string $email): string
    {
        $email = trim($email);
        if ($email === '') {
            return trim($existing);
        }

        $parts = self::parseEmails($existing);
        $lower = array_map(strtolower(...), $parts);

        if (! in_array(strtolower($email), $lower, true)) {
            $parts[] = $email;
        }

        $result = implode(self::DELIMITER, $parts);

        if (strlen($result) <= self::MAX_LENGTH) {
            return $result;
        }

        while (count($parts) > 1 && strlen(implode(self::DELIMITER, $parts)) > self::MAX_LENGTH) {
            array_shift($parts);
        }

        $result = implode(self::DELIMITER, $parts);

        if (strlen($result) > self::MAX_LENGTH) {
            return substr($result, 0, self::MAX_LENGTH);
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private static function parseEmails(string $existing): array
    {
        $existing = trim($existing);
        if ($existing === '') {
            return [];
        }

        $parts = array_map(trim(...), explode(',', $existing));

        return array_values(array_filter($parts, fn (string $part): bool => $part !== ''));
    }
}
