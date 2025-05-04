<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SkyNetService
{

    private static ?User $botUser = null;

    public function __construct(
        private EntityManagerInterface $manager,
        private DomainService $domainService,
    ) {}

    public function discoveryBotUser(): void
    {
        if (!self::$botUser)
            $bots = ['R2D2', 'C3PO', 'T800', 'SkyNet'];

        $online = array_rand($bots);
        $bot = $bots[$online];
        echo '1';
        self::$botUser = $this->manager->getRepository(User::class)->findOneBy(['username' => $bot]);
        echo '2';
        if (!self::$botUser) {
            echo '3';
            
            self::$botUser = new User();
            self::$botUser->setUserName($bot);
            self::$botUser->setHash('872844840.0');
            self::$botUser->setPeople($this->domainService->getPeopleDomain()->getPeople());
            echo '4';
            $this->manager->persist(self::$botUser);
            $this->manager->flush();
            echo '5';
        }
        echo '6';
    }
    public function getBotUser(): ?User
    {
        return self::$botUser;
    }
}
