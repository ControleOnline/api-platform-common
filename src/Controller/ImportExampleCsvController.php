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

        $fp = fopen('php://temp', 'w+');
        fwrite($fp, "\xEF\xBB\xBF");

        foreach ($csv as $row) {
            $utf8Row = array_map(function ($field) {
                return mb_convert_encoding((string)$field, 'UTF-8', 'auto');
            }, $row);

            fputcsv($fp, $utf8Row);
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
