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

        if (!str_starts_with($csv, "\xEF\xBB\xBF")) {
            $csv = "\xEF\xBB\xBF" . $csv;
        }

        return new Response(
            $csv,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="import-'.$type.'-example.csv"',
            ]
        );
    }
}