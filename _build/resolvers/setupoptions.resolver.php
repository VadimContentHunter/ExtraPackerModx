<?php

use xPDO\xPDO;
use xPDO\Transport\xPDOTransport;

/**
 * Разрешаем настройки путем настройки параметров электронной почты.
 *
 * @package quip
 * @subpackage build
 */
$success = false;
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        /* emailsTo */
        $setting = $object->xpdo->getObject('modSystemSetting', array('key' => 'quip.emailsTo'));
        if ($setting != null) {
            $setting->set('value', $options['emailsTo']);
            $setting->save();
        } else {
            $object->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[Quip] emailsTo параметр не может быть найден, поэтому параметр не может быть изменен.');
        }

        /* emailsFrom */
        $setting = $object->xpdo->getObject('modSystemSetting', array('key' => 'quip.emailsFrom'));
        if ($setting != null) {
            $setting->set('value', $options['emailsFrom']);
            $setting->save();
        } else {
            $object->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[Quip] Параметр emailsFrom не может быть найден, поэтому параметр не может быть изменен. ');
        }

        /* emailsReplyTo */
        $setting = $object->xpdo->getObject('modSystemSetting', array('key' => 'quip.emailsReplyTo'));
        if ($setting != null) {
            $setting->set('value', $options['emailsReplyTo']);
            $setting->save();
        } else {
            $object->xpdo->log(xPDO::LOG_LEVEL_ERROR, '[Quip] Параметр emailsReplyTo не может быть найден, поэтому параметр не может быть изменен.');
        }

        $success = true;
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        $success = true;
        break;
}
return $success;
