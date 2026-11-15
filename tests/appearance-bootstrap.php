<?php

declare(strict_types=1);

// Run isolated package tests against an existing API dependency installation, never its kernel/database.
$autoload = getenv('COMMON_API_AUTOLOAD') ?: dirname(__DIR__).'/vendor/autoload.php';
if (!is_file($autoload)) throw new RuntimeException('Set COMMON_API_AUTOLOAD to a Composer autoload.php.');
$loader = require $autoload;
$source = dirname(__DIR__).'/src/';
$map = [];
foreach (['Entity/Category', 'Entity/Routes', 'Repository/MenuRepository', 'Service/MenuConfigService', 'Service/MenuAppearanceProperties'] as $class) {
    $map['ControleOnline\\'.str_replace('/', '\\', $class)] = $source.$class.'.php';
}
$loader->addClassMap($map);
