<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\Module;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;

class DeviceService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private WalletService $walletService,
        private ConfigService $configService,
    ) {}


    public function discoveryDevice($deviceId)
    {
        $device = $this->manager->getRepository(Device::class)->findOneBy([
            'device' => $deviceId
        ]);
        if (!$device) {
            $device = new Device();
            $device->setDevice($deviceId);
            $this->manager->persist($device);
            $this->manager->flush();
        }
        return $device;
    }

    public function discoveryMainConfigs(Device $device, People $people)
    {
        $this->discoveryCashWallet($people);
        $this->discoveryWithdrawlWallet($people);
        $this->discoveryInfinitePayWallet($people);
        $this->discoveryCieloWallet($people);
    }

    public function discoveryDeviceConfig(Device $device, People $people)
    {
        $device_config = $this->manager->getRepository(DeviceConfig::class)->findOneBy([
            'device' => $device,
            'people' => $people
        ]);
        if (!$device_config) {
            $device_config = new DeviceConfig();
            $device_config->setDevice($device);
            $device_config->setPeople($people);
            $this->manager->persist($device_config);
        }

        return $device_config;
    }

    public function addDeviceConfigs(People $people, array $configs, $deviceId)
    {
        $device = $this->discoveryDevice($deviceId);

        $device_config = $this->discoveryDeviceConfig($device,  $people);
        foreach ($configs as $key => $config)
            $device_config->addConfig($key,  $config);

        $this->manager->persist($device_config);
        $this->manager->flush();

        return $device_config;
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
        $this->configService->addConfig(
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
        $this->configService->addConfig(
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
        $this->configService->addConfig(
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
        $this->configService->addConfig(
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
