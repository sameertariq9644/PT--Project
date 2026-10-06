<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    /**
     * Stream rows as a CSV download without building the file in memory.
     *
     * @param  list<string>  $headings
     * @param  iterable<array<int, scalar|null>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $output = fopen('php://output', 'w');

            // UTF-8 byte order mark so Excel reads names and dashes correctly.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headings, escape: '');

            foreach ($rows as $row) {
                fputcsv($output, array_map(static::safeCell(...), $row), escape: '');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Stop a visitor-supplied value from being run as a spreadsheet formula.
     */
    protected static function safeCell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 && ! is_numeric($value) ? "'".$value : $value;
    }
}
