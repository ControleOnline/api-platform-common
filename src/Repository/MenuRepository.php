<?php

namespace ControleOnline\Repository;

use ControleOnline\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Menu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Menu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Menu[]    findAll()
 * @method Menu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MenuRepository extends ServiceEntityRepository
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Menu::class);
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  public function findVisibleRowsForPeople(int $peopleId, int $companyId, string $appType, bool $isSuper): array
  {
    $connection = $this->getEntityManager()->getConnection();

    $sql = 'SELECT DISTINCT menu.*,
            category.name AS category_label,
            category.color AS category_color,
            category.icon AS category_icon,
            routes.route AS route,
            routes.color AS color,
            routes.icon AS icon,
            routes.module_id AS module
            FROM menu
            INNER JOIN category ON category.id = menu.category_id
            INNER JOIN routes ON routes.id = menu.route_id ';

    $params = [
      'appType' => $appType,
    ];

    if ($isSuper) {
      $sql .= ' WHERE menu.enabled = 1 AND menu.app_type = :appType ';
    } else {
      $sql .= ' INNER JOIN menu_link_type ON menu_link_type.menu_id = menu.id
                INNER JOIN people_link ON people_link.link_type = menu_link_type.link_type
                WHERE menu.enabled = 1
                AND menu.app_type = :appType
                AND people_link.enable = 1
                AND people_link.company_id = :companyId
                AND people_link.people_id = :peopleId ';

      $params['companyId'] = $companyId;
      $params['peopleId'] = $peopleId;
    }

    $sql .= ' ORDER BY category.name ASC, menu.sort_order ASC, menu.menu ASC';

    return $connection->executeQuery($sql, $params)->fetchAllAssociative();
  }
}
