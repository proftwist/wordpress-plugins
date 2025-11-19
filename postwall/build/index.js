/**
 * Post Wall Gutenberg Block
 *
 * Registers the Post Wall block for the Gutenberg editor
 *
 * @package PostWall
 * @since 1.0.0
 */

(function (wp) {
    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var __ = wp.i18n.__;

    /**
     * Регистрация Gutenberg-блока Post Wall
     */
    registerBlockType('postwall/post-wall', {
        title: __('Post Wall', 'postwall'),
        icon: 'grid-view',
        category: 'widgets',
        attributes: {
            siteUrl: {
                type: 'string',
                default: ''
            },
            headingTag: {
                type: 'string',
                default: 'h3'
            },
            selectedYear: {
                type: 'string',
                default: 'last12'
            }
        },

        /**
         * Функция редактирования блока
         *
         * @param {Object} props - Свойства блока
         */
        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // Получаем значения из атрибутов блока
            var siteUrl = attributes.siteUrl || '';
            var headingTag = attributes.headingTag || 'h3';
            var selectedYear = attributes.selectedYear || 'last12';

            // Генерируем опции для выбора года
            var yearOptions = [
                { label: __('Last 12 months', 'postwall'), value: 'last12' }
            ];

            // Добавляем доступные годы (от текущего до 2010)
            var currentYear = new Date().getFullYear();
            for (var year = currentYear; year >= 2010; year--) {
                yearOptions.push({
                    label: year.toString(),
                    value: year.toString()
                });
            }

            return el(
                'div',
                { className: props.className },
                el(
                    'div',
                    { className: 'postwall-placeholder' },
                    __('Post wall', 'postwall')
                ),
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Post Wall Settings', 'postwall'), initialOpen: true },
                        el(TextControl, {
                            label: __('Site URL', 'postwall'),
                            value: siteUrl,
                            onChange: function (value) {
                                setAttributes({ siteUrl: value });
                            },
                            placeholder: __('https://example.com', 'postwall')
                        }),
                        el(SelectControl, {
                            label: __('Year', 'postwall'),
                            value: selectedYear,
                            options: yearOptions,
                            onChange: function (value) {
                                setAttributes({ selectedYear: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Heading Tag', 'postwall'),
                            value: headingTag,
                            options: [
                                { label: __('H2', 'postwall'), value: 'h2' },
                                { label: __('H3', 'postwall'), value: 'h3' },
                                { label: __('H4', 'postwall'), value: 'h4' },
                                { label: __('Plain Text', 'postwall'), value: 'div' }
                            ],
                            onChange: function (value) {
                                setAttributes({ headingTag: value });
                            }
                        })
                    )
                )
            );
        },

        /**
         * Функция сохранения блока
         */
        save: function () {
            // Рендеринг происходит на стороне сервера
            return null;
        }
    });
})(window.wp);