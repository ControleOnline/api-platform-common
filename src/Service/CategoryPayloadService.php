<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Category;

class CategoryPayloadService
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(Category $category, string $iriPrefix = '/categories'): array
    {
        $iriPrefix = '/' . trim($iriPrefix, '/');
        $files = [];

        foreach ($category->getCategoryFiles() as $categoryFile) {
            $file = $categoryFile->getFile();
            $files[] = [
                '@id' => '/category_files/' . $categoryFile->getId(),
                'id' => $categoryFile->getId(),
                'file' => [
                    '@id' => '/files/' . $file->getId(),
                    'id' => $file->getId(),
                    'fileType' => $file->getFileType(),
                    'fileName' => $file->getFileName(),
                    'context' => $file->getContext(),
                    'extension' => $file->getExtension(),
                ],
            ];
        }

        $parent = $category->getParent();

        return [
            '@id' => $iriPrefix . '/' . $category->getId(),
            '@type' => 'Category',
            'id' => $category->getId(),
            'name' => $category->getName(),
            'categoryFiles' => $files,
            'context' => $category->getContext(),
            'parent' => $parent ? [
                '@id' => $iriPrefix . '/' . $parent->getId(),
                'id' => $parent->getId(),
                'name' => $parent->getName(),
            ] : null,
            'company' => '/people/' . $category->getCompany()->getId(),
            'icon' => $category->getIcon(),
            'color' => $category->getColor(),
            'sortOrder' => $category->getSortOrder(),
        ];
    }
}
