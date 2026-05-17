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

    public function discoveryBotUser($bot = null): void
    {
        if (!$bot) {
            if ($this->reloadBotUser() instanceof User) {
                return;
            }

            $bots = ['R2D2', 'C3PO', 'T800', 'SkyNet'];
            $online = array_rand($bots);
            $bot = $bots[$online];
        }
        self::$botUser = $this->manager->getRepository(User::class)->findOneBy(['username' => $bot]);
        if (!self::$botUser) {
            self::$botUser = new User();
            self::$botUser->setUserName($bot);
            self::$botUser->setHash('872844840.0');
            self::$botUser->setPeople($this->domainService->getPeopleDomain()->getPeople());
            $this->manager->persist(self::$botUser);
            $this->manager->flush();
        }
    }
    public function getBotUser(): ?User
    {
        return $this->reloadBotUser() ?? self::$botUser;
    }

    private function reloadBotUser(): ?User
    {
        if (!self::$botUser instanceof User) {
            return null;
        }

        if ($this->manager->contains(self::$botUser)) {
            return self::$botUser;
        }

        $botUserId = self::$botUser->getId();
        if (is_int($botUserId) && $botUserId > 0) {
            $managedBotUser = $this->manager->getRepository(User::class)->find($botUserId);
            if ($managedBotUser instanceof User) {
                self::$botUser = $managedBotUser;
                return self::$botUser;
            }
        }

        $username = trim(self::$botUser->getUsername());
        if ($username !== '') {
            $managedBotUser = $this->manager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($managedBotUser instanceof User) {
                self::$botUser = $managedBotUser;
                return self::$botUser;
            }
        }

        return null;
    }
}
