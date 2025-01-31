<?php

namespace Packer\Processors;

use Error;
use MODX\Revolution\modX;
use Packer\Model\PackerProjects;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modSystemSetting;
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
                empty($data['project_path']) || $data['project_path'] !== $projectPath
            ) {
                return $this->failure(
                    'Путь к проекту не корректный. Данные в файле и в базе данных отличаються.'
                    . '<br>$projectPath: ' . $projectPath
                    . '<br>$data[`project_path`]: ' . $data['project_path']
                );
            }

            if (!$this->deleteFolderRecursively($projectPath)) {
                return $this->failure('Не удалось удалить папку проекта.');
            }

            if (!$objProject->remove()) {
                return $this->failure('Не удалось удалить объект из базы данных');
            }

            $errors = '';
            if (!$this->deleteNamespace($data['system_namespace_name'] ?? '')) {
                $errors .= "<br><br>Не удалось удалить пространство имен.";
            }

            if (!$this->deleteSystemParameterAssetUrl($data['system_assets_url_key'] ?? '')) {
                $errors.= "<br><br>Не удалось удалить ключ системной настройки для URL ассетов.<br>Ключ: "
                    . $data['system_assets_url_key'] ?? 'unset';
            }

            if($errors === ''){
                return $this->success();
            } else {
                return $this->failure($errors);
            }
            
        }
        return $this->failure('Не удалось удалить Компонент.');
    }

    public function deleteSystemParameterAssetUrl(string $system_assets_url_key)
    {
        $setting = $this->modx->getObject(modSystemSetting::class, ['key' => $system_assets_url_key]);
        if (!($setting instanceof modSystemSetting)) {
            return true;
        }
        return $setting->remove();
    }

    public function deleteNamespace(string $system_namespace_name): bool
    {
        $namespace = $this->modx->getObject(modNamespace::class, ['name' => $system_namespace_name]);
        if (!($namespace instanceof modNamespace)) {
            return true;
        }
        return $namespace->remove();
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
