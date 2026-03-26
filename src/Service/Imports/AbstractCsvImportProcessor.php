<?php

namespace ControleOnline\Service\Imports;

abstract class AbstractCsvImportProcessor implements ImportProcessorInterface
{
    protected function generateUtf8Csv(array $rows): string
    {
        $fp = fopen('php://temp', 'r+');

        if ($fp === false) {
            return '';
        }

        // UTF-8 BOM improves compatibility with spreadsheet tools like Excel.
        fwrite($fp, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            $utf8Row = array_map(static function (mixed $value): string {
                if ($value === null) {
                    return '';
                }

                $value = (string) $value;
                $normalized = iconv('UTF-8', 'UTF-8//IGNORE', $value);

                return $normalized === false ? $value : $normalized;
            }, $row);

            fputcsv($fp, $utf8Row);
        }

        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);

        return $content === false ? '' : $content;
    }
}