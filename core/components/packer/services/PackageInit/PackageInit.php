<?php

namespace Packer\Services\PackageInit;

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

class PackageInit
{
    private ?modX $modx = null;

    private ?modCategory $generalCategory = null;

    private array $matchValueParameterParser  = [];

    private array $lateBindingData = [];

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

        $objNamespace = $this->modx->getObject(modNamespace::class, ['name' => $this->namespaceName]);
        if (!($objNamespace instanceof modNamespace)) {
            throw new Error("Invalid namespace");
        }

        $this->initCategory();
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

    public function executeLateBindingData(): bool
    {
        foreach ($this->lateBindingData as $className => $classData) {
            foreach ($classData as $dependentSearchFieldValue => $useData) {
                foreach ($useData as $useFieldName => $referenceData) {
                    $className                  = 'MODX\Revolution\\' . $className;
                    $dependentSearchFieldName   = $referenceData['dependentSearchFieldName'] ?? '';
                    $tableClassName             = $referenceData['tableClassName'] ?? '';
                    $searchFieldName            = $referenceData['searchFieldName'] ?? '';
                    $getFieldName               = $referenceData['getFieldName'] ?? '';
                    $searchFieldValue           = $referenceData['searchFieldValue'] ?? '';

                    $objResource = $this->modx->getObject($className, [
                        $dependentSearchFieldName => $dependentSearchFieldValue
                    ]);
                    if ($objResource instanceof $className) {

                        // поиск объекта с которым связываем
                        $tableClassName = 'MODX\Revolution\\' . $tableClassName;
                        $referenceObj = $this->modx->getObject($tableClassName, [
                            $searchFieldName => $searchFieldValue
                        ]);
                        if ($referenceObj instanceof $tableClassName) {
                            $objResource->set($useFieldName, $referenceObj->get($getFieldName));
                            return $objResource->save();
                        }
                    }
                    // END
                }
            }
        }

        return false;
    }

    public function checkInitBaseParameter()
    {
        if (
            empty($this->projectName) ||
            empty($this->namespaceName) ||
            empty($this->projectPath) ||
            $this->modx === null
        ) {
            throw new Error('Init base parameters are not set.');
        }
    }

    public function addToGeneralCategory(xPDOObject $obj)
    {
        $this->initCategory();
        $this->generalCategory->addMany($obj);
    }

    public function initCategory()
    {
        // Попытка найти существующую категорию по имени
        $this->generalCategory = $this->modx->getObject(modCategory::class, [
            'category' => 'Extra' . trim($this->projectName)
        ]);

        // Если категория не найдена, создаем новую
        if (!$this->generalCategory) {
            $this->generalCategory = $this->modx->newObject(modCategory::class, [
                'category' => 'Extra' . trim($this->projectName)
            ]);
        }
    }

    /**
     * Добавляет сниппеты в MODX.
     * 
     * @param array<string, array> $configs Параметры <НазваниеСниппета: конфигурации>
     * - snippet -  Можно использовать парсер для загрузки содержимого файла.
     * - static_file -  Можно использовать парсер для получения имени файла.
     * 
     * @return void
     */
    public function addSnippets(array $configs): void
    {
        $this->checkInitBaseParameter();

        foreach ($configs as $snippetName => $snippetConfig) {
            if (!is_string($snippetName)) {
                throw new Error("Некорректные настройки сниппета: " . ($snippetName ?? 'Неизвестный'));
            }

            // Получаем или создаем объект сниппета
            $snippet = $this->modx->getObject(modSnippet::class, ["name" => $snippetName])
                ?: $this->modx->newObject(modSnippet::class);

            // Устанавливаем имя, если оно отличается
            if ($snippet->get('name') !== $snippetName) {
                $snippet->set('name', $snippetName);
            }

            // Обрабатываем параметры сниппета
            foreach ($snippetConfig as $key => $value) {
                if ($key === 'name') continue;

                // Парсим snippet и static_file
                if (in_array($key, ['snippet', 'static_file'], true)) {
                    $value = ParameterParser::processBuild($value, $this->matchValueParameterParser);
                }

                $snippet->set($key, $value);
            }

            $this->addToGeneralCategory($snippet);
        }
    }


    /**
     * Добавляет чанки в MODX.
     *
     * @param array<string, array> $configs Параметры <НазваниеЧанка: конфигурации>
     * @return void
     */
    public function addChunks(array $configs): void
    {
        $this->checkInitBaseParameter();

        foreach ($configs as $chunkName => $chunkConfig) {
            if (!is_string($chunkName)) {
                throw new Error("Некорректные настройки chunk: " . ($chunkName ?? 'Неизвестный'));
            }

            $chunk = $this->modx->getObject(modChunk::class, ["name" => $chunkName])
                ?: $this->modx->newObject(modChunk::class);

            // Устанавливаем имя, если оно отличается
            if ($chunk->get('name') !== $chunkName) {
                $chunk->set('name', $chunkName);
            }

            foreach ($chunkConfig as $key => $value) {
                if (in_array($key, ['snippet', 'static_file'], true)) {
                    $value = ParameterParser::processBuild($value, $this->matchValueParameterParser);
                }

                if ($chunk->get($key) !== $value) {
                    $chunk->set($key, $value);
                }
            }

            $this->addToGeneralCategory($chunk);
        }
    }

    /**
     * Добавляет шаблоны в MODX.
     *
     * @param array<string, array> $configs Параметры <НазваниеTemplate: конфигурации>
     * @return void
     */
    public function addTemplates(array $configs): void
    {
        $this->checkInitBaseParameter();

        foreach ($configs as $templateName => $templateConfig) {
            if (!is_string($templateName)) {
                throw new Error("Некорректные настройки template: " . ($templateName ?? 'Неизвестный'));
            }

            $template = $this->modx->getObject(modTemplate::class, ["templatename" => $templateName])
                ?: $this->modx->newObject(modTemplate::class);

            // Устанавливаем имя, если оно отличается
            if ($template->get('templatename') !== $templateName) {
                $template->set('templatename', $templateName);
            }

            foreach ($templateConfig as $key => $value) {
                if (in_array($key, ['content', 'static_file'], true)) {
                    $value = ParameterParser::processBuild($value, $this->matchValueParameterParser);
                }

                if ($template->get($key) !== $value) {
                    $template->set($key, $value);
                }
            }

            $this->addToGeneralCategory($template);
        }
    }

    /**
     * Добавляет TV (пользовательские переменные) в MODX.
     *
     * @param array<string, array> $configs Параметры <НазваниеTV: конфигурации>
     * @return void
     */
    public function addTv(array $configs): void
    {
        $this->checkInitBaseParameter();

        foreach ($configs as $tvName => $tvConfig) {
            if (!is_string($tvName) || !isset($tvConfig['type'], $tvConfig['elements'])) {
                throw new Error("Некорректные настройки TV: " . ($tvName ?? 'Неизвестный'));
            }

            $tv = $this->modx->getObject(modTemplateVar::class, ["name" => $tvName])
                ?: $this->modx->newObject(modTemplateVar::class);

            // Устанавливаем имя, если оно отличается
            if ($tv->get('name') !== $tvName) {
                $tv->set('name', $tvName);
            }

            foreach ($tvConfig as $key => $value) {
                if ($tv->get($key) !== $value) {
                    $tv->set($key, $value);
                }
            }

            $this->addToGeneralCategory($tv);
        }
    }

    /**
     * Добавляет элементы меню в MODX.
     *
     * @param array<string, array> $configs Параметры <НазваниеМеню: конфигурации>
     * @return void
     */
    public function addMenu(array $configs): void
    {
        $this->checkInitBaseParameter();

        foreach ($configs as $menuName => $menuConfig) {
            if (!is_string($menuName) || !isset($menuConfig['action'])) {
                throw new Error("Некорректные настройки меню: " . ($menuName ?? 'Неизвестный'));
            }

            $menu = $this->modx->getObject(modMenu::class, ["text" => $menuName])
                ?: $this->modx->newObject(modMenu::class);

            // Устанавливаем имя, если оно отличается
            if ($menu->get('text') !== $menuName) {
                $menu->set('text', $menuName);
            }

            // Устанавливаем namespace, если он отличается
            if ($menu->get('namespace') !== $this->namespaceName) {
                $menu->set('namespace', $this->namespaceName);
            }

            foreach ($menuConfig as $key => $value) {
                if ($key === "namespace") continue;

                if ($menu->get($key) !== $value) {
                    $menu->set($key, $value);
                }
            }

            if ($menu->save()) {
                $this->modx->log(modX::LOG_LEVEL_INFO, "Создано меню: '{$menuName}'");
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, "Не удалось создать меню: '{$menuName}'");
            }
        }
    }



    /**
     * Добавляет ресурсы (страницы) в MODX.
     *
     * @param array<string, array> $configs Параметры <НазваниеResource: конфигурации>
     * @return void
     */
    public function addResources(array $configs): void
    {
        $this->checkInitBaseParameter();

        foreach ($configs as $resourcePageTitle => $resourceConfig) {
            if (!is_string($resourcePageTitle)) {
                throw new Error("Некорректные настройки Resource: " . ($resourcePageTitle ?? 'Неизвестный'));
            }

            $resource = $this->modx->getObject(modResource::class, ["pagetitle" => $resourcePageTitle])
                ?: $this->modx->newObject(modResource::class);

            // Устанавливаем pagetitle, если он отличается
            if ($resource->get('pagetitle') !== $resourcePageTitle) {
                $resource->set('pagetitle', $resourcePageTitle);
            }

            foreach ($resourceConfig as $key => $value) {
                if ($key === "id") continue;

                if (in_array($key, ["published", "show_in_tree"], true)) {
                    $value = 1; // Всегда 1
                } elseif (in_array($key, ["content", "template", "content_type"], true)) {
                    $value = ParameterParser::processBuild($value, $this->matchValueParameterParser);
                }

                if (in_array($key, ["template", "content_type"], true)) {
                    $this->addLateBindingData('modResource', $resourcePageTitle, $key, 'pagetitle', $value);
                    continue;
                }

                if ($resource->get($key) !== $value) {
                    $resource->set($key, $value);
                }
            }

            if ($resource->save()) {
                $this->modx->log(modX::LOG_LEVEL_INFO, "Создан/обновлён ресурс: '{$resourcePageTitle}'");
            } else {
                $this->modx->log(modX::LOG_LEVEL_ERROR, "Ошибка создания/обновления ресурса: '{$resourcePageTitle}'");
            }
        }
    }

    /**
     * @param array<string, array> $configs Параметры <НазваниеResource: конфигурации>
     * @return void
     */
    public function removeResources(array $configs): void
    {
        $this->checkInitBaseParameter();

        foreach ($configs as $resourcePageTitle => $resourceConfig) {
            if (!is_string($resourcePageTitle)) {
                throw new Error("Некорректные настройки Resource: " . ($resourcePageTitle ?? 'Неизвестный'));
            }

            $resource = $this->modx->getObject(modResource::class, ["pagetitle" => $resourcePageTitle]);
            if ($resource instanceof modResource) {
                $resource->remove();
                $this->modx->log(modX::LOG_LEVEL_INFO, "Удалён ресурс: '{$resourcePageTitle}'");
            }
        }
    }


    public function init()
    {
        // $this->modx->removeCollection(modSnippet::class, ['category' => $category->get('id')]);
        $this->checkInitBaseParameter();
        if ($this->generalCategory->save()) {
            if ($this->executeLateBindingData()) {
                // echo "Зависимости пофиксированы.\n";
                $this->modx->log(modX::LOG_LEVEL_INFO, "Зависимости пофиксированы.\n");
            } else {
                // echo "Ошибка пофиксирования зависимостей.\n";
                $this->modx->log(modX::LOG_LEVEL_ERROR, "Ошибка пофиксирования зависимостей.\n");
            }
            // echo "Создана категория 'General'.\n";
            $this->modx->log(modX::LOG_LEVEL_INFO, "Создана категория 'General'.\n");
        } else {
            // echo "Ошибка создания категории 'General'.\n";
            // exit;
            $this->modx->log(modX::LOG_LEVEL_ERROR, "Ошибка создания категории 'General'.\n");
        }

        $this->modx->cacheManager->refresh();


        // $this->modx->log(modX::LOG_LEVEL_INFO, "\nСоздание пакета.\nВремя выполнения: {----}\n");
    }

    public function deleteCategoryAndElements()
    {
        $this->modx->removeCollection(modChunk::class, ['category' => $this->generalCategory->get('id')]);
        $this->modx->removeCollection(modSnippet::class, ['category' => $this->generalCategory->get('id')]);
        $this->modx->removeCollection(modTemplateVar::class, ['category' => $this->generalCategory->get('id')]);
        $this->modx->removeCollection(modTemplate::class, ['category' => $this->generalCategory->get('id')]);
        $this->modx->removeCollection(modMenu::class, ['namespace' => $this->namespaceName]);
        $this->generalCategory->remove();
        $this->modx->log(modX::LOG_LEVEL_INFO, "Удалена категория и все связанные объекты.\n");

        $this->modx->cacheManager->refresh();
    }
}