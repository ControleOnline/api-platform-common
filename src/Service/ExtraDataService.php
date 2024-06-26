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
    private $request;
    public function __construct(
        private  EntityManagerInterface $manager,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }
    public function persist()
    {
        $extra_data = $this->request->get('extra-data');
        if (!$extra_data)
            return;
        foreach ($extra_data['entity_id']['data'] as $data) {
            $extra_fields = $this->manager->getRepository(ExtraFields::class)->find($data['id']);

            $extraData = $this->manager->getRepository(ExtraData::class)->findOneBy([
                'entity_id' => $extra_data['entity_id'],
                'entity_name' => $extra_data['entity_name'],
                'extra_fields' => $extra_fields
            ]);

            if (!$extraData)
                $extraData = new ExtraData();

            $extraData->setExtraFields($extra_fields);
            $extraData->setEntityName($extra_data['entity_name']);
            $extraData->setEntityId($extra_data['entity_id']);
            $extraData->setValue($extra_data['value']);
            $this->manager->persist($extraData);
            $this->manager->flush();
        }
    }
}
