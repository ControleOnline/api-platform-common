<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Translate;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Language;
use ControleOnline\Service\HydratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CreateTranslateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private HydratorService $hydrator
    ) {}

    public function __invoke(Request $request): Response
    {
        try {

            $data = json_decode($request->getContent(), true);

            if (!$data) {
                throw new BadRequestHttpException('Invalid JSON');
            }

            foreach (['key', 'language', 'people', 'store', 'type', 'translate'] as $field) {
                if (!isset($data[$field])) {
                    throw new BadRequestHttpException("Field '{$field}' is required");
                }
            }

            $peopleId = preg_replace('/\D/', '', $data['people']);
            $languageId = preg_replace('/\D/', '', $data['language']);

            $people = $this->em->getRepository(People::class)->find($peopleId);
            $language = $this->em->getRepository(Language::class)->find($languageId);

            if (!$people) {
                throw new BadRequestHttpException('People not found');
            }

            if (!$language) {
                throw new BadRequestHttpException('Language not found');
            }

            $repo = $this->em->getRepository(Translate::class);

            $existing = $repo->findOneBy([
                'key' => $data['key'],
                'language' => $language,
                'people' => $people,
                'store' => $data['store'],
                'type' => $data['type']
            ]);

            if ($existing) {
                $result = $existing;
            } else {

                $translate = new Translate();
                $translate->setKey($data['key']);
                $translate->setLanguage($language);
                $translate->setPeople($people);
                $translate->setStore($data['store']);
                $translate->setType($data['type']);
                $translate->setTranslate($data['translate']);

                $this->em->persist($translate);
                $this->em->flush();

                $result = $translate;
            }

            return new Response(
                json_encode(
                    $this->hydrator->data($result, ['translate:read'])
                ),
                200,
                ['Content-Type' => 'application/ld+json']
            );
        } catch (\Exception $e) {

            return new Response(
                json_encode($this->hydrator->error($e)),
                400,
                ['Content-Type' => 'application/ld+json']
            );
        }
    }
}
