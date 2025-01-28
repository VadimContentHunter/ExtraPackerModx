<?php

// namespace Packer\Controllers;

use Packer\Packer;
use MODX\Revolution\modExtraManagerController;

$a = 0;

class PackerSettingManagerController extends modExtraManagerController
{
    public Packer $packer;


    /**
     *
     */
    public function initialize()
    {
        $this->packer = $this->modx->services->get('Packer');
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return [];
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return 'Packer';
        // return $this->modx->lexicon('synccatalogmanager');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        // $this->addCss($this->SyncCatalogManager->updateUrlConfig('cssUrl', 'mgr/icons.css', true));
        // $this->addJavascript($this->SyncCatalogManager->updateUrlConfig('jsUrl', 'mgr/synccatalogmanager.js', true));

        // $this->addHtml('<script type="text/javascript">
        // SyncCatalogManager.config = ' . json_encode($this->SyncCatalogManager->config) . ';
        // SyncCatalogManager.config.connector_url = "' . ($this->SyncCatalogManager->getConfig('connectorUrl') ?? '') . '";
        // Ext.onReady(function() {MODx.load({ xtype: "synccatalogmanager-page-catalog"});});
        // </script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        // $this->content .= '<div id="synccatalogmanager-panel-catalog-div"></div>';
        return '';
    }

    /**
     * Do any page-specific logic and/or processing here
     *
     * @param array $scriptProperties
     *
     * @return void
     */
    public function process(array $scriptProperties = []) {}

    /**
     * Define the default controller action for this namespace
     *
     * @static
     * @return string A default controller action
     */
    public static function getDefaultController()
    {
        return 'index';
    }
}