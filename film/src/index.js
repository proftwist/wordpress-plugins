(function() {
    'use strict';

    // Импорт зависимостей из WordPress
    const { React } = window;
    const { registerBlockType } = window.wp.blocks;
    const { useBlockProps, BlockControls, InspectorControls } = window.wp.blockEditor;
    const { ToolbarGroup, Button, PanelBody, RangeControl, SelectControl } = window.wp.components;
    const { __ } = window.wp.i18n;
    const { SVG, Path } = window.wp.primitives;

    // Иконка блока - галерея изображений
    const blockIcon = React.createElement(SVG, { viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" },
        React.createElement(Path, {
            d: "M16.375 4.5H4.625a.125.125 0 0 0-.125.125v8.254l2.859-1.54a.75.75 0 0 1 .68-.016l2.384 1.142 2.89-2.074a.75.75 0 0 1 .874 0l2.313 1.66V4.625a.125.125 0 0 0-.125-.125Zm.125 9.398-2.75-1.975-2.813 2.02a.75.75 0 0 1-.76.067l-2.444-1.17L4.5 14.583v1.792c0 .069.056.125.125.125h11.75a.125.125 0 0 0 .125-.125v-2.477ZM4.625 3C3.728 3 3 3.728 3 4.625v11.75C3 17.273 3.728 18 4.625 18h11.75c.898 0 1.625-.727 1.625-1.625V4.625C18 3.728 17.273 3 16.375 3H4.625ZM20 8v11c0 .69-.31 1-.999 1H6v1.5h13.001c1.52 0 2.499-.982 2.499-2.5V8H20Z",
            fillRule: "evenodd",
            clipRule: "evenodd"
        })
    );

    // Регистрация блока фотоплёнки
    registerBlockType('film/film-gallery', {
        title: __('Фотоплёнка', 'film'),
        description: __('Галерея в стиле фотоплёнки с горизонтальной прокруткой', 'film'),
        category: 'media',
        icon: blockIcon,

        // Атрибуты блока
        attributes: {
            images: {
                type: 'array',
                default: []
            },
            height: {
                type: 'number',
                default: 500
            },
            linkTo: {
                type: 'string',
                default: 'none'
            },
            align: {
                type: 'string',
                default: 'none'
            }
        },

        // Поддержка выравнивания
        supports: {
            align: ['wide', 'full']
        },

        // Функция редактирования блока
        edit: function({ attributes, setAttributes }) {
            const { images, height, linkTo, align } = attributes;
            const blockProps = useBlockProps();

            /**
             * Открывает медиабиблиотеку для выбора изображений
             */
            const openMediaLibrary = () => {
                // Используем стандартный медиазагрузчик WordPress
                const frame = wp.media({
                    title: __('Выберите изображения', 'film'),
                    multiple: true,
                    library: {
                        type: 'image'
                    },
                    button: {
                        text: __('Использовать выбранные изображения', 'film')
                    }
                });

                // Обработчик выбора изображений
                frame.on('select', function() {
                    const selectedImages = frame.state().get('selection').toJSON().map(image => ({
                        id: image.id,
                        url: image.url,
                        alt: image.alt || '',
                        caption: image.caption
                    }));
                    setAttributes({ images: selectedImages });
                });

                frame.open();
            };

            /**
             * Удаляет изображение из галереи
             * @param {number} index - Индекс удаляемого изображения
             */
            const removeImage = (index) => {
                const updatedImages = images.filter((_, i) => i !== index);
                setAttributes({ images: updatedImages });
            };

            /**
             * Перемещает изображение вверх по списку
             * @param {number} index - Индекс перемещаемого изображения
             */
            const moveImageUp = (index) => {
                if (index === 0) return;
                const updatedImages = [...images];
                [updatedImages[index - 1], updatedImages[index]] = [updatedImages[index], updatedImages[index - 1]];
                setAttributes({ images: updatedImages });
            };

            /**
             * Перемещает изображение вниз по списку
             * @param {number} index - Индекс перемещаемого изображения
             */
            const moveImageDown = (index) => {
                if (index === images.length - 1) return;
                const updatedImages = [...images];
                [updatedImages[index], updatedImages[index + 1]] = [updatedImages[index + 1], updatedImages[index]];
                setAttributes({ images: updatedImages });
            };

            return React.createElement(React.Fragment, null,
                // Панель инструментов блока
                React.createElement(BlockControls, null,
                    React.createElement(ToolbarGroup, null,
                        React.createElement(Button, {
                            icon: blockIcon,
                            label: __('Добавить/Изменить изображения', 'film'),
                            onClick: openMediaLibrary
                        })
                    )
                ),

                // Панель инспектора с настройками
                React.createElement(InspectorControls, null,
                    React.createElement(PanelBody, {
                        title: __('Настройки фотоплёнки', 'film'),
                        initialOpen: true
                    },
                        React.createElement(RangeControl, {
                            label: __('Высота плёнки', 'film'),
                            value: height,
                            onChange: (value) => setAttributes({ height: value }),
                            min: 100,
                            max: 1000,
                            step: 10
                        }),
                        React.createElement(SelectControl, {
                            label: __('Ссылки на изображения', 'film'),
                            value: linkTo,
                            options: [
                                { label: __('Без ссылки', 'film'), value: 'none' },
                                { label: __('Ссылка на файл', 'film'), value: 'media' },
                                { label: __('Страница вложения', 'film'), value: 'attachment' }
                            ],
                            onChange: (value) => setAttributes({ linkTo: value })
                        })
                    )
                ),

                // Основной интерфейс блока
                React.createElement('div', blockProps,
                    React.createElement('div', {
                        className: 'film-gallery-editor',
                        style: { height: height + 'px' }
                    },
                        images.length === 0
                            ? React.createElement('div', { className: 'film-placeholder' },
                                React.createElement('p', null, __('Добавьте изображения в фотоплёнку', 'film')),
                                React.createElement(Button, {
                                    isPrimary: true,
                                    onClick: openMediaLibrary
                                }, __('Выберите изображения', 'film'))
                            )
                            : React.createElement(React.Fragment, null,
                                React.createElement('div', { className: 'film-strip' },
                                    images.map((image, index) =>
                                        React.createElement('div', {
                                            key: index,
                                            className: 'film-frame'
                                        },
                                            React.createElement('img', {
                                                src: image.url,
                                                alt: image.alt
                                            })
                                        )
                                    )
                                ),
                                React.createElement('div', {
                                    style: { marginTop: '10px', textAlign: 'center' }
                                },
                                    React.createElement(Button, {
                                        isSecondary: true,
                                        onClick: openMediaLibrary
                                    }, __('Добавить/Заменить изображения', 'film'))
                                )
                            )
                    )
                )
            );
        },

        // Функция сохранения блока (рендеринг на сервере)
        save: function() {
            return null; // Рендеринг на сервере через PHP
        }
    });

})();