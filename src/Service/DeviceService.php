<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;

class DeviceService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ConfigService $configService,
    ) {}

    public function getPrinters(People $people)
    {
        $device_configs = $this->manager->getRepository(DeviceConfig::class)->findBy([
            'people' => $people
        ]);
        $devices = [];
        foreach ($device_configs as $device_config) {
            $configs = $device_config->getConfigs(true);
            if (isset($configs['pos-gateway']) && $configs['pos-gateway'] == 'cielo')
                $devices[] = $device_config->getDevice();
        }
        return $devices;
    }

    public function findDevices(array|string $devices)
    {
        return $this->manager->getRepository(Device::class)->findBy([
            'device' => $devices
        ]);
    }

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


    public function discoveryDeviceConfig(Device $device, People $people): DeviceConfig
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
}
