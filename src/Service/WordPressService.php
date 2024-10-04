<?php

namespace ControleOnline\Service;

use GuzzleHttp\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class WordPressService
{

  public function __construct(private EntityManagerInterface $manager, private RequestStack $requestStack) {}

  public static function getAllPosts($domain, array $input): array
  {

    try {
      $client   = new Client(['verify' => false]);
      $response = $client->get($domain . '/wp-json/wp/v2/posts', [
        'query' => $input
      ]);

      $result   = json_decode($response->getBody());

      return $result;
    } catch (\Exception $e) {
      print_r($e);
    }

    return null;
  }
}
