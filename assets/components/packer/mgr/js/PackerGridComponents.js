class PackerGridComponents extends MODx.grid.Grid {
    static xtype = "packer-grid-components";

    constructor(config = {}) {
        let selModel = new Ext.grid.CheckboxSelectionModel();

        Ext.applyIf(config, {
            url: packerInstance.config.connectorUrl,
            fields: PackerGridComponents.getFields(),
            columns: PackerGridComponents.getColumns(selModel),
            // tbar: PackerGridComponents.getTopBar(),
            sm: selModel,
            baseParams: {
                action: "Packer\\Processors\\SaveSettingsProcessor",
            },
            listeners: {
                // rowDblClick: function (grid, rowIndex, e) {
                //     const row = grid.store.getAt(rowIndex);
                //     this.updateItem(grid, e, row);
                // }
            },
            viewConfig: {
                forceFit: true,
                autoFill: true,
                scrollOffset: 0,
                // enableRowBody: true,
                // showPreview: true,
                // getRowClass: function (rec) {
                //     return !rec.data.active ? "synccatalogmanager-grid-row-disabled" : "";
                // },
            },
            paging: true,
            pageSize: 12,
            remoteSort: true,
            autoHeight: true,
        });
        super(config);

        this.initStore();
    }

    initStore() {
        // Clear selection on grid refresh
        this.store.on(
            "load",
            function () {
                if (this._getSelectedIds().length) {
                    this.getSelectionModel().clearSelections();
                }
            },
            this
        );
    }

    static getFields() {
        return [
            "id",
            "page_id",
            "name",
            "parent_page_id",
            "count_product",
            "activate",
            "actions",
        ];
    }

    static getColumns(sm) {
        return [
            sm,
            {
                header: "Id",
                dataIndex: "id",
                sortable: true,
                width: 70,
            },
            {
                header: "id на сервере",
                dataIndex: "page_id",
                sortable: true,
                width: 120,
            },
            {
                header: "Название",
                dataIndex: "name",
                sortable: true,
                width: 200,
            },
            {
                header: "Принадлежит",
                dataIndex: "parent_page_id",
                sortable: true,
                width: 120,
            },
            {
                header: "Кол-во товаров",
                dataIndex: "count_product",
                sortable: true,
                width: 120,
            },
            // {
            //     header: "Включено",
            //     dataIndex: "activate",
            //     renderer: SyncCatalogManager.utils.renderBoolean,
            //     sortable: true,
            //     width: 100,
            // },
            // {
            //     header: "Действия",
            //     dataIndex: "actions",
            //     renderer: SyncCatalogManager.utils.renderActions,
            //     sortable: false,
            //     width: 100,
            //     id: "actions",
            // },
        ];
    }

    static getTopBar() {
        return [
            "->",
            {
                xtype: "synccatalogmanager-field-search",
                width: 250,
                listeners: {
                    search: {
                        fn: function (field) {
                            this._doSearch(field);
                        },
                        scope: this,
                    },
                    clear: {
                        fn: function (field) {
                            field.setValue("");
                            this._clearSearch();
                        },
                        scope: this,
                    },
                },
            },
        ];
    }

    // getMenu(grid, rowIndex) {
    //     const ids = this._getSelectedIds();

    //     const row = grid.getStore().getAt(rowIndex);
    //     const menu = SyncCatalogManager.utils.getMenu(
    //         row.data["actions"],
    //         this,
    //         ids
    //     );

    //     this.addContextMenuItem(menu);
    // }

    // disableItem() {
    //     const ids = this._getSelectedIds();
    //     if (!ids.length) {
    //         return false;
    //     }
    //     MODx.Ajax.request({
    //         url: this.config.url,
    //         params: {
    //             action: "SyncCatalogManager\\Processors\\synchronization\\Categories\\DeactivateCategories",
    //             ids: Ext.util.JSON.encode(ids),
    //         },
    //         listeners: {
    //             success: {
    //                 fn: function () {
    //                     this.refresh();
    //                 },
    //                 scope: this,
    //             },
    //         },
    //     });
    // }

    // onClick (e) {
    //     let elem = e.getTarget();

    //     if (elem.nodeName === "I") {
    //        elem = elem.parentElement;
    //     }

    //     if (elem.nodeName == "BUTTON") {
    //         const row = this.getSelectionModel().getSelected();
    //         if (typeof row != "undefined") {
    //             const action = elem.getAttribute("action");
    //             if (action == "showMenu") {
    //                 const ri = this.getStore().find("id", row.id);
    //                 return this._showMenu(this, ri, e);
    //             } else if (typeof this[action] === "function") {
    //                 this.menu.record = row.data;
    //                 return this[action](this, e);
    //             }
    //         }
    //     }
    //     return this.processEvent("click", e);
    // }

    // enableItem() {
    //     const ids = this._getSelectedIds();
    //     if (!ids.length) {
    //         return false;
    //     }
    //     MODx.Ajax.request({
    //         url: this.config.url,
    //         params: {
    //             action: "SyncCatalogManager\\Processors\\synchronization\\Categories\\ActivateCategories",
    //             ids: Ext.util.JSON.encode(ids),
    //         },
    //         listeners: {
    //             success: {
    //                 fn: function () {
    //                     this.refresh();
    //                 },
    //                 scope: this,
    //             },
    //         },
    //     });
    // }

    _getSelectedIds() {
        const ids = [];
        const selected = this.getSelectionModel().getSelections();

        for (const i in selected) {
            if (!selected.hasOwnProperty(i)) {
                continue;
            }
            ids.push(selected[i]["id"]);
        }

        return ids;
    }

    // _doSearch(tf) {
    //     this.getStore().baseParams.query = tf.getValue();
    //     this.getBottomToolbar().changePage(1);
    // }

    // _clearSearch() {
    //     this.getStore().baseParams.query = "";
    //     this.getBottomToolbar().changePage(1);
    // }
}

Ext.reg(PackerGridComponents.xtype, PackerGridComponents);