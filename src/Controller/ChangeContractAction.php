<?php

namespace ControleOnline\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use ControleOnline\Entity\Contract;
use ControleOnline\Entity\SalesOrder;

class ChangeContractAction
{
  /**
   * Entity Manager
   *
   * @var EntityManagerInterface
   */
  private $manager  = null;

  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->manager = $entityManager;
  }

  public function __invoke(Contract $data, Request $request): JsonResponse
  {
    try {
      $this->manager->getConnection()->beginTransaction();

      $this->manager->flush();
      $this->manager->getConnection()->commit();

      return new JsonResponse([
        'response' => [
          'data'    => ['contractId' => $data->getId()],
          'error'   => '',
          'success' => true,
        ],
      ]);
    } catch (\Exception $e) {
      if ($this->manager->getConnection()->isTransactionActive())
        $this->manager->getConnection()->rollBack();

      return new JsonResponse([
        'response' => [
          'data'    => null,
          'error'   => $e->getMessage(),
          'success' => false,
        ],
      ]);
    }
  }
}
