<?php

namespace ControleOnline\Tests\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ControleOnline\Controller\GetFileDataAction;
use ControleOnline\Entity\File;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FileDownloadRouteTest extends TestCase
{
    public function testDownloadRoutesIncludeBareAndTenantPrefixedTemplates(): void
    {
        $attributes = (new ReflectionClass(File::class))->getAttributes(ApiResource::class);
        self::assertNotEmpty($attributes);

        $templates = [];
        foreach ($attributes as $attribute) {
            $resource = $attribute->newInstance();
            foreach ($resource->operations ?? [] as $operation) {
                if ($operation instanceof Get && is_string($operation->uriTemplate)) {
                    $templates[] = $operation->uriTemplate;
                    if (str_contains((string) $operation->uriTemplate, 'download')) {
                        self::assertSame(GetFileDataAction::class, $operation->controller);
                    }
                }
            }
        }

        self::assertContains('/{appDomain}/files/{id}/download', $templates);
        self::assertContains('/files/{id}/download', $templates);
    }
}
