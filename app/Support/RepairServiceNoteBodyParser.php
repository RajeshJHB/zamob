<?php

namespace App\Support;

final class RepairServiceNoteBodyParser
{
    /**
     * @return array{imei: string, make: string, model: string, problem: string, price: string, repair_place: string}
     */
    public static function parse(string $body): array
    {
        $fields = [
            'imei' => '',
            'make' => '',
            'model' => '',
            'problem' => '',
            'price' => '',
            'repair_place' => '',
        ];

        /** @var array<string, string> $labelMap */
        $labelMap = [
            'imei' => 'imei',
            'make' => 'make',
            'model' => 'model',
            'problem' => 'problem',
            'price' => 'price',
            'repair place' => 'repair_place',
        ];

        foreach (preg_split('/\r\n|\r|\n/', $body) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (! preg_match('/^([^:]+):\s*(.*)$/u', $line, $matches)) {
                continue;
            }

            $key = strtolower(trim($matches[1]));
            if (! array_key_exists($key, $labelMap)) {
                continue;
            }

            $fields[$labelMap[$key]] = trim($matches[2]);
        }

        return $fields;
    }
}
