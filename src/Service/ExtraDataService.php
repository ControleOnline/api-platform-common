<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\ExtraData;
use ControleOnline\Entity\ExtraFields;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
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
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    private function getUserIp()
    {
        return $this->request->getClientIp();
    }

    public function discoveryDevice(&$entity)
    {
        $device = $this->request->headers->get('DEVICE') ?: $this->getUserIp();

        if (method_exists($entity, 'setDevice')) {
            $entity->setDevice($device);
        }
    }

    public function discoveryUser(&$entity)
    {
        if (method_exists($entity, 'setUser')) {
            $entity->setUser($this->security->getUser());
        }
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
    private function persistData(&$entity = null)
    {

        if ($entity) {
            $entity_id = $entity->getId();
            $entity_name = (new \ReflectionClass($entity::class))->getShortName();

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
