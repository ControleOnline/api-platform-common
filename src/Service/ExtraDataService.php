<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Device;
use ControleOnline\Entity\DeviceConfig;
use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\ExtraFields;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;

class ExtraDataService
{
    private static $persisted = false;
    private $request;
    public function __construct(
        private EntityManagerInterface $manager,
        private RequestStack $requestStack,
        private Security $security,
        private DeviceService $deviceService,
        private SkyNetService $skyNetService

    ) {
        $this->request = $requestStack->getCurrentRequest();
    }


    public function getByExtraFieldByEntity(ExtraFields $extraFields, object $entity)
    {
        $class = $this->getEntityName($entity);
        return $this->manager->getRepository(ExtraData::class)->findOneBy([
            'extra_fields' => $extraFields,
            'entity_name' => $class->getShortName(),
            'entity_id' => $entity->getId()
        ]);
    }

    public function getExtraDataFromEntity(object $entity)
    {
        $class = $this->getEntityName($entity);
        return $this->manager->getRepository(ExtraData::class)->findOneBy([
            'entity_id' => $entity->getId(),
            'entity_name' => $class->getShortName(),
        ]);
    }

    public function getEntityByExtraData(string $context, string $fieldName, string $code, object|string $entity)
    {
        $class = $this->getEntityName($entity);
        $extraFields = $this->discoveryExtraFields($fieldName, $context, '{}');

        $extraData = $this->manager->getRepository(ExtraData::class)->findOneBy([
            'extra_fields' => $extraFields,
            'entity_name' => $class->getShortName(),
            'value' => $code
        ]);

        if ($extraData)
            return $this->manager->getRepository($class->getName())->find($extraData->getEntityId());

        return null;
    }

    public function discoveryExtraData(object $entity, string $context, string $fieldName, string $code)
    {
        $class = $this->getEntityName($entity);
        $extraData = $this->getEntityByExtraData($context, $fieldName, $code, $entity);
        $extraFields = $this->discoveryExtraFields($fieldName, $context, '{}');

        if ($extraData) return $extraData;

        $extraData = new ExtraData();
        $extraData->setEntityId($entity->getId());
        $extraData->setExtraFields($extraFields);
        $extraData->setValue($code);
        $extraData->setEntityName($class->getShortName());
        $this->manager->persist($extraData);
        $this->manager->flush();

        return $this->manager->getRepository($class->getName())->find($extraData->getEntityId());
    }

    public function discoveryExtraFields(string $fieldName, string $context, ?string $configs = '{}', ?string $fieldType = 'text', ?bool $required = false): ExtraFields
    {
        $extraFields = $this->manager->getRepository(ExtraFields::class)->findOneBy([
            'name' => $fieldName,
            'type' => $fieldType,
            'context' => $context
        ]);

        if (!$extraFields) {
            $extraFields = new ExtraFields();
            $extraFields->setName($fieldName);
            $extraFields->setContext($context);
            $extraFields->setConfigs($configs);
            $extraFields->setType($fieldType);
            $extraFields->setRequired($required);
            $this->manager->persist($extraFields);
            $this->manager->flush();
        }

        return $extraFields;
    }

    private function getUserIp()
    {
        return $this->request->getClientIp();
    }

    public function discoveryDevice(&$entity)
    {
        if ($entity instanceof Device || $entity instanceof DeviceConfig || !$this->request->headers)
            return;

        $deviceId = $this->request->headers->get('DEVICE') ?: $this->getUserIp();
        if (method_exists($entity, 'setDevice')) {
            if ($entity->getDevice()) return;
            $device = $this->deviceService->discoveryDevice($deviceId);
            $entity->setDevice($device);
        }
    }

    public function discoveryUser(&$entity)
    {
        $token = $this->security->getToken();
        $user = $token ? $token->getUser() : $this->skyNetService->getBotUser();

        if (method_exists($entity, 'setUser') && !$entity->getUser())
            $entity->setUser($user);
    }

    public function persist(&$entity)
    {
        if (self::$persisted == true)
            return;
        self::$persisted = true;

        //$this->manager->persist($entity);
        //$this->manager->flush();
        $this->persistData($entity);
    }

    private function getEntityName(object | string $entity): \ReflectionClass
    {
        return (new \ReflectionClass($entity));
    }

    private function persistData(&$entity = null)
    {

        if ($entity) {
            $entity_id = $entity->getId();
            $entity_name = $this->getEntityName($entity)->getShortName();

            //$this->manager->persist($entity);
        } else {
            $json =       json_decode($this->request->getContent(), true);
            $extra_data = isset($json['extra-data']) ? $json['extra-data'] : null;
            if (!$extra_data)
                return;
            $entity_id = $extra_data['entity_id'];
            $entity_name = $extra_data['entity_name'];
        }


        if (!$entity_id || !$entity_name)
            return;

        if (!isset($extra_data) || !isset($extra_data['data']))
            return;

        foreach ($extra_data['data'] as $key => $data) {
            $extra_fields = $this->manager->getRepository(ExtraFields::class)->find($key);

            $extraData = $this->manager->getRepository(ExtraData::class)->findOneBy([
                'entity_id' => $entity_id,
                'entity_name' => $entity_name,
                'extra_fields' => $extra_fields
            ]);

            if (!$extraData)
                $extraData = new ExtraData();

            $extraData->setExtraFields($extra_fields);
            $extraData->setEntityName($entity_name);
            $extraData->setEntityId($entity_id);
            $extraData->setValue($data);
            $this->manager->persist($extraData);
        }


        $this->manager->flush();
    }

    public function  noChange()
    {
        $this->persistData();
    }
}
