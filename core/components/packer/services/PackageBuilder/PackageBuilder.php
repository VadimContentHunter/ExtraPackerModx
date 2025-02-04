<?php

namespace Packer\Services\PackageBuilder;

use Error;
use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;
use MODX\Revolution\modMenu;
use MODX\Revolution\modChunk;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modCategory;
use MODX\Revolution\modResource;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modNamespace;
use Packer\Utils\ParameterParser;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\Transport\modPackageBuilder;
use Packer\Packer;
use Packer\Services\PackageBuilder\SettingVehicle;

class PackageBuilder
{
    private ?modX $modx = null;

    private ?modPackageBuilder $builder = null;

    private ?modCategory $generalCategory = null;

    private array $matchValueParameterParser  = [];

    private array $lateBindingData = [];

    private ?Packer $packer = null;

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
        $this->matchValueParameterParser = [
            "project_path" => $this->projectPath,
            "base_path" => $this->projectPath,
            "core_path" => $this->sourceCore,
            "assets_path" => $this->sourceAssets ?? '',
            "namespace_name" => $this->namespaceName,
            "relative_core_path" => $this->config['relative_core_path'] ?? 'core/components/' . $this->namespaceName . '/',
        ];

        $this->modx = new modX();
        $this->modx->initialize('mgr');
        $this->modx->setLogLevel(modX::LOG_LEVEL_INFO);
        $this->modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

        $this->packer = $this->modx->services->get('Packer');
        $this->initPackage();
        $this->generalCategory = $this->modx->newObject(modCategory::class, [
            'category' => $this->projectName
        ]);
        
    }

    public function addLateBindingData(
        string $dependentClassName,
        string $dependentSearchFieldValue,
        string $dependentUseFieldName,
        string $dependentSearchFieldName,
        array $referenceData
    ): void {
        // Убеждаемся, что для указанного класса есть массив
        if (!isset($this->lateBindingData[$dependentClassName])) {
            $this->lateBindingData[$dependentClassName] = [];
        }

        if (!isset($this->lateBindingData[$dependentClassName][$dependentSearchFieldValue])) {
            $this->lateBindingData[$dependentClassName][$dependentSearchFieldValue] = [];
        }

        if (!isset($this->lateBindingData[$dependentClassName][$dependentSearchFieldValue][$dependentUseFieldName])) {
            $this->lateBindingData[$dependentClassName][$dependentSearchFieldValue][$dependentUseFieldName] = [];
        }

        $referenceData = array_merge(
            $referenceData,
            [
                'dependentSearchFieldName' => $dependentSearchFieldName,
            ],
        );

        // Объединяем переданные данные с уже существующими
        $this->lateBindingData[$dependentClassName][$dependentSearchFieldValue][$dependentUseFieldName] = array_merge(
            $this->lateBindingData[$dependentClassName][$dependentSearchFieldValue][$dependentUseFieldName],
            $referenceData
        );
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

        $corePath = '{core_path}components/' . strtolower($this->namespaceName) . '/';
        $assetsPath = '';

        $objNamespace = $this->modx->getObject(modNamespace::class, ['name' => $this->namespaceName]);
        if ($objNamespace instanceof modNamespace) {
            $assetsPath = $objNamespace->get('assets_path') !== null
                ? '{assets_path}components/' . strtolower($this->namespaceName) . '/'
                : '';
        }

        // Создаём транспортный пакет
        $this->builder = new modPackageBuilder($this->modx);
        $this->builder->createPackage($this->projectName, $this->version, $this->release);
        $this->builder->registerNamespace($this->namespaceName, false, true, $corePath, $assetsPath);
    }

    /**
     * @param array<string,array> $configs Параметры <НазваниеСниппета: конфигурации>
     * - snippet -  в данном параметре можно использовать парсер, для получении
     *              содержимого в файле "build:path:/.../${{base_path}}.../content.file"
     * - static_file -  в данном параметре можно использовать парсер,для получения 
     *                  имени файла "build:path:name:/.../${{base_path}}.../content.file"
     * @return void
     */
    public function addSnippets(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $snippetName => $snippetConfig) {
            if (is_string($snippetName)) {
                $snippet = $this->modx->newObject(modSnippet::class);
                $snippet->set('name', $snippetName);
                foreach ($snippetConfig as $key => $value) {
                    if ($key === "snippet") {
                        $snippet->set($key, ParameterParser::processBuild($value, $this->matchValueParameterParser));
                        continue;
                    }

                    if ($key === "static_file") {
                        $snippet->set($key, ParameterParser::processBuild($value, $this->matchValueParameterParser));
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
     * - snippet -  в данном параметре можно использовать парсер, для получении
     *              содержимого в файле "build:path:/.../${{base_path}}.../content.file"
     * - static_file -  в данном параметре можно использовать парсер,для получения 
     *                  имени файла "build:path:name:/.../${{base_path}}.../content.file"
     * @return void
     */
    public function addChunks(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $chunkName => $chunkConfig) {
            if (is_string($chunkName)) {
                $chunk = $this->modx->newObject(modChunk::class);
                $chunk->set('name', $chunkName);
                foreach ($chunkConfig as $key => $value) {
                    if ($key === "snippet") {
                        $chunk->set($key, ParameterParser::processBuild($value, $this->matchValueParameterParser));
                        continue;
                    }

                    if ($key === "static_file") {
                        $chunk->set($key, ParameterParser::processBuild($value, $this->matchValueParameterParser));
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
     *  - content - в данном параметре можно использовать парсер, для получении
     *              содержимого в файле "build:path:/.../${{base_path}}.../content.file"
     *  - static_file - в данном параметре можно использовать парсер,для получения 
     *                  имени файла "build:path:name:/.../${{base_path}}.../content.file"
     * @return void
     */
    public function addTemplates(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $templateName => $templateConfig) {
            if (is_string($templateName)) {

                $template = $this->modx->newObject(modTemplate::class);
                $template->set('templatename', $templateName);
                foreach ($templateConfig as $key => $value) {
                    if ($key === "content") {
                        $template->set($key, ParameterParser::processBuild($value, $this->matchValueParameterParser));
                        continue;
                    }

                    if ($key === "static_file") {
                        $template->set($key, ParameterParser::processBuild($value, $this->matchValueParameterParser));
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

    /**
     * @param array<string,array> $configs Параметры <НазваниеМеню: конфигурации>
     * @return void
     */
    public function addMenu(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $menuName => $menuConfig) {
            if (
                is_string($menuName) &&
                array_key_exists('action', $menuConfig)
            ) {
                // $this->modx->log(modX::LOG_LEVEL_INFO, $menuName);
                $menu = $this->modx->newObject(modMenu::class);
                $menu->set('text', $menuName);
                $menu->set('namespace', $this->namespaceName);
                foreach ($menuConfig as $key => $value) {
                    if ($key === "namespace") {
                        continue;
                    }
                    $menu->set($key, $value);
                }

                $settingVehicle = new SettingVehicle($this->modx, $this->builder);
                $settingVehicle->setObject($menu, 'text', setOldPK: true);
                $settingVehicle->putVehicle();
            } else {
                throw new Error("Не корректные настройки TV '" . $menuName ?? 'Неизвестный' . "'");
            }
        }
    }

    /**
     * @param array<string,array> $configs Параметры <НазваниеResource: конфигурации>
     * @return void
     */
    public function addResources(array $configs)
    {
        $this->checkInitBaseParameter();
        foreach ($configs as $resourcePageTitle => $resourceConfig) {
            if (is_string($resourcePageTitle)) {

                $resource = $this->modx->newObject(modResource::class);
                $resource->set('pagetitle', $resourcePageTitle);
                foreach ($resourceConfig as $key => $value) {
                    if ($key === "id") {
                        continue;
                    }
                    if ($key === "published") {
                        $resource->set($key, 1);
                        continue;
                    }

                    if ($key === "content") {
                        $resource->set($key, ParameterParser::processBuild($value, $this->matchValueParameterParser));
                        continue;
                    }

                    if ($key === "template") {
                        $this->addLateBindingData(
                            'modResource',
                            $resourcePageTitle,
                            'template',
                            'pagetitle',
                            ParameterParser::processBuild($value)
                        );
                        continue;
                    }

                    if ($key === "content_type") {
                        $this->addLateBindingData(
                            'modResource',
                            $resourcePageTitle,
                            'content_type',
                            'pagetitle',
                            ParameterParser::processBuild($value)
                        );
                        continue;
                    }

                    if ($key === "show_in_tree") {
                        $resource->set($key, 1);
                        continue;
                    }

                    $resource->set($key, $value);
                }

                $settingVehicle = new SettingVehicle($this->modx, $this->builder);
                $settingVehicle->setObject($resource, 'pagetitle');
                $settingVehicle->putVehicle();
            } else {
                throw new Error("Не корректные настройки Resource '" . $resourcePageTitle ?? 'Неизвестный' . "'");
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
        $settingVehicle->addAttribute('LateBindingData', $this->lateBindingData);
        $settingVehicle->setObject($this->generalCategory, 'category');
        $settingVehicle->addRelatedObjAttribute('Snippets', 'name');
        $settingVehicle->addRelatedObjAttribute('Chunks', 'name');
        $settingVehicle->addRelatedObjAttribute('Templates', 'templatename');
        $settingVehicle->addRelatedObjAttribute('TemplateVars', 'name');
        $settingVehicle->addRelatedObjAttribute('Menus', 'text');
        $settingVehicle->copyFile($this->sourceCore, MODX_CORE_PATH . 'components/');

        if (is_dir($this->sourceAssets)) {
            $settingVehicle->copyFile($this->sourceAssets, '/var/www/test-modx/assets/' . 'components/');
        }

        $settingVehicle->addResolver($this->packer->buildPath('corePath', 'services/PackageBuilder/resolvers/uninstall_package.resolver.php') );
        $settingVehicle->addResolver($this->packer->buildPath('corePath', 'services/PackageBuilder/resolvers/LateBindingData.resolver.php'));
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
