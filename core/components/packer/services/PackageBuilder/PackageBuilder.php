<?php

namespace Packer\Services\PackageBuilder;

use Error;
use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;
use MODX\Revolution\modChunk;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modCategory;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modNamespace;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\Transport\modPackageBuilder;
use Packer\Services\PackageBuilder\SettingVehicle;

class PackageBuilder
{
    private ?modX $modx = null;

    private ?modPackageBuilder $builder = null;

    private ?modCategory $generalCategory = null;

    private string $newCorePath = '';

    private string $newAssetsPath = '';

    private string $resolversPath = '';

    /**
     * @param string $projectName
     * @param string $namespaceName
     * @param string $projectPath
     * @param string $version
     * @param string $release
     * @param array $config Дополнительный файл с настройками
     */
    public function __construct(
        private string $projectName,
        private string $namespaceName,
        private string $projectPath,
        private string $sourceCore,
        private ?string $sourceAssets = null,
        private string $version = "1.0",
        private string $release = "dev",
        private array $config = [],
    ) {
        $this->newCorePath = 'core/components/' . strtolower($this->namespaceName) . '/';
        $this->newAssetsPath = 'assets/components/' . strtolower($this->namespaceName) . '/';

        $this->resolversPath = rtrim(__DIR__) . '/resolvers/';

        $this->modx = new modX();
        $this->modx->initialize('mgr');
        $this->modx->setLogLevel(modX::LOG_LEVEL_INFO);
        $this->modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

        $this->initPackage();

        $this->generalCategory = $this->modx->newObject(modCategory::class, [
            'category' => $this->projectName
        ]);
    }

    public function checkInitBaseParameter()
    {
        if (
            empty($this->projectName) ||
            empty($this->namespaceName) ||
            empty($this->projectPath) ||
            $this->modx === null ||
            $this->builder === null
        ) {
            throw new Error('Init base parameters are not set.');
        }
    }

    public function addToGeneralCategory(xPDOObject $obj)
    {
        if ($this->generalCategory === null) {
            $this->generalCategory = $this->modx->newObject(modCategory::class, [
                'category' => $this->projectName
            ]);
        }
        $this->generalCategory->addMany($obj);
    }

    public function initPackage()
    {
        // $this->checkInitBaseParameter();

        $corePath = '{core_path}' . $this->newCorePath;
        $assetsPath = '';

        $objNamespace = $this->modx->getObject(modNamespace::class, ['name' => $this->namespaceName]);
        if ($objNamespace instanceof modNamespace) {
            $assetsPath = $objNamespace->get('assets_path') !== null
                ? '{assets_path}' . $this->newAssetsPath
                : '';
        }

        // Создаём транспортный пакет
        $this->builder = new modPackageBuilder($this->modx);
        $this->builder->createPackage($this->projectName, $this->version, $this->release);
        $this->builder->registerNamespace($this->namespaceName, false, true, $corePath, $assetsPath);
    }

    /**
     * @param array<string,array> $configs Параметры <НазваниеСниппета: конфигурации>
     * - pathDev - Путь к нахождению сниппета
     * @return void
     */
    public function addSnippets(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $snippetName => $snippetConfig) {
            $fullPath = rtrim($this->projectPath) . '/' . trim($snippetConfig['pathDev'] ?? '');
            if (
                is_string($snippetName) &&
                array_key_exists('pathDev', $snippetConfig) &&
                file_exists($fullPath)
            ) {
                $fileNameWithExtension = basename($fullPath);

                $snippet = $this->modx->newObject(modSnippet::class);
                foreach ($snippet as $key => $value) {
                    if ($key === "pathDev") {
                        continue;
                    }

                    if ($key === "snippet") {
                        $snippet->set($key, file_get_contents($fullPath));
                        continue;
                    }

                    if ($key === "static_file") {
                        $snippet->set($key, $this->newCorePath . 'elements/snippets/' . $fileNameWithExtension);
                        continue;
                    }

                    $snippet->set($key, $value);
                }

                $this->addToGeneralCategory($snippet);
                // if (!$this->addToGeneralCategory($snippet)) {
                //     $settingVehicle = new SettingVehicle($this->modx, $this->builder);
                //     $settingVehicle->setObject($snippet, 'name');
                //     $settingVehicle->putVehicle();
                // }
            } else {
                throw new Error("Не корректные настройки сниппета '" . $snippetName ?? 'Неизвестный' . "'");
            }
        }
    }

    /**
     * @param array<string,array> $configs Параметры <НазваниеЧанка: конфигурации>
     * - pathDev - Путь к нахождению сниппета
     * @return void
     */
    public function addChunks(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $chunkName => $chunkConfig) {
            $fullPath = rtrim($this->projectPath) . '/' . trim($snippetConfig['pathDev'] ?? '');
            if (
                is_string($chunkName) &&
                array_key_exists('pathDev', $chunkConfig) &&
                file_exists($fullPath)
            ) {
                $fileNameWithExtension = basename($fullPath);

                $chunk = $this->modx->newObject(modChunk::class);
                foreach ($chunk as $key => $value) {
                    if ($key === "pathDev") {
                        continue;
                    }

                    if ($key === "snippet") {
                        $chunk->set($key, file_get_contents($fullPath));
                        continue;
                    }

                    if ($key === "static_file") {
                        $chunk->set($key, $this->newCorePath . 'elements/chunks/' . $fileNameWithExtension);
                        continue;
                    }

                    $chunk->set($key, $value);
                }

                $this->addToGeneralCategory($chunk);
                // if (!$this->addToGeneralCategory($chunk)) {
                //     $settingVehicle = new SettingVehicle($this->modx, $this->builder);
                //     $settingVehicle->setObject($chunk, 'name');
                //     $settingVehicle->putVehicle();
                // }
            } else {
                throw new Error("Не корректные настройки chunk '" . $chunkName ?? 'Неизвестный' . "'");
            }
        }
    }

    /**
     * @param array<string,array> $configs Параметры <НазваниеTemplate: конфигурации>
     * - pathDev - Путь к нахождению сниппета
     * @return void
     */
    public function addTemplates(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $templateName => $templateConfig) {
            $fullPath = rtrim($this->projectPath) . '/' . trim($snippetConfig['pathDev'] ?? '');
            if (
                is_string($templateName) &&
                array_key_exists('pathDev', $templateConfig) &&
                file_exists($fullPath)
            ) {
                $fileNameWithExtension = basename($fullPath);

                $template = $this->modx->newObject(modTemplate::class);
                foreach ($template as $key => $value) {
                    if ($key === "pathDev") {
                        continue;
                    }

                    if ($key === "content") {
                        $template->set($key, file_get_contents($fullPath));
                        continue;
                    }

                    if ($key === "static_file") {
                        $template->set($key, $this->newCorePath . 'elements/templates/' . $fileNameWithExtension);
                        continue;
                    }

                    $template->set($key, $value);
                }

                $this->addToGeneralCategory($template);
            } else {
                throw new Error("Не корректные настройки template '" . $templateName ?? 'Неизвестный' . "'");
            }
        }
    }

    /**
     * @param array<string,array> $configs Параметры <НазваниеTV: конфигурации>
     * @return void
     */
    public function addTv(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $tvName => $tvConfig) {
            if (
                is_string($tvName) &&
                // array_key_exists('name', $tvConfig) &&
                array_key_exists('type', $tvConfig) &&
                array_key_exists('elements', $tvConfig)
            ) {

                $tv = $this->modx->newObject(modTemplateVar::class);
                $tv->set('name', $tvName);
                foreach ($tvConfig as $key => $value) {
                    $tv->set($key, $value);
                }

                $this->addToGeneralCategory($tv);
            } else {
                throw new Error("Не корректные настройки TV '" . $tvName ?? 'Неизвестный' . "'");
            }
        }
    }

    public function initGeneralCategory()
    {
        $this->checkInitBaseParameter();
        if (!is_dir($this->sourceCore)) {
            throw new Error("Не корректный путь в параметре sourceCore: " . $this->sourceCore);
        }

        // $this->generalCategory = $this->modx->newObject(modCategory::class, [
        //     'category' => $this->projectName
        // ]);

        $settingVehicle = new SettingVehicle($this->modx, $this->builder);
        $settingVehicle->setObject($this->generalCategory, 'category');
        $settingVehicle->addRelatedObjAttribute('Snippets', 'name');
        $settingVehicle->addRelatedObjAttribute('Chunks', 'name');
        $settingVehicle->addRelatedObjAttribute('Templates', 'templatename');
        $settingVehicle->addRelatedObjAttribute('TemplateVars', 'name');
        $settingVehicle->copyFile($this->sourceCore, 'MODX_CORE_PATH . components/');

        if (is_dir($this->sourceAssets)) {
            $settingVehicle->copyFile($this->sourceAssets, 'MODX_ASSETS_PATH . components/');
        }

        $settingVehicle->addResolver(rtrim($this->resolversPath) . '/uninstall_package.resolver.php');
        $settingVehicle->putVehicle();
    }

    public function initPackageAttributes()
    {
        $this->checkInitBaseParameter();

        $this->builder->setPackageAttributes([
            'license' => file_get_contents(rtrim($this->sourceCore) . '/docs/license.txt') ?: '',
            'readme' => file_get_contents(rtrim($this->sourceCore) . '/docs/readme.txt') ?: '',
            'changelog' => file_get_contents(rtrim($this->sourceCore) . '/docs/changelog.txt') ?: '',
            'requires' => [
                'modx' => '>=3.0.0'
            ],
            // 'setup-options' => [
            //     'source' => $this->sourceCore . 'setup.options.php',
            //     'install' => $this->sourceCore . 'setup/install.php',
            //     'update' => $this->sourceCore . 'setup/update.php',
            //     'uninstall' => $this->sourceCore . 'setup/uninstall.php',
            //     'options' => [
            //         'install_example' => true,
            //         'auto_create_category' => true
            //     ]
            // ],
            // 'lexicons' => [
            //     'en' => $this->sourceCore . 'lexicon/en/',
            //     'ru' => $this->sourceCore . 'lexicon/ru/',
            // ]
        ]);
    }

    public function build()
    {
        $this->checkInitBaseParameter();
        $this->initGeneralCategory();
        $this->initPackageAttributes();
        $this->builder->pack();

        $this->modx->log(modX::LOG_LEVEL_INFO, "\nСоздание пакета.\nВремя выполнения: {----}\n");
    }
}