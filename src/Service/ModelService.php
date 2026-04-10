<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Contract;
use Twig\Environment;

class ModelService
{
    public function __construct(
        private Environment $twig
    ) {}

    public function genetateFromModel(Contract $contract): string
    {
        $content = $contract
            ->getContractModel()
            ->getFile()
            ->getContent();

        $template = $this->twig->createTemplate($content);

        $data = [
            'contract' => $contract,
            'service' => $this
        ];

        return $template->render($data);
    }
}
