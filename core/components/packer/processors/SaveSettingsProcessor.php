<?php

namespace Packer\Processors;

use Error;
use Packer\Packer;
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

        $projectName = $this->getProperty('project_name');
        $projectPath = $this->getProperty('project_path');
        $projectAssetsUrl = $this->getProperty('project_assets_url');
        $systemNamespaceName = $this->getProperty('system_namespace_name');
        $systemNamespacePathCore = $this->getProperty('system_namespace_path_core', "");
        $systemNamespacePathAssets = $this->getProperty('system_namespace_path_assets', "");

        if (
            $projectName === null ||
            $projectPath === null ||
            $systemNamespaceName === null ||
            $systemNamespacePathCore === null ||
            $systemNamespacePathAssets === null
        ) {
            $this->failure('Необходимо заполнить все ОБЯЗАТЕЛЬНЫЕ поля.');
            return;
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
            ]);
        } catch (Error $err) {
            $this->failure($err->getMessage());
        }

        // $productId = $this->getProperty('product_id');
        // if($productId === null){
        //     $this->failure('id продукта не определенно.');
        // }

        // $loggerSyncBd = new LoggerSyncBd($this->modx);
        // $product = $this->getProducts($productId, $loggerSyncBd);
        // return $product !== null
        //             ? $this->success('Данные продукта получены успешно.', $product)
        //             : $this->failure('Данные продукта небыли найдены.');

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
