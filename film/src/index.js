// Импорт стилей
import './style.css';
import './style-frontend.css';

(function() {
    'use strict';

    // Импорт зависимостей из WordPress
    const { React } = window;
    const { registerBlockType } = window.wp.blocks;
    const { useBlockProps, BlockControls, InspectorControls } = window.wp.blockEditor;
    const { ToolbarGroup, Button, PanelBody, RangeControl, SelectControl, Modal, Dashicon } = window.wp.components;
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

    // Компонент редактирования блока
    const FilmGalleryEdit = ({ attributes, setAttributes }) => {
        const { images, height, linkTo, align } = attributes;
        const blockProps = useBlockProps();

        // Состояние для модального окна редактирования галереи
        const [isEditingGallery, setIsEditingGallery] = React.useState(false);

        /**
         * Открывает медиабиблиотеку для добавления новых изображений
         */
        const openMediaLibrary = () => {
            // Проверяем доступность wp.media
            if (!wp.media) {
                console.error('wp.media is not available');
                return;
            }

            const frame = wp.media({
                title: __('Выберите изображения для добавления в галерею', 'film'),
                multiple: true,
                library: {
                    type: 'image'
                },
                button: {
                    text: __('Добавить в галерею', 'film')
                }
            });

            // Обработчик выбора изображений
            frame.on('select', function() {
                const selection = frame.state().get('selection');
                const selectedImages = selection.map(function(attachment) {
                    attachment = attachment.toJSON();
                    return {
                        id: attachment.id,
                        url: attachment.url,
                        alt: attachment.alt || '',
                        caption: attachment.caption,
                        title: attachment.title
                    };
                });

                // Добавляем новые изображения к существующим
                const updatedImages = [...images, ...selectedImages];
                setAttributes({ images: updatedImages });
            });

            frame.open();
        };

        /**
         * Открывает интерфейс редактирования галереи
         */
        const openGalleryEditor = () => {
            setIsEditingGallery(true);
        };

        /**
         * Закрывает интерфейс редактирования галереи
         */
        const closeGalleryEditor = () => {
            setIsEditingGallery(false);
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

        /**
         * Очищает всю галерею
         */
        const clearGallery = () => {
            if (confirm(__('Вы уверены, что хотите удалить все изображения из галереи?', 'film'))) {
                setAttributes({ images: [] });
                setIsEditingGallery(false);
            }
        };

        return React.createElement(React.Fragment, null,
            // Панель инструментов блока
            React.createElement(BlockControls, null,
                React.createElement(ToolbarGroup, null,
                    // Кнопка редактирования галереи (только если есть изображения)
                    images.length > 0 && React.createElement(Button, {
                        icon: 'edit',
                        label: __('Редактировать галерею', 'film'),
                        onClick: openGalleryEditor
                    }),
                    // Кнопка добавления изображений
                    React.createElement(Button, {
                        icon: 'plus',
                        label: __('Добавить изображения', 'film'),
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
                ),

                // Панель управления галереей
                images.length > 0 && React.createElement(PanelBody, {
                    title: __('Управление галереей', 'film'),
                    initialOpen: false
                },
                    React.createElement('div', { style: { display: 'flex', flexDirection: 'column', gap: '10px' } },
                        React.createElement(Button, {
                            isSecondary: true,
                            onClick: openGalleryEditor
                        }, __('Редактировать галерею', 'film')),
                        React.createElement(Button, {
                            isSecondary: true,
                            onClick: openMediaLibrary
                        }, __('Добавить изображения', 'film')),
                        images.length > 0 && React.createElement(Button, {
                            isDestructive: true,
                            onClick: clearGallery
                        }, __('Очистить галерею', 'film'))
                    )
                )
            ),

            // Модальное окно редактирования галереи
            isEditingGallery && React.createElement(Modal, {
                title: __('Редактирование галереи', 'film'),
                onRequestClose: closeGalleryEditor,
                className: 'film-gallery-editor-modal',
                style: { maxWidth: '800px' }
            },
                React.createElement('div', { className: 'film-gallery-editor-content' },
                    // Заголовок и кнопка добавления
                    React.createElement('div', { className: 'film-gallery-editor-header' },
                        React.createElement('h2', null, __('Изображения в галерее', 'film')),
                        React.createElement(Button, {
                            isPrimary: true,
                            onClick: openMediaLibrary
                        }, __('Добавить в галерею', 'film'))
                    ),

                    // Список изображений для редактирования
                    React.createElement('div', { className: 'film-gallery-edit-list' },
                        images.length === 0
                            ? React.createElement('div', { className: 'film-gallery-empty' },
                                React.createElement(Dashicon, { icon: 'format-gallery', size: 60 }),
                                React.createElement('p', null, __('В галерее пока нет изображений', 'film')),
                                React.createElement(Button, {
                                    isPrimary: true,
                                    onClick: openMediaLibrary
                                }, __('Добавить изображения', 'film'))
                            )
                            : images.map((image, index) =>
                                React.createElement('div', {
                                    key: index,
                                    className: 'film-gallery-edit-item'
                                },
                                    React.createElement('div', { className: 'film-gallery-edit-preview' },
                                        React.createElement('img', {
                                            src: image.url,
                                            alt: image.alt,
                                            className: 'film-gallery-edit-image'
                                        }),
                                        React.createElement('div', { className: 'film-gallery-edit-number' },
                                            index + 1
                                        )
                                    ),
                                    React.createElement('div', { className: 'film-gallery-edit-details' },
                                        React.createElement('div', { className: 'film-gallery-edit-filename' },
                                            image.url.split('/').pop()
                                        ),
                                        React.createElement('div', { className: 'film-gallery-edit-title' },
                                            image.title || __('Без названия', 'film')
                                        ),
                                        React.createElement('div', { className: 'film-gallery-edit-meta' },
                                            new Date().toLocaleDateString('ru-RU'), ' • ',
                                            '890 KB • ',
                                            '2000×1413'
                                        )
                                    ),
                                    React.createElement('div', { className: 'film-gallery-edit-actions' },
                                        React.createElement(Button, {
                                            icon: 'arrow-up-alt2',
                                            isSmall: true,
                                            onClick: () => moveImageUp(index),
                                            disabled: index === 0,
                                            label: __('Поднять выше', 'film')
                                        }),
                                        React.createElement(Button, {
                                            icon: 'arrow-down-alt2',
                                            isSmall: true,
                                            onClick: () => moveImageDown(index),
                                            disabled: index === images.length - 1,
                                            label: __('Опустить ниже', 'film')
                                        }),
                                        React.createElement(Button, {
                                            icon: 'trash',
                                            isSmall: true,
                                            isDestructive: true,
                                            onClick: () => removeImage(index),
                                            label: __('Удалить', 'film')
                                        })
                                    )
                                )
                            )
                    ),

                    // Кнопки управления внизу
                    images.length > 0 && React.createElement('div', { className: 'film-gallery-editor-footer' },
                        React.createElement('div', { className: 'film-gallery-stats' },
                            __('Всего изображений:', 'film'), ' ', images.length
                        ),
                        React.createElement('div', { className: 'film-gallery-footer-actions' },
                            React.createElement(Button, {
                                isSecondary: true,
                                onClick: openMediaLibrary,
                                style: { marginRight: '10px' }
                            }, __('Добавить ещё', 'film')),
                            React.createElement(Button, {
                                isPrimary: true,
                                onClick: closeGalleryEditor
                            }, __('Готово', 'film'))
                        )
                    )
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
                                className: 'film-gallery-actions'
                            },
                                React.createElement(Button, {
                                    isSecondary: true,
                                    onClick: openGalleryEditor,
                                    style: { marginRight: '10px' }
                                }, __('Редактировать галерею', 'film')),
                                React.createElement(Button, {
                                    isSecondary: true,
                                    onClick: openMediaLibrary
                                }, __('Добавить изображения', 'film'))
                            )
                        )
                )
            )
        );
    };

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
        edit: FilmGalleryEdit,

        // Функция сохранения блока (рендеринг на сервере)
        save: function() {
            return null;
        }
    });

})();