<?php

namespace ControleOnline\Service\Import;

use ControleOnline\Entity\Import;
use ControleOnline\Service\LeadService;

class LeadsImportService implements ImportProcessorInterface
{
    public function __construct(
        private LeadService $leadsService
    ) {}

    public function getType(): string
    {
        return 'leads';
    }

    public function process(Import $import): void
    {
        $file = $import->getFile();

        $rows = explode("\n", $file->getContent());

        foreach ($rows as $index => $row) {

            if ($index === 0) {
                continue;
            }

            $data = str_getcsv($row);

            $this->leadsService->createLead([
                'empresa' => $data[0] ?? null,
                'cnpj' => $data[1] ?? null,
                'segmento' => $data[2] ?? null,
                'responsavel' => $data[3] ?? null,
                'telefones' => $data[4] ?? null,
                'emails' => $data[5] ?? null,
                'endereco' => $data[6] ?? null,
                'cidade' => $data[7] ?? null,
                'estado' => $data[8] ?? null,
                'cep' => $data[9] ?? null,
                'numero' => $data[10] ?? null,
                'complemento' => $data[11] ?? null,
            ]);
        }
    }

    public function getExampleCsv(): string
    {
        $rows = [
            [
                'Empresa',
                'CNPJ',
                'Segmento',
                'Responsavel',
                'Telefones',
                'Emails',
                'Endereco',
                'Cidade',
                'Estado',
                'CEP',
                'Numero',
                'Complemento'
            ],
            [
                'Empresa Exemplo LTDA',
                '12345678000199',
                'Tecnologia',
                'João Silva',
                '11999999999,11888888888',
                'contato@empresa.com,financeiro@empresa.com',
                'Rua das Flores',
                'São Paulo',
                'SP',
                '01001000',
                '100',
                'Sala 10'
            ]
        ];

        $fp = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        rewind($fp);

        return stream_get_contents($fp);
    }
}