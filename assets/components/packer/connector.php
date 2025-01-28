<?php

use Packer\Packer;
use MODX\Revolution\modX;

$currentDir = __DIR__;
$configCorePath = null;

while ($currentDir !== '/' && $currentDir !== '' && $currentDir !== '.') {
    $path = $currentDir . '/config.core.php';
    if (file_exists($path)) {
        $configCorePath = $path;
        break;
    }
    $currentDir = dirname($currentDir);
}

if ($configCorePath) {
    require_once $configCorePath;
} else {
    throw new Error('Файл config.core.php не найден.');
}

require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

/**
 * @var modX $modx
 */

$packer = $modx->services->get('Packer');
if (!($packer instanceof Packer)) {
    throw new Error('Не найден класс Packer');
}

// $packer->buildPath('processorsPath');
$modx->lexicon->load('packer:default');
$modx->getRequest();

/** @var MODX\Revolution\modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest([
    'processors_path' => $packer->buildPath('processorsPath'),
    'location' => '',
]);
