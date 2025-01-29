<?php

// namespace Packer\Controllers;

use Packer\Packer;
use MODX\Revolution\modExtraManagerController;

class PackerMainManagerController extends modExtraManagerController
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
        return  [
            'packer:default',
            'packer:setting',
        ];
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
        return $this->modx->lexicon('packer');
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

        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/Packer.js', true));
        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/misc/PackerComboSearch.js', true));
        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/misc/PackerUtils.js', true));
        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/misc/VTypes.js', true));
        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/misc/PackerComboDropDawnList.js', true));

        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/windows/PackerWindowSettings.js', true));
        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/PackerGridComponents.js', true));

        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/PackerPanelMain.js', true));
        $this->addJavascript($this->packer->buildPath('assetsUrl', 'mgr/js/PackerPageMain.js', true));

        $this->addHtml('<script type="text/javascript">
        packerInstance.config = ' . json_encode($this->packer->config) . ';
        Ext.onReady(function() {MODx.load({ xtype: "packer-page-main"});});
        </script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $this->content .= '<div id="packer-panel-main-div"></div>';
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