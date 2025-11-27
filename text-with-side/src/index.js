const { registerBlockType } = wp.blocks;
const {
	RichText,
	MediaUpload,
	InspectorControls,
	useBlockProps
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
	description: __( 'Text block that displays in the margin with optional image', 'text-with-side' ),
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

		const blockProps = useBlockProps({
			className: `text-with-side-block text-with-side-${position}`,
			'data-position': position
		});

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
							label={ __( 'Display Position', 'text-with-side' ) }
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
							label={ __( 'Image Width', 'text-with-side' ) }
							value={ width }
							onChange={ ( value ) => setAttributes( { width: value } ) }
							help={ __( 'Default: 150px', 'text-with-side' ) }
						/>
					</PanelBody>
				</InspectorControls>

				<div { ...blockProps }>
					<div className="text-with-side-inner">
						{ imageUrl && (
							<div className="text-with-side-image">
								<img
									src={ imageUrl }
									alt={ imageAlt }
									style={ { width } }
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
						{ __( 'This block will appear in the margin on the frontend', 'text-with-side' ) }
					</div>
				</div>
			</>
		);
	},
	save: ( { attributes } ) => {
		return null; // Используем render_callback из PHP
	},
} );