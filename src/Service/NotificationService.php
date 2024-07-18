<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class NotificationService
{

  public function __construct(
    private EntityManagerInterface $manager,
    private Security               $security,
    private RequestStack $requestStack,
    private PusherService $pusher
  ) {
  }

  public function afterPersist(Notification $notification)
  {
    $this->pusher->push(['company' => 1, 'people' => 1], 'my_topic');
  }
}
