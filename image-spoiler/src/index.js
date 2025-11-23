/**
 * Расширение блока Image для добавления функции спойлера
 */

import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToolbarButton } from '@wordpress/components';

import './style.css';
import './editor.css';

/**
 * Иконка для кнопки спойлера
 */
const spoilerIcon = (
    <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
        <line x1="4" y1="4" x2="20" y2="20" stroke="currentColor" strokeWidth="2" />
    </svg>
);

/**
 * Добавляем атрибуты к блоку Image
 */
function addSpoilerAttributes(settings, name) {
    if (name !== 'core/image') {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            isSpoiler: {
                type: 'boolean',
                default: false,
            },
            spoilerText: {
                type: 'string',
                default: '',
            },
        },
    };
}

addFilter(
    'blocks.registerBlockType',
    'image-spoiler/add-attributes',
    addSpoilerAttributes
);

/**
 * Добавляем элементы управления в редактор
 */
const withSpoilerControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        if (props.name !== 'core/image') {
            return <BlockEdit {...props} />;
        }

        const { attributes, setAttributes } = props;
        const { isSpoiler, spoilerText } = attributes;

        // Обработчик переключения спойлера
        const toggleSpoiler = () => {
            setAttributes({
                isSpoiler: !isSpoiler,
                // Устанавливаем текст по умолчанию при включении
                spoilerText: !isSpoiler && !spoilerText
                    ? __('Потенциально неприемлемый контент', 'image-spoiler')
                    : spoilerText
            });
        };

        return (
            <Fragment>
                {/* Кнопка в тулбаре */}
                <BlockControls>
                    <ToolbarButton
                        icon={spoilerIcon}
                        label={__('Спойлер', 'image-spoiler')}
                        isPressed={isSpoiler}
                        onClick={toggleSpoiler}
                    />
                </BlockControls>

                {/* Настройки в правой панели */}
                {isSpoiler && (
                    <InspectorControls>
                        <PanelBody
                            title={__('Настройки спойлера', 'image-spoiler')}
                            initialOpen={true}
                        >
                            <TextControl
                                label={__('Текст спойлера', 'image-spoiler')}
                                value={spoilerText}
                                onChange={(value) => setAttributes({ spoilerText: value })}
                                help={__('Текст, который будет отображаться поверх размытого изображения', 'image-spoiler')}
                            />
                        </PanelBody>
                    </InspectorControls>
                )}

                <BlockEdit {...props} />
            </Fragment>
        );
    };
}, 'withSpoilerControls');

addFilter(
    'editor.BlockEdit',
    'image-spoiler/with-controls',
    withSpoilerControls
);