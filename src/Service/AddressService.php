<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Address;
use ControleOnline\Entity\Cep;
use ControleOnline\Entity\City;
use ControleOnline\Entity\Country;
use ControleOnline\Entity\District;
use ControleOnline\Entity\People;
use ControleOnline\Entity\State;
use ControleOnline\Entity\Street;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use Exception;

class AddressService
{


  public function __construct(
    private  EntityManagerInterface $manager
  ) {}

  public function discoveryAddress(
    ?People $people = null,
    int $postalCode,
    int $streetNumber,
    string $streetName,
    string $district,
    string $city,
    string $uf,
    string $countryCode,
    ?string $complement = null,
    ?int $latitude = 0,
    ?int $longitude = 0,
    ?string $nickName = 'Default',
  ): Address {

    $cep = ($postalCode) ? $this->discoveryCep($postalCode) : null;
    $country = ($countryCode) ? $this->getCountry($countryCode) : null;
    $state = ($uf && $country) ? $this->discoveryState($country, $uf) : null;
    $city = ($city && $state) ? $this->discoveryCity($state, $city) : null;
    $district = ($district && $city) ? $this->discoveryDistrict($city, $district) : null;
    $street = ($streetName && $cep && $district) ? $this->discoveryStreet($cep, $district, $streetName) : null;

    $address =  $this->manager->getRepository(Address::class)->findOneBy([
      'people' => $people,
      'street' => $street,
      'number' => $streetNumber,
      'complement' => $complement
    ]);
    
    if (!$address) {
      $address = new Address();
      $address->setNumber($streetNumber);
      $address->setNickname($nickName);
      $address->setComplement($complement);
      $address->setStreet($street);
      $address->setPeople($people);
    }
    if ($latitude > 0) $address->setLatitude($latitude);
    if ($longitude > 0) $address->setLongitude($longitude);


    $this->manager->persist($address);
    $this->manager->flush();

    return  $address;
  }



  public function discoveryCep(string $postalCode): Cep
  {
    $cep = $this->manager->getRepository(Street::class)->findOneBy(['cep' => $postalCode]);

    if (!$cep) {
      $cep = new Cep();
      $cep->setCep($postalCode);
      $this->manager->persist($cep);
      $this->manager->flush();
    }

    return $cep;
  }
  public function discoveryStreet(Cep $cep, District $district, string $streetName): Street
  {
    $search = [
      'cep' => $cep,
      'district' => $district,
      'street' => $streetName
    ];
    $street =  $this->manager->getRepository(Street::class)->findOneBy($search);

    if (!$street) {
      $street = new Street();
      $street->setCep($cep);
      $street->setDistrict($district);
      $street->setStreet($streetName);
      $this->manager->persist($street);
      $this->manager->flush();
    }
    return  $street;
  }

  public function discoveryDistrict(City $city, string $districtName): District
  {
    $search = [
      'city' => $city
    ];

    $search['district'] = $districtName;

    $district =  $this->manager->getRepository(District::class)->findOneBy($search);

    if (!$district) {
      $district = new District();
      $district->setCity($city);
      $district->setDistrict($districtName);
      $this->manager->persist($district);
      $this->manager->flush();
    }
    return  $district;
  }
  public function discoveryCity(State $state, ?string $cityName = null, ?string $cod_ibge = null): City
  {
    if (!$cityName && !$cod_ibge)
      throw new Exception("Need a param to search city", 404);

    $search = [
      'state' => $state
    ];
    if ($state)
      $search['city'] = $cityName;
    if ($cod_ibge)
      $search['cod_ibge'] = $cod_ibge;

    $city =  $this->manager->getRepository(City::class)->findOneBy($search);

    if (!$city) {
      $city = new City();
      $city->setCity($cityName);
      $city->setState($state);
      $city->setIbge($cod_ibge);
      $this->manager->persist($city);
      $this->manager->flush();
    }
    return  $city;
  }
  public function discoveryState(Country $country, ?string $uf = null, ?string $stateName = null, ?string $cod_ibge = null): State
  {
    if (!$uf && !$stateName && !$cod_ibge)
      throw new Exception("Need a param to search state", 404);

    $search = [
      'country' => $country
    ];
    if ($stateName)
      $search['state'] = $stateName;
    if ($cod_ibge)
      $search['cod_ibge'] = $cod_ibge;
    if ($uf)
      $search['uf'] = $uf;

    $state = $this->manager->getRepository(State::class)->findOneBy($search);


    if (!$state) {
      $state = new State();
      $state->setState($stateName);
      $state->setIbge($cod_ibge);
      $state->setUf($uf);
      $state->setCountry($country);
      $this->manager->persist($state);
      $this->manager->flush();
    }
    return  $state;
  }

  public function getCountry(string $countryCode): Country
  {
    return $this->manager->getRepository(Country::class)->findOneBy(['countryCode' => $countryCode]);
  }
}
