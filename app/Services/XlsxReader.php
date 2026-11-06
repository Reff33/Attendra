<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Minimal streaming-free XLSX reader.
 *
 * Reads the first worksheet of an .xlsx file into plain string rows so the
 * roster importer can treat CSV and XLSX identically.
 * ponytail: first sheet only, no formulas/styles/dates — upgrade to PhpSpreadsheet
 * if the roster ever needs multi-sheet selection or formatted values.
 */
class XlsxReader
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * @return array<int, array<int, string>> Rows of cell values, gaps filled with ''.
     */
    public function rows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Unable to open XLSX archive.');
        }

        try {
            $shared = $this->sharedStrings($zip);
            $sheetXml = $zip->getFromName($this->firstSheetPath($zip));

            if ($sheetXml === false) {
                throw new RuntimeException('XLSX file has no readable worksheet.');
            }
        } finally {
            $zip->close();
        }

        return $this->parseSheet($sheetXml, $shared);
    }

    /**
     * Resolve the first worksheet's internal path via workbook rels, with a fallback.
     */
    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook !== false && $rels !== false) {
            $wb = simplexml_load_string($workbook);
            $relsXml = simplexml_load_string($rels);

            if ($wb !== false && $relsXml !== false) {
                $sheet = $wb->children(self::NS_MAIN)->sheets->sheet[0] ?? null;

                if ($sheet !== null) {
                    $rid = (string) ($sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '');

                    foreach ($relsXml->Relationship as $rel) {
                        if ((string) $rel['Id'] === $rid) {
                            $target = ltrim((string) $rel['Target'], '/');

                            return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                        }
                    }
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $doc = simplexml_load_string($xml);

        if ($doc === false) {
            return [];
        }

        $strings = [];

        foreach ($doc->children(self::NS_MAIN)->si as $si) {
            // Rich text splits a single value across several <r><t> runs.
            $text = '';
            foreach ($si->xpath('.//*[local-name()="t"]') as $t) {
                $text .= (string) $t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $shared
     * @return array<int, array<int, string>>
     */
    private function parseSheet(string $xml, array $shared): array
    {
        $doc = simplexml_load_string($xml);

        if ($doc === false) {
            throw new RuntimeException('XLSX worksheet is not valid XML.');
        }

        $rows = [];

        foreach ($doc->children(self::NS_MAIN)->sheetData->row as $row) {
            $cells = [];
            $maxIndex = -1;

            foreach ($row->c as $cell) {
                // Default-namespaced docs make $cell['r'] resolve empty; attributes() is reliable.
                $attrs = $cell->attributes();
                $index = $this->columnIndex((string) $attrs['r']);
                $type = (string) $attrs['t'];
                $value = $this->cellValue($cell, $type, $shared);

                $cells[$index] = $value;
                $maxIndex = max($maxIndex, $index);
            }

            if ($maxIndex < 0) {
                continue;
            }

            $line = [];
            for ($i = 0; $i <= $maxIndex; $i++) {
                $line[] = $cells[$i] ?? '';
            }

            // Drop fully blank rows, matching the CSV parser.
            if (count(array_filter($line, fn ($c) => trim($c) !== '')) === 0) {
                continue;
            }

            $rows[] = $line;
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $shared
     */
    private function cellValue(SimpleXMLElement $cell, string $type, array $shared): string
    {
        if ($type === 'inlineStr') {
            $text = '';
            foreach ($cell->xpath('.//*[local-name()="t"]') as $t) {
                $text .= (string) $t;
            }

            return trim($text);
        }

        $raw = (string) $cell->v;

        if ($type === 's') {
            return trim($shared[(int) $raw] ?? '');
        }

        return trim($raw);
    }

    /**
     * "AB12" => 27 (zero-based column index).
     */
    private function columnIndex(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }
}
