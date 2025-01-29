packerInstance.grid.Components = function (config) {
    config = config || {};
    // if (!config.id) {
    //     config.id = "packer-grid-components";
    // }
    let sm = new Ext.grid.CheckboxSelectionModel();
    
    Ext.applyIf(config, {
        url: packerInstance.config.connectorUrl,
        fields: this.getFields(),
        columns: this.getColumns(sm),
        tbar: this.getTopBar(),
        sm: sm,
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
    packerInstance.grid.Components.superclass.constructor.call(this, config);

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
};
Ext.extend(packerInstance.grid.Components, MODx.grid.Grid, {
    // windows: {},

    getFields: function () {
        return [
            "id",
            "name",
            "namespaces",
            "version",
        ];
    },

    getColumns: function (sm) {
        return [
            sm,
            {
                header: "Id",
                dataIndex: "id",
                sortable: true,
                width: 70,
            },
            {
                header: "Название",
                dataIndex: "name",
                sortable: true,
                width: 200,
            },
            {
                header: "Пространства имён",
                dataIndex: "namespaces",
                sortable: true,
                width: 200,
            },
            {
                header: "Версия",
                dataIndex: "version",
                sortable: true,
                width: 70,
            },
        ];
    },

    getTopBar: function () {
        return [
            "->",
            {
                xtype: "packer-field-search",
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
    },

    // getMenu: function (grid, rowIndex) {
    //     const ids = this._getSelectedIds();

    //     const row = grid.getStore().getAt(rowIndex);
    //     const menu = SyncCatalogManager.utils.getMenu(
    //         row.data["actions"],
    //         this,
    //         ids
    //     );

    //     this.addContextMenuItem(menu);
    // },

    // disableItem: function () {
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
    // },

    onClick: function (e) {
        let elem = e.getTarget();

        if (elem.nodeName === "I") {
           elem = elem.parentElement;
        }

        if (elem.nodeName == "BUTTON") {
            const row = this.getSelectionModel().getSelected();
            if (typeof row != "undefined") {
                const action = elem.getAttribute("action");
                if (action == "showMenu") {
                    const ri = this.getStore().find("id", row.id);
                    return this._showMenu(this, ri, e);
                } else if (typeof this[action] === "function") {
                    this.menu.record = row.data;
                    return this[action](this, e);
                }
            }
        }
        return this.processEvent("click", e);
    },

    // enableItem: function () {
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
    // },

    _getSelectedIds: function () {
        const ids = [];
        const selected = this.getSelectionModel().getSelections();

        for (const i in selected) {
            if (!selected.hasOwnProperty(i)) {
                continue;
            }
            ids.push(selected[i]["id"]);
        }

        return ids;
    },

    _doSearch: function (tf) {
        this.getStore().baseParams.query = tf.getValue();
        this.getBottomToolbar().changePage(1);
    },

    _clearSearch: function () {
        this.getStore().baseParams.query = "";
        this.getBottomToolbar().changePage(1);
    },
});
Ext.reg("packer-grid-components", packerInstance.grid.Components);
