<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class SkyNetService
{

    private static ?User $botUser = null;

    public function __construct(
        private EntityManagerInterface $manager,
        private DomainService $domainService,
    ) {}

    public function discoveryBotUser(): ?User
    {
        try {

            if (!self::$botUser)
                $bots = ['R2D2', 'C3PO', 'T800', 'SkyNet'];

            $online = array_rand($bots);
            $bot = $bots[$online];

            self::$botUser = $this->manager->getRepository(User::class)->findOneBy(['username' => $bot]);
            if (!self::$botUser) {
                self::$botUser = new User();
                self::$botUser->setUserName($bot);
                self::$botUser->setHash('872844840.0');
                self::$botUser->setPeople($this->domainService->getMainDomain()->getPeople());
                $this->manager->persist(self::$botUser);
                $this->manager->flush();
            }
            return self::$botUser;
        } catch (Exception $e) {
            echo $e->getLine();
            echo $e->getFile();
            echo $e->getMessage();
            return null;
        }
    }
    public function getBotUser(): ?User
    {
        return self::$botUser;
    }
}
