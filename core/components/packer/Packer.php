<?php

namespace Packer;

use Error;
use MODX\Revolution\modX;
use MODX\Revolution\modNamespace;

class Packer
{
    public modX $modx;
    public $config = [];
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;

        $namespace = $modx->getObject(modNamespace::class, ['name' => 'packer']);
        if (!($namespace instanceof modNamespace)) {
            throw new Error("Не удалось найти в пространствах имен modx, параметр packer");
        }

        $corePath = empty($config['corePath']) ? $this->processPlaceholders($modx, $namespace->get('path')) : $config['corePath'];
        $assetsPath = empty($config['assetsPath']) ? $this->processPlaceholders($modx, $namespace->get('assets_path')) : $config['assetsPath'];
        $assetsUrl = empty($config['assetsUrl']) ? MODX_ASSETS_URL . 'components/packer/' : $config['assetsUrl'];

        $this->config = array_merge([
            'corePath' => $this->joinPath([$corePath], true),
            'assetsPath' => $this->joinPath([$assetsPath], true),
            'modelPath' => $this->joinPath([$corePath, 'model/'], false),
            'processorsPath' => $this->joinPath([$corePath, 'processors/'], false),
            'templatesPath' => $this->joinPath([$corePath, 'templates/'], false),
            'chunksPath' => $this->joinPath([$corePath, 'elements/chunks/'], false),
            'jsUrl' => $this->joinPath([$assetsUrl, 'js/'], false),
            'cssUrl' => $this->joinPath([$assetsUrl, 'css/'], false),
            'assetsUrl' => $this->joinPath([$assetsUrl], false),
            'connectorUrl' => $this->joinPath([$assetsUrl, 'connector.php'], false),
        ], $config);
    }

    public function processPlaceholders(modX $modx, string $path): string
    {
        // Ищем все плейсхолдеры в формате {ключ}
        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($modx) {
            $placeholder = $matches[1];
            // Получаем значение плейсхолдера из системных настроек
            return $modx->getOption($placeholder, null, $matches[0]);
        }, $path);
    }


    /**
     * Объединяет несколько частей пути безопасно (используя trim) и проверяет существование.
     *
     * @param array $parts Части пути для объединения.
     * @param bool $isRequired Проверять ли существование пути.
     * @return string Сформированный безопасный путь.
     * @throws Error Если путь не существует и $isRequired = true.
     */
    public function joinPath(array $parts, bool $isRequired = false): string
    {
        // Применяем trim ко всем частям пути, кроме первого абсолютного пути
        $trimmedParts = array_map(function ($part, $index) {
            return $index === 0 && str_starts_with($part, '/')
                ? rtrim($part, '/') // Оставляем ведущий слэш у первого абсолютного пути
                : trim($part, '/'); // Убираем лишние слэши у остальных частей
        }, $parts, array_keys($parts));

        // Объединяем части в единый путь
        $resultPath = implode('/', $trimmedParts);

        // Преобразуем в абсолютный путь для проверки
        $absolutePath = realpath($resultPath);

        // Проверяем существование пути, если это требуется
        if ($isRequired && $absolutePath === false) {
            throw new Error("Путь '{$resultPath}' не существует.");
        }

        return $resultPath;
    }


    /**
     * Возвращает путь по указанному ключу или объединяет его с переданным путем.
     * Если $updateCache = true, добавляет к результату метку времени.
     *
     * @param string $key Ключ параметра в конфигурации.
     * @param string|null $path Дополнительный путь для объединения.
     * @param bool $updateCache Добавить метку времени к пути.
     * @return string Обновленный или текущий путь.
     * @throws Error Если ключ не существует в конфигурации.
     */
    public function buildPath(string $key, ?string $path = null, bool $updateCache = false): string
    {
        if (!isset($this->config[$key])) {
            throw new Error("Параметр '{$key}' не найден в конфигурации.");
        }

        $basePath = rtrim($this->config[$key], '/');
        $resultPath = $path === null ? $basePath : $basePath . '/' . ltrim($path, '/');

        // Добавление временной метки, если указано
        if ($updateCache) {
            $resultPath .= '?time=' . time();
        }

        return $resultPath;
    }
}
