<?php

namespace ControleOnline\Service;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\RequestStack;


use Doctrine\ORM\EntityManagerInterface;


class HydratorService
{
    /**
     * Entity Manager
     *
     * @var EntityManagerInterface
     */
    private $manager = null;

    /**
     * Security
     *
     * @var Security
     */
    private $security = null;
    private $serializer;
    private $request;
    private $uri;

    public function __construct(
        Security $security,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        RequestStack $requestStack
    ) {
        $this->security = $security;
        $this->manager = $entityManager;
        $this->serializer = $serializer;
        $this->request = $requestStack->getCurrentRequest();
        $this->uri = $this->request ? $this->request->getPathInfo() : '';
    }

    public function collection($class, $groups,  mixed $arguments = [], int $limit = 0, int $page = 1, array $orderby = [])
    {
        $response = $this->getBasicResponse($class);

        $response['hydra:member']      =   $this->getMembers($class, $groups, $arguments, $limit, $page, $orderby);
        $response['hydra:search']       =   $this->getSearch($class);
        $response['hydra:totalItems']   =   $this->getCount($class, $arguments);

        return $response;
    }

    public function result($result)
    {
        //$response = $this->getBasicResponse($class);
        //$response['hydra:search']       =   $this->getSearch($class);

        $response['hydra:member']      =  $result;
        $response['hydra:totalItems']   =   count($response['hydra:member']);

        return $response;
    }

    public function item($class, $id, $groups)
    {
        $data =     $this->manager->getRepository($class)->find(preg_replace("/[^0-9]/", "", $id));
        $analisesSerialized = $this->serializer->serialize($data, 'jsonld', ['groups' => $groups]);
        return json_decode($analisesSerialized);
    }

    private function getBasicResponse($class)
    {
        $className = substr($class, strrpos($class, '\\') + 1);

        $response['@id']        = '/' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
        $response['@context']   =   "/contexts/" . $className;
        $response['@type']      =   "hydra:Collection";

        $response['hydra:view'] = [
            '@id' =>   $this->uri,
            '@type' => 'hydra:PartialCollectionView'
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
            '@type' => 'hydra:IriTemplate',
            'hydra:template' =>   $this->uri . '{?' . implode(',', array_values($arguments)) . '}',
            'hydra:variableRepresentation' => 'BasicRepresentation',
            'hydra:mapping' => []
        ];

        foreach ($metadata->getFieldNames() as $field) {

            $search['hydra:mapping'][] = [
                '@type' => 'IriTemplateMapping',
                'variable' => $field,
                'property' => $field,
                'required' => false
            ];
            $search['hydra:mapping'][] = [
                '@type' => 'IriTemplateMapping',
                'variable' => $field . '[]',
                'property' => $field,
                'required' => false
            ];
        }

        return $search;
    }
}
