<?php

namespace ControleOnline\Library\Rates;

use ControleOnline\Library\Rates\Model\User;

class RateServiceFactory
{
  public static function create(string $type, string $user = null, string $password = null): CarrierRatesInterface
  {
    $clientName = sprintf('\\ControleOnline\\Library\\Rates\\%s\\Client', ucfirst($type));

    if (!class_exists($clientName)) {
      throw new \Exception('Rate service is not available');
    }

    $authUser = (new User())
      ->setKey  ($user)
      ->setToken($password)
    ;

    $service  = new $clientName();

    $service->setUser($authUser);

    return $service;
  }
}
