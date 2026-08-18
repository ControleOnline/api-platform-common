<?php

namespace ControleOnline\Service;

use Symfony\Component\HttpFoundation\Request;

class CategoryProjectionRequestParser
{
    /**
     * Returns null when no projection was supplied and an empty array when the
     * consumer explicitly supplied an empty projection.
     *
     * @return int[]|null
     */
    public function parse(Request $request): ?array
    {
        $query = $request->query->all();
        if (!array_key_exists('categoryIds', $query) && !array_key_exists('ids', $query)) {
            return null;
        }

        $value = $query['categoryIds'] ?? $query['ids'];
        if (is_string($value)) {
            $value = preg_split('/\s*,\s*/', $value);
        }
        if (!is_array($value)) {
            $value = [$value];
        }

        $ids = [];
        foreach ($value as $id) {
            $id = (int) preg_replace('/\D+/', '', (string) $id);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
