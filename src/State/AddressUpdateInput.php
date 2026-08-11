<?php

namespace ControleOnline\State;

use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Scalar-focused input for PUT /addresses.
 * Textual relation fields from the client are ignored so they never hit IRI denormalization.
 */
final class AddressUpdateInput
{
    #[Groups(['address:write'])]
    public ?string $nickname = null;

    #[Groups(['address:write'])]
    public int|string|null $number = null;

    #[Groups(['address:write'])]
    public ?string $complement = null;

    #[Groups(['address:write'])]
    public ?string $people = null;

    // Accepted but ignored for IRI safety (frontend may still send them on edit)
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
    public ?string $cep = null;
}
