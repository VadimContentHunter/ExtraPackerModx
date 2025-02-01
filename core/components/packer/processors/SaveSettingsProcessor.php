<?php

namespace Packer\Processors;

use Error;
use Packer\Packer;
use MODX\Revolution\modX;
use Packer\Model\PackerProjects;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\Processors\Processor;

class SaveSettingsProcessor extends Processor
{
    protected ?Packer $packer = null;

    public function process()
    {
        $this->packer = $this->modx->services->get('Packer');

        // автоматическая генерация
        $enableAutoSettings = $this->getProperty('enable_auto_settings', false);
        $projectParentPath = $this->getProperty('project_parent_path_auto_setting', '');
        $projectNameAutoSetting = $this->getProperty('project_name_auto_setting', '');
        if ($projectNameAutoSetting === '' && $enableAutoSettings !== false) {
            // $this->modx->log(modX::LOG_LEVEL_INFO, json_encode($this->properties));
            return $this->failure('Необходимо указать имя проекта.');
        }
        $this->generationBaseParams($enableAutoSettings, $projectParentPath, $projectNameAutoSetting);

        $projectName = $this->getProperty('project_name');
        $projectPath = $this->getProperty('project_path');
        $projectAssetsUrl = $this->getProperty('project_assets_url');
        $systemNamespaceName = $this->getProperty('system_namespace_name');
        $systemNamespacePathCore = $this->getProperty('system_namespace_path_core', "");
        $systemNamespacePathAssets = $this->getProperty('system_namespace_path_assets', "");

        if (
            $projectName === null ||
            $projectName === '' ||
            $projectPath === null ||
            $systemNamespaceName === null ||
            $systemNamespacePathCore === null ||
            $systemNamespacePathAssets === null
        ) {
            $this->modx->log(modX::LOG_LEVEL_INFO, $projectName);
            $this->modx->log(modX::LOG_LEVEL_INFO, $projectPath);
            $this->modx->log(modX::LOG_LEVEL_INFO, $systemNamespaceName);
            $this->modx->log(modX::LOG_LEVEL_INFO, $systemNamespacePathCore);
            $this->modx->log(modX::LOG_LEVEL_INFO, $systemNamespacePathAssets);
            return $this->failure('Необходимо заполнить все ОБЯЗАТЕЛЬНЫЕ поля.');
        }

        $systemNamespaceName = strtolower($systemNamespaceName);
        $projectPath = $this->packer->processPlaceholders($this->modx, $projectPath);
        $systemNamespacePathCore = $this->packer->processPlaceholders($this->modx, $systemNamespacePathCore);
        $systemNamespacePathAssets = $this->packer->processPlaceholders($this->modx, $systemNamespacePathAssets);

        try {
            $this->removeNamespaceFromFile($projectPath);
            $this->createFolderSys($projectPath);
            $namespaceName = $this->createNameSpace($systemNamespaceName, $systemNamespacePathAssets, $systemNamespacePathCore);
            $sysAssetsUrl = $this->createSystemParameterAssetUrl($projectName, $projectAssetsUrl, $namespaceName);

            $this->createProjectJsonFile($projectPath, [
                "project_name" => $projectName,
                "project_path" => $projectPath,
                "project_assets_url" => $sysAssetsUrl !== null ? $sysAssetsUrl->get("value") : "",
                "system_namespace_name" => strtolower($namespaceName),
                "system_namespace_path_core" => $systemNamespacePathCore ?? '',
                "system_namespace_path_assets" => $systemNamespacePathAssets ?? '',
                "system_assets_url_key" => $sysAssetsUrl !== null ? $sysAssetsUrl->get("key") : "",
                "version" => "1.0",
                "release" => "dev",
                "config_path" => rtrim($projectPath, '/\\') . '/_configs/'
            ]);

            $this->generationBaseFileFolders($projectPath, $systemNamespacePathAssets, $systemNamespacePathCore);
        } catch (Error $err) {
            return $this->failure($err->getMessage());
        }

        return $this->success();
    }

    public function removeNamespaceFromFile(string $projectPath)
    {
        $filePath = rtrim($projectPath, "\\/") . "/packer_project.json";
        if (file_exists($filePath)) {
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);
            if (isset($data['system_namespace_name']) && !empty($data['system_namespace_name'])) {
                $object = $this->modx->getObject(modNamespace::class, ['name' => $data['system_namespace_name'] ?? '']);
                if ($object instanceof modNamespace) {
                    if (!$object->remove()) {
                        throw new Error("Не удалось удалить запись.");
                    }
                }
            }
        }
    }

    public function generationBaseParams(
        string|bool $enableAutoSettings,
        string $projectParentPath,
        string $projectName
    ) {
        if (is_string($enableAutoSettings) && $enableAutoSettings === 'on') {
            $enableAutoSettings = true;
            // $this->modx->log(modX::LOG_LEVEL_INFO, 'enableAutoSettings = true');
        }

        if ($enableAutoSettings !== true) {
            // $this->modx->log(modX::LOG_LEVEL_INFO, 'if ($enableAutoSettings !== true)');
            return;
        }

        $basePath = $this->modx->getOption('base_path');
        if ($projectParentPath === '') {
            $projectParentPath = rtrim($basePath, '\\/') . '/extras';
        }


        $projectFolderName = 'Extra' . $projectName;
        $projectParentPath = rtrim($projectParentPath, '\\/');
        $projectPath = $projectParentPath . '/' . $projectFolderName;
        $projectAssetsUrl = '';
        $namespaceName = mb_strtolower($projectName);
        $namespaceAssets = $projectPath . '/assets/components/' . $namespaceName;
        $namespaceCore = $projectPath . '/core/components/' . $namespaceName;

        // Если путь содержит ядро, то убираем его
        if (strpos($namespaceAssets, $basePath) === 0) {
            $projectAssetsUrl = trim(substr($namespaceAssets, strlen($basePath)), '\\/');
        }

        $this->setProperty('project_name', $projectName);
        $this->setProperty('project_path', $projectPath . '/');
        $this->setProperty('project_assets_url', $projectAssetsUrl . '/');
        $this->setProperty('system_namespace_name', $namespaceName);
        $this->setProperty('system_namespace_path_core', $namespaceCore . '/');
        $this->setProperty('system_namespace_path_assets', $namespaceAssets . '/');
        // $this->modx->log(modX::LOG_LEVEL_INFO, 'generationBaseParams - END');
    }

    public function generationBaseFileFolders(string $projectPath, string $namespaceAssets, string $namespaceCore)
    {
        $this->createDirectory($projectPath, ['_configs'], [
            'tvs.json',
            'snippets.json',
            'chunks.json',
            'templates.json'
        ]);

        $this->createDirectory(null, [$namespaceAssets, $namespaceCore]);

        if (is_dir($namespaceCore)) {
            $this->createDirectory($namespaceCore, ['docs'], [
                'changelog.txt',
                'license.txt',
                'readme.txt'
            ]);

            $this->createFiles($namespaceCore, ['bootstrap.php']);

            $this->createDirectory($namespaceCore, [
                'elements',
                'controllers',
                'model',
                'lexicon',
                'schema',
                'processors'
            ]);
        }
    }

    /**
     * Создает указанные директории и файлы внутри них (если переданы).
     */
    private function createDirectory(?string $basePath, array $dirs, array $files = [])
    {
        foreach ($dirs as $dir) {
            $path = $basePath ? rtrim($basePath, '/\\') . "/$dir/" : rtrim($dir, '/\\') . '/';

            if (!is_dir($path) && !mkdir($path, 0777, true)) {
                throw new Error("Не удалось создать папку: $path");
            }

            $this->createFiles($path, $files);
        }
    }

    /**
     * Создает файлы, если они не существуют.
     */
    private function createFiles(string $dirPath, array $files)
    {
        foreach ($files as $file) {
            $filePath = rtrim($dirPath, '/\\') . "/$file";
            if (!file_exists($filePath)) {
                file_put_contents($filePath, "");
            }
        }
    }


    public function createProjectJsonFile(string $projectPath, array $data)
    {
        // Преобразуем массив в формат JSON
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);

        // Проверяем, существует ли папка для файла
        if (!is_dir(dirname($projectPath))) {
            throw new Error("Папку для не найдена.");
        }

        // Записываем JSON в файл
        $filePath = rtrim($projectPath, "\\/") . "/packer_project.json";
        if (file_put_contents($filePath, $jsonData) === false) {
            throw new Error("Не удалось создать файл");
        }
    }

    /**
     * @param string $projectPath
     * @return PackerProjects
     * 
     * @throws Error Если не удалось создать папку
     */
    public function createFolderSys(string $projectPath): PackerProjects
    {
        $packerDb = $this->modx->getObject(PackerProjects::class, ['project_path' => $projectPath]);
        if (!($packerDb instanceof PackerProjects)) {
            if (!is_dir($projectPath)) {
                // Если папки нет, создаем её
                if (!mkdir($projectPath, 0777, true)) {
                    throw new Error("Не удалось создать папку");
                }
            }

            $packerDb = $this->modx->newObject(PackerProjects::class);
            $packerDb->set("project_path", $projectPath);
            $packerDb->save();
        } else {
            // Если объект существует в базе, но папки нет на диске
            if (!is_dir($projectPath)) {
                // Папки нет — создаем её
                if (!mkdir($projectPath, 0777, true)) {
                    throw new Error("Не удалось создать папку");
                }
            }
        }

        return $packerDb;
    }



    public function createSystemParameterAssetUrl(string $projectName, ?string $url = null, string $namespace = ""): ?modSystemSetting
    {
        $systemAssetsUrlKey = 'extra_' . strtolower($projectName) . '_assets_url';
        $setting = $this->modx->getObject(modSystemSetting::class, ['key' => $systemAssetsUrlKey]);

        if (!($setting instanceof modSystemSetting) && $url === null) {
            return null;
        }

        if (!($setting instanceof modSystemSetting)) {
            $setting = $this->modx->newObject(modSystemSetting::class);
            $setting->set('key', $systemAssetsUrlKey);
        }

        $setting->set('value', $url ?? "");
        $setting->set('namespace', $namespace);
        $setting->set('xtype', 'textfield');
        $setting->set('area', '_extra');
        $setting->save();

        return $setting;
    }

    public function createNameSpace(string $name, ?string $assetPath = null, ?string $corePath = null): string
    {

        $namespace = $this->modx->getObject(modNamespace::class, ['name' => $name]);
        if (!($namespace instanceof modNamespace)) {
            $namespace = $this->modx->newObject(modNamespace::class);
            $namespace->set('name', $name);
            $namespace->set('core_path', $corePath ?? "");
            $namespace->set('assets_path', $assetPath ?? "");
        } else {
            if ($corePath !== null) {
                $namespace->set('core_path', $corePath);
            }

            if ($assetPath !== null) {
                $namespace->set('assets_path', $assetPath);
            }
        }

        $namespace->save();
        return $namespace->get('name');
    }
}
