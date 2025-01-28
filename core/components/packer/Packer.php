<?php
namespace Packer;

use MODX\Revolution\modX;

class Packer {
    public modX $modx;
    public $config = [];
    public function __construct(modX $modx, array $config = []) {
        $this->modx =& $modx;
        $this->config = $config;
        // $basePath = $this->modx->getOption('doodles.core_path',$config,$this->modx->getOption('core_path').'components/doodles/');
        // $assetsUrl = $this->modx->getOption('doodles.assets_url',$config,$this->modx->getOption('assets_url').'components/doodles/');
        // $this->config = array_merge(array(
        //     'basePath' => $basePath,
        //     'corePath' => $basePath,
        //     'modelPath' => $basePath.'model/',
        //     'processorsPath' => $basePath.'processors/',
        //     'templatesPath' => $basePath.'templates/',
        //     'chunksPath' => $basePath.'elements/chunks/',
        //     'jsUrl' => $assetsUrl.'js/',
        //     'cssUrl' => $assetsUrl.'css/',
        //     'assetsUrl' => $assetsUrl,
        //     'connectorUrl' => $assetsUrl.'connector.php',
        // ),$config);
    }
}