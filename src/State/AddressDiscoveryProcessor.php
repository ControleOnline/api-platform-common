<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Service\AddressService;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AddressDiscoveryProcessor implements ProcessorInterface
{
    public function __construct(
        private AddressService $addressService,
        private EntityManagerInterface $manager
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $input = $context['request']->toArray();

        $people = null;
        if (!empty($input['people'])) {
            $people = $this->manager
                ->getRepository(People::class)
                ->find($input['people']);
        }

        return $this->addressService->discoveryAddress(
            $people,
            $input['postalCode'] ?? throw new BadRequestHttpException('postalCode required'),
            $input['streetNumber'] ?? throw new BadRequestHttpException('streetNumber required'),
            $input['streetName'] ?? throw new BadRequestHttpException('streetName required'),
            $input['district'] ?? throw new BadRequestHttpException('district required'),
            $input['city'] ?? throw new BadRequestHttpException('city required'),
            $input['uf'] ?? throw new BadRequestHttpException('uf required'),
            $input['countryCode'] ?? throw new BadRequestHttpException('countryCode required'),
            $input['complement'] ?? null,
            $input['latitude'] ?? 0,
            $input['longitude'] ?? 0,
            $input['nickName'] ?? 'Default'
        );
    }
}