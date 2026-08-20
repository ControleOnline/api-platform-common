# Postal code (CEP) lookup

## Endpoint

`GET /postal-codes/{cep}`

- **Does not persist** any address or CEP entity.
- Accepts CEP with or without mask (`01310-100` or `01310100`).
- Normalized to 8 digits before provider calls.

## Response contract (200)

```json
{
  "cep": "01310100",
  "street": "Avenida Paulista",
  "district": "Bela Vista",
  "city": "São Paulo",
  "state": "São Paulo",
  "uf": "SP",
  "country": "Brasil",
  "number": "",
  "complement": "",
  "latitude": null,
  "longitude": null,
  "provider": "viacep",
  "formatted": null,
  "map": null,
  "facade": null,
  "hasMapsKey": false
}
```

Optional `map` / `facade` when `GMAPS_KEY` is configured and coordinates are available.

## Errors

| HTTP | type | When |
|------|------|------|
| 400 | `invalid_cep` | CEP does not have exactly 8 digits after normalization |
| 404 | `cep_not_found` | Valid format but no provider returned an address |
| 502 | `provider_unavailable` / `lookup_failed` | All external providers failed (timeout/network) |

Clients must allow manual address fill when 502 is returned.

## Providers (balancer)

Order: **Postmon → ViaCEP → Google Maps**. First successful response wins.

## Security

Same surface as other address helpers in this module. Parent apps apply `access_control` / firewall; the route itself does not create records and returns only public postal data for the requested CEP.

## Implementation

- Controller: `ControleOnline\Controller\AddressGeoController::lookupPostalCode`
- Balancer: `ControleOnline\Library\Postalcode\PostalcodeProviderBalancer`
- Entity DTO: `ControleOnline\Library\Postalcode\Entity\Address` (not Doctrine)

## Related

- Issue: ControleOnline/api-platform-common#14
- Persistence remains on `POST /addresses` (out of scope here)
