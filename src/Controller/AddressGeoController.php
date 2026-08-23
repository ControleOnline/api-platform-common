<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Country;
use ControleOnline\Entity\State;
use ControleOnline\Library\Postalcode\Exception\InvalidParameterException;
use ControleOnline\Library\Postalcode\Exception\ProviderRequestException;
use ControleOnline\Library\Postalcode\PostalcodeProviderBalancer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class AddressGeoController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route(path: '/postal-codes/{cep}', name: 'lookup_postal_code', methods: ['GET'])]
    public function lookupPostalCode(string $cep): JsonResponse
    {
        $digits = preg_replace('/\D+/', '', $cep) ?? '';
        if (strlen($digits) !== 8) {
            return new JsonResponse([
                'title' => 'Invalid CEP',
                'detail' => 'CEP must have exactly 8 digits',
                'status' => 400,
                'type' => 'invalid_cep',
            ], 400);
        }

        try {
            $balancer = new PostalcodeProviderBalancer();
            $address = $balancer->search($digits);
            $provider = $balancer->getProviderCodeName();
            if (method_exists($address, 'setProvider') && !$address->getProvider()) {
                $address->setProvider($provider);
            }

            $payload = method_exists($address, 'toArray') ? $address->toArray() : [];
            $normalized = [
                'cep' => $digits,
                'street' => (string) ($payload['street'] ?? ''),
                'district' => (string) ($payload['district'] ?? ''),
                'city' => (string) ($payload['city'] ?? ''),
                'state' => (string) ($payload['state'] ?? ($payload['uf'] ?? '')),
                'uf' => (string) ($payload['uf'] ?? ($payload['state'] ?? '')),
                'country' => (string) ($payload['country'] ?? 'Brasil'),
                'number' => (string) ($payload['number'] ?? ''),
                'complement' => (string) ($payload['complement'] ?? ''),
                'latitude' => $payload['latitude'] ?? null,
                'longitude' => $payload['longitude'] ?? null,
                'provider' => $payload['provider'] ?? $provider,
                'formatted' => $payload['formatted'] ?? null,
            ];

            // Geocode with FULL address text (Nominatim does not work well with CEP-only).
            $normalized = $this->enrichCoordinatesFromNominatim($normalized, $digits);

            $lat = $normalized['latitude'] ?? null;
            $lng = $normalized['longitude'] ?? null;
            $gmapsKey = $_ENV['GMAPS_KEY'] ?? getenv('GMAPS_KEY') ?: null;
            $normalized['map'] = null;
            $normalized['facade'] = null;
            $normalized['hasMapsKey'] = (bool) $gmapsKey;

            if ($this->isValidCoord($lat) && $this->isValidCoord($lng)) {
                $normalized['map'] = [
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lng,
                ];
                if ($gmapsKey) {
                    $normalized['map']['staticUrl'] = sprintf(
                        'https://maps.googleapis.com/maps/api/staticmap?center=%s,%s&zoom=16&size=640x360&maptype=roadmap&markers=color:red%%7C%s,%s&key=%s',
                        $lat, $lng, $lat, $lng, $gmapsKey
                    );
                    $normalized['facade'] = [
                        'streetViewUrl' => sprintf(
                            'https://maps.googleapis.com/maps/api/streetview?size=640x360&location=%s,%s&fov=80&heading=0&pitch=0&key=%s',
                            $lat, $lng, $gmapsKey
                        ),
                    ];
                }
            }

            return new JsonResponse($normalized, 200);
        } catch (InvalidParameterException $e) {
            return new JsonResponse([
                'title' => 'Invalid CEP',
                'detail' => $e->getMessage(),
                'status' => 400,
                'type' => 'invalid_cep',
            ], 400);
        } catch (\Throwable $e) {
            // Preserve balancer-style messages when available
            $detail = $e->getMessage() ?: 'Postal code lookup failed';
            if (str_contains(strtolower($detail), 'not available') || $e instanceof ProviderRequestException) {
                return new JsonResponse([
                    'title' => 'Postal code lookup failed',
                    'detail' => $detail,
                    'status' => 502,
                    'type' => 'provider_unavailable',
                ], 502);
            }
            if (str_contains(strtolower($detail), 'not found')) {
                return new JsonResponse([
                    'title' => 'CEP not found',
                    'detail' => $detail,
                    'status' => 404,
                    'type' => 'not_found',
                ], 404);
            }
            return new JsonResponse([
                'title' => 'Postal code lookup failed',
                'detail' => 'Unexpected error during CEP lookup',
                'status' => 502,
                'type' => 'lookup_failed',
            ], 502);
        }
    }

    /**
     * Resolve lat/lng via Nominatim using the FULL address string.
     * CEP-only queries are unreliable; always send street + district + city + UF + CEP + country.
     */
    private function enrichCoordinatesFromNominatim(array $payload, string $cepDigits): array
    {
        if ($this->isValidCoord($payload['latitude'] ?? null) && $this->isValidCoord($payload['longitude'] ?? null)) {
            return $payload;
        }

        $query = $this->buildFullAddressQuery($payload, $cepDigits);
        if ($query === '') {
            return $payload;
        }

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 8,
                'connect_timeout' => 4,
                'headers' => [
                    'User-Agent' => 'ControleOnline-AddressGeo/1.0 (app-community#430; nominatim-geocode)',
                    'Accept' => 'application/json',
                ],
            ]);
            $response = $client->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'format' => 'json',
                    'limit' => 1,
                    'q' => $query,
                ],
            ]);
            $data = json_decode((string) $response->getBody(), true);
            if (is_array($data) && !empty($data[0]['lat']) && !empty($data[0]['lon'])) {
                $payload['latitude'] = (float) $data[0]['lat'];
                $payload['longitude'] = (float) $data[0]['lon'];
                $payload['provider'] = ($payload['provider'] ?? 'cep') . '+nominatim';
                if (!empty($data[0]['display_name'])) {
                    $payload['formatted'] = (string) $data[0]['display_name'];
                }
            }
        } catch (\Throwable $e) {
            // Keep CEP text fields; coordinates stay null
        }

        return $payload;
    }

    /**
     * Example: "Rua Antônio Bonini, Vila Santista, Atibaia, SP, 12941-040, Brasil"
     */
    private function buildFullAddressQuery(array $payload, string $cepDigits): string
    {
        $cepFormatted = strlen($cepDigits) === 8
            ? substr($cepDigits, 0, 5) . '-' . substr($cepDigits, 5)
            : $cepDigits;

        $parts = array_filter([
            trim((string) ($payload['street'] ?? '')),
            trim((string) ($payload['district'] ?? '')),
            trim((string) ($payload['city'] ?? '')),
            trim((string) ($payload['uf'] ?? $payload['state'] ?? '')),
            $cepFormatted,
            'Brasil',
        ], static fn ($v) => $v !== '');

        return implode(', ', $parts);
    }

    private function isValidCoord($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        if (!is_numeric($value)) {
            return false;
        }
        $n = (float) $value;
        // 0,0 is the entity default "empty" — treat as missing
        return abs($n) > 0.000001;
    }

    #[Route(path: '/address-geo/countries', name: 'address_geo_countries', methods: ['GET'])]
    public function countries(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $qb = $this->em->createQueryBuilder()
            ->select('c.id, c.countrycode AS code, c.countryname AS name')
            ->from(Country::class, 'c')
            ->orderBy('c.countryname', 'ASC');
        if ($q !== '') {
            $qb->andWhere('c.countryname LIKE :q OR c.countrycode LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        $items = $qb->getQuery()->getArrayResult();
        return new JsonResponse(['member' => $items, 'totalItems' => count($items)]);
    }

    #[Route(path: '/address-geo/states', name: 'address_geo_states', methods: ['GET'])]
    public function states(Request $request): JsonResponse
    {
        $country = trim((string) $request->query->get('country', 'BR'));
        $qb = $this->em->createQueryBuilder()
            ->select('s.id, s.state AS name, s.uf AS uf, c.countrycode AS countryCode')
            ->from(State::class, 's')
            ->join('s.country', 'c')
            ->orderBy('s.state', 'ASC');
        if ($country !== '') {
            $qb->andWhere('c.countrycode = :cc OR c.isoalpha3 = :cc')
                ->setParameter('cc', strtoupper($country));
        }
        $items = $qb->getQuery()->getArrayResult();
        return new JsonResponse(['member' => $items, 'totalItems' => count($items)]);
    }
}
