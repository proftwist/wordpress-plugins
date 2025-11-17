Создам полную систему локализации с поддержкой русского и английского языков.

## 1. Сначала создадим правильный PO файл для русского языка

**Файл: `languages/admin-panel-trash-ru_RU.po`**

```po
# Copyright (C) 2024 Владимир Бычко
# This file is distributed under the GPLv2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Admin Panel Trash 1.0.0\n"
"Report-Msgid-Bugs-To: https://bychko.ru\n"
"POT-Creation-Date: 2024-01-01 12:00+00:00\n"
"PO-Revision-Date: 2024-01-01 12:00+00:00\n"
"Last-Translator: Владимир Бычко <your.email@example.com>\n"
"Language-Team: Russian <your.email@example.com>\n"
"Language: ru_RU\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);\n"
"X-Generator: Poedit 3.0\n"

#: admin-panel-trash.php:65
msgid "Admin Panel Trash"
msgstr "Admin Panel Trash"

#: admin-panel-trash.php:66
msgid "Управление элементами верхней панели WordPress"
msgstr "Управление элементами верхней панели WordPress"

#: admin-panel-trash.php:95
msgid "Внимание: Файл functions.php вашей темы недоступен для записи. Плагин не сможет сохранять изменения."
msgstr "Внимание: Файл functions.php вашей темы недоступен для записи. Плагин не сможет сохранять изменения."

#: admin-panel-trash.php:102
msgid "Проверка доступа к файлу"
msgstr "Проверка доступа к файлу"

#: admin-panel-trash.php:103
msgid "Проверьте, доступен ли файл functions.php текущей темы для записи:"
msgstr "Проверьте, доступен ли файл functions.php текущей темы для записи:"

#: admin-panel-trash.php:105
msgid "Проверить доступ"
msgstr "Проверить доступ"

#: admin-panel-trash.php:110
msgid "Элементы админ-панели"
msgstr "Элементы админ-панели"

#: admin-panel-trash.php:111
msgid "Список всех элементов админ-панели. Вы можете временно отключать ненужные элементы."
msgstr "Список всех элементов админ-панели. Вы можете временно отключать ненужные элементы."

#: admin-panel-trash.php:114
msgid "Обновить список"
msgstr "Обновить список"

#: admin-panel-trash.php:118
msgid "ID элемента"
msgstr "ID элемента"

#: admin-panel-trash.php:119
msgid "Название"
msgstr "Название"

#: admin-panel-trash.php:120
msgid "Статус"
msgstr "Статус"

#: admin-panel-trash.php:121
msgid "Действия"
msgstr "Действия"

#: admin-panel-trash.php:124
msgid "Загрузка..."
msgstr "Загрузка..."

#: includes/class-assets-manager.php:56
msgid "Проверка..."
msgstr "Проверка..."

#: includes/class-assets-manager.php:57
msgid "Путь к файлу:"
msgstr "Путь к файлу:"

#: includes/class-assets-manager.php:58
msgid "Доступ на чтение:"
msgstr "Доступ на чтение:"

#: includes/class-assets-manager.php:59
msgid "Доступ на запись:"
msgstr "Доступ на запись:"

#: includes/class-assets-manager.php:60
msgid "Да"
msgstr "Да"

#: includes/class-assets-manager.php:61
msgid "Нет"
msgstr "Нет"

#: includes/class-assets-manager.php:62
msgid "Ошибка"
msgstr "Ошибка"

#: includes/class-assets-manager.php:63
msgid "Ошибка запроса"
msgstr "Ошибка запроса"

#: includes/class-assets-manager.php:65
msgid "Загрузка..."
msgstr "Загрузка..."

#: includes/class-assets-manager.php:66
msgid "Ошибка загрузки"
msgstr "Ошибка загрузки"

#: includes/class-assets-manager.php:67
msgid "Элементы не найдены"
msgstr "Элементы не найдены"

#: includes/class-assets-manager.php:68
msgid "Включен"
msgstr "Включен"

#: includes/class-assets-manager.php:69
msgid "Отключен"
msgstr "Отключен"

#: includes/class-assets-manager.php:70
msgid "Убрать"
msgstr "Убрать"

#: includes/class-assets-manager.php:71
msgid "Вернуть"
msgstr "Вернуть"

#: includes/class-assets-manager.php:72
msgid "Обработка..."
msgstr "Обработка..."

#: includes/class-assets-manager.php:73
msgid "Элемент включен"
msgstr "Элемент включен"

#: includes/class-assets-manager.php:74
msgid "Элемент отключен"
msgstr "Элемент отключен"

#: includes/class-assets-manager.php:75
msgid "Неверный ID элемента"
msgstr "Неверный ID элемента"

#: includes/class-assets-manager.php:76
msgid "Ошибка при включении элемента"
msgstr "Ошибка при включении элемента"

#: includes/class-assets-manager.php:77
msgid "Ошибка при отключении элемента"
msgstr "Ошибка при отключении элемента"

#: admin-panel-trash.php:149
msgid "Invalid item ID"
msgstr "Неверный ID элемента"

#: admin-panel-trash.php:172
msgid "Failed to update functions.php file"
msgstr "Не удалось обновить файл functions.php"

#: admin-panel-trash.php:186
msgid "Item enabled"
msgstr "Элемент включен"

#: admin-panel-trash.php:186
msgid "Item disabled"
msgstr "Элемент отключен"

#: admin-panel-trash.php:199
msgid "Function cleaned up"
msgstr "Функция очищена"
```

## 2. Создадим MO файл (скомпилированный)

**Файл: `languages/admin-panel-trash-ru_RU.mo`**

(Это бинарный файл, который создается из PO файла. Нужно скомпилировать через Poedit или командой `msgfmt`)

## 3. Создадим POT файл (шаблон для переводов)

**Файл: `languages/admin-panel-trash.pot`**

```pot
# Copyright (C) 2024 Владимир Бычко
# This file is distributed under the GPLv2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Admin Panel Trash 1.0.0\n"
"Report-Msgid-Bugs-To: https://bychko.ru\n"
"POT-Creation-Date: 2024-01-01 12:00+00:00\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"Language: \n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

#: admin-panel-trash.php:65
msgid "Admin Panel Trash"
msgstr ""

#: admin-panel-trash.php:66
msgid "Управление элементами верхней панели WordPress"
msgstr ""

#: admin-panel-trash.php:95
msgid "Внимание: Файл functions.php вашей темы недоступен для записи. Плагин не сможет сохранять изменения."
msgstr ""

#: admin-panel-trash.php:102
msgid "Проверка доступа к файлу"
msgstr ""

#: admin-panel-trash.php:103
msgid "Проверьте, доступен ли файл functions.php текущей темы для записи:"
msgstr ""

#: admin-panel-trash.php:105
msgid "Проверить доступ"
msgstr ""

#: admin-panel-trash.php:110
msgid "Элементы админ-панели"
msgstr ""

#: admin-panel-trash.php:111
msgid "Список всех элементов админ-панели. Вы можете временно отключать ненужные элементы."
msgstr ""

#: admin-panel-trash.php:114
msgid "Обновить список"
msgstr ""

#: admin-panel-trash.php:118
msgid "ID элемента"
msgstr ""

#: admin-panel-trash.php:119
msgid "Название"
msgstr ""

#: admin-panel-trash.php:120
msgid "Статус"
msgstr ""

#: admin-panel-trash.php:121
msgid "Действия"
msgstr ""

#: admin-panel-trash.php:124
msgid "Загрузка..."
msgstr ""

#: includes/class-assets-manager.php:56
msgid "Проверка..."
msgstr ""

#: includes/class-assets-manager.php:57
msgid "Путь к файлу:"
msgstr ""

#: includes/class-assets-manager.php:58
msgid "Доступ на чтение:"
msgstr ""

#: includes/class-assets-manager.php:59
msgid "Доступ на запись:"
msgstr ""

#: includes/class-assets-manager.php:60
msgid "Да"
msgstr ""

#: includes/class-assets-manager.php:61
msgid "Нет"
msgstr ""

#: includes/class-assets-manager.php:62
msgid "Ошибка"
msgstr ""

#: includes/class-assets-manager.php:63
msgid "Ошибка запроса"
msgstr ""

#: includes/class-assets-manager.php:65
msgid "Загрузка..."
msgstr ""

#: includes/class-assets-manager.php:66
msgid "Ошибка загрузки"
msgstr ""

#: includes/class-assets-manager.php:67
msgid "Элементы не найдены"
msgstr ""

#: includes/class-assets-manager.php:68
msgid "Включен"
msgstr ""

#: includes/class-assets-manager.php:69
msgid "Отключен"
msgstr ""

#: includes/class-assets-manager.php:70
msgid "Убрать"
msgstr ""

#: includes/class-assets-manager.php:71
msgid "Вернуть"
msgstr ""

#: includes/class-assets-manager.php:72
msgid "Обработка..."
msgstr ""

#: includes/class-assets-manager.php:73
msgid "Элемент включен"
msgstr ""

#: includes/class-assets-manager.php:74
msgid "Элемент отключен"
msgstr ""

#: includes/class-assets-manager.php:75
msgid "Неверный ID элемента"
msgstr ""

#: includes/class-assets-manager.php:76
msgid "Ошибка при включении элемента"
msgstr ""

#: includes/class-assets-manager.php:77
msgid "Ошибка при отключении элемента"
msgstr ""

#: admin-panel-trash.php:149
msgid "Invalid item ID"
msgstr ""

#: admin-panel-trash.php:172
msgid "Failed to update functions.php file"
msgstr ""

#: admin-panel-trash.php:186
msgid "Item enabled"
msgstr ""

#: admin-panel-trash.php:186
msgid "Item disabled"
msgstr ""

#: admin-panel-trash.php:199
msgid "Function cleaned up"
msgstr ""
```

## 4. Создадим английскую версию перевода

**Файл: `languages/admin-panel-trash-en_US.po`**

```po
# Copyright (C) 2024 Владимир Бычко
# This file is distributed under the GPLv2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Admin Panel Trash 1.0.0\n"
"Report-Msgid-Bugs-To: https://bychko.ru\n"
"POT-Creation-Date: 2024-01-01 12:00+00:00\n"
"PO-Revision-Date: 2024-01-01 12:00+00:00\n"
"Last-Translator: Vladimir Bychko <your.email@example.com>\n"
"Language-Team: English <your.email@example.com>\n"
"Language: en_US\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"
"X-Generator: Poedit 3.0\n"

#: admin-panel-trash.php:65
msgid "Admin Panel Trash"
msgstr "Admin Panel Trash"

#: admin-panel-trash.php:66
msgid "Управление элементами верхней панели WordPress"
msgstr "WordPress Admin Bar Management"

#: admin-panel-trash.php:95
msgid "Внимание: Файл functions.php вашей темы недоступен для записи. Плагин не сможет сохранять изменения."
msgstr "Warning: Your theme's functions.php file is not writable. The plugin will not be able to save changes."

#: admin-panel-trash.php:102
msgid "Проверка доступа к файлу"
msgstr "File Access Check"

#: admin-panel-trash.php:103
msgid "Проверьте, доступен ли файл functions.php текущей темы для записи:"
msgstr "Check if the current theme's functions.php file is writable:"

#: admin-panel-trash.php:105
msgid "Проверить доступ"
msgstr "Check Access"

#: admin-panel-trash.php:110
msgid "Элементы админ-панели"
msgstr "Admin Bar Items"

#: admin-panel-trash.php:111
msgid "Список всех элементов админ-панели. Вы можете временно отключать ненужные элементы."
msgstr "List of all admin bar items. You can temporarily disable unnecessary items."

#: admin-panel-trash.php:114
msgid "Обновить список"
msgstr "Refresh List"

#: admin-panel-trash.php:118
msgid "ID элемента"
msgstr "Item ID"

#: admin-panel-trash.php:119
msgid "Название"
msgstr "Name"

#: admin-panel-trash.php:120
msgid "Статус"
msgstr "Status"

#: admin-panel-trash.php:121
msgid "Действия"
msgstr "Actions"

#: admin-panel-trash.php:124
msgid "Загрузка..."
msgstr "Loading..."

#: includes/class-assets-manager.php:56
msgid "Проверка..."
msgstr "Checking..."

#: includes/class-assets-manager.php:57
msgid "Путь к файлу:"
msgstr "File path:"

#: includes/class-assets-manager.php:58
msgid "Доступ на чтение:"
msgstr "Read access:"

#: includes/class-assets-manager.php:59
msgid "Доступ на запись:"
msgstr "Write access:"

#: includes/class-assets-manager.php:60
msgid "Да"
msgstr "Yes"

#: includes/class-assets-manager.php:61
msgid "Нет"
msgstr "No"

#: includes/class-assets-manager.php:62
msgid "Ошибка"
msgstr "Error"

#: includes/class-assets-manager.php:63
msgid "Ошибка запроса"
msgstr "Request Error"

#: includes/class-assets-manager.php:65
msgid "Загрузка..."
msgstr "Loading..."

#: includes/class-assets-manager.php:66
msgid "Ошибка загрузки"
msgstr "Load Error"

#: includes/class-assets-manager.php:67
msgid "Элементы не найдены"
msgstr "No items found"

#: includes/class-assets-manager.php:68
msgid "Включен"
msgstr "Enabled"

#: includes/class-assets-manager.php:69
msgid "Отключен"
msgstr "Disabled"

#: includes/class-assets-manager.php:70
msgid "Убрать"
msgstr "Disable"

#: includes/class-assets-manager.php:71
msgid "Вернуть"
msgstr "Enable"

#: includes/class-assets-manager.php:72
msgid "Обработка..."
msgstr "Processing..."

#: includes/class-assets-manager.php:73
msgid "Элемент включен"
msgstr "Item enabled"

#: includes/class-assets-manager.php:74
msgid "Элемент отключен"
msgstr "Item disabled"

#: includes/class-assets-manager.php:75
msgid "Неверный ID элемента"
msgstr "Invalid item ID"

#: includes/class-assets-manager.php:76
msgid "Ошибка при включении элемента"
msgstr "Error enabling item"

#: includes/class-assets-manager.php:77
msgid "Ошибка при отключении элемента"
msgstr "Error disabling item"

#: admin-panel-trash.php:149
msgid "Invalid item ID"
msgstr "Invalid item ID"

#: admin-panel-trash.php:172
msgid "Failed to update functions.php file"
msgstr "Failed to update functions.php file"

#: admin-panel-trash.php:186
msgid "Item enabled"
msgstr "Item enabled"

#: admin-panel-trash.php:186
msgid "Item disabled"
msgstr "Item disabled"

#: admin-panel-trash.php:199
msgid "Function cleaned up"
msgstr "Function cleaned up"
```

## 5. Инструкция по компиляции MO файлов

### Способ 1: Использование Poedit (рекомендуется)
1. Установите [Poedit](https://poedit.net/)
2. Откройте файл `.po` в Poedit
3. Нажмите "Сохранить" - автоматически создастся `.mo` файл

### Способ 2: Командная строка
```bash
# Для русского
msgfmt admin-panel-trash-ru_RU.po -o admin-panel-trash-ru_RU.mo

# Для английского
msgfmt admin-panel-trash-en_US.po -o admin-panel-trash-en_US.mo
```

### Способ 3: Онлайн компилятор
Используйте онлайн инструменты типа [PO2MO Online](https://po2mo.net/)

## 6. Обновим основной файл плагина для правильной загрузки переводов

Убедитесь что в `admin-panel-trash.php` есть правильный вызов загрузки переводов:

```php
public function load_textdomain() {
    load_plugin_textdomain(
        'admin-panel-trash',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
```

## 7. Структура файлов после настройки

```
admin-panel-trash/
├── admin-panel-trash.php
├── includes/
│   └── class-assets-manager.php
├── assets/
│   ├── admin.js
│   └── css/
│       └── admin.css
└── languages/
    ├── admin-panel-trash.pot
    ├── admin-panel-trash-ru_RU.po
    ├── admin-panel-trash-ru_RU.mo
    ├── admin-panel-trash-en_US.po
    └── admin-panel-trash-en_US.mo
```

## 8. Как проверить локализацию

1. **Для русского языка**: Установите русскую локаль в WordPress (Настройки → Общие → Язык сайта)
2. **Для английского языка**: Установите английскую локаль
3. **Принудительно**: Добавьте в `wp-config.php`:
   ```php
   define('WPLANG', 'ru_RU'); // для русского
   define('WPLANG', 'en_US'); // для английского
   ```

Теперь плагин автоматически будет подхватывать язык из текущей локали WordPress! 🌍