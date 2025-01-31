let hasAutoSetting = true;

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
                layout: "form",
                items: [
                    packerInstance.utils.getFieldObject({
                        fieldXtype: "checkbox",
                        fieldLabel: "Автоматическое заполнение",
                        fieldName: "enable_auto_settings",
                        descriptionText:
                            `Если галочка будет включено, тогда все основные поля, будут сгенерированы
                             на основе названии проекта и названии папки проекта.`,
                        config: {
                            checked: hasAutoSetting ? true : false,
                            boxLabel: "Включить авто. заполнение",
                            listeners: {
                                check: function (checkbox, checked) {
                                    hasAutoSetting = checked;
                                    let extraLeftFields = Ext.getCmp("auto-settings-container");
                                    if (extraLeftFields) {
                                        extraLeftFields.findBy((element) => {
                                            if (typeof element.allowBlank === "boolean" && element?.defaultAllowBlank !== true) {
                                                element.allowBlank = hasAutoSetting ? false : true;
                                            }
                                        });

                                        extraLeftFields.setVisible(checked);
                                        extraLeftFields.doLayout();
                                    }

                                    let extraManualFields = Ext.getCmp("manual-settings-container");
                                    if (extraManualFields) {
                                        extraManualFields.findBy((element) => {
                                            if (typeof element.allowBlank === "boolean" && element?.defaultAllowBlank !== true) {
                                                element.allowBlank = hasAutoSetting ? true : false;
                                            }
                                        });

                                        extraManualFields.setVisible(hasAutoSetting ? false : true);
                                        extraManualFields.doLayout();
                                    }
                                },
                            },
                        },
                    }),
                ],
            },
            {
                layout: "column",
                xtype: "fieldset",
                title: "Настройки для автоматической генерации всех параметров",
                id: "auto-settings-container",
                hidden: hasAutoSetting ? false : true,
                items: [
                    {
                        columnWidth: 0.5,
                        layout: "form",
                        items: this.getAutoSettingFieldsLeftColumn(),
                    },
                    {
                        columnWidth: 0.5,
                        layout: "form",
                        items: this.getAutoSettingFieldsRightColumn(),
                    }
                ]
            },
            {
                layout: "column",
                xtype: "fieldset",
                title: "Настройки для ручного ввода всех параметров",
                id: "manual-settings-container",
                hidden: hasAutoSetting ? true : false, // скрыты по умолчанию
                items: [
                    {
                        columnWidth: 0.5,
                        layout: "form",
                        items: this.getManualSettingFieldsLeftColumn(),
                    },
                    {
                        columnWidth: 0.5,
                        layout: "form",
                        items: this.getManualSettingFieldsRightColumn(),
                    }
                ]
            }
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

    getAutoSettingFieldsLeftColumn: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldLabel: "Название проекта",
                fieldName: "project_name_auto_setting",
                descriptionText: "Введите название проекта. Оно будет использоваться для создания папок и базовых файлов.",
                allowBlank: (hasAutoSetting ? false : true),
                config: {
                    vtype: "CamelCase",
                    msgTarget: "under",
                }
            }),
        ];
    },

    getAutoSettingFieldsRightColumn: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldLabel: "Корневая директория проекта",
                fieldName: "project_parent_path_auto_setting",
                descriptionText: "Введите путь к директории, где будет создан ваш проект. (Папка для проекта будет создана автоматически) Можно использовать плейсхолдеры, пример: {core_path}. Данный путь будет использоватся во время разработки.",
                allowBlank: true,
                config: {
                    vtype: "ValidPath",
                    msgTarget: "under",
                    defaultAllowBlank: true,
                }
            }),
        ];
    },
    // hasAutoSetting
    getManualSettingFieldsLeftColumn: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldLabel: "Название проекта",
                fieldName: "project_name",
                descriptionText: "Введите название проекта. Оно будет использоваться для создания папок и базовых файлов.",
                allowBlank: (hasAutoSetting ? true : false),
                config: {
                    vtype: "CamelCase",
                    msgTarget: "under",
                }
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "Введите путь к проекту (включая папку проекта)",
                fieldName: "project_path",
                descriptionText: "Введите путь к проекту. Можно использовать плейсхолдеры, пример: {core_path}. Данный путь будет использоватся во время разработки.",
                allowBlank: (hasAutoSetting ? true : false),
                config: {
                    vtype: "ValidPath",
                    msgTarget: "under",
                }
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "Введите url путь для ваших активов во время разработки",
                fieldName: "project_assets_url",
                descriptionText:
                    "Введите путь к ядру (core). Можно использовать плейсхолдеры, пример: {core_path}. Данный путь будет использоватся во время разработки.",
                allowBlank: true,
                config: {
                    vtype: "UrlPath",
                    msgTarget: "under",
                    defaultAllowBlank: true,
                },
            }),
        ];
    },

    getManualSettingFieldsRightColumn: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldLabel: "Название пространства имен",
                fieldName: "system_namespace_name",
                descriptionText: "Введите название для пространства имен. Оно используется и во время разработки и будет установлено при распоковке.",
                allowBlank: (hasAutoSetting ? true : false),
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
                allowBlank: hasAutoSetting ? true : false,
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
                allowBlank: (hasAutoSetting ? true : false),
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
                failure: {
                    fn: function (response) {
                        // Если запрос завершился неудачей, отображаем сообщение об ошибке
                        var errorMessage = response.message || 'Произошла ошибка при удалении компонента.';
                        MODx.msg.alert('Ошибка', errorMessage);
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
