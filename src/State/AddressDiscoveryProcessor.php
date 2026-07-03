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

        $postalCode = preg_replace('/\D+/', '', (string) ($input['cep'] ?? ''));

        if ($postalCode === '') {
            throw new BadRequestHttpException('cep required');
        }

        $latitude = array_key_exists('latitude', $input) && $input['latitude'] !== null && $input['latitude'] !== ''
            ? (float) $input['latitude']
            : null;
        $longitude = array_key_exists('longitude', $input) && $input['longitude'] !== null && $input['longitude'] !== ''
            ? (float) $input['longitude']
            : null;

        return $this->addressService->discoveryAddress(
            $postalCode,
            $input['number'] ?? throw new BadRequestHttpException('number required'),
            $input['street'] ?? throw new BadRequestHttpException('street required'),
            $input['district'] ?? throw new BadRequestHttpException('district required'),
            $input['city'] ?? throw new BadRequestHttpException('city required'),
            $input['state'] ?? throw new BadRequestHttpException('state required'),
            $input['country'] ?? throw new BadRequestHttpException('country required'),
            $people,
            $input['complement'] ?? null,
            $latitude,
            $longitude,
            $input['nickname'] ?? 'Default'
        );
    }
}
