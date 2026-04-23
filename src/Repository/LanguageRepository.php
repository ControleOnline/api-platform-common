<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Language;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Language|null find($id, $lockMode = null, $lockVersion = null)
 * @method Language|null findOneBy(array $criteria, array $orderBy = null)
 * @method Language[]    findAll()
 * @method Language[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LanguageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Language::class);
    }

    public function findOneByCode(string $languageCode): ?Language
    {
        $normalizedCode = trim(str_replace('_', '-', $languageCode));
        if ($normalizedCode === '') {
            return null;
        }

        $exactMatch = $this->findOneBy(['language' => $normalizedCode]);
        if ($exactMatch instanceof Language) {
            return $exactMatch;
        }

        return $this->createQueryBuilder('language')
            ->andWhere('LOWER(language.language) = :language')
            ->setParameter('language', mb_strtolower($normalizedCode))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
