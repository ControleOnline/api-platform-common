<?php

namespace ControleOnline\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ControleOnline\Entity\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Applies scalar updates to an existing Address without re-deserializing relation IRIs.
 */
class AddressUpdateProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $manager)
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
        }

        if ($data->nickname !== null && $data->nickname !== '') {
            $address->setNickname($data->nickname);
        }
        if ($data->number !== null && $data->number !== '') {
            $address->setNumber((int) $data->number);
        }
        if ($data->complement !== null) {
            $address->setComplement($data->complement);
        }

        $this->manager->persist($address);
        $this->manager->flush();

        return $address;
    }
}
