<?php

namespace ControleOnline\Serializer;

use ControleOnline\Entity\File;
use ControleOnline\Entity\People;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

/** Read file metadata without initializing binary content in operational responses. */
final class FileMetadataNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(private EntityManagerInterface $manager, private RequestStack $requestStack) {}

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        $groups = (array) ($context['groups'] ?? []);
        return $data instanceof File && empty($context['_file_metadata_projected'])
            && array_intersect($groups, ['category:read', 'product:read', 'order_details:read', 'order_product:read', 'order:write'])
            && !array_intersect($groups, ['*', 'file_item:read', 'file:write', 'spool_item:read'])
            && $this->manager->isUninitializedObject($data);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $id = $object->getId();
        $request = $this->requestStack->getCurrentRequest();
        $key = '_file_metadata_' . spl_object_id($this->manager) . '_' . $id;
        $file = $request?->attributes->get($key);
        if (!$file instanceof File) {
            $row = $this->manager->getConnection()->fetchAssociative(
                'SELECT id, file_type, file_name, context, extension, public, people_id FROM files WHERE id = ?', [$id],
            );
            if (!$row) throw new \RuntimeException('File metadata not found.');
            // A detached read snapshot: never modify/register a partially loaded managed entity.
            $metadata = $this->manager->getClassMetadata(File::class);
            $file = $metadata->newInstance();
            $metadata->setIdentifierValues($file, ['id' => (int) $row['id']]);
            $file->setFileType($row['file_type'])->setFileName($row['file_name'])
                ->setContext($row['context'])->setExtension($row['extension'])->setPublic((bool) $row['public']);
            if ($row['people_id']) $file->setPeople($this->manager->getReference(People::class, (int) $row['people_id']));
            $request?->attributes->set($key, $file);
        }
        $context['_file_metadata_projected'] = true;
        return $this->normalizer->normalize($file, $format, $context);
    }

    public function getSupportedTypes(?string $format): array { return [File::class => false]; }
}
