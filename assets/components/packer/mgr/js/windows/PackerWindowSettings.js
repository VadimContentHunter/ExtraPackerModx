packerInstance.window.Settings = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = "packer-window-settings";
    }

    Ext.applyIf(config, {
        url: packerInstance.config.connectorUrl,
        baseParams: {
            action: "Packer\\Processors\\SaveSettingsProcessor",
        },
        title: "Настройки",
        // width: 800,
        fields: [
            {
                layout: "column",
                items: [
                    {
                        // xtype: "form",
                        columnWidth: 1,
                        layout: "form",
                        // id: "general_requests_form",
                        items: {
                            xtype: "fieldset",
                            title: "Настройка синхронизации с 'проектом 111'",
                            collapsible: false,
                            items: this.getFieldsLeftColumn(),
                        },
                    },
                    // {
                    //     // xtype: "form",
                    //     columnWidth: 0.5,
                    //     layout: "form",
                    //     // id: "order_requests_form",
                    //     items: {
                    //         xtype: "fieldset",
                    //         title: "Настройка обработчика продуктов для сохранений данных из синхронизации",
                    //         collapsible: false,
                    //         items: this.getFieldsRightColumn(),
                    //     },
                    // },
                    // {
                    //     // xtype: "form",
                    //     columnWidth: 0.5,
                    //     layout: "form",
                    //     // id: "order_requests_form",
                    //     items: {
                    //         xtype: "fieldset",
                    //         title: "Настройка обработчика нанесения для сохранений данных из синхронизации",
                    //         collapsible: false,
                    //         items: this.getFieldsRightColumnPrint(),
                    //     },
                    // },
                ],
            },
        ],
        collapsible: false,
        parent: {
            isWindowOpen: false,
        },
        console: {
            updateConsole: function () {},
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
                this.console.updateConsole();
            },
            failure: function (response) {
                this.console.updateConsole();
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

     this.on(
         "render",
         function () {
             this.loader = new Ext.LoadMask(this.getEl(), {
                 msg: "Загрузка данных...",
             });
         },
         this
     );

     Ext.Ajax.on(
         "requestcomplete",
         function () {
             this.loader.hide();
         },
         this
     );
};
Ext.extend(packerInstance.window.Settings, MODx.Window, {
    getFieldsLeftColumn: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldLabel: "Логин",
                fieldName: "login",
                descriptionText: "Логин для получение выгрузки 'Проекта 111'.",
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "Пароль",
                fieldName: "password",
                descriptionText: "Пароль для получение выгрузки 'Проекта 111'",
                fieldType: "password",
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "IP-адрес",
                fieldName: "ipAddress",
                descriptionText:
                    "Укажите IP-адрес сервера. Это требует проект 111, для доступа.",
                config: {
                    vtype: "IPAddress", // Добавление пользовательской валидации для IP-адреса
                    msgTarget: "under",
                },
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "URL для размерения выгрузки",
                fieldName: "urlPath",
                descriptionText:
                    "Поле показывает куда система скачивает данные о выгрузке.",
                allowBlank: true,
                config: {
                    readOnly: true,
                    style: "color: #999999",
                },
            }),
        ];
    },

    getFieldsRightColumn: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldXtype: "numberfield",
                fieldLabel: "Пропускаемых продуктов",
                fieldName: "handler_skip_product",
                descriptionText:
                    "Укажите сколько обработчик должен пропустить продуктов в файле.",
                config: {
                    allowDecimals: false,
                    minValue: 0,
                    maskRe: /[0-9]/,
                },
            }),
            packerInstance.utils.getFieldObject({
                fieldLabel: "Выберите chunk файла 'product.xml'",
                fieldName: "handler_chunk_name",
                descriptionText:
                    "Укажите название chunk файла, с которого обработчик будет начинать.",
                fieldXtype: "packer-combo-drop-down-list",
            }),
        ];
    },

    getFieldsRightColumnPrint: function () {
        return [
            packerInstance.utils.getFieldObject({
                fieldXtype: "numberfield",
                fieldLabel: "Пропускаемых продуктов для нанесения",
                fieldName: "handler_skip_print_product",
                descriptionText:
                    "Укажите сколько обработчик должен пропустить продуктов для нанесения.",
                config: {
                    allowDecimals: false,
                    minValue: 0,
                    maskRe: /[0-9]/,
                },
            }),
        ];
    },

    requestData: function () {
        this.loader.show();
        MODx.Ajax.request({
            url: this.url,
            params: {
                action: "Packer\\Processors\\SaveSettingsProcessor",
            },
            listeners: {
                success: {
                    fn: function (response) {
                        this.setValues(response.object);

                        // console.log(response.object.dropDownLists);
                        // if (Array.isArray(response.object.dropDownLists)) {
                        //     response.object.dropDownLists.forEach((item) => {
                        //         // Проверяем, что компонент уже был создан и данные загружены
                        //         let form = this?.fp?.getForm();
                        //         let comboField = form?.findField(item.name);
                        //         // console.log(comboField);
                        //         if (comboField) {
                        //             // Устанавливаем список для выпадающего меню
                        //             comboField.getStore().loadData(item.list);

                        //             // Устанавливаем выбранное значение
                        //             comboField.setValue(
                        //                 response.object[item.name]
                        //             );
                        //         }
                        //     });
                        // }
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
