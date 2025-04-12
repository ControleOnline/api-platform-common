<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Menu;
use ControleOnline\Entity\People;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
 AS Security;
use ControleOnline\Repository\MenuRepository;


class GetMenuByPeopleAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Request
   *
   * @var Request
   */
  private $request  = null;

  /**
   * Security
   *
   * @var Security
   */
  private $security = null;

  /**
   * @var \ControleOnline\Repository\MenuRepository
   */
  private $repository = null;


  public function __construct(Security $security, EntityManagerInterface $entityManager)
  {
    $this->manager    = $entityManager;
    $this->security   = $security;
    $this->repository = $this->manager->getRepository(Menu::class);
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      $menu  = [];

      $company = $request->query->get('myCompany', null);

      if ($company === null)
        throw new Exception("Company not found", 404);


      $myCompany = $this->manager->getRepository(People::class)
        ->find($company);

      if ($myCompany === null)
        throw new Exception("Company not found", 404);



      $currentUser = $this->security->getToken()->getUser();
      /**
       * @var People
       */
      $userPeople = $currentUser->getPeople();

      $menu =  $this->getMenuByPeople($userPeople, $myCompany);


      return new JsonResponse([
        'response' => [
          'data'    => $menu,
          'count'   => 1,
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {

      return new JsonResponse([
        'response' => [
          'data'    => [],
          'count'   => 0,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }

  private function getMenuByPeople(People $userPeople, People $myCompany)
  {

    $return = [];
    $connection = $this->manager->getConnection();

    // build query

    $sql  = 'SELECT menu.*,
            category.name AS category_label,
            category.color AS category_color,
            routes.route AS route,
            routes.color AS color,
            routes.icon  AS icon,
            routes.module_id  AS module,
            category.icon AS category_icon FROM menu             
             INNER JOIN category ON category.id = menu.category_id
             INNER JOIN menu_role ON menu.id = menu_role.menu_id
             INNER JOIN people_role ON people_role.role_id = menu_role.role_id    
             INNER JOIN routes ON routes.id = menu.route_id
             WHERE people_role.company_id=:myCompany AND people_role.people_id=:userPeople    
             GROUP BY menu.id
             ';


    $params = [];

    $params['myCompany']   = $myCompany->getId();
    $params['userPeople']   = $userPeople->getId();
    // execute query

    $result = $connection->executeQuery($sql, $params)->fetchAllAssociative();

    foreach ($result as $menu) {

      $return['modules'][$menu['category_id']]['id'] = $menu['category_id'];
      $return['modules'][$menu['category_id']]['label'] = $menu['category_label'];
      $return['modules'][$menu['category_id']]['color'] = $menu['category_color'];
      $return['modules'][$menu['category_id']]['icon'] = $menu['category_icon'];
      $return['modules'][$menu['category_id']]['menus'][] = [
        'id' => $menu['id'],
        'label' =>  $menu['menu'],
        'icon' =>  $menu['icon'],
        'color' =>  $menu['color'],
        'route' =>  $menu['route'],
        'module' =>  '/modules/' . $menu['module'],
      ];
    }



    return $return;
  }
}