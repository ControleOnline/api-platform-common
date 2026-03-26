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

        $output = '';

        foreach ($csv as $row) {
            $output .= implode(',', array_map(function($field) {
                // Escapa aspas e coloca entre aspas se tiver vírgula ou caracteres especiais
                $field = str_replace('"', '""', $field);
                return '"' . $field . '"';
            }, $row)) . "\n";
        }

        // Garante UTF-8
        $output = mb_convert_encoding($output, 'UTF-8', 'UTF-8');

        return new Response(
            "\xEF\xBB\xBF" . $output,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="import-' . $type . '-example.csv"',
            ]
        );
    }
}