<?php

use xPDO\xPDO;
use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;
use MODX\Revolution\modChunk;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modTemplate;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modTemplateVar;
use MODX\Revolution\modCategory;
use MODX\Revolution\modNamespace;

/**
 * TODO: Нужно добавить удаление других элементов (если потребуется)
 */

class ModxObjectManager
{
    /**
     * Возвращает имя класса для работы с объектами MODX
     *
     * @param string $nameAttr
     * @return class-string<xPDOObject>|null
     */
    public static function getModxClassName(string $nameAttr): ?string
    {
        return match ($nameAttr) {
            'TemplateVars' => modTemplateVar::class,
            'Chunks' => modChunk::class,
            'Snippets' => modSnippet::class,
            'Plugins' => modPlugin::class,
            'Templates' => modTemplate::class,
            'Categories' => modCategory::class,
            'Namespaces' => modNamespace::class,
            default => null,
        };
    }

    /**
     * Удаляет объект из базы MODX
     *
     * @param modX $modx      Экземпляр объекта modX
     * @param string $className  Класс объекта (например, modChunk::class)
     * @param string $uniqueKey  Название уникального ключа (например, 'name' или 'id')
     * @param mixed  $value      Значение ключа (например, 'myChunk' или 15)
     * @return bool              Возвращает true при успешном удалении, иначе false
     */
    public static function removeObject(modX $modx, string $className, string $uniqueKey, mixed $value): bool
    {
        $dbObject = $modx->getObject($className, [$uniqueKey => $value]);

        if (!$dbObject instanceof xPDOObject) {
            $modx->log(modX::LOG_LEVEL_WARN, "[Удаление объектов] Объект не найден: $className ($uniqueKey => $value)");
            return false;
        }

        $result = $dbObject->remove();
        $modx->log(
            $result ? modX::LOG_LEVEL_INFO : modX::LOG_LEVEL_ERROR,
            "[Удаление объектов] " . ($result ? "Удалён" : "Ошибка удаления") . " объект: $className ($uniqueKey => $value)"
        );

        return $result;
    }

    /**
     * Возвращает значение по ключу из объекта (можно передать JSON строку)
     *
     * @param mixed $object      Объект, из которого нужно получить значение
     * @param string $uniqueKey  Уникальный ключ для поиска значения в объекте
     * @return mixed|null        Возвращает значение по ключу или null, если не найдено
     */
    public static function getValueByKey(mixed $object, string $uniqueKey): mixed
    {
        // Если объект - это строка, пытаемся декодировать его как JSON
        if (is_string($object)) {
            $object = json_decode($object, true);
            if (!is_array($object)) {
                // Логируем ошибку, если JSON не удаётся декодировать
                modX::getInstance()->log(modX::LOG_LEVEL_ERROR, "[Удаление объектов] Ошибка декодирования JSON");
                return null;
            }
        }

        // Проверяем, существует ли ключ в объекте
        if (!isset($object[$uniqueKey])) {
            // Логируем ошибку, если ключ не найден
            modX::getInstance()->log(modX::LOG_LEVEL_ERROR, "[Удаление объектов] Ошибка: Ключ '$uniqueKey' отсутствует в объекте");
            return null;
        }

        return $object[$uniqueKey];
    }
}

class RemoveRelativeObject
{
    private modX $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    /**
     * Проверяет, является ли структура данных пакета корректной
     */
    private function isValidPackageData(mixed $packageData): bool
    {
        return is_array($packageData) && isset($packageData['unique_key'], $packageData['object']);
    }

    /**
     * Ищет объекты для удаления и удаляет их
     */
    public function searchAndRemoveObjects(mixed $relatedObjects): void
    {
        if (!is_array($relatedObjects)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[Удаление объектов] Ошибка: relatedObjects не является массивом');
            return;
        }

        foreach ($relatedObjects as $nameAttr => $package) {
            $className = ModxObjectManager::getModxClassName($nameAttr);

            if ($className === null) {
                $this->modx->log(modX::LOG_LEVEL_WARN, "[Удаление объектов] Неизвестный тип объекта: $nameAttr");
                continue;
            }

            if (!is_array($package)) {
                $this->modx->log(modX::LOG_LEVEL_WARN, "[Удаление объектов] Ошибка: $nameAttr не является массивом");
                continue;
            }

            foreach ($package as $packageData) {
                if ($this->isValidPackageData($packageData)) {
                    $object = $packageData['object'];
                    $uniqueKey = $packageData['unique_key'];

                    $value = ModxObjectManager::getValueByKey($object, $uniqueKey);
                    if ($value === null) {
                        continue;
                    }

                    $result = ModxObjectManager::removeObject(
                        $this->modx,
                        $className,
                        $uniqueKey,
                        $value
                    );

                    if (!$result) {
                        // Используем метод getValueByKey для получения значения
                        $logValue = ModxObjectManager::getValueByKey($object, $uniqueKey) ?? 'null';
                        $this->modx->log(modX::LOG_LEVEL_ERROR, "[Удаление объектов] Ошибка удаления объекта: $className ($uniqueKey => $logValue)");
                    }
                } else {
                    $this->modx->log(modX::LOG_LEVEL_WARN, '[Удаление объектов] Ошибка структуры данных:');
                    $this->modx->log(modX::LOG_LEVEL_WARN, '-- is_array($packageData): ' . (is_array($packageData) ? 'true' : 'false'));
                    $this->modx->log(modX::LOG_LEVEL_WARN, '-- unique_key: ' . (isset($packageData['unique_key']) ? 'Существует' : 'Не найдено'));
                    $this->modx->log(modX::LOG_LEVEL_WARN, '-- object: ' . (isset($packageData['object']) ? 'Существует' : 'Не найдено'));
                }
            }
        }
    }
}

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
/** @var xPDOObject $object */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return false;
}

$modx = $transport->xpdo;

// Устанавливаем действия для удаления
$packageAction = $options[xPDOTransport::PACKAGE_ACTION] ?? null;
if ($packageAction === xPDOTransport::ACTION_UNINSTALL) {
    $removeRelativeObject = new RemoveRelativeObject($modx);
    $removeRelativeObject->searchAndRemoveObjects($options["related_objects"] ?? []);

    if (
        $options["unique_key"] === 'category' &&
        array_key_exists("object", $options)
    ) {
        $value = ModxObjectManager::getValueByKey($options["object"],  $options["unique_key"]);
        if ($value !== null) {
            ModxObjectManager::removeObject($modx, modCategory::class, 'category', $value);
        }
    }

    if (
        is_string($options["namespace"]) &&
        $options["namespace"] !== ""
    ) {
        ModxObjectManager::removeObject($modx, modNamespace::class, 'name', $options["namespace"]);
    }
}

return true;
