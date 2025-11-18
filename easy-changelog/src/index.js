import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { setLocaleData } from '@wordpress/i18n';
import Edit from './edit';

// Загрузка локализации для блока
(function() {
    try {
        // Получаем локаль WordPress
        const locale = window.wp && window.wp.i18n ? window.wp.i18n.getLocaleData('easy-changelog') : null;

        // Если локаль не найдена, пробуем загрузить из JSON файла
        if (!locale) {
            // Определяем локаль на основе языка WordPress
            const currentLocale = window.wp && window.wp.i18n ? window.wp.i18n.getLocaleData() : {};
            const language = currentLocale.locale || 'en_US';

            // Загружаем соответствующий JSON файл переводов
            if (language.startsWith('ru')) {
                // Русская локаль
                const translations = {
                    "": {
                        "domain": "easy-changelog",
                        "lang": "ru_RU",
                        "plural-forms": "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2);"
                    },
                    "Easy Changelog": ["Легкий Чейнджлог"],
                    "Display a beautiful changelog with JSON data": ["Отображение красивого чейнджлога с данными JSON"],
                    "Changelog": ["История изменений"],
                    "Added": ["Добавлено"],
                    "JSON Editor": ["Редактор JSON"],
                    "Preview": ["Предпросмотр"],
                    "Changelog JSON": ["JSON чейнджлога"],
                    "Enter valid JSON array with version, date, and added fields": ["Введите валидный массив JSON с полями version, date и added"],
                    "Invalid JSON format": ["Неверный формат JSON"],
                    "JSON must be an array": ["JSON должен быть массивом"],
                    "Fix JSON errors to see preview": ["Исправьте ошибки JSON для просмотра"],
                    "No changelog data to display": ["Нет данных чейнджлога для отображения"],
                    "Changelog Settings": ["Настройки чейнджлога"],
                    "Use the JSON Editor tab to input your changelog data in JSON format.": ["Используйте вкладку редактора JSON для ввода данных чейнджлога в формате JSON."],
                    "Invalid changelog data": ["Неверные данные чейнджлога"]
                };
                setLocaleData(translations, 'easy-changelog');
            } else {
                // Английская локаль (по умолчанию)
                const translations = {
                    "": {
                        "domain": "easy-changelog",
                        "lang": "en_US",
                        "plural-forms": "nplurals=2; plural=(n != 1);"
                    },
                    "Easy Changelog": ["Easy Changelog"],
                    "Display a beautiful changelog with JSON data": ["Display a beautiful changelog with JSON data"],
                    "Changelog": ["Changelog"],
                    "Added": ["Added"],
                    "JSON Editor": ["JSON Editor"],
                    "Preview": ["Preview"],
                    "Changelog JSON": ["Changelog JSON"],
                    "Enter valid JSON array with version, date, and added fields": ["Enter valid JSON array with version, date, and added fields"],
                    "Invalid JSON format": ["Invalid JSON format"],
                    "JSON must be an array": ["JSON must be an array"],
                    "Fix JSON errors to see preview": ["Fix JSON errors to see preview"],
                    "No changelog data to display": ["No changelog data to display"],
                    "Changelog Settings": ["Changelog Settings"],
                    "Use the JSON Editor tab to input your changelog data in JSON format.": ["Use the JSON Editor tab to input your changelog data in JSON format."],
                    "Invalid changelog data": ["Invalid changelog data"]
                };
                setLocaleData(translations, 'easy-changelog');
            }
        } else {
            // Если локаль найдена, используем её
            setLocaleData(locale, 'easy-changelog');
        }
    } catch (e) {
        console.warn('Easy Changelog: Failed to load translations', e);
    }
})();

registerBlockType('easy-changelog/changelog', {
    title: __('Easy Changelog', 'easy-changelog'),
    description: __('Display a beautiful changelog with JSON data', 'easy-changelog'),
    category: 'widgets',
    icon: 'list-view',
    supports: {
        html: false,
    },
    attributes: {
        changelogData: {
            type: 'string',
            default: `[
    {
        "version": "1.0.0",
        "date": "15.01.2024",
        "added": [
            "Initial release of the plugin",
            "Basic changelog functionality",
            "Gutenberg block integration"
        ]
    },
    {
        "version": "1.1.0",
        "date": "20.01.2024",
        "added": [
            "Added preview tab",
            "Improved styling",
            "JSON validation"
        ]
    }
]`
        }
    },
    edit: Edit,
    save: () => {
        return null; // Используем render_callback в PHP
    }
});