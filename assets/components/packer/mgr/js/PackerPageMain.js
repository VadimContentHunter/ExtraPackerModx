packerInstance.page.Main = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [
            {
                xtype: "packer-panel-main",
                renderTo: "packer-panel-main-div",
            },
        ],
    });
    packerInstance.page.Main.superclass.constructor.call(this, config);
};
Ext.extend(packerInstance.page.Main, MODx.Component);
Ext.reg("packer-page-main", packerInstance.page.Main);
