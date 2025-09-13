<?php

namespace ControleOnline\Service;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
as Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\RequestStack;


use Doctrine\ORM\EntityManagerInterface;
use Exception;

class HydratorService
{

    private $request;
    private $uri;

    public function __construct(
        private  EntityManagerInterface $manager,
        private   SerializerInterface $serializer,
        RequestStack $requestStack
    ) {

        $this->serializer = $serializer;
        $this->request = $requestStack->getCurrentRequest();
        $this->uri = $this->request ? $this->request->getPathInfo() : '';
    }
    public function error(Exception $e)
    {
        $errorResponse = [
            '@context' => '/contexts/Error',
            '@type' => 'Error',
            'hydra:title' => 'An error occurred',
            'hydra:description' => $e->getMessage(),
            'trace' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ],
        ];

        return $errorResponse;
    }

    public function collectionData($data, $class, $groups,  mixed $arguments = [])
    {
        $response = $this->getBasicResponse($class);

        $response['member']      =   $this->data($data, $groups);
        $response['search']       =   $this->getSearch($class);
        $response['totalItems']   =   $this->getCount($class, $arguments);

        return $response;
    }
    public function collection($class, $groups,  mixed $arguments = [], int $limit = 0, int $page = 1, array $orderby = [])
    {
        $response = $this->getBasicResponse($class);

        $response['member']      =   $this->getMembers($class, $groups, $arguments, $limit, $page, $orderby);
        $response['search']       =   $this->getSearch($class);
        $response['totalItems']   =   $this->getCount($class, $arguments);

        return $response;
    }

    public function result($result)
    {
        //$response = $this->getBasicResponse($class);
        //$response['search']       =   $this->getSearch($class);

        $response['member']      =  $result;
        $response['totalItems']   =   count($response['member']);

        return $response;
    }

    public function data($data, $groups)
    {
        $analisesSerialized = $this->serializer->serialize($data, 'jsonld', ['groups' => $groups]);
        return json_decode($analisesSerialized);
    }


    public function item($class, $id, $groups)
    {
        $data = $this->manager->getRepository($class)->find(preg_replace("/[^0-9]/", "", $id));
        return $this->data($data, $groups);
    }

    private function getBasicResponse($class)
    {
        $className = substr($class, strrpos($class, '\\') + 1);

        $response['@id']        = '/' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
        $response['@context']   =   "/contexts/" . $className;
        $response['@type']      =   "Collection";

        $response['view'] = [
            '@id' =>   $this->uri,
            '@type' => 'PartialCollectionView'
        ];
        return $response;
    }

    private function getMembers($class, $groups, mixed $arguments = [], int $limit = 0, int $page = 1, array $orderby = [])
    {

        if ($limit < 1)
            $limit = $this->request->get('itemsPerPage') ?: 50;

        if ($page == 1)
            $offset = (($page = $this->request->get('page') ?: 1) - 1) * $limit;

        $data =     $this->manager->getRepository($class)->findBy($arguments, $orderby, $limit, $offset);

        return $this->serialize($data, ['groups' => $groups]);
    }

    private function serialize($data, array $groups = [])
    {
        $analisesSerialized = $this->serializer->serialize($data, 'jsonld', $groups);
        return json_decode($analisesSerialized);
    }


    private function getCount($class, $arguments)
    {
        return $this->manager->getRepository($class)->count($arguments);
    }

    private function getSearch($class)
    {

        $metadata = $this->manager->getClassMetadata($class);
        $arguments = $metadata->getFieldNames();
        $search = [
            '@type' => 'IriTemplate',
            'template' =>   $this->uri . '{?' . implode(',', array_values($arguments)) . '}',
            'variableRepresentation' => 'BasicRepresentation',
            'mapping' => []
        ];

        foreach ($metadata->getFieldNames() as $field) {

            $search['mapping'][] = [
                '@type' => 'IriTemplateMapping',
                'variable' => $field,
                'property' => $field,
                'required' => false
            ];
            $search['mapping'][] = [
                '@type' => 'IriTemplateMapping',
                'variable' => $field . '[]',
                'property' => $field,
                'required' => false
            ];
        }

        return $search;
    }
}
