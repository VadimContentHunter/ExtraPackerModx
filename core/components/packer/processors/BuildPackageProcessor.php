<?php

namespace Packer\Processors;

use MODX\Revolution\modX;
use Packer\Model\PackerProjects;
use MODX\Revolution\modNamespace;
use MODX\Revolution\Processors\Processor;
use Packer\Services\PackageBuilder\PackageBuilderFactory;

class BuildPackageProcessor extends Processor
{
    public function process()
    {
        $ids = json_decode($this->getProperty('ids', []), true);
        if (!is_array($ids) || count($ids) === 0) {
            return $this->failure('ids компонентов не определены.');
        }

        $objProject = $this->modx->getObject(PackerProjects::class, ['id' => $ids[0] ?? 0]);
        $this->modx->log(modX::LOG_LEVEL_INFO, '$objProject: ' . json_encode($objProject));
        if ($objProject instanceof PackerProjects) {
            ob_start();
                $this->buildPackage($objProject?->get('project_path') ?? '');
            $output = ob_get_clean();
            return $this->success($output);
        } else {
            return $this->failure('Компонент не найден.');
        }
    }

    public function buildPackage(string $projectPath)
    {
        $filePath = rtrim($projectPath, "\\/") . "/packer_project.json";
        $this->modx->log(modX::LOG_LEVEL_INFO, $filePath);
        if (file_exists($filePath)) {
            $dataPackerProject = json_decode(file_get_contents($filePath), true);

            $packageBuilder = PackageBuilderFactory::createFromConfig($dataPackerProject);
            // $packageBuilder->addTv(json_decode(
            //     file_get_contents(
            //         PROJECT_DATA["project_path"] . "configs/tvs.conf.json"
            //     ),
            //     true
            // ));
            $packageBuilder->build();
        }

        return null;
    }
}
