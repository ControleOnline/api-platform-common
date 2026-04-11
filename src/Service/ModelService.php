<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Contract;
use ControleOnline\Entity\Model;
use Twig\Environment;

class ModelService
{
    public function __construct(
        private Environment $twig
    ) {}

    public function render(Model $model, array $data = []): string
    {
        $content = $model->getFile()->getContent(true);
        $template = $this->twig->createTemplate($content);

        return $template->render($data);
    }

    public function genetateFromModel(Contract $contract): string
    {
        return $this->render($contract->getContractModel(), [
            'contract' => $contract,
            'service' => $this
        ]);
    }
}
