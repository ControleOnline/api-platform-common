<?php

namespace ControleOnline\Service\Imports;

use ControleOnline\Entity\Import;

abstract class ImportCommon implements ImportProcessorInterface
{
    protected static $CSV_HEADERS;

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $header = is_string($header) ? trim($header) : '';
            $header = ltrim($header, "\xEF\xBB\xBF");
            // Example CSV templates mark required columns with a trailing '*'.
            // Strip it so the same file remains a valid upload template.
            return rtrim($header, '*');
        }, $headers);
    }


    protected function import(Import $import, array $headers, $service): void
    {
        self::$CSV_HEADERS = $headers;
        $file = $import->getFile();
        $content = $file?->getContent(true) ?? '';

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Nao foi possivel abrir o arquivo CSV para importacao.');
        }

        fwrite($handle, $content);
        rewind($handle);
        $delimiter = $this->detectDelimiter($content);

        $headers = null;
        $lineNumber = 0;
        $successCount = 0;
        $errorMessages = [];

        while (($row = fgetcsv($handle, null, $delimiter)) !== false) {
            $lineNumber++;

            if ($row === [null] || $this->isEmptyRow($row)) {
                continue;
            }

            if ($headers === null) {
                $headers = $this->normalizeHeaders($row);
                $this->validateHeaders($headers);
                continue;
            }

            $rowData = $this->mapRowToHeaders($headers, $row);

            try {
                $service->importFromCSV(
                    $rowData,
                    $import->getPeople()
                );
                $successCount++;
            } catch (\Throwable $e) {
                $errorMessages[] = sprintf('Linha %d: %s', $lineNumber, $e->getMessage());
            }
        }

        fclose($handle);

        if ($headers === null) {
            throw new \InvalidArgumentException('O arquivo CSV enviado esta vazio.');
        }

        if ($errorMessages !== []) {
            $feedback = array_merge(
                [sprintf('%d linha(s) importada(s) com sucesso.', $successCount)],
                $errorMessages
            );
            $import->setFeedback(implode("\n", $feedback));
            return;
        }

        $import->setFeedback(sprintf('%d linha(s) importada(s) com sucesso.', $successCount));
    }

    // Spreadsheets saved by Excel in pt-BR use ';' as separator; pick whichever
    // of ';' or ',' splits the header line into more columns.
    protected function detectDelimiter(string $content): string
    {
        $content = ltrim($content, "\xEF\xBB\xBF");
        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            if (trim($line) === '') {
                continue;
            }

            return count(str_getcsv($line, ';')) > count(str_getcsv($line, ',')) ? ';' : ',';
        }

        return ',';
    }

    protected function validateHeaders(array $headers): void
    {
        $missingHeaders = array_values(array_diff(self::$CSV_HEADERS, $headers));

        if ($missingHeaders !== []) {
            throw new \InvalidArgumentException(
                'Cabecalho CSV invalido. Colunas ausentes: ' . implode(', ', $missingHeaders)
            );
        }
    }

    protected function mapRowToHeaders(array $headers, array $row): array
    {
        $mappedRow = [];

        foreach (self::$CSV_HEADERS as $index => $header) {
            $headerIndex = array_search($header, $headers, true);
            $mappedRow[$header] = $headerIndex === false ? null : ($row[$headerIndex] ?? null);
        }

        return $mappedRow;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }
}
