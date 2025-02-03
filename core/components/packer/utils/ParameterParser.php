<?php
namespace Packer\Utils;

class ParameterParser
{
    public static function processBuild(mixed $content, array $params = [])
    {
        // Проверяем, начинается ли строка с 'build:'
        if (is_string($content) && substr($content, 0, 6) === 'build:') {
            $content = substr($content, 6);

            if (str_starts_with($content, 'path:')) {
                $content = self::processPath($content, $params);
            } elseif (str_starts_with($content, 'bd:')) {
                $content = self::processBd($content);
            }
        }

        return $content;
    }

    public static function processPath(string $content, array $matchValue = []): string
    {
        if (substr($content, 0, 5) === 'path:') {
            $filePath = substr($content, 5);
            $filePath = self::getNewPath($filePath, $matchValue);

            if (str_starts_with($filePath, 'read:')) {
                $filePath = str_replace('read:', '', $content);
                if (file_exists($filePath)) {
                    return file_get_contents($filePath) ?: '';  // Возвращаем содержимое файла
                } else {
                    return '';
                }
            }

            return $filePath;
        }

        return $content;
    }

    public static function processBd(string $content): mixed
    {
        if (str_starts_with($content, 'bd:')) {
            $valuesQuery = explode(":", str_replace('bd:', '', $content));
            if (count($valuesQuery) < 4) {
                return '';
            }

            return [
                'tableClassName' => $valuesQuery[0] ?? '',
                'searchFieldName' => $valuesQuery[1] ?? '',
                'getFieldName' => $valuesQuery[2] ?? '',
                'searchFieldValue' => $valuesQuery[3] ?? '',
            ];
        }

        return null;
    }

    public static function getNewPath($path, $matchValue = [])
    {
        preg_match_all('/\$\{\{([^}]+)\}\}/', $path, $matches);

        foreach ($matches[1] as $match) {
            if (isset($matchValue[$match])) {
                $path = str_replace('${{' . $match . '}}', $matchValue[$match], $path);
            }
        }

        return $path;
    }
}
