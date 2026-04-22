<?php

namespace ControleOnline\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LoggerService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private TokenStorageInterface $tokenStorage
    ) {}

    public function getLogger(string $name): LoggerInterface
    {
        $normalizedName = trim($name) !== '' ? trim($name) : 'application';

        return new DatabaseLogger(
            $this->manager->getConnection(),
            $this->tokenStorage,
            $normalizedName
        );
    }
}
