# Packer – генератор пакетов для MODX 3  

**Версия:** 1.0 (релиз)  
**Совместимость:** MODX 3  
**Моя почта:** project.k.vadim@gmail.com    
**Ссылка на гитхаб:** https://github.com/VadimContentHunter/ExtraPackerModx 

## 📌 Описание  
Packer – это инструмент для создания проектов (пакетов) для MODX 3.  
Он позволяет автоматически генерировать структуру папок и базовые файлы, а также  
упаковывать проекты в установочные пакеты для дальнейшего использования.  

---

## 🚀 Возможности  

### 🔹 Создание проектов  
Packer позволяет создавать проекты вручную или автоматически на основе названия проекта.  

При создании проекта происходит:  
- Размещение проекта в определённой директории  
- Генерация базового файла `packer_project.json`, содержащего параметры проекта  

Пример `packer_project.json`:  

```json
{
    "project_name": "Packer",
    "project_path": "/var/www/test-modx/extras/ExtraPackerModx/",
    "project_assets_url": "extras/ExtraPackerModx/assets/components/packer/",
    "relative_core_path": "extras/ExtraPackerModx/core/components/packer/",
    "system_namespace_name": "packer",
    "system_namespace_path_core": "/var/www/test-modx/extras/ExtraPackerModx/core/components/packer/",
    "system_namespace_path_assets": "/var/www/test-modx/extras/ExtraPackerModx/assets/components/packer/",
    "system_assets_url_key": "extra_packer_assets_url",
    "version": "1.0",
    "release": "dev",
    "config_path": "/var/www/test-modx/extras/ExtraPackerModx/_configs"
}
```

---

### 🔹 Управление проектами  
- 📂 **Вывод списка созданных проектов**  
- 🗜️ **Упаковка проектов в установочные пакеты MODX** (основная функция)  
- 📌 **Инициализация элементов (меню, ресурсы и т. д.)** без необходимости установки пакета  
- 🗑️ **Удаление созданных элементов**  
- ❌ **Удаление проекта (удаление каталога проекта и файлов)**  

---

## 📄 Конфигурационные файлы  

### 🔹 `menus.json` – Конфигурация меню  
Определяет элементы меню, которые будут добавлены в MODX.  
Пример:  

```json
{
    "packer": {
        "parent": "topnav",
        "action": "main",
        "menuindex": 3,
        "description": "Описание первого пункта меню",
        "icon": "<i class='icon-shipping-fast icon'></i>"
    }
}
```

### 🔹 `resources.json` – Конфигурация ресурсов  
Описывает ресурсы, которые будут добавлены в MODX.  
Пример:  

```json
{
    "sitemap": {
        "type": "document",
        "longtitle": "Длинное название",
        "description": "Описание",
        "alias": "sitemap",
        "parent": 0,
        "isfolder": 0,
        "introtext": "Краткое описание ресурса",
        "content": "",
        "template": "build:bd:modTemplate:templatename:id:sitemap",
        "searchable": 1,
        "cacheable": 0,
        "class_key": "MODX\Revolution\modDocument",
        "context_key": "web",
        "content_type": "build:bd:modContentType:name:id:XML",
        "uri": "sitemap.xml",
        "uri_override": 0,
        "hide_children_in_tree": 0
    }
}
```

### 🔹 `templates.json` – Конфигурация шаблонов  
Определяет шаблоны, которые будут использоваться в проекте.  
Пример:  

```json
{
    "sitemap": {
        "description": "Описание sitemap",
        "content": "build:path:read:${{core_path}}elements/sitemap.tpl",
        "source": 1,
        "static": 1,
        "static_file": "build:path:${{relative_core_path}}elements/templates/sitemap.tpl"
    }
}
```

### 🔹 `tvs.json` – Конфигурация TV  
Описывает TV параметры для ресурсов.  
Пример:  

```json
{
    "testChangefreq": {
        "source": 0,
        "property_preprocess": 0,
        "type": "listbox",
        "caption": "Частота изменения страницы для Sitemap",
        "description": "Вероятная частота изменения этой страницы.",
        "elements": "always==1||hourly==2||daily==3||weekly==4||monthly==5||yearly==6||never==7",
        "rank": 0,
        "display": "string",
        "default_text": "5",
        "input_properties": {
            "allowBlank": "true"
        }
    }
}
```

Также используются файлы `chunks.json` и `snippets.json` для описания чанков и сниппетов.  
