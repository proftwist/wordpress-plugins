const { registerBlockType } = wp.blocks;
const {
    RichText,         // Компонент для редактирования текста
    MediaUpload,      // Компонент для загрузки медиафайлов
    InspectorControls // Компонент для панели настроек блока
} = wp.blockEditor;
const {
    PanelBody,        // Контейнер для группировки настроек
    SelectControl,    // Выпадающий список для выбора опций
    TextControl,      // Текстовое поле для ввода
    Button            // Кнопка для действий
} = wp.components;
const { __ } = wp.i18n; // Функция для переводов

/**
 * Регистрация Gutenberg блока "Text with Side"
 *
 * Этот блок позволяет создавать текстовые блоки с изображениями,
 * которые отображаются на полях основного контента страницы.
 */
registerBlockType( 'text-with-side/text-with-side', {
    // Основные параметры блока
    title: __( 'Text with Side', 'text-with-side' ),                                    // Название блока
    description: __( 'Text block with side image that floats in margins on frontend', 'text-with-side' ), // Описание блока
    category: 'common',                                                                 // Категория блока в редакторе
    icon: 'align-left',                                                                  // Иконка блока

    // Атрибуты блока - данные, которые сохраняются в базе данных
    attributes: {
        content: {                          // Текстовое содержимое блока
            type: 'string',
            default: '',
        },
        imageId: {                          // ID изображения в медиатеке WordPress
            type: 'number',
            default: 0,
        },
        imageUrl: {                         // URL изображения
            type: 'string',
            default: '',
        },
        imageAlt: {                         // Альтернативный текст изображения
            type: 'string',
            default: '',
        },
        position: {                         // Позиция блока: слева или справа
            type: 'string',
            default: 'left',
        },
        imageLink: {                       // Тип ссылки на изображение
            type: 'string',
            default: 'none',
        },
        width: {                           // Ширина изображения (например, "150px")
            type: 'string',
            default: '150px',
        },
    },

    /**
     * Функция редактирования блока (отображается в редакторе Gutenberg)
     *
     * @param {Object} attributes - Текущие атрибуты блока
     * @param {Function} setAttributes - Функция для обновления атрибутов
     * @returns {JSX.Element} - React компонент для редактора
     */
    edit: ( { attributes, setAttributes } ) => {
        // Извлекаем атрибуты из объекта для удобства использования
        const {
            content,
            imageUrl,
            imageAlt,
            position,
            imageLink,
            width
        } = attributes;

        /**
         * Обработчик выбора изображения из медиатеки
         *
         * @param {Object} media - Объект с данными выбранного изображения
         */
        const onSelectImage = ( media ) => {
            setAttributes( {
                imageId: media.id,      // Сохраняем ID изображения
                imageUrl: media.url,    // Сохраняем URL изображения
                imageAlt: media.alt,    // Сохраняем альтернативный текст
            } );
        };

        /**
         * Обработчик удаления изображения
         * Сбрасывает все связанные с изображением атрибуты
         */
        const onRemoveImage = () => {
            setAttributes( {
                imageId: 0,             // Сбрасываем ID изображения
                imageUrl: '',           // Очищаем URL
                imageAlt: '',           // Очищаем альтернативный текст
            } );
        };

        // Возвращаем JSX разметку для редактора
        return (
            <>
                {/* Панель настроек блока (отображается в боковой панели редактора) */}
                <InspectorControls>
                    <PanelBody title={ __( 'Block Settings', 'text-with-side' ) }>
                        {/* Настройка позиции отображения блока на фронтенде */}
                        <SelectControl
                            label={ __( 'Display Position on Frontend', 'text-with-side' ) }
                            value={ position }
                            options={ [
                                { label: __( 'Left', 'text-with-side' ), value: 'left' },
                                { label: __( 'Right', 'text-with-side' ), value: 'right' },
                            ] }
                            onChange={ ( value ) => setAttributes( { position: value } ) }
                        />

                        {/* Настройка типа ссылки на изображение */}
                        <SelectControl
                            label={ __( 'Image Link', 'text-with-side' ) }
                            value={ imageLink }
                            options={ [
                                { label: __( 'None', 'text-with-side' ), value: 'none' },
                                { label: __( 'To Media File', 'text-with-side' ), value: 'media' },
                                { label: __( 'To Attachment Page', 'text-with-side' ), value: 'attachment' },
                            ] }
                            onChange={ ( value ) => setAttributes( { imageLink: value } ) }
                        />

                        {/* Настройка ширины изображения */}
                        <TextControl
                            label={ __( 'Image Width on Frontend', 'text-with-side' ) }
                            value={ width }
                            onChange={ ( value ) => setAttributes( { width: value } ) }
                            help={ __( 'Default: 150px', 'text-with-side' ) }
                        />
                    </PanelBody>
                </InspectorControls>

                {/* Основной визуальный блок для редактора */}
                <div className={ `text-with-side-block text-with-side-${ position }` } data-position={ position }>
                    <div className="text-with-side-inner">
                        {/* Отображение изображения (если оно есть) */}
                        { imageUrl && (
                            <div className="text-with-side-image">
                                <img
                                    src={ imageUrl }
                                    alt={ imageAlt }
                                    style={ { width: '100%', maxWidth: '300px' } }
                                />
                                <Button
                                    className="remove-image"
                                    onClick={ onRemoveImage }
                                    isSmall
                                    isDestructive
                                >
                                    { __( 'Remove Image', 'text-with-side' ) }
                                </Button>
                            </div>
                        )}

                        {/* Кнопка добавления изображения (если изображения нет) */}
                        { ! imageUrl && (
                            <MediaUpload
                                onSelect={ onSelectImage }
                                type="image"
                                render={ ( { open } ) => (
                                    <Button
                                        className="add-image"
                                        onClick={ open }
                                        isPrimary
                                        style={ { width: '100%', maxWidth: '300px', margin: '0 auto', display: 'block' } }
                                    >
                                        { __( 'Add Image', 'text-with-side' ) }
                                    </Button>
                                ) }
                            />
                        )}

                        {/* Редактор текстового содержимого */}
                        <div className="text-with-side-content">
                            <RichText
                                tagName="div"
                                value={ content }
                                onChange={ ( value ) => setAttributes( { content: value } ) }
                                placeholder={ __( 'Enter your text here...', 'text-with-side' ) }
                                multiline="p"
                            />
                        </div>
                    </div>

                    {/* Информационное уведомление для пользователя */}
                    <div className="text-with-side-editor-notice">
                        { __( 'On frontend this block will float in the ', 'text-with-side' ) }
                        <strong>{ position === 'left' ? __( 'left margin', 'text-with-side' ) : __( 'right margin', 'text-with-side' ) }</strong>
                        { __( ' with width: ', 'text-with-side' ) }
                        <strong>{ width }</strong>
                    </div>
                </div>
            </>
        );
    },

    /**
     * Функция сохранения блока (генерирует HTML для фронтенда)
     *
     * @param {Object} attributes - Атрибуты блока для сохранения
     * @returns {JSX.Element|null} - HTML разметка или null если блок пуст
     */
    save: ( { attributes } ) => {
        // Извлекаем атрибуты для удобства
        const {
            content,
            imageUrl,
            imageAlt,
            position,
            imageLink,
            width
        } = attributes;

        // Если нет ни текста, ни изображения - не сохраняем блок
        if ( ! content && ! imageUrl ) {
            return null;
        }

        // Подготавливаем изображение в зависимости от настроек ссылки
        let imageElement = null;
        if ( imageUrl ) {
            // Базовое изображение
            imageElement = (
                <img
                    src={ imageUrl }
                    alt={ imageAlt }
                    style={ { width } }
                />
            );

            // Оборачиваем в ссылку в зависимости от настроек
            if ( imageLink === 'media' && attributes.imageId ) {
                // Ссылка на медиафайл
                imageElement = (
                    <a href={ "#" } className="text-with-side-image-link">
                        { imageElement }
                    </a>
                );
            } else if ( imageLink === 'attachment' && attributes.imageId ) {
                // Ссылка на страницу вложения
                imageElement = (
                    <a href={ "#" } className="text-with-side-image-link">
                        { imageElement }
                    </a>
                );
            } else {
                // Без ссылки
                imageElement = (
                    <div className="text-with-side-image-link">
                        { imageElement }
                    </div>
                );
            }
        }

        // Возвращаем финальную HTML разметку для фронтенда
        return (
            <div className={ `text-with-side-block text-with-side-${ position }` }>
                <div className="text-with-side-inner">
                    { imageUrl && (
                        <div className="text-with-side-image">
                            { imageElement }
                        </div>
                    ) }
                    { content && (
                        <div className="text-with-side-content">
                            <RichText.Content tagName="div" value={ content } />
                        </div>
                    ) }
                </div>
            </div>
        );
    },
} );