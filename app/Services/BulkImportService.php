<?php

namespace App\Services;

class BulkImportService
{
    /**
     * Parse raw CSV content into array of rows with clean UTF-8 string trimming.
     */
    public function parseCsv(string $content): array
    {
        if (trim($content) === '') {
            return [];
        }

        // Handle possible UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            if (empty($row) || (count($row) === 1 && $row[0] === null)) {
                continue;
            }

            $cleaned = array_map(function ($cell) {
                return html_entity_decode(trim((string) $cell), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }, $row);

            if (count(array_filter($cleaned, fn($c) => $c !== '')) === 0) {
                continue;
            }

            $rows[] = $cleaned;
        }

        fclose($stream);
        return $rows;
    }

    /**
     * Heuristic column auto-detection based on header titles.
     */
    public function detectColumns(array $headers): array
    {
        $detected = [
            'name'      => null,
            'matric_no' => null,
            'ic_last_4' => null,
            'email'     => null,
            'program'   => null,
        ];

        foreach ($headers as $h) {
            $lbl = strtolower(trim($h['label']));
            $idx = $h['index'];

            // Name detection
            if ($detected['name'] === null && (
                str_contains($lbl, 'nama') ||
                str_contains($lbl, 'name') ||
                str_contains($lbl, 'student') ||
                str_contains($lbl, 'pelajar')
            )) {
                $detected['name'] = $idx;
            }

            // Matric No detection
            if ($detected['matric_no'] === null && (
                str_contains($lbl, 'matric') ||
                str_contains($lbl, 'matrik') ||
                str_contains($lbl, 'no_matrik') ||
                str_contains($lbl, 'no matrik') ||
                str_contains($lbl, 'no pendaftaran') ||
                str_contains($lbl, 'no_pend') ||
                str_contains($lbl, 'student_id') ||
                str_contains($lbl, 'student id') ||
                str_contains($lbl, 'id pelajar')
            )) {
                $detected['matric_no'] = $idx;
            }

            // IC / Identity No detection
            if ($detected['ic_last_4'] === null && (
                str_contains($lbl, 'ic') ||
                str_contains($lbl, 'kp') ||
                str_contains($lbl, 'kad pengenalan') ||
                str_contains($lbl, 'nric') ||
                str_contains($lbl, 'identity') ||
                str_contains($lbl, 'ic4') ||
                str_contains($lbl, 'last 4') ||
                str_contains($lbl, 'last_4')
            )) {
                $detected['ic_last_4'] = $idx;
            }

            // Email detection
            if ($detected['email'] === null && (
                str_contains($lbl, 'email') ||
                str_contains($lbl, 'e-mel') ||
                str_contains($lbl, 'emel') ||
                str_contains($lbl, 'mail')
            )) {
                $detected['email'] = $idx;
            }

            // Program / Course detection
            if ($detected['program'] === null && (
                str_contains($lbl, 'program') ||
                str_contains($lbl, 'kursus') ||
                str_contains($lbl, 'course') ||
                str_contains($lbl, 'kod program') ||
                str_contains($lbl, 'kelas') ||
                str_contains($lbl, 'fakulti') ||
                str_contains($lbl, 'faculty')
            )) {
                $detected['program'] = $idx;
            }
        }

        return $detected;
    }
}
