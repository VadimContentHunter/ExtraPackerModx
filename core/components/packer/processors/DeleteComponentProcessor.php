<?php

namespace Packer\Processors;

use MODX\Revolution\modX;
use Packer\Model\PackerProjects;
use MODX\Revolution\modNamespace;
use MODX\Revolution\Processors\Processor;

class DeleteComponentProcessor extends Processor
{
    public function process()
    {
        $ids = json_decode($this->getProperty('ids', []), true);
        if (!is_array($ids) || count($ids) === 0) {
            return $this->failure('ids компонентов не определены.');
        }

        $objProject = $this->modx->getObject(PackerProjects::class, ['id' => $ids[0] ?? 0]);
        $projectPath = $objProject?->get('project_path') ?? '';
        $filePath = rtrim($projectPath, "\\/") . "/packer_project.json";
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            if (
                !empty($data['project_path']) &&
                $data['project_path'] === $projectPath &&
                $this->deleteFolderRecursively($projectPath)
            ) {
                if (!$objProject->remove()) {
                    return $this->failure('Не удалось удалить объект из базы данных');
                } else {
                    return $this->success();
                }
            }
        }

        return $this->failure('Не удалось удалить Компонент.');
    }

    public function deleteFolderRecursively($folderPath): bool
    {
        // Проверяем, существует ли папка
        if (!is_dir($folderPath)) {
            // echo "Папка не существует: $folderPath\n";
            return false;
        }

        // Получаем список всех файлов и папок внутри
        $files = array_diff(scandir($folderPath), array('.', '..'));

        foreach ($files as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;

            // Если это папка, рекурсивно вызываем функцию для ее удаления
            if (is_dir($filePath)) {
                $this->deleteFolderRecursively($filePath);
            } else {
                // Если это файл, удаляем его
                unlink($filePath);
            }
        }

        // Удаляем саму папку
        rmdir($folderPath);
        return true;
        // echo "Папка удалена: $folderPath\n";
    }
}
