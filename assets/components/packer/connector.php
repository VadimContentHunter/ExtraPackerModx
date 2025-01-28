<?php

use Packer\Packer;
use Error;

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
    throw new Exception('Файл config.core.php не найден.');
}

$packer = $modx->services->get('Packer');
if(!($packer instanceof Packer)) {
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