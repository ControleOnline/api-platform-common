<?php

namespace ControleOnline\Service;

class RequestPayloadService
{
    public function decodeJsonContent(?string $content): array
    {
        if (!is_string($content) || trim($content) === '') {
            throw new \InvalidArgumentException('Invalid JSON');
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid JSON');
        }

        return $decoded;
    }

    public function normalizeNumericId(mixed $value): int
    {
        return (int) preg_replace('/\D+/', '', (string) $value);
    }

    public function normalizeOptionalNumericId(mixed $value): ?int
    {
        $normalized = $this->normalizeNumericId($value);

        return $normalized > 0 ? $normalized : null;
    }
}
