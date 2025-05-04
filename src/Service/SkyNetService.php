<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SkyNetService
{

    private static ?User $botUser = null;

    public function __construct(
        private EntityManagerInterface $manager
    ) {}

    public function discoveryBotUser(): User
    {
        if (!self::$botUser)
            $bots = ['R2D2', 'C3PO', 'T800', 'SkyNet'];

        $online = array_rand($bots);
        $bot = $bots[$online];

        self::$botUser = $this->manager->getRepository(User::class)->findOneBy(['username' => $bot]);
        if (!self::$botUser) {
            self::$botUser = new User();
            self::$botUser->setUserName($bot);
            self::$botUser->setHash('872844840.0');
            $this->manager->persist(self::$botUser);
            $this->manager->flush();
        }
        return self::$botUser;
    }
    public function getBotUser(): ?User
    {
        return self::$botUser;
    }
}
