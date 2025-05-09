<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\People;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Config|null find($id, $lockMode = null, $lockVersion = null)
 * @method Config|null findOneBy(array $criteria, array $orderBy = null)
 * @method Config[]    findAll()
 * @method Config[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Config::class);
  }

  public function updatePeopleConfigKey(People $people, string $key, string $value): void
  {
    try {
      $params = [];

      $this->getEntityManager()->getConnection()
        ->executeQuery('START TRANSACTION', $params);

      $rawSQL = <<<SQL
          UPDATE config
            SET configValue = :value
          WHERE people_id = :id AND configKey = :key
        SQL;

      $params = [
        'id'    => $people->getId(),
        'key'   => $key,
        'value' => $value,
      ];

      $this->getEntityManager()->getConnection()
        ->executeQuery($rawSQL, $params);

      $this->getEntityManager()->getConnection()
        ->executeQuery('COMMIT', $params);
    } catch (\Exception $e) {
      $this->getEntityManager()->getConnection()
        ->executeQuery('ROLLBACK', $params);

      throw new \Exception($e->getMessage());
    }
  }

  public function getKeyValuesByPeople(People $people, string $key): ?array
  {
    $result = $this->createQueryBuilder('a')
      ->andWhere('a.people = :people')
      ->andWhere('a.configKey LIKE :prefix')
      ->setParameter('people', $people)
      ->setParameter('prefix', $key . '-%')

      ->getQuery()
      ->getResult();

    if (empty($result))
      return null;

    $configs = [];

    /**
     * @var Config $config
     */
    foreach ($result as $config) {
      $configs[$config->getConfigKey()] = $config->getConfigValue();
    }

    return $configs;
  }

  public function getItauConfigByPeople(People $people): ?array
  {

    $config = $this->getEntityManager()->getRepository(Config::class)->findOneBy([
      'people' => $people,
      'configKey' => 'payment_type'
    ]);


    if (empty($config) || $config->getConfigValue() != 'itau')
      return null;

    $result = $this->createQueryBuilder('a')
      ->andWhere('a.people = :people')
      ->andWhere('a.configKey LIKE :prefix')
      ->setParameter('people', $people)
      ->setParameter('prefix', 'itau-shopline-%')
      ->getQuery()
      ->getResult();

    if (empty($result))
      return null;

    $configs = [];

    /**
     * @var Config $config
     */
    foreach ($result as $config) {
      $configs[$config->getConfigKey()] = $config->getConfigValue();
    }

    return $configs;
  }

  public function getMauticConfigByPeople(People $people): ?array
  {
    $result = $this->createQueryBuilder('a')
      ->andWhere('a.people = :people')
      ->andWhere('a.configKey LIKE :prefix')
      ->setParameter('people', $people)
      ->setParameter('prefix', 'mautic-%')

      ->getQuery()
      ->getResult();

    if (empty($result))
      return null;

    $configs = [];

    /**
     * @var Config $config
     */
    foreach ($result as $config) {
      $configs[$config->getConfigKey()] = $config->getConfigValue();
    }

    return $configs;
  }
}
