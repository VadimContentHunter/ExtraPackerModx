Ext.apply(Ext.form.VTypes, {
    IPAddress: function (value) {
        return /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(value);
    },
    IPAddressText: "Введите правильный IP-адрес",
    IPAddressMask: /[\d\.]/i,
});
