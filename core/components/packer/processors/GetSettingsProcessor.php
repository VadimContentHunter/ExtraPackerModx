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
            "project_name" => "Test1",
            "project_assets_url" => "extras/Packer/assets/components/packer",
            "namespace_name" => "testprojectone",
            "namespace_path_core" => "extras/Packer/core/components/packer/",
            "namespace_path_assets" => "extras/Packer/assets/components/packer/"
        ]);
    }
}
