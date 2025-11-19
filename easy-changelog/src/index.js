import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';
import './style.scss';

// Получаем переводы из PHP
const i18n = window.easyChangelogI18n || {};

/**
 * Регистрируем Gutenberg блок для отображения changelog
 *
 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/block-api/
 */
registerBlockType('easy-changelog/changelog', {
    // Заголовок блока - используем переводы из PHP с fallback
    title: i18n.title || __('Easy Changelog', 'easy-changelog'),

    // Описание блока
    description: i18n.description || __('Блок для отображения changelog с редактором JSON и предпросмотром', 'easy-changelog'),

    // Категория блока в редакторе
    category: 'easy-changelog',

    // Иконка блока
    icon: 'list-view',

    // Поддерживаемые функции блока
    supports: {
        html: false, // Запрещаем редактирование HTML для сохранения целостности блока
    },

    // Атрибуты блока - данные, которые сохраняются в базе данных
    attributes: {
        changelogData: {
            type: 'string',
            // Пример данных по умолчанию
            default: '[\n  {\n    "version": "1.0.0",\n    "date": "19.11.2025",\n    "added": ["Первоначальный релиз плагина", "Базовая функциональность блоков"]\n  },\n  {\n    "version": "0.9.0",\n    "date": "15.11.2025",\n    "added": ["Бета-версия плагина", "Тестирование функциональности"]\n  }\n]'
        }
    },

    // Компонент редактирования блока
    edit: Edit,

    // Функция сохранения блока (возвращает null, так как блок сам рендерится)
    save: () => null
});