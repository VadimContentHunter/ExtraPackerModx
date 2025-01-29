Ext.apply(Ext.form.VTypes, {
    IPAddress: function (value) {
        return /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(value);
    },
    IPAddressText: "Введите правильный IP-адрес",
    IPAddressMask: /[\d\.]/i,
});

Ext.apply(Ext.form.VTypes, {
    ValidPath: function (value) {
        // Проверка корректности шаблонных конструкций {ключ}
        const placeholderPattern = /{\w+}/g; // Ищем {ключ}
        const invalidPlaceholderPattern = /{[^a-z_]+}/; // Проверяем, что внутри {} только буквы и _

        // Проверка основного пути (без шаблонов)
        const cleanPath = value.replace(placeholderPattern, ""); // Убираем {ключ} для проверки структуры пути
        const pathPattern = /^\/?[\w{}\/.-]+$/; // Разрешенные символы

        // Условия проверки
        if (
            invalidPlaceholderPattern.test(value) || // Ошибочный {ключ}
            !pathPattern.test(value) || // Ошибочные символы в пути
            value.includes("//") || // Двойные слэши
            value.includes("..") || // Запрещаем ".."
            value.includes("/{/") || value.includes("/}") || // Неправильное расположение {}
            value.includes("{/") || value.includes("{.") || value.includes("{-") || // Ошибочные {форматы
            value.includes("}/") || value.includes(".}") || value.includes("-}") // Ошибочные }форматы
        ) {
            return false;
        }
        return true;
    },
    ValidPathText: "Введите корректный путь. Разрешены: буквы, цифры, `_`, `-`, `/`. Можно использовать `{ключ}`.",
    ValidPathMask: /[\w{}\/.-]/i, // Ограничение ввода (запрещаем пробелы, спецсимволы)
});


Ext.apply(Ext.form.VTypes, {
    CamelCase: function(value) {
        // Регулярное выражение для проверки CamelCase (начиная с заглавной буквы и далее в таком стиле)
        return /^[A-Z][a-zA-Z0-9]*$/.test(value);
    },
    CamelCaseText: "Название должно быть в стиле CamelCase, например, ProjectTest1",
    CamelCaseMask: /[a-zA-Z0-9]/i, // Разрешенные символы: латинские буквы и цифры
});

Ext.apply(Ext.form.VTypes, {
    NamespaceName: function(value) {
        // Регулярное выражение для проверки, чтобы строка состояла только из букв в нижнем регистре
        return /^[a-z]+$/.test(value);
    },
    NamespaceNameText: "Namespace должен содержать только маленькие буквы (a-z)",
    NamespaceNameMask: /[a-z]/i, // Маска ввода, разрешает только буквы в нижнем регистре
});

Ext.apply(Ext.form.VTypes, {
    UrlPath: function(value) {
        // Регулярное выражение для проверки пути URL без хост-имени и протокола
        // Теперь допускаются слэши в начале и в конце пути
        const urlPathPattern = /^\/?([a-z0-9-]+(?:\/[a-z0-9-_]+)*)\/?$/i;
        return urlPathPattern.test(value);
    },
    UrlPathText: "Введите корректный путь URL. Путь может начинаться и заканчиваться слэшем и содержать только буквы, цифры, дефисы, подчеркивания и слэши.",
    UrlPathMask: /[a-z0-9-_\/]/i,  // Маска ввода разрешает буквы, цифры, дефисы, слэши и подчеркивания
});


