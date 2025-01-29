let Packer = function (config) {
    config = config || {};
    Packer.superclass.constructor.call(this, config);
};
Ext.extend(Packer, Ext.Component, {
    page: {},
    window: {},
    grid: {},
    tree: {},
    panel: {},
    combo: {},
    config: {},
    view: {},
    utils: {},
    console: {},
    form: {},
});
Ext.reg("packer", Packer);

const packerInstance = new Packer();