<?php
namespace Packer\Services\PackageInit;

use Error;
use Packer\Services\PackageInit\PackageInit;

class PackageInitFactory
{
    /**
     * Создает объект PackageBuilder из массива конфигурации.
     *
     * @param array $config
     * @return PackageInit
     * @throws Error Если отсутствуют обязательные параметры.
     */
    public static function createFromConfig(array $config): PackageInit
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
        return new PackageInit(
            $config['project_name'],
            $config['system_namespace_name'],
            $config['project_path'],
            $config['system_namespace_path_core'],
            $sourceAssets,
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
    public static function createFromJsonFile(string $filePath): PackageInit
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
