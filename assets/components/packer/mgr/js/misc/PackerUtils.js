packerInstance.utils.renderBoolean = function (value) {
    return value
        ? String.format('<span class="green">{0}</span>', _("yes"))
        : String.format('<span class="red">{0}</span>', _("no"));
};

packerInstance.utils.getMenu = function (actions, grid, selected) {
    const menu = [];
    let cls, icon, title, action;

    for (const i in actions) {
        if (!actions.hasOwnProperty(i)) {
            continue;
        }

        const a = actions[i];
        if (!a["menu"]) {
            if (a == "-") {
                menu.push("-");
            }
            continue;
        } else if (
            menu.length > 0 &&
            (/^remove/i.test(a["action"]) || /^delete/i.test(a["action"]))
        ) {
            menu.push("-");
        }

        if (selected.length > 1) {
            if (!a["multiple"]) {
                continue;
            } else if (typeof a["multiple"] == "string") {
                a["title"] = a["multiple"];
            }
        } else if (selected.length === 1 && a["onlyMultiple"] === true) {
            continue;
        }

        icon = a["icon"] ? a["icon"] : "";
        if (typeof a["cls"] == "object") {
            if (typeof a["cls"]["menu"] != "undefined") {
                icon += " " + a["cls"]["menu"];
            }
        } else {
            cls = a["cls"] ? a["cls"] : "";
        }

        title = a["title"] ? a["title"] : a["title"];
        action = a["action"] ? grid[a["action"]] : "";

        menu.push({
            handler: action,
            text: String.format(
                `<span class="synccatalogmanager-menu {0}">
                    <div class= "synccatalogmanager-menu-icon">
                        <i class="{1}"></i>
                    </div>
                    <p>{2}</p>
                </span>`,
                cls,
                icon,
                title
            ),
            scope: grid,
        });
    }

    return menu;
};

packerInstance.utils.renderActions = function (value, props, row) {
    // console.log(value);
    // console.log(props);
    // console.log(row);
    const res = [];
    let cls, icon, title, action, item;
    for (const i in row.data.actions) {
        if (!row.data.actions.hasOwnProperty(i)) {
            continue;
        }
        const a = row.data.actions[i];
        if (!a['button']) {
            continue;
        }

        icon = a['icon'] ? a['icon'] : '';
        if (typeof(a['cls']) == 'object') {
            if (typeof(a['cls']['button']) != 'undefined') {
                icon += ' ' + a['cls']['button'];
            }
        }
        else {
            cls = a['cls'] ? a['cls'] : '';
        }
        action = a['action'] ? a['action'] : '';
        title = a['title'] ? a['title'] : '';

        item = String.format(
            `<li class="{0}">
                <button class="synccatalogmanager-actions-btn-default" action="{2}" title="{3}">
                    <i class="{1}"></i>
                </button>
            </li>`,
            cls,
            icon,
            action,
            title
        );

        res.push(item);
    }

    return String.format(
        '<ul class="synccatalogmanager-actions">{0}</ul>',
        res.join('')
    );
};

packerInstance.utils.requestButtonConsole = function (_action, _console, renderShow = true, _parameters = {}) {
    if (typeof _parameters !== 'object') {
        console.error("requestButtonConsole: _parameters is not a object.");
        return;
    }
    if (renderShow === true) {
        _console.loader.show();
    }
    _parameters.action = _action;
    if (typeof _action === 'string') {
        MODx.Ajax.request({
            url: _console.config.url,
            params: _parameters,
            listeners: {
                success: {
                    fn: function (response) {
                        _console.updateConsole();
                    },
                    scope: _console,
                },
                failure: {
                    fn: function (response) {
                        _console.updateConsole();
                    },
                    scope: _console,
                },
            },
        });
    }
};

packerInstance.utils.getFieldObject = function ({
    fieldLabel,
    fieldName,
    descriptionText,
    fieldType = null,
    fieldXtype = "textfield",
    allowBlank = false,
    descriptionHidden = false,
    config = {},
}) {
    return {
        xtype: "fieldset",
        collapsible: false,
        border: false,
        labelWidth: 150,
        style: "margin: 0px;",
        items: [
            Object.assign(
                {
                    xtype: fieldXtype,
                    fieldLabel: fieldLabel,
                    inputType: fieldType,
                    name: fieldName,
                    allowBlank: allowBlank,
                    anchor: "100%",
                },
                config
            ),
            {
                xtype: "label",
                cls: "desc-under",
                html: descriptionText,
                hidden: descriptionHidden,
            },
        ],
    };
};