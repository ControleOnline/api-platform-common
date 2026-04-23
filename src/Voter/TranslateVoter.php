<?php

namespace ControleOnline\Voter;

use ControleOnline\Entity\People;
use ControleOnline\Entity\Translate;
use ControleOnline\Entity\User;
use ControleOnline\Repository\PeopleRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TranslateVoter extends Voter
{
    public const VIEW = 'TRANSLATE_VIEW';
    public const MANAGE = 'TRANSLATE_MANAGE';

    public function __construct(private PeopleRepository $peopleRepository) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::MANAGE], true)
            && $subject instanceof Translate;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        $company = $subject->getPeople();
        if (!$company instanceof People) {
            return false;
        }

        $userPeople = $user->getPeople();
        if ($userPeople->getId() === $company->getId()) {
            return true;
        }

        return $this->peopleRepository->getCompanyPeopleLinks($company, $userPeople, null, 1) !== null;
    }
}
