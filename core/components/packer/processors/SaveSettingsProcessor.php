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
            'results' => [],
        ]);
    }

}
