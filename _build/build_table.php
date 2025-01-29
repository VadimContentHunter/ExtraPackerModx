<?php
require_once dirname(__FILE__) . '/build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

use MODX\Revolution\modX;

$modx = new modX();
$modx->initialize('mgr');

if (empty(PROJECT_DATA['project_name']) || empty(PROJECT_DATA['project_path']) || empty(PROJECT_DATA['system_namespace_path_core'])) {
    echo "\\nbuild_table: Нет данных в файле проекта.\\n";
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
$pkg = $projectName . '\Model';
$namespacePrefix = $projectName . '\\';

$modx->addPackage($pkg, $modelPath, null, $namespacePrefix);
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
