<?php

namespace ControleOnline\State;

use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Textual input used by the address discovery POST operation.
 *
 * The persisted Address entity keeps relational Street data, while this
 * input represents the values supplied by a client before discovery runs.
 */
final class AddressDiscoveryInput
{
    #[Groups(['address:write'])]
    public ?string $street = null;

    #[Groups(['address:write'])]
    public ?string $city = null;

    #[Groups(['address:write'])]
    public ?string $district = null;

    #[Groups(['address:write'])]
    public ?string $state = null;

    #[Groups(['address:write'])]
    public ?string $country = null;

    #[Groups(['address:write'])]
    public ?string $people = null;

    #[Groups(['address:write'])]
    public int|string|null $number = null;

    #[Groups(['address:write'])]
    public ?string $complement = null;

    #[Groups(['address:write'])]
    public ?string $nickname = null;

    #[Groups(['address:write'])]
    public ?string $cep = null;

    #[Groups(['address:write'])]
    public float|string|null $latitude = null;

    #[Groups(['address:write'])]
    public float|string|null $longitude = null;
}
