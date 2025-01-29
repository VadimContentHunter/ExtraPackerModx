<?php

/**
 * Определите константы пути MODX, необходимые для установки ядра
 *
 * @package quip
 * @subpackage build
 */
define('MODX_CORE_PATH', '/var/www/test-modx/core/');
define('MODX_CONFIG_KEY', 'config');


$jsonFilePath = __DIR__ . '/../packer_project.json';
if (file_exists($jsonFilePath)) {
    $jsonContent = file_get_contents($jsonFilePath);
    $projectData = json_decode($jsonContent, true); // второй параметр true заставляет json_decode вернуть ассоциативный массив
    if ($projectData === null) {
        define('PROJECT_DATA', null);
    } else {
        define('PROJECT_DATA', $projectData);
    }
} else {
    define('PROJECT_DATA', null);
}
