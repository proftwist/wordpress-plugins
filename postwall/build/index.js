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
                        { title: __('Настройки Post Wall', 'postwall'), initialOpen: true },
                        el(TextControl, {
                            label: __('Site URL', 'postwall'),
                            value: siteUrl,
                            onChange: function (value) {
                                setAttributes({ siteUrl: value });
                            },
                            placeholder: __('https://example.com', 'postwall')
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