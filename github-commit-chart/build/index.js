(function (wp) {
    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;

    registerBlockType('github-commit-chart/git-diagram', {
        title: 'Git-диаграмма',
        icon: 'chart-bar',
        category: 'widgets',
        attributes: {
            githubProfile: {
                type: 'string',
                default: ''
            }
        },
        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            // Получаем значение из атрибутов блока
            var githubProfile = attributes.githubProfile || '';

            return el(
                'div',
                { className: props.className },
                el(
                    'div',
                    { className: 'github-commit-chart-placeholder' },
                    githubProfile ?
                        'Git-диаграмма для ' + githubProfile + ' будет отображаться здесь' :
                        'Введите имя пользователя GitHub в настройках блока'
                ),
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: 'Настройки Git-диаграммы', initialOpen: true },
                        el(TextControl, {
                            label: 'Путь к профилю Github',
                            value: githubProfile,
                            onChange: function (value) {
                                setAttributes({ githubProfile: value });
                            },
                            placeholder: 'например: username'
                        })
                    )
                )
            );
        },
        save: function () {
            // Рендеринг происходит на стороне сервера
            return null;
        }
    });
})(window.wp);