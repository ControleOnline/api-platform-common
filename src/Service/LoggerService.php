<?php

namespace ControleOnline\Service;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoggerService
{
  private static $loggers = [];
  private string $logsDir;

  public function __construct(ParameterBagInterface $parameterBag)
  {
    $this->logsDir = $parameterBag->get('kernel.logs_dir');
  }

  public function getLogger(string $name): LoggerInterface
  {
    if (!isset(self::$loggers[$name])) {
      $logger = new Logger($name);
      $logger->pushHandler(new StreamHandler(
        $this->logsDir . '/' . $name . '.log',
        Logger::INFO
      ));
      self::$loggers[$name] = $logger;
    }

    return self::$loggers[$name];
  }
}
