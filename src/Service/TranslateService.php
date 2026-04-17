<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\Translate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TranslateService
{
    public function __construct(private EntityManagerInterface $manager) {}

    public function createFromContent(?string $content): Translate
    {
        return $this->createFromPayload($this->decodePayload($content));
    }

    public function createFromPayload(array $payload): Translate
    {
        if ($payload === []) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        foreach (['key', 'language', 'people', 'store', 'type', 'translate'] as $field) {
            if (!isset($payload[$field])) {
                throw new BadRequestHttpException("Field '{$field}' is required");
            }
        }

        $people = $this->manager->getRepository(People::class)->find(
            $this->normalizeReferenceId($payload['people'])
        );
        if (!$people instanceof People) {
            throw new BadRequestHttpException('People not found');
        }

        $language = $this->manager->getRepository(Language::class)->find(
            $this->normalizeReferenceId($payload['language'])
        );
        if (!$language instanceof Language) {
            throw new BadRequestHttpException('Language not found');
        }

        $existing = $this->manager->getRepository(Translate::class)->findOneBy([
            'key' => $payload['key'],
            'language' => $language,
            'people' => $people,
            'store' => $payload['store'],
            'type' => $payload['type'],
        ]);

        if ($existing instanceof Translate) {
            return $existing;
        }

        $translate = new Translate();
        $translate->setKey($payload['key']);
        $translate->setLanguage($language);
        $translate->setPeople($people);
        $translate->setStore($payload['store']);
        $translate->setType($payload['type']);
        $translate->setTranslate($payload['translate']);

        $this->manager->persist($translate);
        $this->manager->flush();

        return $translate;
    }

    private function decodePayload(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeReferenceId(mixed $reference): string
    {
        return preg_replace('/\D/', '', (string) $reference);
    }
}
