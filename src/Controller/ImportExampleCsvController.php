<?php

namespace ControleOnline\Controller;

use ControleOnline\Service\ImportService;
use Symfony\Component\HttpFoundation\Response;

class ImportExampleCsvController
{
    public function __construct(
        private ImportService $importService
    ) {}

    public function __invoke(string $type): Response
    {
        $csv = $this->importService->getExampleCsv($type);

        $fp = fopen('php://memory', 'r+');

        // BOM UTF-8
        fwrite($fp, "\xEF\xBB\xBF");

        foreach ($csv as $row) {
            // Garante que cada campo é UTF-8
            $row = array_map(function($field) {
                return mb_convert_encoding((string)$field, 'UTF-8', 'UTF-8');
            }, $row);
            
            fputcsv($fp, $row, ',', '"');
        }

        rewind($fp);
        $stream = stream_get_contents($fp);
        fclose($fp);

        return new Response(
            $stream,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="import-' . $type . '-example.csv"',
            ]
        );
    }
}