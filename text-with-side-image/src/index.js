import { registerBlockType } from '@wordpress/blocks';
import { RichText, MediaUpload, MediaUploadCheck, InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, SelectControl, RangeControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { image as icon } from '@wordpress/icons';
import './editor.scss';
import './style.scss';

import metadata from './block.json';

registerBlockType(metadata.name, {
    edit: ({ attributes, setAttributes }) => {
        const { content, imageUrl, imageAlt, imageId, imagePosition, imageWidth } = attributes;

        const blockProps = useBlockProps();

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
                imageId: 0
            });
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Image Settings', 'tsi')} initialOpen={!!imageUrl}>
                        <SelectControl
                            label={__('Image Position', 'tsi')}
                            value={imagePosition}
                            options={[
                                { label: __('Left side', 'tsi'), value: 'left' },
                                { label: __('Right side', 'tsi'), value: 'right' }
                            ]}
                            onChange={(value) => setAttributes({ imagePosition: value })}
                        />

                        {imageUrl && (
                            <RangeControl
                                label={__('Image Width', 'tsi')}
                                value={imageWidth}
                                onChange={(value) => setAttributes({ imageWidth: value })}
                                min={80}
                                max={300}
                                step={10}
                                help={__('Width in pixels', 'tsi')}
                            />
                        )}
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div className={`tsi-block tsi-image-${imagePosition}`}>
                        {imageUrl ? (
                            <div className="tsi-image" style={{ width: `${imageWidth}px` }}>
                                <div className="tsi-image-preview">
                                    <img src={imageUrl} alt={imageAlt} />
                                    <div className="tsi-image-actions">
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
                                                    {__('Replace', 'tsi')}
                                                </Button>
                                            )}
                                        />
                                        <Button
                                            onClick={onRemoveImage}
                                            variant="secondary"
                                            size="small"
                                            isDestructive
                                        >
                                            {__('Remove', 'tsi')}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <MediaUploadCheck>
                                <MediaUpload
                                    onSelect={onSelectImage}
                                    allowedTypes={['image']}
                                    render={({ open }) => (
                                        <div className="tsi-image-upload">
                                            <Placeholder
                                                icon={icon}
                                                label={__('Side Image', 'tsi')}
                                                instructions={__('Add an optional image that appears beside the text', 'tsi')}
                                            >
                                                <Button
                                                    variant="primary"
                                                    onClick={open}
                                                >
                                                    {__('Select Image', 'tsi')}
                                                </Button>
                                            </Placeholder>
                                        </div>
                                    )}
                                />
                            </MediaUploadCheck>
                        )}

                        <div className="tsi-content-wrapper">
                            <RichText
                                tagName="div"
                                className="tsi-content"
                                value={content}
                                onChange={(value) => setAttributes({ content: value })}
                                placeholder={__('Enter your text here... You can add formatting, links, and more.', 'tsi')}
                            />
                        </div>
                    </div>
                </div>
            </>
        );
    },

    save: ({ attributes }) => {
        const { content, imageUrl, imageAlt, imagePosition, imageWidth } = attributes;
        const blockProps = useBlockProps.save();

        return (
            <div {...blockProps}>
                <div className={`tsi-block tsi-image-${imagePosition}`}>
                    {imageUrl && (
                        <div className="tsi-image" style={{ width: `${imageWidth}px` }}>
                            <img src={imageUrl} alt={imageAlt} />
                        </div>
                    )}
                    <div className="tsi-content-wrapper">
                        <RichText.Content
                            tagName="div"
                            className="tsi-content"
                            value={content}
                        />
                    </div>
                </div>
            </div>
        );
    }
});