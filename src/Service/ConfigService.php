<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\Module;
use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleDomain;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\PseudoTypes\Numeric_;
use phpDocumentor\Reflection\Types\Integer;

class ConfigService
{
    private $request;
    public function __construct(
        private EntityManagerInterface $manager,
        private RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getConfig(People $people, $key, $json = false)
    {
        $config = $this->discoveryConfig($people, $key, false);
        $value =  $config ? $config->getConfigValue() : null;
        return $json ? json_decode($value, true) : $value;
    }

    private function discoveryConfig(People $people, $key, $create = true): ?Config
    {
        $config =   $this->manager->getRepository(Config::class)->findOneBy([
            'people' => $people,
            'configKey' => $key
        ]);
        if ($config)
            return $config;
        if ($create) {
            $config = new Config();
            $config->setConfigKey($key);
            $config->setPeople($people);

            return $config;
        }
        return null;
    }

    public function addConfig(
        People $people,
        string $key,
        array $values,
        Module $module,
        $visibility = 'private'
    ) {
        $config = $this->discoveryConfig($people, $key);
        $newValue = json_decode($config->getConfigValue(), true) || [];
        if (!is_array($newValue))
            $newValue = [$newValue];

        if (is_array($values))
            foreach ($values as $key => $value)
                $newValue[$key] = $value;
        else
            $newValue[] = $values;

        $config->setConfigValue(json_encode($newValue));
        $config->setVisibility($visibility);
        $config->setModule($module);
        $this->manager->persist($config);
        $this->manager->flush();
        return $config;
    }
}
