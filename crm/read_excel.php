<?php
$file = 'C:\Users\91963\Downloads\bLK MESSAGE.xlsx';
if (!file_exists($file)) die("File not found");

$zip = new ZipArchive();
if ($zip->open($file) === true) {
    // Read shared strings
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    $strings = [];
    if ($sharedStringsXml) {
        $xml = simplexml_load_string($sharedStringsXml);
        foreach ($xml->si as $val) {
            if (isset($val->t)) {
                $strings[] = (string)$val->t;
            } elseif (isset($val->r)) {
                $text = '';
                foreach ($val->r as $r) {
                    $text .= (string)$r->t;
                }
                $strings[] = $text;
            }
        }
    }

    // Read sheet1
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml) {
        $xml = simplexml_load_string($sheetXml);
        $data = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $val = (string)$cell->v;
                if (isset($cell['t']) && (string)$cell['t'] == 's') {
                    $val = $strings[$val] ?? $val;
                }
                $rowData[] = trim($val);
            }
            $data[] = $rowData;
        }
        echo json_encode($data, JSON_PRETTY_PRINT);
    }
    $zip->close();
}
