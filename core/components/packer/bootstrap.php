<?php

// require_once __DIR__ . '/Packer.php';

use xPDO\xPDO;
use Packer\Packer;
use MODX\Revolution\modX;

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


// Проверяем наличие таблицы
$tableName = $modx->getOption(xPDO::OPT_TABLE_PREFIX) . 'packer_projects';
$query = $modx->query("SHOW TABLES LIKE '{$tableName}'");
if (!$query || !$query->fetch(PDO::FETCH_COLUMN)) {
    // Если таблицы нет, создаем ее
    $modx->log(modX::LOG_LEVEL_INFO, "Таблица {$tableName} отсутствует. Создаем...");
    $modx->addPackage('Packer\Model', $namespace['path'] . 'model/', null, 'Packer\\');
    $manager = $modx->getManager();
    if ($manager->createObjectContainer('Packer\Model\PackerProjects')) {
        $modx->log(modX::LOG_LEVEL_INFO, "Таблица {$tableName} успешно создана.");
    } else {
        $modx->log(modX::LOG_LEVEL_ERROR, "Ошибка при создании таблицы {$tableName}.");
    }
} else {
    $modx->log(modX::LOG_LEVEL_INFO, "Таблица {$tableName} уже существует.");
}

$modx->services->add('Packer', function ($c) use ($modx) {
    $assetUrl = $modx->getOption('extra_packer_assets_url');
    return new Packer($modx, [
        'assetsUrl' => $assetUrl !== null ? '/' . trim($assetUrl) : null,
    ]);
});
