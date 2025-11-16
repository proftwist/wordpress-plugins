/**
 * GitHub Commit Chart Gutenberg Block
 *
 * Registers the GitHub Commit Chart block for the Gutenberg editor
 *
 * @package GitHubCommitChart
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
     * Регистрация Gutenberg-блока GitHub Commit Chart
     */
    registerBlockType('github-commit-chart/git-diagram', {
        title: __('Git Diagram', 'github-commit-chart'),
        icon: 'chart-bar',
        category: 'widgets',
        attributes: {
            githubProfile: {
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
            var githubProfile = attributes.githubProfile || '';
            var headingTag = attributes.headingTag || 'h3';

            return el(
                'div',
                { className: props.className },
                el(
                    'div',
                    { className: 'github-commit-chart-placeholder' },
                    githubProfile ?
                        __('Git diagram for', 'github-commit-chart') + ' ' + githubProfile + ' ' + __('will be displayed here', 'github-commit-chart') :
                        __('Enter GitHub username in block settings', 'github-commit-chart')
                ),
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __('Git Diagram Settings', 'github-commit-chart'), initialOpen: true },
                        el(TextControl, {
                            label: __('GitHub Profile Path', 'github-commit-chart'),
                            value: githubProfile,
                            onChange: function (value) {
                                setAttributes({ githubProfile: value });
                            },
                            placeholder: __('e.g., username', 'github-commit-chart')
                        }),
                        el(SelectControl, {
                            label: __('Heading Tag', 'github-commit-chart'),
                            value: headingTag,
                            options: [
                                { label: 'H2', value: 'h2' },
                                { label: 'H3', value: 'h3' },
                                { label: 'H4', value: 'h4' },
                                { label: __('Plain Text', 'github-commit-chart'), value: 'div' }
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