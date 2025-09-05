<?php

namespace ControleOnline\Service;

use Doctrine\ORM\EntityManagerInterface;
use ControleOnline\Entity\Contract as MyContract;


class ModelService
{
    protected $request;
    public function __construct(
        private EntityManagerInterface $manager,
    ) {}


    public function genetateFromModel(MyContract $contract): string
    {
        return $contract->getContractModel()->getFile()->getContent();
    }
}
