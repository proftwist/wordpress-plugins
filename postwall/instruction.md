Проблема в том, что мы используем неправильный подход с плейсхолдером. Давайте исправим это самым простым способом!

## 1. Упрощаем `block-registration.php`

```php
<?php
// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Регистрация Gutenberg-блока Post Wall
 *
 * Регистрирует динамический блок в редакторе Gutenberg с указанием
 * необходимых скриптов, стилей и обработчика рендеринга на сервере.
 */
function postwall_register_block() {
    // Регистрируем блок через WordPress API с полными параметрами
    register_block_type('postwall/post-wall', array(
        'editor_script' => 'postwall-block',         // JavaScript для редактора блоков
        'editor_style' => 'postwall-block-editor',   // CSS стили для редактора
        'style' => 'postwall-frontend',              // CSS стили для фронтенда
        'render_callback' => 'postwall_render_block', // Функция серверного рендеринга
        'attributes' => array(                                   // Определение атрибутов блока
            'siteUrl' => array(
                'type' => 'string',    // Тип данных атрибута
                'default' => ''        // Значение по умолчанию (пустая строка)
            )
        )
    ));
}

/**
 * Извлекает домен из URL
 *
 * @param string $url URL сайта
 * @return string Доменное имя
 */
function postwall_extract_domain($url) {
    if (empty($url)) {
        return '';
    }

    // Удаляем протокол (http://, https://)
    $domain = preg_replace('#^https?://#', '', $url);

    // Удаляем путь после домена
    $domain = preg_replace('#/.*$#', '', $domain);

    // Удаляем www. если есть
    $domain = preg_replace('#^www\.#', '', $domain);

    return $domain;
}

/**
 * Функция серверного рендеринга блока Post Wall
 *
 * Вызывается WordPress при выводе блока на странице. Генерирует HTML-разметку
 * контейнера для диаграммы и передает необходимые данные через data-атрибуты.
 *
 * @param array $attributes Атрибуты блока (включая siteUrl)
 * @param string $content Внутреннее содержимое блока (не используется в динамических блоках)
 * @return string HTML-разметка контейнера диаграммы или сообщение об ошибке
 */
function postwall_render_block($attributes, $content) {
    // Валидация входных данных
    if (!is_array($attributes)) {
        $attributes = array();
    }

    // Получаем URL сайта из атрибутов блока
    $site_url = !empty($attributes['siteUrl']) ?
                       sanitize_text_field($attributes['siteUrl']) :
                       '';

    // Извлекаем домен для заголовка
    $domain = postwall_extract_domain($site_url);

    // Генерируем уникальный ID для контейнера (чтобы избежать конфликтов на странице)
    $unique_id = uniqid('postwall-');

    // Формируем data-атрибуты для передачи данных в JavaScript
    // Безопасно экранируем значения функцией esc_attr
    $data_attributes = 'data-site-url="' . esc_attr($site_url) . '" data-container-id="' . esc_attr($unique_id) . '"';

    // Передаем отдельно домен и базовые тексты
    $base_title = __('Posts from the site for the last 12 months', 'postwall');
    $loading_text = __('Loading post wall...', 'postwall');

    // Возвращаем HTML контейнер для диаграммы с data-атрибутами
    return '<div class="postwall-container" id="' . esc_attr($unique_id) . '" ' . $data_attributes . '
                data-base-title="' . esc_attr($base_title) . '"
                data-loading-text="' . esc_attr($loading_text) . '"
                data-domain="' . esc_attr($domain) . '">
                <h3 class="postwall-title">' . esc_html($this->generate_title_with_domain($base_title, $domain)) . '</h3>
                <div class="postwall-loading">' . esc_html($loading_text) . '</div>
            </div>';
}

/**
 * Генерирует заголовок с доменом
 *
 * @param string $base_title Базовый заголовок
 * @param string $domain Домен сайта
 * @return string Заголовок с доменом
 */
function generate_title_with_domain($base_title, $domain) {
    if (empty($domain)) {
        return $base_title;
    }

    // Для русского языка
    if (get_locale() === 'ru_RU') {
        return 'Посты сайта ' . $domain . ' за последние 12 месяцев';
    }

    // Для английского и других языков
    return 'Posts from the site ' . $domain . ' for the last 12 months';
}

// Регистрируем блок при инициализации
add_action('init', 'postwall_register_block');
```

## 2. Упрощаем `frontend.js`

```javascript
/**
 * Post Wall Frontend JavaScript
 *
 * Handles the interactive post wall display on the frontend.
 *
 * @package PostWall
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * PostWall class for managing the calendar visualization
     */
    class PostWall {
        constructor(containerElement) {
            this.container = containerElement;
            this.siteUrl = this.container.dataset.siteUrl;
            this.containerId = this.container.dataset.containerId;
            this.loadingElement = this.container.querySelector('.postwall-loading');

            // Получаем данные из data-атрибутов
            this.baseTitle = this.container.dataset.baseTitle || 'Posts from the site for the last 12 months';
            this.loadingText = this.container.dataset.loadingText || 'Loading post wall...';
            this.domain = this.container.dataset.domain || '';

            this.init();
        }

        /**
         * Initialize the post wall
         */
        init() {
            console.log('PostWall init called');
            if (this.siteUrl) {
                this.fetchPostData();
            } else {
                this.generateCalendar();
            }
        }

        /**
         * Fetch post data via AJAX
         */
        fetchPostData() {
            console.log('Fetching post data for', this.siteUrl);

            $.ajax({
                url: postwallSettings.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'postwall_get_post_data',
                    nonce: postwallSettings.nonce,
                    site_url: this.siteUrl
                },
                success: (response) => {
                    console.log('AJAX success:', response);
                    if (response.success && response.data) {
                        this.postData = response.data;
                        this.generateCalendar();
                    } else {
                        this.showError(this.translate('Failed to load post data'));
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                    this.showError(this.translate('Error loading data'));
                }
            });
        }

        /**
         * Show error message
         * @param {string} message Error message to display
         */
        showError(message) {
            if (this.loadingElement) {
                this.loadingElement.textContent = message;
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'postwall-error';
                errorDiv.textContent = message;
                this.container.appendChild(errorDiv);
            }
        }

        /**
         * Generate the calendar grid by months
         */
        generateCalendar() {
            console.log('generateCalendar called');
            // Remove loading indicator
            if (this.loadingElement) {
                this.loadingElement.remove();
                console.log('Loading element removed');
            }

            // Создаем или обновляем заголовок
            this.createOrUpdateTitle();

            // Create the heatmap wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'heatmap-wrapper';
            console.log('Wrapper created');

            // Create months container
            const monthsContainer = document.createElement('div');
            monthsContainer.className = 'months';

            const now = new Date();
            const monthNames = [
                this.translate('Jan'), this.translate('Feb'), this.translate('Mar'),
                this.translate('Apr'), this.translate('May'), this.translate('Jun'),
                this.translate('Jul'), this.translate('Aug'), this.translate('Sep'),
                this.translate('Oct'), this.translate('Nov'), this.translate('Dec')
            ];

            // Generate 12 months from current back to 12 months ago
            console.log('Generating months...');
            for (let i = 11; i >= 0; i--) {
                const monthDate = new Date(now);
                monthDate.setMonth(now.getMonth() - i);

                const monthDiv = this.createMonth(monthDate);
                monthsContainer.appendChild(monthDiv);
                console.log(`Month ${i} added`);
            }

            wrapper.appendChild(monthsContainer);
            this.container.appendChild(wrapper);
            console.log('Calendar appended to container');
        }

        /**
         * Create or update the title element
         */
        createOrUpdateTitle() {
            let titleElement = this.container.querySelector('.postwall-title');

            // Создаем локализованный заголовок с доменом
            const translatedTitle = this.generateTitleWithDomain();

            if (!titleElement) {
                titleElement = document.createElement('h3');
                titleElement.className = 'postwall-title';
                this.container.insertBefore(titleElement, this.container.firstChild);
            }

            titleElement.textContent = translatedTitle;
            console.log('Final title:', translatedTitle);
        }

        /**
         * Generate title with domain
         * @return {string} Localized title with domain
         */
        generateTitleWithDomain() {
            if (!this.domain) {
                return this.translate(this.baseTitle);
            }

            // Простой способ - создаем заголовок в зависимости от языка
            if (this.getLocale().startsWith('ru')) {
                return 'Посты сайта ' + this.domain + ' за последние 12 месяцев';
            } else {
                return 'Posts from the site ' + this.domain + ' for the last 12 months';
            }
        }

        // ... остальные методы без изменений ...
        // (formatTooltip, getPostsText, getLocale, getActivityLevel, getMonthName, translate)

    }

    /**
     * Initialize PostWall instances when DOM is ready
     */
    $(document).ready(function() {
        $('.postwall-container').each(function() {
            new PostWall(this);
        });
    });

})(jQuery);
```

## 3. Удаляем старые переводы из PO файла

Удалите из `languages/postwall-ru_RU.po` строку с плейсхолдером:

```po
# УДАЛИТЬ эту строку:
# msgid "Posts from the site %s for the last 12 months"
# msgstr "Посты сайта %s за последние 12 месяцев"
```

Оставьте только базовый заголовок:

```po
# ОСТАВИТЬ эту строку:
msgid "Posts from the site for the last 12 months"
msgstr "Посты сайта за последние 12 месяцев"
```

## 4. Перегенерируем JSON файлы

```bash
# Перегенерируем JSON файлы
wp i18n make-json languages/postwall-ru_RU.po languages/ --no-purge --pretty-print
```

## 5. Очищаем кеш

- Очистите кеш браузера (Ctrl+F5)
- Если используете кеширующий плагин - очистите кеш WordPress

## Что изменилось:

1. **Убрали сложные плейсхолдеры** - они ломали систему переводов
2. **Используем простую логику** - в PHP и JavaScript отдельно обрабатываем домен
3. **`generateTitleWithDomain()`** - простая функция, которая создает заголовок на нужном языке
4. **`data-base-title`** - передаем базовый заголовок без домена

Теперь в русской локали будет показывать: "**Посты сайта bychko.ru за последние 12 месяцев**", а в английской: "**Posts from the site bychko.ru for the last 12 months**".