<?php

use xPDO\xPDO;
use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modTemplateVar;

/**
 * TODO: НУжно добавить удаление Пространства имен, категории и других Элементов
 */

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
/** @var xPDOObject $object */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return false;
}

$modx = $transport->xpdo;

function removeTemplateObj(modX $modx, mixed $uniqueKey, mixed $object)
{
    if (is_string($object)) {
        $object = json_decode($object, true);
    }

    if (
        is_array($object) &&
        is_string($uniqueKey) &&
        $uniqueKey !== "" &&
        array_key_exists($uniqueKey, $object)
    ) {
        $tv = $modx->getObject(modTemplateVar::class, [$uniqueKey => $object[$uniqueKey]]);
        if ($tv instanceof modTemplateVar) {
            $r = $tv->remove();
            $modx->log(
                modX::LOG_LEVEL_INFO,
                '[removeTemplateObj] $tv :' . ($r === true ? "Удалось удалить" : "Не удалось удалить")
            );
        } else {
            $modx->log(
                modX::LOG_LEVEL_INFO,
                '[removeTemplateObj] Не удалось получить объект gettype($tv):' . gettype($tv)
            );

            $modx->log(
                modX::LOG_LEVEL_INFO,
                '[removeTemplateObj] $object[$uniqueKey]:' . $object[$uniqueKey]
            );
        }
    } else {
        $modx->log(
            modX::LOG_LEVEL_INFO,
            '[removeTemplateObj] Не корректные данные. $uniqueKey:'
                . ($uniqueKey ?? '') . ' | is_array($object) = ' . (is_array($object) ? 'true' : 'false')
        );
    }
}

// Устанавливаем действия для удаления
$packageAction = $options[xPDOTransport::PACKAGE_ACTION];
if ($packageAction === xPDOTransport::ACTION_UNINSTALL) {
    $relatedObjects = $options["related_objects"];
    if (is_array($relatedObjects)) {
        foreach ($relatedObjects as $nameAttr => $package) {
            if ($nameAttr === "TemplateVars") {
                foreach ($package as $packageKey => $packageData) {
                    if (
                        is_array($packageData) &&
                        array_key_exists('unique_key', $packageData) &&
                        array_key_exists('object', $packageData)
                    ) {
                        removeTemplateObj($modx, $packageData['unique_key'], $packageData['object']);
                    } else {
                        $modx->log(modX::LOG_LEVEL_INFO, '[УДАЛЕНИЕ ЭЛЕМЕНТОВ] is_array($packageData): '
                            . (is_array($packageData) ? 'true' : 'false'));
                        $modx->log(modX::LOG_LEVEL_INFO, '-- $unique_key: '
                            . (array_key_exists('unique_key', $packageData) ? 'Существует' : "Не Найдено"));
                        $modx->log(modX::LOG_LEVEL_INFO, '-- $object: '
                            . (array_key_exists('unique_key', $packageData) ? 'Существует' : "Не Найдено"));
                    }
                }
            } else {
                $modx->log(modX::LOG_LEVEL_INFO, '[УДАЛЕНИЕ ЭЛЕМЕНТОВ] $nameAttr: ' . $nameAttr);
            }
        }
    }
}

return true;
