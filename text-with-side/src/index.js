const { registerBlockType } = wp.blocks;
const {
    RichText,
    MediaUpload,
    InspectorControls
} = wp.blockEditor;
const {
    PanelBody,
    SelectControl,
    TextControl,
    Button
} = wp.components;
const { __ } = wp.i18n;

registerBlockType( 'text-with-side/text-with-side', {
    title: __( 'Text with Side', 'text-with-side' ),
    description: __( 'Text block with side image that floats in margins on frontend', 'text-with-side' ),
    category: 'common',
    icon: 'align-left',
    attributes: {
        content: {
            type: 'string',
            default: '',
        },
        imageId: {
            type: 'number',
            default: 0,
        },
        imageUrl: {
            type: 'string',
            default: '',
        },
        imageAlt: {
            type: 'string',
            default: '',
        },
        position: {
            type: 'string',
            default: 'left',
        },
        imageLink: {
            type: 'string',
            default: 'none',
        },
        width: {
            type: 'string',
            default: '150px',
        },
    },
    edit: ( { attributes, setAttributes } ) => {
        const {
            content,
            imageUrl,
            imageAlt,
            position,
            imageLink,
            width
        } = attributes;

        const onSelectImage = ( media ) => {
            setAttributes( {
                imageId: media.id,
                imageUrl: media.url,
                imageAlt: media.alt,
            } );
        };

        const onRemoveImage = () => {
            setAttributes( {
                imageId: 0,
                imageUrl: '',
                imageAlt: '',
            } );
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title={ __( 'Block Settings', 'text-with-side' ) }>
                        <SelectControl
                            label={ __( 'Display Position on Frontend', 'text-with-side' ) }
                            value={ position }
                            options={ [
                                { label: __( 'Left', 'text-with-side' ), value: 'left' },
                                { label: __( 'Right', 'text-with-side' ), value: 'right' },
                            ] }
                            onChange={ ( value ) => setAttributes( { position: value } ) }
                        />
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
                        <TextControl
                            label={ __( 'Image Width on Frontend', 'text-with-side' ) }
                            value={ width }
                            onChange={ ( value ) => setAttributes( { width: value } ) }
                            help={ __( 'Default: 150px', 'text-with-side' ) }
                        />
                    </PanelBody>
                </InspectorControls>

                <div className={ `text-with-side-block text-with-side-${ position }` } data-position={ position }>
                    <div className="text-with-side-inner">
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
    save: ( { attributes } ) => {
        const {
            content,
            imageUrl,
            imageAlt,
            position,
            imageLink,
            width
        } = attributes;

        if ( ! content && ! imageUrl ) {
            return null;
        }

        let imageElement = null;
        if ( imageUrl ) {
            imageElement = (
                <img
                    src={ imageUrl }
                    alt={ imageAlt }
                    style={ { width } }
                />
            );

            if ( imageLink === 'media' && attributes.imageId ) {
                imageElement = (
                    <a href={ "#" } className="text-with-side-image-link">
                        { imageElement }
                    </a>
                );
            } else if ( imageLink === 'attachment' && attributes.imageId ) {
                imageElement = (
                    <a href={ "#" } className="text-with-side-image-link">
                        { imageElement }
                    </a>
                );
            } else {
                imageElement = (
                    <div className="text-with-side-image-link">
                        { imageElement }
                    </div>
                );
            }
        }

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