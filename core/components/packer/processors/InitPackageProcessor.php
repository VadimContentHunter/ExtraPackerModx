<?php

namespace Packer\Processors;

use MODX\Revolution\modX;
use Packer\Model\PackerProjects;
use MODX\Revolution\Processors\Processor;
use Packer\Services\PackageInit\PackageInitFactory;

class InitPackageProcessor extends Processor
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
            $packageInit = PackageInitFactory::createFromConfig($dataPackerProject);

            $configPath = $dataPackerProject['config_path'] ?? null;
            if (!empty($configPath)) {
                $packageInit->addTv($this->readConfigFile('tvs.json', $configPath));
                $packageInit->addSnippets($this->readConfigFile('snippets.json', $configPath));
                $packageInit->addChunks($this->readConfigFile('chunks.json', $configPath));
                $packageInit->addTemplates($this->readConfigFile('templates.json', $configPath));
                $packageInit->addMenu($this->readConfigFile('menus.json', $configPath));
                $packageInit->addResources($this->readConfigFile('resources.json', $configPath));
            }

            $packageInit->init();
        }

        return null;
    }

    public function readConfigFile(string $filename, string $directory): array
    {
        $filePath = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return []; // Файл не найден или недоступен
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'php':
                return include $filePath;
            case 'json':
                return json_decode(file_get_contents($filePath), true) ?? [];
            case 'ini':
                return parse_ini_file($filePath) ?: [];
                // case 'yaml':
                // case 'yml':
                //     return function_exists('yaml_parse_file') ? yaml_parse_file($filePath) : [];
            default:
                return []; // Неподдерживаемый формат
        }
    }
}
