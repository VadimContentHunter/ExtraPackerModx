<?php
/**
 * Свойства сниппета по умолчанию
 *
 * @package quip
 * @subpackage build
 */
$properties = array(
    array(
        'name' => 'closed',
        'desc' => 'Если установлено значение true, ветка не будет принимать новые комментарии. ',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => false,
    ),
    array(
        'name' => 'dateFormat',
        'desc' => 'Формат дат, отображаемых для комментария.',
        'type' => 'textfield',
        'options' => '',
        'value' => '%b %d, %Y at %I:%M %p',
    ),
    /* ...другое удалено для краткости ... */
);
return $properties;