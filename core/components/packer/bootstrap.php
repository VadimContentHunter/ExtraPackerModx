<?php

// require_once __DIR__ . '/Packer.php';

use MODX\Revolution\modX;
use Packer\Packer;

/**
 * @var modX $modx
 */

// Для работы с базой данных. Добавляет методы для ОРМ объекта указанной базы данных
// $modx->addPackage();

// Для работы с одним и тем же объектом, Реализуется сингл тон.
// Появляется один раз при вызове при повторном использовании возвращает тот же объект
// $modx->services->add('', function ($c) use ($modx) {});

// Для работы с новым объектом
// $modx->services[ModxPro\PdoTools\Fetch::class] = $modx->services->factory(function ($c) use ($modx) {
//     return new $class($modx, $config);
// });

// $a = 0;

// core/components/packer/bootstrap.php
spl_autoload_register(function ($class) {
    $prefix = 'Packer\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

$modx->addPackage('Packer\Model', $namespace['path'] . 'model/', null, 'Packer\\');

$modx->services->add('Packer', function ($c) use ($modx) {
    $assetUrl = $modx->getOption('extra_packer_assets_url');
    return new Packer($modx, [
        'assetsUrl' => $assetUrl !== null ? '/' . trim($assetUrl) : null,
    ]);
});
