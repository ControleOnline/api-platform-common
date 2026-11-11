<?php

declare(strict_types=1);

namespace ControleOnline\Service;

use Symfony\Component\Validator\Constraints\CssColor;
use Symfony\Component\Validator\Validation;

/** Optional menu colors never overwrite the original color/icon or theme palette. */
final class MenuAppearanceProperties
{
    public static function fields(bool $route = false): array
    {
        return $route ? ['textColor', 'iconColor', 'iconBackgroundColor'] : ['textColor', 'iconColor'];
    }

    public static function changes(array $payload, bool $route = false): array
    {
        $result = [];
        foreach (self::fields($route) as $field) {
            if (!array_key_exists($field, $payload)) continue;
            $value = $payload[$field];
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $result[$field] = null;
                continue;
            }
            if (!is_string($value)) throw new \InvalidArgumentException('Invalid appearance color: '.$field);
            $value = trim($value);
            if (in_array(strtolower($value), ['inherit', 'initial', 'unset', 'revert', 'revert-layer', 'currentcolor'], true) || strlen($value) > 50 || (!preg_match('/^\$[A-Za-z][A-Za-z0-9_-]*$/D', $value)
                && count(Validation::createValidator()->validate($value, new CssColor())) > 0)) {
                throw new \InvalidArgumentException('Invalid appearance color: '.$field);
            }
            $result[$field] = $value;
        }
        return $result;
    }

    public static function apply(object $entity, array $changes): void
    {
        foreach ($changes as $field => $value) $entity->{'set'.ucfirst($field)}($value);
    }

    public static function values(object $entity, bool $route = false): array
    {
        $result = [];
        foreach (self::fields($route) as $field) $result[$field] = $entity->{'get'.ucfirst($field)}();
        return $result;
    }
}
