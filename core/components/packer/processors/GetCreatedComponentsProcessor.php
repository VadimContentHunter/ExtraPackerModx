<?php

namespace Packer\Processors;

use MODX\Revolution\Processors\Processor;

class GetCreatedComponentsProcessor extends Processor
{
    public function process() {
        // $productId = $this->getProperty('product_id');
        // if($productId === null){
        //     $this->failure('id продукта не определенно.');
        // }

        // $loggerSyncBd = new LoggerSyncBd($this->modx);
        // $product = $this->getProducts($productId, $loggerSyncBd);
        // return $product !== null
        //             ? $this->success('Данные продукта получены успешно.', $product)
        //             : $this->failure('Данные продукта небыли найдены.');

        return json_encode([
            'success' => true,
            'total' => 12,
            'results' => [
                [
                    'id' => 0,
                    'name' => '-----',
                    'namespaces' => "Packer",
                    'version' => "0.4-beta",
                    'actions' => $this->getActions()
                ],
                [
                    'id' => 1,
                    'name' => 'Какой то 1',
                    'namespaces' => "Packer",
                    'version' => "dev",
                    'actions' => $this->getActions()
                ],
                [
                    'id' => 2,
                    'name' => 'Пункт 2',
                    'namespaces' => "Packer",
                    'version' => "2.0",
                    'actions' => $this->getActions()
                ],
                [
                    'id' => 3,
                    'name' => 'Третий',
                    'namespaces' => "Третий",
                    'version' => "0.4-beta-2",
                    'actions' => $this->getActions()
                ],
                [
                    'id' => 3,
                    'name' => 'Третий копия',
                    'namespaces' => "Packer Третий копия",
                    'version' => "0.1",
                    'actions' => $this->getActions()
                ],
            ],
        ]);
    }

    public function getActions(): array
    {
        $active = false;
        return [
            [
                "action" => "openEditProduct",
                "button" => true,
                "cls" => "",
                "icon" => "icon-edit-pen",
                "menu" => true,
                "multiple" => false,
                "onlyMultiple" => false,
                "title" => "Обзор / Редактировать",
            ],
            [
                "action" => $active ? "disableItem" : "enableItem",
                "button" => true,
                "cls" => "",
                "icon" => "icon-power" . ($active ? " icon-power-red" : " icon-power-green"),
                "menu" => true,
                "multiple" => $active ? "Выключить товары" : "Включить товары",
                "title" => $active ? "Выключить товар" : "Включить товар",
            ],
            [
                "action" => !$active ? "disableItem" : "enableItem",
                "button" => false,
                "cls" => "",
                "icon" => "icon-power" . (!$active ? " icon-power-red" : " icon-power-green"),
                "menu" => true,
                "multiple" => !$active ? "Выключить товары" : "Включить товары",
                "onlyMultiple" => true,
                "title" => !$active ? "Выключить товар" : "Включить товар",
            ],
        ];
    }
}
