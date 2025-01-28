class PackerPageMain extends MODx.Component {
    static xtype = "packer-page-main";

    constructor(config = {}) {
        Ext.applyIf(config, {
            components: [
                {
                    xtype: PackerPanelMain.xtype,
                    renderTo: "packer-panel-main-div",
                },
            ],
        });
        super(config);
    }
} 

Ext.reg(PackerPageMain.xtype, PackerPageMain);

// packerInstance
// const packerSectionInstance = new PackerPage(packerInstance, 'packer-panel-main-div');