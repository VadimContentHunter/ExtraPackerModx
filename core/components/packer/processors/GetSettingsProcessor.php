<?php

namespace Packer\Processors;

use MODX\Revolution\Processors\Processor;

class GetSettingsProcessor extends Processor
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

        return $this->success(object: [
            "project_name" => "",
            "project_path" => "",
            "project_assets_url" => "",
            "system_namespace_name" => "",
            "system_namespace_path_core" => "",
            "system_namespace_path_assets" => ""
        ]);
    }
}
