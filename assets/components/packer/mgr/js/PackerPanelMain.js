packerInstance.panel.Main = function (config) {
    config = config || {};
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
                    this.getPanelItem("Мои компоненты", "packer-grid-components"),
                ],
            },
        ],
    });
    packerInstance.panel.Main.superclass.constructor.call(this, config);
};
Ext.extend(packerInstance.panel.Main, MODx.Panel, {
    getPanelItem(title, xtype) {
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
});
Ext.reg("packer-panel-main", packerInstance.panel.Main);