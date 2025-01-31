<?php

require_once dirname(__FILE__) . '/build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;
use MODX\Revolution\modChunk;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modCategory;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modNamespace;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\Transport\modPackageBuilder;
use MODX\Revolution\Transport\modTransportVehicle;

class SettingVehicle
{
    private ?modTransportVehicle $vehicle = null;

    private ?xPDOObject $obj = null;

    private array $attributes = [];

    public function __construct(
        private modX $modx,
        private modPackageBuilder $builder
    ) {}

    public function checkVehicle()
    {
        if ($this->vehicle === null) {
            throw new Error("Не создан Vehicle");
        }
    }

    public function setObject(xPDOObject $obj, string $uniqueKey, bool $update = true, bool $setOldPK = false)
    {
        $this->obj = $obj;
        $this->attributes = [
            xPDOTransport::UNIQUE_KEY => $uniqueKey,
            xPDOTransport::UPDATE_OBJECT => $update,
            xPDOTransport::PRESERVE_KEYS => $setOldPK,
        ];
    }

    public function addRelatedObjAttribute(string $attributeName, string $uniqueKey, bool $update = true, bool $setOldPK = false)
    {
        if (!array_key_exists(xPDOTransport::RELATED_OBJECTS, $this->attributes)) {
            $this->attributes[xPDOTransport::RELATED_OBJECTS] = true;
        }

        if (!array_key_exists(xPDOTransport::RELATED_OBJECT_ATTRIBUTES, $this->attributes)) {
            $this->attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES] = [];
        }

        $this->attributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES][$attributeName] = [
            xPDOTransport::UNIQUE_KEY => $uniqueKey,
            xPDOTransport::UPDATE_OBJECT => $update,
            xPDOTransport::PRESERVE_KEYS => $setOldPK,
        ];
    }

    private function createVehicle()
    {
        if ($this->obj === null) {
            throw new Error("Не корректный объект для создании Vehicle");
        }
        if ($this->vehicle === null) {
            $this->vehicle = $this->builder->createVehicle($this->obj, $this->attributes);
        }
    }

    public function copyFile(string $sourcePath, string $targetPath)
    {
        $this->createVehicle();
        $this->checkVehicle();
        $this->vehicle->resolve('file', array(
            'source' => $sourcePath,
            'target' => "return '$targetPath';",
        ));
    }

    public function addResolver(string $sourcePath)
    {
        $this->createVehicle();
        $this->checkVehicle();
        $this->vehicle->resolve('php', array(
            'source' => $sourcePath,
        ));
    }

    public function putVehicle()
    {
        $this->createVehicle();
        $this->checkVehicle();
        $this->builder->putVehicle($this->vehicle);
    }
}


class PackageBuilder
{
    private ?modX $modx = null;

    private ?modPackageBuilder $builder = null;

    private ?modCategory $generalCategory = null;

    private string $newCorePath = '';

    private string $newAssetsPath = '';

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
            throw new Exception('Init base parameters are not set.');
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
        $settingVehicle->copyFile($this->sourceCore, MODX_CORE_PATH . 'components/');

        if (is_dir($this->sourceAssets)) {
            $settingVehicle->copyFile($this->sourceAssets, '/var/www/test-modx/assets/' . 'components/');
        }

        $settingVehicle->addResolver(rtrim($this->projectPath) . '/_build/resolvers/uninstall_package.resolver.php');
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

class PackageBuilderFactory
{
    /**
     * Создает объект PackageBuilder из массива конфигурации.
     *
     * @param array $config
     * @return PackageBuilder
     * @throws Error Если отсутствуют обязательные параметры.
     */
    public static function createFromConfig(array $config): PackageBuilder
    {
        $requiredKeys = [
            'project_name',
            'project_path',
            // 'project_assets_url',
            'system_namespace_name',
            'system_namespace_path_core',
            // 'system_namespace_path_assets',
            // 'system_assets_url_key',
            // 'version',
            // 'release'
        ];

        // Проверяем, что все обязательные ключи присутствуют
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new Error("Отсутствует обязательный параметр: {$key}");
            }
        }

        // Определяем переменные с дефолтными значениями
        $sourceAssets = $config['system_namespace_path_assets'] ?? null;
        $version = $config['version'] ?? "1.0";
        $release = $config['release'] ?? "dev";

        return new PackageBuilder(
            $config['project_name'],
            $config['system_namespace_name'],
            $config['project_path'],
            $config['system_namespace_path_core'],
            $sourceAssets,
            $version,
            $release,
            $config // Передаем весь конфиг
        );
    }

    /**
     * Создает объект PackageBuilder из JSON-файла.
     *
     * @param string $filePath Путь к JSON-файлу.
     * @return PackageBuilder
     * @throws Error Если файл не найден, невалидный JSON или отсутствуют параметры.
     */
    public static function createFromJsonFile(string $filePath): PackageBuilder
    {
        if (!file_exists($filePath)) {
            throw new Error("Файл конфигурации не найден: {$filePath}");
        }

        $jsonContent = file_get_contents($filePath);
        $config = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Error("Ошибка парсинга JSON: " . json_last_error_msg());
        }

        return self::createFromConfig($config);
    }
}

$packageBuilder = PackageBuilderFactory::createFromConfig(PROJECT_DATA);
$packageBuilder->addTv(json_decode(
    file_get_contents(
        PROJECT_DATA["project_path"] . "configs/tvs.conf.json"
    ),
    true
));
$packageBuilder->build();
