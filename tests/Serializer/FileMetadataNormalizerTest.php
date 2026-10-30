<?php
namespace ControleOnline\Common\Tests\Serializer;

use ControleOnline\Entity\File;
use ControleOnline\Serializer\FileMetadataNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class FileMetadataNormalizerTest extends TestCase
{
    private function fixture(): array
    {
        $metadata = new ClassMetadata(File::class);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->initializeReflection(new RuntimeReflectionService());
        $metadata->wakeupReflection(new RuntimeReflectionService());
        $file = new File();
        $metadata->setIdentifierValues($file, ['id' => 42]);
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::exactly(2))->method('fetchAssociative')
            ->with(self::callback(fn ($sql) => !str_contains($sql, 'content')), [42])
            ->willReturn(['id' => 42, 'file_type' => 'image', 'file_name' => 'water', 'context' => 'products',
                'extension' => 'png', 'public' => 1, 'people_id' => null]);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')->willReturn($connection);
        $manager->method('getClassMetadata')->willReturn($metadata);
        $manager->method('isUninitializedObject')->willReturnCallback(fn ($value) => $value === $file);
        $stack = new RequestStack();
        $stack->push(Request::create('/orders/1'));
        $normalizer = new FileMetadataNormalizer($manager, $stack);
        $serializer = new Serializer([$normalizer, new ObjectNormalizer(new ClassMetadataFactory(new AttributeLoader()))]);
        return [$serializer, $file, $stack, $manager];
    }

    public function testOperationalMetadataPreservesFieldsAndCachesOnlyWithinTheRequest(): void
    {
        [$serializer, $file, $stack] = $this->fixture();
        $first = $serializer->normalize($file, null, ['groups' => ['order_product:read']]);
        self::assertSame(['id' => 42, 'fileType' => 'image', 'fileName' => 'water', 'context' => 'products', 'extension' => 'png'], $first);
        self::assertSame($first, $serializer->normalize($file, null, ['groups' => ['order_product:read']]));
        $stack->pop();
        $stack->push(Request::create('/orders/2'));
        self::assertSame($first, $serializer->normalize($file, null, ['groups' => ['order_product:read']]));
        self::assertFalse((new \ReflectionProperty(File::class, 'content'))->isInitialized($file));
    }

    public function testBinaryReadsAndWritesKeepTheOriginalPath(): void
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('getConnection');
        $manager->method('isUninitializedObject')->willReturn(true);
        $normalizer = new FileMetadataNormalizer($manager, new RequestStack());
        foreach ([[], ['file_item:read'], ['file:write'], ['spool_item:read'], ['*'], ['order:write', 'file:write']] as $groups)
            self::assertFalse($normalizer->supportsNormalization(new File(), null, ['groups' => $groups]));
    }

    public function testKernelJsonLdPreservesTheSameFileIdentityAndMetadata(): void
    {
        require_once ($_SERVER['CONTROLEONLINE_TEST_APP_ROOT'] ?? dirname(__DIR__, 5)) . '/config/bootstrap.php';
        [, $file, $stack, $manager] = $this->fixture();
        $kernel = new class('dev', true) extends \App\Kernel {
            public function getProjectDir(): string { return $_SERVER['CONTROLEONLINE_TEST_APP_ROOT'] ?? parent::getProjectDir(); }
        };
        $kernel->boot();
        try {
            $controller = $kernel->getContainer()->get(\ControleOnline\Controller\OrderProductCollectionController::class);
            $hydrator = (new \ReflectionProperty($controller, 'hydratorService'))->getValue($controller);
            $serializer = (new \ReflectionProperty($hydrator, 'serializer'))->getValue($hydrator);
            $inner = $serializer;
            if ($inner instanceof \Symfony\Component\Serializer\Debug\TraceableSerializer)
                $inner = (new \ReflectionProperty($inner, 'serializer'))->getValue($inner);
            $found = false;
            foreach ((new \ReflectionProperty($inner, 'normalizers'))->getValue($inner) as $normalizer) {
                if ($normalizer instanceof \Symfony\Component\Serializer\Debug\TraceableNormalizer)
                    $normalizer = (new \ReflectionProperty($normalizer, 'normalizer'))->getValue($normalizer);
                if ($normalizer instanceof FileMetadataNormalizer) {
                    (new \ReflectionProperty($normalizer, 'manager'))->setValue($normalizer, $manager);
                    (new \ReflectionProperty($normalizer, 'requestStack'))->setValue($normalizer, $stack);
                    $found = true;
                }
            }
            self::assertTrue($found, 'The live kernel must register the metadata normalizer.');
            $baseline = (new File())->setFileType('image')->setFileName('water')->setContext('products')
                ->setExtension('png')->setPublic(true)->setContent('binary data');
            (new \ReflectionProperty(File::class, 'id'))->setValue($baseline, 42);
            $context = ['groups' => ['order_product:read']];
            $expected = $serializer->normalize($baseline, 'jsonld', $context);
            $actual = $serializer->normalize($file, 'jsonld', $context);
            self::assertSame($expected, $actual);
            self::assertSame($actual, $serializer->normalize($file, 'jsonld', $context));
            $stack->pop();
            $stack->push(Request::create('/orders/2'));
            self::assertSame($actual, $serializer->normalize($file, 'jsonld', $context));
            self::assertArrayHasKey('@id', $actual);
            self::assertArrayNotHasKey('content', $actual);
        } finally { $kernel->shutdown(); }
    }
}
