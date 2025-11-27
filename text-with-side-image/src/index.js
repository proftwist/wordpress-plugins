import { registerBlockType } from '@wordpress/blocks';
import { RichText, MediaUpload, MediaUploadCheck, InspectorControls, useBlockProps, Placeholder } from '@wordpress/block-editor';
import { Button, PanelBody, SelectControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { image as icon } from '@wordpress/icons';
import './editor.scss';
import './style.scss';

import metadata from './block.json';

registerBlockType(metadata.name, {
    edit: ({ attributes, setAttributes }) => {
        const { imageUrl, imageAlt, imageId, imagePosition, imageWidth, caption } = attributes;

        const blockProps = useBlockProps({
            className: `tsi-margin-block tsi-position-${imagePosition}`
        });

        const onSelectImage = (media) => {
            setAttributes({
                imageUrl: media.url,
                imageAlt: media.alt || __('Side image', 'tsi'),
                imageId: media.id
            });
        };

        const onRemoveImage = () => {
            setAttributes({
                imageUrl: '',
                imageAlt: '',
                imageId: 0,
                caption: ''
            });
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Margin Content Settings', 'tsi')} initialOpen={true}>
                        <SelectControl
                            label={__('Position in Margin', 'tsi')}
                            value={imagePosition}
                            options={[
                                { label: __('Left margin', 'tsi'), value: 'left' },
                                { label: __('Right margin', 'tsi'), value: 'right' }
                            ]}
                            onChange={(value) => setAttributes({ imagePosition: value })}
                        />

                        {imageUrl && (
                            <RangeControl
                                label={__('Content Width', 'tsi')}
                                value={imageWidth}
                                onChange={(value) => setAttributes({ imageWidth: value })}
                                min={80}
                                max={300}
                                step={10}
                                help={__('Width of margin content in pixels', 'tsi')}
                            />
                        )}
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div className="tsi-margin-content" style={{ width: `${imageWidth}px` }}>
                        {imageUrl ? (
                            <>
                                <div className="tsi-margin-image">
                                    <img src={imageUrl} alt={imageAlt} />
                                </div>
                                <div className="tsi-margin-caption">
                                    <RichText
                                        tagName="div"
                                        value={caption}
                                        onChange={(value) => setAttributes({ caption: value })}
                                        placeholder={__('Enter caption text for the margin...', 'tsi')}
                                        allowedFormats={['core/bold', 'core/italic', 'core/link']}
                                    />
                                </div>
                                <div className="tsi-margin-actions">
                                    <MediaUpload
                                        onSelect={onSelectImage}
                                        allowedTypes={['image']}
                                        value={imageId}
                                        render={({ open }) => (
                                            <Button
                                                onClick={open}
                                                variant="secondary"
                                                size="small"
                                            >
                                                {__('Replace Image', 'tsi')}
                                            </Button>
                                        )}
                                    />
                                    <Button
                                        onClick={onRemoveImage}
                                        variant="secondary"
                                        size="small"
                                        isDestructive
                                    >
                                        {__('Remove All', 'tsi')}
                                    </Button>
                                </div>
                            </>
                        ) : (
                            <div className="tsi-margin-placeholder">
                                <Placeholder
                                    icon={icon}
                                    label={__('Margin Content', 'tsi')}
                                    instructions={__('Add image and caption that will appear in the page margin', 'tsi')}
                                >
                                    <MediaUpload
                                        onSelect={onSelectImage}
                                        allowedTypes={['image']}
                                        render={({ open }) => (
                                            <Button
                                                variant="primary"
                                                onClick={open}
                                            >
                                                {__('Select Image', 'tsi')}
                                            </Button>
                                        )}
                                    />
                                </Placeholder>
                            </div>
                        )}
                    </div>
                </div>
            </>
        );
    },

    save: ({ attributes }) => {
        const { imageUrl, imageAlt, imagePosition, imageWidth, caption } = attributes;
        const blockProps = useBlockProps.save({
            className: `tsi-margin-block tsi-position-${imagePosition}`
        });

        return (
            <div {...blockProps}>
                {imageUrl && (
                    <div className="tsi-margin-content" style={{ width: `${imageWidth}px` }}>
                        <div className="tsi-margin-image">
                            <img src={imageUrl} alt={imageAlt} />
                        </div>
                        {caption && (
                            <div className="tsi-margin-caption">
                                <RichText.Content
                                    tagName="div"
                                    value={caption}
                                />
                            </div>
                        )}
                    </div>
                )}
            </div>
        );
    }
});