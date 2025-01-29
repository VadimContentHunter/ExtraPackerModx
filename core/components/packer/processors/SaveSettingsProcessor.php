<?php

namespace Packer\Processors;

use MODX\Revolution\Processors\Processor;

class SaveSettingsProcessor extends Processor
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
                ],
                [
                    'id' => 1,
                    'name' => 'Какой то 1',
                    'namespaces' => "Packer",
                    'version' => "dev",
                ],
                [
                    'id' => 2,
                    'name' => 'Пункт 2',
                    'namespaces' => "Packer",
                    'version' => "2.0",
                ],
                [
                    'id' => 3,
                    'name' => 'Третий',
                    'namespaces' => "Третий",
                    'version' => "0.4-beta-2",
                ],
                [
                    'id' => 3,
                    'name' => 'Третий копия',
                    'namespaces' => "Packer Третий копия",
                    'version' => "0.1",
                ],
            ],
        ]);
    }

}
