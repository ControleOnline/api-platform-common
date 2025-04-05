<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Config;
use ControleOnline\Entity\Device;
use ControleOnline\Entity\Module;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;


class ConfigService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private WalletService $walletService
    ) {}

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

    public function discoveryMainConfigs(People $people, Device $device)
    {
        $this->discoveryCashWallet($people);
        $this->discoveryWithdrawlWallet($people);
        $this->discoveryInfinitePayWallet($people);
        $this->discoveryCieloWallet($people);

        return $this->getCompanyConfigs($people);
    }

    public function getCompanyConfigs(People $people, $visibility = 'public')
    {
        return   $this->manager->getRepository(Config::class)->findBy([
            'people' => $people,
            'visibility' => $visibility
        ]);
    }

    private function discoveryCashWallet(People $company)
    {

        $paymentTypes = [[
            'paymentType' => 'Dinheiro',
            'frequency' => 'single',
            'installments' => 'single',
            'paymentCode' => null
        ]];
        /**
         * @todo Module need be variable
         */
        $module = $this->manager->getRepository(Module::class)->find(8);
        $wallet = $this->walletService->discoverWallet($company, 'Caixa');
        $this->addConfig(
            $company,
            'pos-cash-wallet',
            $wallet->getId(),
            $module,
            'private'
        );

        foreach ($paymentTypes as $paymentType)
            $this->walletService->discoverWalletPaymentType(
                $wallet,
                $this->walletService->discoverPaymentType($company, $paymentType),
                $paymentType['paymentCode']
            );
    }

    private function discoveryWithdrawlWallet(People $company)
    {

        $paymentTypes = [[
            'paymentType' => 'Dinheiro',
            'frequency' => 'single',
            'installments' => 'single',
            'paymentCode' => null
        ]];

        $this->walletService->discoverWallet($company, 'Reserva');
        /**
         * @todo Module need be variable
         */
        $module = $this->manager->getRepository(Module::class)->find(8);
        $wallet = $this->walletService->discoverWallet($company, 'Reserva');
        $this->addConfig(
            $company,
            'pos-withdrawl-wallet',
            $wallet->getId(),
            $module,
            'private'
        );
        foreach ($paymentTypes as $paymentType)
            $this->walletService->discoverWalletPaymentType(
                $wallet,
                $this->walletService->discoverPaymentType($company, $paymentType),
                $paymentType['paymentCode']
            );
    }

    private function discoveryInfinitePayWallet(People $company)
    {
        $paymentTypes =  [
            [
                'paymentType' => 'Débito',
                'frequency' => 'single',
                'installments' => 'single',
                'paymentCode' => 'debit',
            ],
            [
                'paymentType' => 'Crédito à Vista',
                'frequency' => 'single',
                'installments' => 'single',
                'paymentCode' => 'credit',
            ],
            [
                'paymentType' => 'Crédito Parcelado',
                'frequency' => 'single',
                'installments' => 'split',
                'paymentCode' => 'credit',
            ],
        ];


        /**
         * @todo Module need be variable
         */
        $module = $this->manager->getRepository(Module::class)->find(8);
        $wallet = $this->walletService->discoverWallet($company, 'Infine Pay');
        $this->addConfig(
            $company,
            'pos-infinite-pay-wallet',
            $wallet->getId(),
            $module,
            'private'
        );

        foreach ($paymentTypes as $paymentType)
            $this->walletService->discoverWalletPaymentType(
                $wallet,
                $this->walletService->discoverPaymentType($company, $paymentType),
                $paymentType['paymentCode']
            );
    }

    private function discoveryCieloWallet(People $company)
    {

        $paymentTypes = [
            [
                'paymentType' => 'Débito',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'DEBITO_AVISTA',
            ],

            [
                'paymentType' => 'Refeição',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'VOUCHER_REFEICAO',
            ],
            [
                'paymentType' => 'Alimentação',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'VOUCHER_ALIMENTACAO',
            ],
            [
                'paymentType' => 'Crédito à Vista',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'CREDITO_AVISTA',
            ],
            [
                'paymentType' => 'PIX',
                'frequency' => 'single',
                'installments' => 'single',

                'paymentCode' => 'PIX',
            ],
            [
                'paymentType' => 'Crédito Parcelado - Cliente',
                'frequency' => 'single',
                'installments' => 'split',

                'paymentCode' => 'CREDITO_PARCELADO_CLIENTE',
            ],
            [
                'paymentType' => 'Crédito Parcelado - Loja',
                'frequency' => 'single',
                'installments' => 'split',

                'paymentCode' => 'CREDITO_PARCELADO_LOJA',
            ],
        ];

        /**
         * @todo Module need be variable
         */
        $module = $this->manager->getRepository(Module::class)->find(8);
        $wallet = $this->walletService->discoverWallet($company, 'Cielo');
        $this->addConfig(
            $company,
            'pos-cielo-wallet',
            $wallet->getId(),
            $module,
            'private'
        );

        foreach ($paymentTypes as $paymentType)
            $this->walletService->discoverWalletPaymentType(
                $wallet,
                $this->walletService->discoverPaymentType($company, $paymentType),
                $paymentType['paymentCode']
            );
    }
}
