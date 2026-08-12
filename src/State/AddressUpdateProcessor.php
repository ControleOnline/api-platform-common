<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\Address;
use ControleOnline\Entity\People;
use ControleOnline\Service\AddressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Applies scalar updates to an existing Address without re-deserializing relation IRIs.
 */
class AddressUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $manager,
        private AddressService $addressService
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Address
    {
        $id = $uriVariables['id'] ?? null;
        if ($id === null) {
            throw new NotFoundHttpException('Address id required');
        }

        /** @var Address|null $address */
        $address = $this->manager->getRepository(Address::class)->find($id);
        if (!$address) {
            throw new NotFoundHttpException(sprintf('Address %s not found', $id));
        }

        if (!$data instanceof AddressUpdateInput) {
            // Fallback: read raw body
            $request = $context['request'] ?? null;
            $payload = $request ? $request->toArray() : [];
            $data = new AddressUpdateInput();
            $data->nickname = $payload['nickname'] ?? null;
            $data->number = $payload['number'] ?? null;
            $data->complement = $payload['complement'] ?? null;
            $data->people = $payload['people'] ?? null;
            $data->street = $payload['street'] ?? null;
            $data->city = $payload['city'] ?? null;
            $data->district = $payload['district'] ?? null;
            $data->state = $payload['state'] ?? null;
            $data->country = $payload['country'] ?? null;
            $data->cep = $payload['cep'] ?? null;
            $data->latitude = $payload['latitude'] ?? null;
            $data->longitude = $payload['longitude'] ?? null;
        }

        if ($this->hasFullTextualAddress($data)) {
            $this->applyTextualAddress($address, $data);
        } else {
            $this->applyScalarFields($address, $data);
        }

        $this->manager->persist($address);
        $this->manager->flush();

        return $address;
    }

    private function applyScalarFields(Address $address, AddressUpdateInput $data): void
    {
        if ($data->nickname !== null && $data->nickname !== '') {
            $address->setNickname($data->nickname);
        }
        if ($data->number !== null && $data->number !== '') {
            $address->setNumber((int) $data->number);
        }
        if ($data->complement !== null) {
            $address->setComplement($data->complement);
        }
    }

    private function hasFullTextualAddress(AddressUpdateInput $data): bool
    {
        foreach (['cep', 'street', 'district', 'city', 'state', 'country'] as $field) {
            if (trim((string) $data->{$field}) === '') {
                return false;
            }
        }

        return true;
    }

    private function applyTextualAddress(Address $address, AddressUpdateInput $data): void
    {
        $country = $this->addressService->getCountry((string) $data->country);
        $state = $this->addressService->discoveryState($country, (string) $data->state);
        $city = $this->addressService->discoveryCity($state, (string) $data->city);
        $district = $this->addressService->discoveryDistrict($city, (string) $data->district);
        $cep = $this->addressService->discoveryCep(preg_replace('/\D+/', '', (string) $data->cep));
        $street = $this->addressService->discoveryStreet($cep, $district, (string) $data->street);

        $address->setStreet($street);
        $address->setPeople($this->resolvePeople($data) ?? $address->getPeople());
        $this->applyScalarFields($address, $data);

        if ($data->latitude !== null && $data->latitude !== '') {
            $address->setLatitude((float) $data->latitude);
        }

        if ($data->longitude !== null && $data->longitude !== '') {
            $address->setLongitude((float) $data->longitude);
        }
    }

    private function resolvePeople(AddressUpdateInput $data): ?People
    {
        if (!$data->people) {
            return null;
        }

        $peopleId = (int) str_replace('/people/', '', $data->people);
        if ($peopleId <= 0) {
            return null;
        }

        return $this->manager->getRepository(People::class)->find($peopleId);
    }
}
