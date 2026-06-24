<?php

namespace App\Support;

use App\Models\Imei;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ImeiCsvExporter
{
    /**
     * @param  list<string>  $columns
     * @param  array<string, string>  $columnLabels
     * @param  LazyCollection<int, Imei>|iterable<int, Imei>  $imeis
     */
    public static function download(iterable $imeis, array $columns, array $columnLabels): StreamedResponse
    {
        $filename = 'imeis-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($imeis, $columns, $columnLabels): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, array_map(
                fn (string $column): string => $columnLabels[$column] ?? $column,
                $columns,
            ));

            foreach ($imeis as $imei) {
                fputcsv($handle, array_map(
                    fn (string $column): string => self::cellValue($imei, $column),
                    $columns,
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function cellValue(Imei $imei, string $column): string
    {
        $value = match ($column) {
            'date_in', 'date_updated' => $imei->{$column}?->format('Y-m-d H:i') ?? '',
            'cost_incl' => ImeiCostIncl::format($imei->cost_excl) ?? '',
            default => (string) ($imei->{$column} ?? ''),
        };

        if (in_array($column, ['imei', 'sn'], true) && $value !== '') {
            return "\t".$value;
        }

        return $value;
    }
}
