<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * Generate a streamed CSV response with UTF-8 BOM encoding for Excel compatibility.
     *
     * @param  array<string>  $headers
     * @param  Collection<int, array<mixed>>|iterable<int, array<mixed>>  $rows
     */
    public function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
