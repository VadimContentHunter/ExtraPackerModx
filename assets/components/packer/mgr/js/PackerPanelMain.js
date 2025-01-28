class PackerPanelMain extends MODx.Panel {
    static xtype = "packer-panel-main";
    constructor(config = {}) {
        Ext.apply(config, {
            baseCls: "modx-formpanel",
            layout: "anchor",
            hideMode: "offsets",
            items: [
                {
                    html: "<h2>" + _("packer") + "</h2>",
                    border: false,
                    cls: "modx-page-header",
                },
                {
                    xtype: "modx-tabs",
                    defaults: { border: false, autoHeight: true },
                    border: true,
                    hideMode: "offsets",
                    items: [
                        PackerPanelMain.getPanelItem("Мои компоненты", PackerGridComponents.xtype),
                        // this.getPanelItem("Настройки", ""),
                    ],
                },
            ],
        });

        super(config);
    }

    static getPanelItem(title, xtype) {
        return {
            title: title,
            layout: "anchor",
            items: [
                {
                    xtype: xtype,
                    cls: "main-wrapper",
                },
            ],
        };
    }
}

Ext.reg(PackerPanelMain.xtype, PackerPanelMain);

// packerInstance
// const packerSectionInstance = new PackerPage(packerInstance, 'packer-panel-main-div');