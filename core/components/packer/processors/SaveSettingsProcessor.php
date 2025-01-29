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
                    'page_id' => 0,
                    'parent_page_id' => 0,
                    'count_product' =>  0,
                    'activate' => false,
                    'actions' => false,
                ],
                [
                    'id' => 1,
                    'name' => 'Какой то 1',
                    'page_id' => 0,
                    'parent_page_id' => 0,
                    'count_product' =>  0,
                    'activate' => false,
                    'actions' => false,
                ],
                [
                    'id' => 2,
                    'name' => 'Пункт 2',
                    'page_id' => 2,
                    'parent_page_id' => 2,
                    'count_product' =>  40,
                    'activate' => false,
                    'actions' => false,
                ],
                [
                    'id' => 3,
                    'name' => 'Третий',
                    'page_id' => 3,
                    'parent_page_id' => 3,
                    'count_product' =>  30,
                    'activate' => false,
                    'actions' => false,
                ],
                [
                    'id' => 3,
                    'name' => 'Третий копия',
                    'page_id' => 33,
                    'parent_page_id' => 33,
                    'count_product' =>  330,
                    'activate' => false,
                    'actions' => false,
                ],
            ],
        ]);
    }

}
