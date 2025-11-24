/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import './style.css';
import './editor.css';

const { name } = metadata;

/**
 * Генерирует опции для выпадающего списка количества постов.
 * @returns {Array} Массив объектов с меткой и значением.
 */
const getNumberOfPostsOptions = () => {
	const options = [];
	for ( let i = 5; i <= 20; i++ ) {
		options.push( { label: i.toString(), value: i } );
	}
	return options;
};

/**
 * Компонент редактирования блока.
 *
 * @param {Object}   props               Свойства компонента.
 * @param {Object}   props.attributes    Атрибуты блока.
 * @param {Function} props.setAttributes Функция для обновления атрибутов.
 * @return {WPElement} Элемент React.
 */
const Edit = ( { attributes, setAttributes } ) => {
	const { numberOfPosts, year } = attributes;
	const [ availableYears, setAvailableYears ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const blockProps = useBlockProps();

	// Загружаем доступные года с помощью REST API.
	useEffect( () => {
		setIsLoading( true );
		apiFetch( { path: '/most-popular/v1/get-years' } )
			.then( ( years ) => {
				setAvailableYears( years );
				setIsLoading( false );
			} )
			.catch( () => {
				// В случае ошибки просто оставляем список пустым.
				setIsLoading( false );
			} );
	}, [] );

	// Подготавливаем опции для выбора года.
	const yearOptions = [
		{ label: __( 'Текущий год', 'most-popular' ), value: 'current' },
	];

	if ( availableYears && availableYears.length > 0 ) {
		availableYears.forEach( ( y ) => {
			yearOptions.push( { label: y, value: y } );
		} );
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Настройки блока', 'most-popular' ) }>
					<SelectControl
						label={ __( 'Количество постов', 'most-popular' ) }
						value={ numberOfPosts }
						options={ getNumberOfPostsOptions() }
						onChange={ ( val ) =>
							setAttributes( { numberOfPosts: parseInt( val, 10 ) } )
						}
					/>
					<SelectControl
						label={ __( 'Год', 'most-popular' ) }
						value={ year }
						options={ yearOptions }
						onChange={ ( val ) => setAttributes( { year: val } ) }
						help={
							isLoading
								? __(
										'Загрузка доступных годов...',
										'most-popular'
								  )
								: ''
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender block={ name } attributes={ attributes } />
		</div>
	);
};

// Регистрируем блок
registerBlockType( name, {
	...metadata,
	edit: Edit,
	// Функция save не нужна, так как используется ServerSideRender.
	// Контент будет сгенерирован на стороне сервера (PHP).
} );