packerInstance.window.Settings = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = "packer-window-settings";
    }

    if (!config.componentId) {
        config.componentId = null;
    }

    Ext.applyIf(config, {
        url: packerInstance.config.connectorUrl,
        baseParams: {
            action: "Packer\\Processors\\SaveSettingsProcessor",
            componentId: config.componentId,
        },
        title: "Настройки",
        width: 800,
        fields: [
            {
                layout: "column",
                items: [
                    {
                        columnWidth: 0.5,
                        layout: "form",
                        items: this.getFieldsLeftColumn(),
                    },
                    {
                        columnWidth: 0.5,
                        layout: "form",
                        items: this.getFieldsRightColumn(),
                    },
                ],
            },
        ],
        collapsible: false,
        parent: {
            isWindowOpen: false,
        },
        listeners: {
            close: function () {
                this.parent.isWindowOpen = false;
            },
            hide: function () {
                this.parent.isWindowOpen = false;
            },
            beforeshow: function () {
                if (this.parent.isWindowOpen) {
                    return false;
                }
                this.parent.isWindowOpen = true;
                this.requestData();
            },
            success: function (response) {
            },
            failure: function (response) {
            },
        },
        keys: [
            {
                key: Ext.EventObject.ENTER,
                shift: true,
                fn: function () {
                    this.submit();
                },
                scope: this,
            },
        ],
    });

    packerInstance.window.Settings.superclass.constructor.call(
        this,
        config
    );

    // this.on(
    //     "render",
    //     function () {
    //         this.loader = new Ext.LoadMask(this.getEl(), {
    //             msg: "Загрузка данных...",
    //         });
    //     },
    //     this
    // );

    // Ext.Ajax.on(
    //     "requestcomplete",
    //     function () {
    //         this.loader.hide();
    //     },
    //     this
    // );
};
Ext.extend(packerInstance.window.Settings, MODx.Window, {
    getFieldsLeftColumn: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldLabel: "Название проекта",
                fieldName: "project_name",
                descriptionText: "Введите название проекта, оно будет участвовать для формирование папок и базовых файлов.",
                config: {
                    vtype: "CamelCase",
                    msgTarget: "under",
                }
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "Введите путь к проекта",
                fieldName: "project_path",
                descriptionText: "Введите путь к проекту. Можно использовать плейсхолдеры, пример: {core_path}. Данный путь будет использоватся во время разработки.",
                config: {
                    vtype: "ValidPath",
                    msgTarget: "under",
                }
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "Введите url путь для ваших активов во время разработки",
                fieldName: "project_assets_url",
                allowBlank: true,
                descriptionText:
                    "Введите путь к ядру (core). Можно использовать плейсхолдеры, пример: {core_path}. Данный путь будет использоватся во время разработки.",
                config: {
                    vtype: "UrlPath",
                    msgTarget: "under",
                },
            }),
            // packerInstance.utils.getFieldObject({
            //     fieldLabel: "Включить опцию",
            //     fieldName: "enable_option",
            //     fieldXtype: "checkbox", // Используем xtype чекбокса
            //     descriptionText: "Отметьте, чтобы включить эту опцию",
            //     descriptionHidden: false,
            //     config: {
            //         boxLabel: "Да", // Текст рядом с чекбоксом
            //         checked: true, // Установить по умолчанию
            //     },
            // }),
        ];
    },

    getFieldsRightColumn: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldLabel: "Название пространства имен",
                fieldName: "system_namespace_name",
                descriptionText: "Введите название для пространства имен. Оно используется и во время разработки и будет установлено при распоковке.",
                config: {
                    vtype: "NamespaceName",
                    msgTarget: "under",
                }
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "Введите путь к ядру во время разработки",
                fieldName: "system_namespace_path_core",
                descriptionText:
                    "Введите путь к ядру (core). Можно использовать плейсхолдеры, пример: {core_path}. Данный путь будет использоватся во время разработки.",
                allowBlank: true,
                config: {
                    vtype: "ValidPath",
                    msgTarget: "under",
                },
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "Введите путь к активам во время разработки",
                fieldName: "system_namespace_path_assets",
                descriptionText:
                    "Введите путь к ядру (core). Можно использовать плейсхолдеры, пример: {core_path}. Данный путь будет использоватся во время разработки.",
                allowBlank: true,
                config: {
                    vtype: "ValidPath",
                    msgTarget: "under",
                },
            }),
        ];
    },

    requestData: function () {
        // this.loader.show();
        MODx.Ajax.request({
            url: this.url,
            params: {
                action: "Packer\\Processors\\GetSettingsProcessor",
                componentId: this.config.componentId,
            },
            listeners: {
                success: {
                    fn: function (response) {
                        this.setValues(response.object);
                    },
                    scope: this,
                },
            },
        });
    },
});

Ext.reg(
    "packer-window-settings",
    packerInstance.window.Settings
);
