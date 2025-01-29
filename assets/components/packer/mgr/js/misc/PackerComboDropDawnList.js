packerInstance.combo.DropDownList = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        fieldLabel: "Выберите элемент",
        name: "selectedItem",
        store: new Ext.data.JsonStore({
            // url: SyncCatalogManager.config.connector_url,
            // baseParams: {
            //     action: "SyncCatalogManager\\Processors\\GetChunkNames",
            // },
            // root: "object",
            fields: ["value", "display"],
            // autoLoad: false, // Добавил автоматическую загрузку
        }),
        mode: "local",
        displayField: "display",
        valueField: "value",
        triggerAction: "all",
        forceSelection: true,
        editable: false, // Запретить ручное введение
        value: "Неопределенно", // Установить значение по умолчанию
        listeners: {
            select: function (combo, record, index) {
                // Здесь record.data.value содержит значение "value" выбранного элемента
                // console.log(record.data.value);
            },
        },
    });
    packerInstance.combo.DropDownList.superclass.constructor.call(
        this,
        config
    );
};
Ext.extend(packerInstance.combo.DropDownList, Ext.form.ComboBox, {});
Ext.reg(
    "packer-combo-drop-down-list",
    packerInstance.combo.DropDownList
);
