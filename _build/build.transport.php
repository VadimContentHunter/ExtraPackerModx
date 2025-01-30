<?php

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0); /* гарантирует, что наш скрипт не завершится  */

// TODO: В дальнейшем заменить quip
$root = dirname(dirname(__FILE__)) . '/';
$sources = array(
    'root' => $root,
    'build' => $root . '_build/',
    'resolvers' => $root . '_build/resolvers/',
    'data' => $root . '_build/data/',
    'lexicon' => $root . 'core/components/quip/lexicon/',
    'source_core' => $root . 'core/components/quip',          // Нет завершающего символа пути "/" (ОБЯЗАТЕЛЬНО)
    'source_assets' => $root . 'assets/components/quip',      // Нет завершающего символа пути "/" (ОБЯЗАТЕЛЬНО)
    'docs' => $root . 'core/components/quip/docs/',
);
unset($root); /* бережем память */

require_once dirname(__FILE__) . '/build.config.php';

require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
// require_once MODX_CORE_PATH . core\src\Revolution\Transport\modPackageBuilder.php
use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;
use MODX\Revolution\modCategory;
use MODX\Revolution\modNamespace;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\Transport\modPackageBuilder;

$modx = new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

if (
    empty(PROJECT_DATA['project_name']) ||
    empty(PROJECT_DATA['project_path']) ||
    empty(PROJECT_DATA['system_namespace_name'])
) {
    printf("\build.transport: Нет данных в файле проекта.\n");
    exit;
}
const PROJECT_NAME = PROJECT_DATA['project_name'];
const NAMESPACE_NAME = PROJECT_DATA['system_namespace_name'];
const VERSION = PROJECT_DATA['version'] ?? "1.0";
const RELEASE = PROJECT_DATA['release'] ?? "dev";

// Создаем объект для работы с транспортным пакетом
$assetsPath = '';
$corePath = '{core_path}components/' . NAMESPACE_NAME . '/';
$objNamespace = $this->modx->getObject(modNamespace::class, ['name' => $data['system_namespace_name'] ?? '']);
if ($objNamespace instanceof modNamespace) {
    $assetsPath = $objNamespace->get('assets_path') ?? '';

}

$builder = new modPackageBuilder($modx);
$builder->createPackage('quip', VERSION, RELEASE);
$builder->registerNamespace(PROJECT_NAME, false, true, $corePath);


//
// Загружаем Сниппет
//
// $snippet = $modx->newObject('modSnippet');
// $snippet->set('id',1);
// $snippet->set('name','Test');
// $vehicle = $builder->createVehicle($snippet,array(
//     xPDOTransport::UNIQUE_KEY => 'name',
//     xPDOTransport::UPDATE_OBJECT => true,
//     xPDOTransport::PRESERVE_KEYS => false,
// ));


//
// Загружаем action/menu
//

/**
 * @var xPDOObject menu Должен быть объектом меню
 * @example Пример $menu = $modx->newObject('modMenu');
 */
// $menu = include $sources['data'].'transport.menu.php';
// $vehicle= $builder->createVehicle($menu,array (
//     xPDOTransport::PRESERVE_KEYS => true,
//     xPDOTransport::UPDATE_OBJECT => true,
//     xPDOTransport::UNIQUE_KEY => 'text',
//     xPDOTransport::RELATED_OBJECTS => true,
//     xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
//         'Action' => array (
//             xPDOTransport::PRESERVE_KEYS => false,
//             xPDOTransport::UPDATE_OBJECT => true,
//             xPDOTransport::UNIQUE_KEY => array ('namespace','controller'),
//         ),
//     ),
// ));
// $builder->putVehicle($vehicle);
// unset($vehicle,$action); /* бережем память */


//
// Загружаем системные переменные
//

// $settings = include $sources['data'].'transport.settings.php';
// $attributes= array(
//     xPDOTransport::UNIQUE_KEY => 'key',
//     xPDOTransport::PRESERVE_KEYS => true,
//     xPDOTransport::UPDATE_OBJECT => false,
// );
// foreach ($settings as $setting) {
//     $vehicle = $builder->createVehicle($setting,$attributes);
//     $builder->putVehicle($vehicle);
// }
// unset($settings,$setting,$attributes);


//
// Создаем контейнер категории
//

/* создаем категорию */
$category = $modx->newObject(modCategory::class);
$category->set('id', 1);
$category->set('category', 'Quip');

$attr = array(
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
        'Snippets' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ),
        'Chunks' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ),
    )
);


$vehicle = $builder->createVehicle($category, $attr);




// ----------------------------------------------------------------
// Валидаторы и Резольверы
//

$vehicle->resolve('file', array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));
$vehicle->resolve('file', array(
    'source' => $sources['source_assets'],
    'target' => "return MODX_ASSETS_PATH . 'components/';",
));
$vehicle->resolve('php', array(
    'source' => $sources['resolvers'] . 'setupoptions.resolver.php',
));
$builder->putVehicle($vehicle);

// ----------------------------------------------------------------
// Атрибуты пакета: license, readme and опции установки
//


/* теперь запакуем файл лицензии, файл readme и параметры настройки  */
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
    'setup-options' => array(
        'source' => $sources['build'] . 'setup.options.php'
    ),
));



// ----------------------------------------------------------------

$builder->pack();

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tend = $mtime;
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO, "\nСоздание пакета.\nВремя выполнения: {$totalTime}\n");

session_write_close();
exit();
