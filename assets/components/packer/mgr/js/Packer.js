class Packer extends Ext.Component {
    static xtype = "packer";
    constructor(config = {}) {
        super(config);
    }
}

// Регистрация компонента
Ext.reg(Packer.xtype, Packer);

// Создание экземпляра
const packerInstance = new Packer();