<?php

namespace ControleOnline\Controller;

use ControleOnline\Entity\Translate;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CreateTranslateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function __invoke(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $peopleId = preg_replace('/\D/', '', $data['people'] ?? '');
        $languageId = preg_replace('/\D/', '', $data['language'] ?? '');

        $people = $this->em->getRepository(People::class)->find($peopleId);
        $language = $this->em->getRepository(Language::class)->find($languageId);

        if (!$people || !$language) {
            throw new BadRequestHttpException('People or Language not found');
        }

        $existing = $this->em->getRepository(Translate::class)->findOneBy([
            'key' => $data['key'] ?? null,
            'language' => $language,
            'people' => $people,
            'store' => $data['store'] ?? null,
            'type' => $data['type'] ?? null,
        ]);

        if ($existing) {
            return $this->json($existing);
        }

        $translate = new Translate();
        $translate->setKey($data['key']);
        $translate->setLanguage($language);
        $translate->setPeople($people);
        $translate->setStore($data['store']);
        $translate->setType($data['type']);
        $translate->setTranslate($data['translate']);

        $this->em->persist($translate);
        $this->em->flush();

        return $this->json($translate);
    }
}
