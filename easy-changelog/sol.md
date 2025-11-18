Помогу исправить указанные проблемы. Вот исправленные файлы:

## 1. Исправленный основной файл плагина

**easy-changelog.php**

```php
<?php
/**
 * Plugin Name: Easy Changelog
 * Plugin URI: http://bychko.ru
 * Description: Гутенберговский блок для создания красивого чейнджлога с предпросмотром
 * Version: 1.0.1
 * Author: Владимир Бычко
 * Author URI: http://bychko.ru
 * Text Domain: easy-changelog
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Константы плагина
define('EASY_CHANGELOG_VERSION', '1.0.1');
define('EASY_CHANGELOG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EASY_CHANGELOG_PLUGIN_PATH', plugin_dir_path(__FILE__));

class EasyChangelog {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('enqueue_block_assets', array($this, 'enqueue_block_assets'));
        add_action('init', array($this, 'load_textdomain'));
    }

    public function init() {
        // Регистрируем скрипты и стили
        $this->register_assets();

        // Регистрируем блок
        register_block_type('easy-changelog/changelog', array(
            'api_version' => 2,
            'editor_script' => 'easy-changelog-editor-script',
            'editor_style' => 'easy-changelog-editor-style',
            'style' => 'easy-changelog-frontend-style',
            'render_callback' => array($this, 'render_block'),
        ));
    }

    public function register_assets() {
        // Скрипты для редактора
        wp_register_script(
            'easy-changelog-editor-script',
            EASY_CHANGELOG_PLUGIN_URL . 'build/index.js',
            array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            EASY_CHANGELOG_VERSION,
            true
        );

        // Локализация для JavaScript
        wp_set_script_translations('easy-changelog-editor-script', 'easy-changelog', EASY_CHANGELOG_PLUGIN_PATH . 'languages');

        // Стили для редактора
        wp_register_style(
            'easy-changelog-editor-style',
            EASY_CHANGELOG_PLUGIN_URL . 'build/index.css',
            array('wp-edit-blocks'),
            EASY_CHANGELOG_VERSION
        );

        // Стили для фронтенда
        wp_register_style(
            'easy-changelog-frontend-style',
            EASY_CHANGELOG_PLUGIN_URL . 'build/style-index.css',
            array(),
            EASY_CHANGELOG_VERSION
        );
    }

    public function enqueue_block_assets() {
        if (has_block('easy-changelog/changelog')) {
            wp_enqueue_style('easy-changelog-frontend-style');
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'easy-changelog',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function render_block($attributes) {
        if (empty($attributes['changelogData'])) {
            return '<p>' . __('No changelog data provided', 'easy-changelog') . '</p>';
        }

        $changelog_data = json_decode($attributes['changelogData'], true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($changelog_data)) {
            return '<p>' . __('Invalid changelog data', 'easy-changelog') . '</p>';
        }

        ob_start();
        ?>
        <div class="easy-changelog-block">
            <h3 class="easy-changelog-title"><?php _e('Changelog', 'easy-changelog'); ?></h3>
            <div class="easy-changelog-list">
                <?php foreach ($changelog_data as $release): ?>
                    <?php if (isset($release['version']) && isset($release['date'])): ?>
                        <div class="easy-changelog-release">
                            <div class="easy-changelog-header">
                                <strong class="easy-changelog-version"><?php echo esc_html($release['version']); ?></strong>
                                <span class="easy-changelog-date"><?php echo esc_html($this->format_date($release['date'])); ?></span>
                            </div>
                            <?php if (!empty($release['added']) && is_array($release['added'])): ?>
                                <div class="easy-changelog-section">
                                    <h4 class="easy-changelog-section-title"><?php _e('Added', 'easy-changelog'); ?></h4>
                                    <ul class="easy-changelog-items">
                                        <?php foreach ($release['added'] as $item): ?>
                                            <li class="easy-changelog-item"><?php echo esc_html($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Форматирование даты в российском формате (DD.MM.YYYY)
     */
    private function format_date($date_string) {
        $timestamp = strtotime($date_string);
        if ($timestamp !== false) {
            return date('d.m.Y', $timestamp);
        }
        return $date_string; // Возвращаем оригинальную строку, если парсинг не удался
    }
}

new EasyChangelog();
```

## 2. Исправленные JSON файлы переводов

**easy-changelog-en_US.json**

```json
{
  "translation-revision-date": "2024-11-18 21:38+0000",
  "generator": "WP-CLI/2.8.1",
  "source": "build/index.js",
  "domain": "easy-changelog",
  "locale_data": {
    "easy-changelog": {
      "": {
        "domain": "easy-changelog",
        "plural-forms": "nplurals=2; plural=(n != 1);",
        "lang": "en_US"
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
      "Invalid changelog data": ["Invalid changelog data"],
      "No changelog data provided": ["No changelog data provided"]
    }
  }
}
```

**easy-changelog-ru_RU.json**

```json
{
  "translation-revision-date": "2024-11-18 21:38+0000",
  "generator": "WP-CLI/2.8.1",
  "source": "build/index.js",
  "domain": "easy-changelog",
  "locale_data": {
    "easy-changelog": {
      "": {
        "domain": "easy-changelog",
        "plural-forms": "nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2);",
        "lang": "ru_RU"
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
      "Invalid changelog data": ["Неверные данные чейнджлога"],
      "No changelog data provided": ["Данные чейнджлога не предоставлены"]
    }
  }
}
```

## 3. Исправленный файл стилей

**src/style.scss**

```scss
// Editor styles
.easy-changelog-editor {
    width: 100%;

    .easy-changelog-tabs {
        width: 100%;
    }

    .components-tab-panel__tabs {
        border-bottom: 1px solid #ddd;
        margin-bottom: 20px;
    }

    .components-tab-panel__tab {
        padding: 10px 20px;
        border: 1px solid transparent;
        border-bottom: none;
        background: none;
        cursor: pointer;

        &.is-active {
            background: #fff;
            border-color: #ddd;
            border-bottom-color: #fff;
            margin-bottom: -1px;
        }
    }

    .easy-changelog-tab-content {
        padding: 20px 0;
    }

    .easy-changelog-textarea {
        font-family: monospace;
        font-size: 14px;
    }
}

// Frontend styles
.easy-changelog-block {
    width: 100%;
    max-width: 100%;
    margin: 2rem 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;

    .easy-changelog-title {
        font-size: 2rem;
        font-weight: 700;
        color: #1e1e1e;
        margin-bottom: 2rem;
        text-align: center;
        border-bottom: 3px solid #007cba;
        padding-bottom: 0.5rem;
    }

    .easy-changelog-list {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .easy-changelog-release {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;

        &:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }
    }

    .easy-changelog-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f0f0f0;
        gap: 2rem; // Добавлен пробел между версией и датой
    }

    .easy-changelog-version {
        font-size: 1.5rem;
        font-weight: 700; // Полужирное начертание
        color: #007cba;
        background: #f0f7ff;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border: 2px solid #007cba;
        white-space: nowrap;
    }

    .easy-changelog-date {
        font-size: 1rem;
        color: #666;
        font-weight: 500;
        background: #f8f9fa;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        white-space: nowrap;
    }

    .easy-changelog-section {
        margin-bottom: 1.5rem;

        &:last-child {
            margin-bottom: 0;
        }
    }

    .easy-changelog-section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 1rem;
        padding-left: 0.5rem;
        border-left: 4px solid #48bb78;
    }

    .easy-changelog-items {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .easy-changelog-item {
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        background: #f8f9fa;
        border-left: 4px solid #007cba;
        border-radius: 0 6px 6px 0;
        transition: all 0.2s ease;

        &:hover {
            background: #e3f2fd;
            transform: translateX(4px);
        }

        &:last-child {
            margin-bottom: 0;
        }

        &::before {
            content: "✓";
            color: #48bb78;
            font-weight: bold;
            margin-right: 0.5rem;
        }
    }
}

// Responsive design
@media (max-width: 768px) {
    .easy-changelog-block {
        .easy-changelog-header {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }

        .easy-changelog-version,
        .easy-changelog-date {
            width: 100%;
            text-align: center;
        }

        .easy-changelog-release {
            padding: 1.5rem;
        }

        .easy-changelog-header {
            gap: 1rem; // Меньший пробел на мобильных
        }
    }
}

// RTL support
.rtl {
    .easy-changelog-block {
        .easy-changelog-item {
            border-left: none;
            border-right: 4px solid #007cba;
            border-radius: 6px 0 0 6px;

            &:hover {
                transform: translateX(-4px);
            }
        }

        .easy-changelog-section-title {
            border-left: none;
            border-right: 4px solid #48bb78;
            padding-left: 0;
            padding-right: 0.5rem;
        }
    }
}
```

## 4. Упрощенный файл регистрации блока

**src/index.js**

```javascript
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';
import './style.scss';

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
        "date": "2024-01-15",
        "added": [
            "Initial release of the plugin",
            "Basic changelog functionality",
            "Gutenberg block integration"
        ]
    },
    {
        "version": "1.1.0",
        "date": "2024-01-20",
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
```

## 5. Исправленный файл edit.js

**src/edit.js**

```javascript
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
    PanelBody,
    TextareaControl,
    TabPanel,
    Notice
} from '@wordpress/components';
import {
    InspectorControls,
    useBlockProps
} from '@wordpress/block-editor';

const Edit = ({ attributes, setAttributes }) => {
    const { changelogData } = attributes;
    const [jsonError, setJsonError] = useState('');
    const [previewData, setPreviewData] = useState([]);
    const blockProps = useBlockProps();

    // Валидация и парсинг JSON
    useEffect(() => {
        try {
            if (changelogData.trim()) {
                const parsed = JSON.parse(changelogData);
                if (Array.isArray(parsed)) {
                    setPreviewData(parsed);
                    setJsonError('');
                } else {
                    setJsonError(__('JSON must be an array', 'easy-changelog'));
                }
            } else {
                setPreviewData([]);
                setJsonError('');
            }
        } catch (error) {
            setJsonError(__('Invalid JSON format', 'easy-changelog'));
            setPreviewData([]);
        }
    }, [changelogData]);

    const tabs = [
        {
            name: 'json',
            title: __('JSON Editor', 'easy-changelog'),
            className: 'easy-changelog-json-tab',
        },
        {
            name: 'preview',
            title: __('Preview', 'easy-changelog'),
            className: 'easy-changelog-preview-tab',
        },
    ];

    const renderJsonTab = () => (
        <div className="easy-changelog-json-editor">
            {jsonError && (
                <Notice status="error" isDismissible={false}>
                    {jsonError}
                </Notice>
            )}
            <TextareaControl
                label={__('Changelog JSON', 'easy-changelog')}
                help={__('Enter valid JSON array with version, date, and added fields', 'easy-changelog')}
                value={changelogData}
                onChange={(value) => setAttributes({ changelogData: value })}
                rows={20}
                className="easy-changelog-textarea"
            />
        </div>
    );

    // Функция форматирования даты в российском формате (DD.MM.YYYY)
    const formatDate = (dateString) => {
        if (!dateString || dateString === 'No date') {
            return dateString;
        }

        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return dateString;
        }

        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();

        return `${day}.${month}.${year}`;
    };

    const renderPreviewTab = () => (
        <div className="easy-changelog-preview">
            {jsonError ? (
                <Notice status="warning" isDismissible={false}>
                    {__('Fix JSON errors to see preview', 'easy-changelog')}
                </Notice>
            ) : previewData.length === 0 ? (
                <Notice status="info" isDismissible={false}>
                    {__('No changelog data to display', 'easy-changelog')}
                </Notice>
            ) : (
                <div className="easy-changelog-block">
                    <h3 className="easy-changelog-title">{__('Changelog', 'easy-changelog')}</h3>
                    <div className="easy-changelog-list">
                        {previewData.map((release, index) => (
                            <div key={index} className="easy-changelog-release">
                                <div className="easy-changelog-header">
                                    <strong className="easy-changelog-version">
                                        {release.version || 'No version'}
                                    </strong>
                                    <span className="easy-changelog-date">
                                        {formatDate(release.date || 'No date')}
                                    </span>
                                </div>
                                {release.added && release.added.length > 0 && (
                                    <div className="easy-changelog-section">
                                        <h4 className="easy-changelog-section-title">
                                            {__('Added', 'easy-changelog')}
                                        </h4>
                                        <ul className="easy-changelog-items">
                                            {release.added.map((item, itemIndex) => (
                                                <li key={itemIndex} className="easy-changelog-item">
                                                    {item}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Changelog Settings', 'easy-changelog')} initialOpen={true}>
                    <p>{__('Use the JSON Editor tab to input your changelog data in JSON format.', 'easy-changelog')}</p>
                </PanelBody>
            </InspectorControls>

            <div className="easy-changelog-editor">
                <TabPanel
                    className="easy-changelog-tabs"
                    activeClass="is-active"
                    onSelect={() => {}}
                    tabs={tabs}
                >
                    {(tab) => (
                        <div className="easy-changelog-tab-content">
                            {tab.name === 'json' && renderJsonTab()}
                            {tab.name === 'preview' && renderPreviewTab()}
                        </div>
                    )}
                </TabPanel>
            </div>
        </div>
    );
};

export default Edit;
```

## Инструкция по обновлению

1. **Замените файлы** согласно приведенным выше исправлениям
2. **Обновите зависимости и пересоберите** плагин:
   ```bash
   npm run build
   ```
3. **Обновите файлы переводов** в папке `languages/`
4. **Активируйте плагин заново** или обновите его

## Основные исправления

✅ **Добавлен пробел между версией и датой:**
- В CSS добавлен `gap: 2rem` для `.easy-changelog-header`
- На мобильных `gap: 1rem`

✅ **Версия выделена полужирным:**
- В PHP: `<strong class="easy-changelog-version">`
- В JavaScript: `<strong className="easy-changelog-version">`
- В CSS: `font-weight: 700`

✅ **Исправлена локализация:**
- Удалена сложная логика загрузки переводов из JavaScript
- Добавлена правильная регистрация переводов через `wp_set_script_translations()`
- JSON файлы переводов теперь указывают на `build/index.js` как источник
- Добавлены недостающие переводы

Теперь блок будет правильно подхватывать язык из локали WordPress и отображать красивый чейнджлог с правильным форматированием!