<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\People;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use ControleOnline\Service\MenuConfigService;


class GetMenuByPeopleAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager = null;

  /**
   * Security
   *
   * @var Security
   */
  private $security = null;

  public function __construct(
    Security $security,
    EntityManagerInterface $entityManager,
    private MenuConfigService $menuConfigService
  )
  {
    $this->manager    = $entityManager;
    $this->security   = $security;
  }

  public function __invoke(Request $request): JsonResponse
  {
    try {

      $menu  = [];

      $company = $request->query->get('myCompany', null);
      $appType = $this->menuConfigService->normalizeAppType(
        $request->query->get('appType', 'MANAGER')
      );
      $menuType = $request->query->get('menuType', null);
      $menuType = is_string($menuType) && trim($menuType) !== '' ? $menuType : null;

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

      $menu = $this->menuConfigService->getMenuForPeople(
        $userPeople,
        $myCompany,
        $appType,
        in_array('ROLE_SUPER', $currentUser->getRoles(), true),
        $menuType
      );


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

}
