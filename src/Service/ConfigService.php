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
        $values,
        Module $module,
        $visibility = 'private'
    ) {
        $config = $this->discoveryConfig($people, $key);

        if (is_array($values)) {
            $currentValue = json_decode($config->getConfigValue(), true);
            $newValue = is_array($currentValue) ? $currentValue : [];
            foreach ($values as $k => $v) {
                $newValue[$k] = $v;
            }
            $config->setConfigValue(json_encode($newValue));
        } else {
            $config->setConfigValue($values);
        }

        $config->setVisibility($visibility);
        $config->setModule($module);
        $this->manager->persist($config);
        $this->manager->flush();
        return $config;
    }
//pos-cash-wallet
//pos-withdrawl-wallet
//    discoverWallet('pos-cash-wallet', 'Caixa');
//discoverWallet('pos-withdrawl-wallet', 'Reserva');
/*
       {
          paymentType: 'Dinheiro',
          frequency: 'single',
          installments: 'single',
          people: '/people/' + currentCompany.id,
        },
*/
private function infinitePay(){
//pos-infinite-pay-wallet

    {
        paymentType: 'Débito',
        frequency: 'single',
        installments: 'single',
        people: '/people/' + currentCompany.id,
        paymentCode: 'debit',
      },
      {
        paymentType: 'Crédito à Vista',
        frequency: 'single',
        installments: 'single',
        people: '/people/' + currentCompany.id,
        paymentCode: 'credit',
      },
      {
        paymentType: 'Crédito Parcelado',
        frequency: 'single',
        installments: 'split',
        people: '/people/' + currentCompany.id,
        paymentCode: 'credit',
      },
}
    private function cielo($company)
    {
//'pos-cielo-wallet'
        return [
            [
                'paymentType' => 'Débito',
                'frequency' => 'single',
                'installments' => 'single',
                'people' => $company,
                'paymentCode' => 'DEBITO_AVISTA',
            ],

            [
                'paymentType' => 'Refeição',
                'frequency' => 'single',
                'installments' => 'single',
                'people' => $company,
                'paymentCode' => 'VOUCHER_REFEICAO',
            ],
            [
                'paymentType' => 'Alimentação',
                'frequency' => 'single',
                'installments' => 'single',
                'people' => $company,
                'paymentCode' => 'VOUCHER_ALIMENTACAO',
            ],
            [
                'paymentType' => 'Crédito à Vista',
                'frequency' => 'single',
                'installments' => 'single',
                'people' => $company,
                'paymentCode' => 'CREDITO_AVISTA',
            ],
            [
                'paymentType' => 'PIX',
                'frequency' => 'single',
                'installments' => 'single',
                'people' => $company,
                'paymentCode' => 'PIX',
            ],
            [
                'paymentType' => 'Crédito Parcelado - Cliente',
                'frequency' => 'single',
                'installments' => 'split',
                'people' => $company,
                'paymentCode' => 'CREDITO_PARCELADO_CLIENTE',
            ],
            [
                'paymentType' => 'Crédito Parcelado - Loja',
                'frequency' => 'single',
                'installments' => 'split',
                'people' => $company,
                'paymentCode' => 'CREDITO_PARCELADO_LOJA',
            ],
        ];
    }
}
