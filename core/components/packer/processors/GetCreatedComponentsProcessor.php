<?php

namespace Packer\Processors;

use Packer\Model\PackerProjects;
use MODX\Revolution\modNamespace;
use MODX\Revolution\Processors\Processor;

class GetCreatedComponentsProcessor extends Processor
{
    public function process()
    {
        $result = [];

        $start  = $this->getProperty('start', 0);
        $limit = $this->getProperty('limit', 12);
        $sort  = $this->getProperty('sort', 'id');
        $dir  = $this->getProperty('dir', 'ASC');

        $query = $this->modx->newQuery(PackerProjects::class);
        $query->sortby($sort, $dir);
        $query->limit($limit, $start);
        $projectsDb = $this->modx->getIterator(PackerProjects::class, $query);
        foreach ($projectsDb as $project) {
            if ($project instanceof PackerProjects) {
                $result[] = $this->getItem($project->get('id'), $project->get('project_path')) ?? [];
            }
        }

        return json_encode([
            'success' => true,
            'total' => 12,
            'results' => $result,
        ]);
    }

    public function getItem(string|int $id, string $projectPath)
    {
        $filePath = rtrim($projectPath, "\\/") . "/packer_project.json";
        if (file_exists($filePath)) {
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);
            if (!empty($data['system_namespace_name']) && !empty($data['project_name'])) {
                $objNamespace = $this->modx->getObject(modNamespace::class, ['name' => $data['system_namespace_name'] ?? '']);
                if ($objNamespace instanceof modNamespace) {
                    return [
                        'id' => $id,
                        'name' => $data['project_name'],
                        'namespaces' => $data['system_namespace_name'],
                        'version' => "dev",
                        'actions' => $this->getActions()
                    ];
                }
            }
        }

        return null;
    }

    public function getActions(): array
    {
        return [
            [
                "action" => "openEditProduct",
                "button" => true,
                "cls" => "",
                "icon" => "icon-edit-pen",
                "menu" => true,
                "multiple" => false,
                "onlyMultiple" => false,
                "title" => "Обзор / Редактировать",
            ],
            [
                "action" => "packUp",
                "button" => true,
                "cls" => "",
                "icon" => "icon-arrow",
                "menu" => true,
                "multiple" => false,
                "onlyMultiple" => false,
                "title" => "Упаковать",
            ],
            [
                "action" => "removeItem",
                "button" => true,
                "cls" => "",
                "icon" => "icon-close",
                "menu" => true,
                "multiple" => false,
                "onlyMultiple" => false,
                "title" => "Удалить компонент",
            ],
        ];
    }
}
