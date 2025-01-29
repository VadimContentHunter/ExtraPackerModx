<?php
require_once dirname(__FILE__) . '/build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

use MODX\Revolution\modX;

function deleteDir($dirPath)
{
    if (!is_dir($dirPath)) {
        return;
    }
    $files = array_diff(scandir($dirPath), array('.', '..'));
    foreach ($files as $file) {
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filePath)) {
            deleteDir($filePath); // Рекурсивно удаляем папку
        } else {
            unlink($filePath); // Удаляем файл
        }
    }
    rmdir($dirPath);
}

$modx = new modX();
$modx->initialize('mgr');

if (empty(PROJECT_DATA['project_name']) || empty(PROJECT_DATA['project_path']) || empty(PROJECT_DATA['system_namespace_path_core'])) {
    printf("\nbuild_table: Нет данных в файле проекта.\n");
    exit;
}

// Получаем данные проекта
$projectName = PROJECT_DATA['project_name'];
$projectPath = rtrim(PROJECT_DATA['project_path'], '/\\');
$projectPathCore = rtrim(PROJECT_DATA['system_namespace_path_core'], '/\\');

// Модификаторы
$modelPath = $projectPathCore . '/model';
$schemaFile = $projectPathCore . '/schema/' . strtolower($projectName) . '.mysql.schema.xml';
$outputDir = $projectPathCore;
$namespaceModel = $projectName . '\Model';
$namespacePrefix = $projectName . '\\';

// Удаляем предыдущие папки моделей
deleteDir($modelPath);

// Создание моделей классов на основе схемы
$modx->addPackage($namespaceModel, $modelPath, null, $namespacePrefix);
$manager = $modx->getManager();
$generator = $manager->getGenerator();
$generator->parseSchema(
    $schemaFile,
    $outputDir,
    [
        "compile" => 1,
        "update" => 1,
        "regenerate" => 1,
        "namespacePrefix" => $namespacePrefix
    ]
);

// Создание самих таблиц
$quantityCreateObjError = 0;

// Фильтрация содержимого, исключая указанные элементы
$filteredContents = array_diff(
    scandir($modelPath),
    [
        'mysql',
        'metadata.mysql.php',
        '.',
        '..'
    ]
);

foreach ($filteredContents as $fileFullName) {
    $filePath = rtrim($modelPath) . '/' . $fileFullName;
    if (!file_exists($filePath)) {
        printf("\nbuild_table: Файл не обнаружен по пути. (%s)\n", $filePath);
        continue;
    }

    require_once $filePath;
    $fileName = preg_replace('/.php$/u', '', $fileFullName);
    $className = $namespaceModel . '\\' . $fileName;
    if (class_exists($className)) {
        $instance = new $className($modx);

        // Удалить таблицу вручную перед созданием новой
        $modx->exec('DROP TABLE IF EXISTS `' . $instance::class . '`');
        $r = $manager->createObjectContainer($instance::class);
        printf("\nbuild_table:  %s, create object: %s\n\n", $instance::class, $r ? 'true' : 'false');
        if (!$r) {
            $quantityCreateObjError++;
        }
    } else {
        printf("\nbuild_table: Класс не найден. (%s)\n", $className);
        continue;
    }
}
printf("\nCount errors: %d\n\n", $quantityError);
