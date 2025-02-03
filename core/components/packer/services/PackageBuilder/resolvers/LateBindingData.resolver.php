<?php

use xPDO\xPDO;
use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;
use MODX\Revolution\modChunk;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modCategory;
use MODX\Revolution\modResource;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modNamespace;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modTemplateVar;

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
/** @var xPDOObject $object */
if (!$transport->xpdo || !($transport instanceof xPDOTransport)) {
    return false;
}

$modx = $transport->xpdo;

function bindData(
    modX $modx,
    string $className,
    string $dependentSearchFieldValue,
    string $dependentUseFieldName,
    array $referenceData
) {
    $className                  = 'MODX\Revolution\\' . $className;
    $dependentSearchFieldName   = $referenceData['dependentSearchFieldName'] ?? '';
    $tableClassName             = $referenceData['tableClassName'] ?? '';
    $searchFieldName            = $referenceData['searchFieldName'] ?? '';
    $getFieldName               = $referenceData['getFieldName'] ?? '';
    $searchFieldValue           = $referenceData['searchFieldValue'] ?? '';


    $objResource = $modx->getObject($className, [
        $dependentSearchFieldName => $dependentSearchFieldValue
    ]);
    if ($objResource instanceof $className) {
        $tableClassName = 'MODX\Revolution\\' . $tableClassName;
        $referenceObj = $modx->getObject($tableClassName, [
            $searchFieldName => $searchFieldValue
        ]);
        if ($referenceObj instanceof $tableClassName) {
            $objResource->set($dependentUseFieldName, $referenceObj->get($getFieldName));
            $r = $objResource->save();
            if ($r) {
                $modx->log(
                    modx::LOG_LEVEL_INFO,
                    '[Позднее связывание] Присвоено зависимуму ресурсу ' .
                        $className . ' : ' . $dependentUseFieldName . ' значение поля ' . $referenceObj->get($getFieldName)
                );
            } else {
                $modx->log(
                    modx::LOG_LEVEL_ERROR,
                    '[Позднее связывание] Не удалось обновить зависимый ресурс ' .
                        $className . ' : ' . $dependentUseFieldName . ' значение поля ' . $referenceObj->get($getFieldName)
                );
            }
        } else {
            $modx->log(
                modx::LOG_LEVEL_ERROR,
                '[Позднее связывание] Не удалось найти связанный объект по полю ' . $searchFieldName .
                    ' с значением ' . $searchFieldValue
            );
        }
    } else {
        $modx->log(
            modx::LOG_LEVEL_ERROR,
            '[Позднее связывание] Не удалось найти ресурс по полю ' . $dependentSearchFieldName
                . ' значение поля ' . $dependentSearchFieldValue
                . ' или не корректный класс ' . $className
        );
    }
}

// Устанавливаем действия для удаления
$packageAction = $options[xPDOTransport::PACKAGE_ACTION] ?? null;
$modx->log(modX::LOG_LEVEL_INFO, '[Позднее связывание] $packageAction:' . $packageAction);
$modx->log(modX::LOG_LEVEL_INFO, '[Позднее связывание] ACTION_INSTALL:' . xPDOTransport::ACTION_INSTALL);
$modx->log(modX::LOG_LEVEL_INFO, '[Позднее связывание] ACTION_UPGRADE:' . xPDOTransport::ACTION_UPGRADE);
// Действие при установке
if (
    $options[xPDOTransport::PACKAGE_ACTION] == xPDOTransport::ACTION_INSTALL
    || $options[xPDOTransport::PACKAGE_ACTION] == xPDOTransport::ACTION_UPGRADE
) {
    if (isset($options['LateBindingData'])) {
        foreach ($options['LateBindingData'] as $className => $objects) {
            // echo "Класс: $className\n";

            foreach ($objects as $fieldValue => $fields) {
                // echo "Значение основного поля: $className\n";

                foreach ($fields as $useField => $data) {
                    bindData($modx, $className, $fieldValue, $useField, $data);
                }
            }
        }
    } else {
        $modx->log(modX::LOG_LEVEL_INFO, '[Позднее связывание] Массив данных для связывания пуст.');
        // $modx->log(modX::LOG_LEVEL_INFO, json_encode($options));
    }
}

return true;
