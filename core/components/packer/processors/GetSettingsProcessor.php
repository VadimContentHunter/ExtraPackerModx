<?php

namespace Packer\Processors;

use MODX\Revolution\modX;
use Packer\Model\PackerProjects;
use MODX\Revolution\modNamespace;
use MODX\Revolution\Processors\Processor;

class GetSettingsProcessor extends Processor
{
    public function process()
    {
        $componentId = (int)$this->getProperty('componentId');
        if ($componentId === 0) {
            return $this->success();
            // return $this->failure('id компонента не определенно.');
        }

        $objProject = $this->modx->getObject(PackerProjects::class, ['id' => $componentId ?? 0]);
        if ($objProject instanceof PackerProjects) {
            return $this->success(object: $this->getItem($objProject->get('project_path')));
        } else {
            return $this->failure('Компонент не найден.');
        }
    }

    public function getItem(string $projectPath)
    {
        $filePath = rtrim($projectPath, "\\/") . "/packer_project.json";
        $this->modx->log(modX::LOG_LEVEL_INFO, $filePath);
        if (file_exists($filePath)) {
            return json_decode(file_get_contents($filePath), true);
        }

        return null;
    }
}
