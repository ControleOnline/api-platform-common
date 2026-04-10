<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Service\AddressService;
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
            $peopleId = $input['people'];


            $peopleId = (int) str_replace('/people/', '', $peopleId);

            $people = $this->manager
                ->getRepository(People::class)
                ->find($peopleId);
        }

        return $this->addressService->discoveryAddress(
            $people,
            $input['cep'] ?? throw new BadRequestHttpException('cep required'),
            $input['number'] ?? throw new BadRequestHttpException('number required'),
            $input['street'] ?? throw new BadRequestHttpException('street required'),
            $input['district'] ?? throw new BadRequestHttpException('district required'),
            $input['city'] ?? throw new BadRequestHttpException('city required'),
            $input['state'] ?? throw new BadRequestHttpException('state required'),
            $input['country'] ?? throw new BadRequestHttpException('country required'),
            $input['complement'] ?? null,
            $input['latitude'] ?? 0,
            $input['longitude'] ?? 0,
            $input['nickname'] ?? 'Default'
        );
    }
}
