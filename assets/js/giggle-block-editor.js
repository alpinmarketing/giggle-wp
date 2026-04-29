/**
 * Giggle WP — Block editor script.
 *
 * Registers the giggle-wp/events block using vanilla wp.* globals.
 * No JSX / build step required — loads directly via wp_register_script().
 */
( function ( blocks, blockEditor, components, element, i18n ) {
	'use strict';

	var __ = i18n.__;
	var el = element.createElement;
	var Fragment = element.Fragment;

	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps     = blockEditor.useBlockProps;

	var PanelBody    = components.PanelBody;
	var TextControl  = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var RangeControl = components.RangeControl;
	var SelectControl = components.SelectControl;
	var Placeholder  = components.Placeholder;

	var LANGUAGE_OPTIONS = [
		{ label: __( '— All languages —', 'giggle-wp' ), value: '' },
		{ label: 'Deutsch',        value: 'de' },
		{ label: 'English',        value: 'en' },
		{ label: 'Español',        value: 'es' },
		{ label: 'Français',       value: 'fr' },
		{ label: 'Italiano',       value: 'it' },
		{ label: 'Nederlands',     value: 'nl' },
		{ label: 'Norsk bokmål',   value: 'nb' },
		{ label: 'Slovenčina',     value: 'sk' },
		{ label: 'Slovenščina',    value: 'sl' },
		{ label: 'Svenska',        value: 'sv' },
		{ label: 'Ελληνικά',       value: 'el' },
		{ label: '日本語',          value: 'ja' },
		{ label: '中文（简体）',    value: 'zh' },
	];

	blocks.registerBlockType( 'giggle-wp/events', {

		/* Attribute schema — must mirror class-giggle-block.php so the editor
		   initialises defaults without waiting for the REST /block-types response. */
		attributes: {
			streamIds:    { type: 'string',  default: '' },
			language:     { type: 'string',  default: '' },
			onlyBookable: { type: 'boolean', default: true },
			dateRange:    { type: 'string',  default: '' },
			pageSize:     { type: 'integer', default: 10 },
			title:        { type: 'string',  default: '' },
			layout:       { type: 'string',  default: 'carousel' },
		},

		/**
		 * Edit component — shown in the block editor canvas.
		 */
		edit: function ( props ) {
			var attrs   = props.attributes;
			var setAttr = props.setAttributes;

			var blockProps = useBlockProps( {
				className: 'giggle-events giggle-events--editor',
			} );

			var hasStreamIds = attrs.streamIds && attrs.streamIds.trim().length > 0;

			return el(
				Fragment,
				null,

				// Sidebar: all configuration options.
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: __( 'Stream Configuration', 'giggle-wp' ),
							initialOpen: true,
						},
						el( TextControl, {
							label: __( 'Stream ID(s)', 'giggle-wp' ),
							help: __( 'Comma-separated stream UUIDs from the Giggle CMS. At least one is required.', 'giggle-wp' ),
							value: attrs.streamIds,
							onChange: function ( val ) { setAttr( { streamIds: val } ); },
							placeholder: 'id1,id2,id3',
						} ),
						el( SelectControl, {
							label: __( 'Language', 'giggle-wp' ),
							help: __( 'ISO language code for translations. Leave empty to return all languages.', 'giggle-wp' ),
							value: attrs.language,
							options: LANGUAGE_OPTIONS,
							onChange: function ( val ) { setAttr( { language: val } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Bookable experiences only', 'giggle-wp' ),
							help: __( 'When enabled, only experiences that can be booked are shown.', 'giggle-wp' ),
							checked: attrs.onlyBookable,
							onChange: function ( val ) { setAttr( { onlyBookable: val } ); },
						} )
					),
					el(
						PanelBody,
						{
							title: __( 'Date & Pagination', 'giggle-wp' ),
							initialOpen: false,
						},
						el( TextControl, {
							label: __( 'Date Range', 'giggle-wp' ),
							help: __( 'ISO 8601 date range: 2026-06-01T00:00:00.000Z|2026-06-30T23:59:59.999Z. Leave empty for all dates.', 'giggle-wp' ),
							value: attrs.dateRange,
							onChange: function ( val ) { setAttr( { dateRange: val } ); },
							placeholder: 'YYYY-MM-DDTHH:mm:ss.sssZ|YYYY-MM-DDTHH:mm:ss.sssZ',
						} ),
						el( RangeControl, {
							label: __( 'Items per page', 'giggle-wp' ),
							help: __( 'Maximum number of experiences to display (1–50).', 'giggle-wp' ),
							value: attrs.pageSize,
							min: 1,
							max: 50,
							onChange: function ( val ) { setAttr( { pageSize: val } ); },
						} )
					),
					el(
						PanelBody,
						{
							title: __( 'Display', 'giggle-wp' ),
							initialOpen: true,
						},
						el( TextControl, {
							label: __( 'Section title', 'giggle-wp' ),
							help: __( 'Optional heading shown above the event list.', 'giggle-wp' ),
							value: attrs.title,
							onChange: function ( val ) { setAttr( { title: val } ); },
						} ),
						el( SelectControl, {
							label: __( 'Layout', 'giggle-wp' ),
							value: attrs.layout || 'carousel',
							options: [
								{ label: __( 'Carousel', 'giggle-wp' ), value: 'carousel' },
								{ label: __( 'Grid',     'giggle-wp' ), value: 'grid'     },
							],
							onChange: function ( val ) { setAttr( { layout: val } ); },
						} )
					)
				),

				// Canvas: placeholder or preview info.
				el(
					'div',
					blockProps,
					hasStreamIds
						? el(
							'div',
							{ className: 'giggle-events__editor-preview' },
							el( 'span', { className: 'giggle-events__editor-label' }, '📅 Giggle Events' ),
							attrs.title
								? el( 'h2', { className: 'giggle-events__title' }, attrs.title )
								: null,
							el(
								'p',
								{ className: 'giggle-events__editor-info' },
								__( 'Stream IDs: ', 'giggle-wp' ),
								el( 'code', null, attrs.streamIds )
							),
							attrs.language
								? el( 'p', { className: 'giggle-events__editor-info' },
									__( 'Language: ', 'giggle-wp' ),
									el( 'code', null, attrs.language )
								  )
								: null,
							el(
								'p',
								{ className: 'giggle-events__editor-info giggle-events__editor-note' },
								__( 'Live event data is fetched and rendered on the frontend.', 'giggle-wp' )
							)
						  )
						: el(
							Placeholder,
							{
								icon: 'calendar-alt',
								label: __( 'Giggle Events', 'giggle-wp' ),
								instructions: __( 'Open the block settings panel and enter at least one Stream ID to display Giggle experiences.', 'giggle-wp' ),
							}
						  )
				)
			);
		},

		/**
		 * Save returns null — rendering is done server-side.
		 */
		save: function () {
			return null;
		},
	} );

} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n
);
