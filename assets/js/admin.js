/* global fieldforgeData, wp, jQuery */
( function ( $, data ) {
	'use strict';

	var i18n = data.i18n || {};

	// -----------------------------------------------------------------------
	// Field type metadata — icons + categories for the type picker modal
	// -----------------------------------------------------------------------

	var FIELD_CATEGORIES = [
		{
			id: 'text',
			label: 'Text',
			icon: 'dashicons-editor-paragraph',
			types: [ 'text', 'textarea', 'number', 'email', 'url', 'password', 'wysiwyg' ]
		},
		{
			id: 'choice',
			label: 'Choice',
			icon: 'dashicons-list-view',
			types: [ 'select', 'checkbox', 'radio', 'true_false' ]
		},
		{
			id: 'media',
			label: 'Media',
			icon: 'dashicons-format-image',
			types: [ 'image', 'file', 'gallery' ]
		},
		{
			id: 'relation',
			label: 'Relational',
			icon: 'dashicons-randomize',
			types: [ 'post_object', 'taxonomy', 'user', 'link' ]
		},
		{
			id: 'content',
			label: 'Content',
			icon: 'dashicons-format-aside',
			types: [ 'date_picker', 'time_picker', 'color_picker', 'message', 'tab', 'accordion' ]
		},
		{
			id: 'layout',
			label: 'Layout',
			icon: 'dashicons-layout',
			types: [ 'repeater', 'flexible_content' ]
		}
	];

	var TYPE_ICONS = {
		text:             'dashicons-editor-paragraph',
		textarea:         'dashicons-text',
		number:           'dashicons-calculator',
		email:            'dashicons-email-alt',
		url:              'dashicons-admin-links',
		password:         'dashicons-lock',
		wysiwyg:          'dashicons-editor-kitchensink',
		select:           'dashicons-arrow-down-alt2',
		checkbox:         'dashicons-yes',
		radio:            'dashicons-marker',
		true_false:       'dashicons-controls-repeat',
		image:            'dashicons-format-image',
		file:             'dashicons-media-default',
		gallery:          'dashicons-format-gallery',
		post_object:      'dashicons-admin-post',
		taxonomy:         'dashicons-tag',
		user:             'dashicons-admin-users',
		link:             'dashicons-admin-links',
		date_picker:      'dashicons-calendar-alt',
		time_picker:      'dashicons-clock',
		color_picker:     'dashicons-art',
		message:          'dashicons-info',
		tab:              'dashicons-category',
		accordion:        'dashicons-arrow-down-alt2',
		repeater:         'dashicons-editor-table',
		flexible_content: 'dashicons-layout'
	};

	// -----------------------------------------------------------------------
	// Utility
	// -----------------------------------------------------------------------

	function uniqueId() {
		return 'field_' + Date.now().toString( 36 ) + Math.random().toString( 36 ).slice( 2, 7 );
	}

	function escAttr( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /"/g, '&quot;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
	}

	function slugify( str ) {
		return str.toLowerCase().replace( /[^a-z0-9]+/g, '_' ).replace( /^_|_$/g, '' );
	}

	// -----------------------------------------------------------------------
	// Toast notification system
	// -----------------------------------------------------------------------

	var ffToast = ( function () {
		var $container;

		function init() {
			if ( $container ) {
				return;
			}
			$container = $( '<div id="ff-toast-container"></div>' );
			$( 'body' ).append( $container );
		}

		function show( message, type, duration ) {
			init();
			type     = type || 'info';
			duration = duration || 3500;

			var icons = {
				success: 'dashicons-yes-alt',
				error:   'dashicons-dismiss',
				warning: 'dashicons-warning',
				info:    'dashicons-info'
			};

			var $toast = $(
				'<div class="ff-toast ff-toast--' + type + '">' +
				'<span class="ff-toast__icon dashicons ' + ( icons[ type ] || icons.info ) + '"></span>' +
				'<span class="ff-toast__msg">' + escAttr( message ) + '</span>' +
				'<button class="ff-toast__close" aria-label="Close">&times;</button>' +
				'</div>'
			);

			$container.append( $toast );

			$toast.find( '.ff-toast__close' ).on( 'click', function () {
				dismiss( $toast );
			} );

			// Auto-dismiss.
			var timer = setTimeout( function () {
				dismiss( $toast );
			}, duration );

			$toast.data( 'timer', timer );
		}

		function dismiss( $toast ) {
			clearTimeout( $toast.data( 'timer' ) );
			$toast.addClass( 'ff-toast--out' );
			setTimeout( function () {
				$toast.remove();
			}, 400 );
		}

		return { show: show };
	}() );

	window.ffToast = ffToast;

	// -----------------------------------------------------------------------
	// Type Picker Modal
	// -----------------------------------------------------------------------

	var TypePickerModal = ( function () {
		var $modal     = null;
		var $overlay   = null;
		var onSelect   = null;
		var activeCategory = 'all';

		function buildModal() {
			var typeMap = data.types || {};

			var catButtons = '<button class="ff-type-cat-btn is-active" data-cat="all">All</button>';
			FIELD_CATEGORIES.forEach( function ( cat ) {
				catButtons += '<button class="ff-type-cat-btn" data-cat="' + cat.id + '">' +
					'<span class="dashicons ' + cat.icon + '"></span>' + cat.label + '</button>';
			} );

			var typeButtons = '';
			FIELD_CATEGORIES.forEach( function ( cat ) {
				cat.types.forEach( function ( slug ) {
					var label = typeMap[ slug ] || slug;
					var icon  = TYPE_ICONS[ slug ] || 'dashicons-admin-generic';
					typeButtons +=
						'<button class="ff-type-btn" data-type="' + slug + '" data-cat="' + cat.id + '">' +
						'<span class="ff-type-btn__icon ff-type-btn__icon--' + cat.id + '">' +
						'<span class="dashicons ' + icon + '"></span></span>' +
						'<span class="ff-type-btn__label">' + escAttr( label ) + '</span>' +
						'</button>';
				} );
			} );

			$overlay = $(
				'<div class="ff-modal-overlay" role="dialog" aria-modal="true" aria-label="Choose Field Type">' +
				'<div class="ff-modal">' +
				'<div class="ff-modal__header">' +
				'<h2 class="ff-modal__title">Select Field Type</h2>' +
				'<button class="ff-modal__close" aria-label="Close">&times;</button>' +
				'</div>' +
				'<div class="ff-modal__search">' +
				'<span class="dashicons dashicons-search ff-modal__search-icon"></span>' +
				'<input type="text" class="ff-modal__search-input" placeholder="Search field types…" />' +
				'</div>' +
				'<div class="ff-modal__cats">' + catButtons + '</div>' +
				'<div class="ff-type-grid">' + typeButtons + '</div>' +
				'<div class="ff-type-grid-empty" style="display:none">No field types found.</div>' +
				'</div>' +
				'</div>'
			);

			$modal = $overlay.find( '.ff-modal' );

			$overlay.on( 'click', function ( e ) {
				if ( $( e.target ).is( '.ff-modal-overlay' ) ) {
					close();
				}
			} );

			$overlay.on( 'click', '.ff-modal__close', close );

			$overlay.on( 'click', '.ff-type-cat-btn', function () {
				$overlay.find( '.ff-type-cat-btn' ).removeClass( 'is-active' );
				$( this ).addClass( 'is-active' );
				activeCategory = $( this ).data( 'cat' );
				filterTypes( $overlay.find( '.ff-modal__search-input' ).val() );
			} );

			$overlay.on( 'input', '.ff-modal__search-input', function () {
				filterTypes( $( this ).val() );
			} );

			$overlay.on( 'click', '.ff-type-btn', function () {
				var slug  = $( this ).data( 'type' );
				var label = $( this ).find( '.ff-type-btn__label' ).text();
				if ( onSelect ) {
					onSelect( slug, label );
				}
				close();
			} );

			$( 'body' ).append( $overlay );

			// Keyboard: Escape closes.
			$( document ).on( 'keydown.ffModal', function ( e ) {
				if ( 27 === e.which ) {
					close();
				}
			} );
		}

		function filterTypes( query ) {
			var lq    = ( query || '' ).toLowerCase();
			var $btns = $overlay.find( '.ff-type-btn' );
			var vis   = 0;

			$btns.each( function () {
				var catMatch  = 'all' === activeCategory || $( this ).data( 'cat' ) === activeCategory;
				var textMatch = ! lq || $( this ).find( '.ff-type-btn__label' ).text().toLowerCase().indexOf( lq ) !== -1;
				var show      = catMatch && textMatch;
				$( this ).toggle( show );
				if ( show ) {
					vis++;
				}
			} );

			$overlay.find( '.ff-type-grid-empty' ).toggle( 0 === vis );
			$overlay.find( '.ff-type-grid' ).toggle( vis > 0 );
		}

		function open( callback ) {
			onSelect = callback;
			activeCategory = 'all';

			if ( ! $overlay ) {
				buildModal();
			}

			$overlay.find( '.ff-type-cat-btn' ).removeClass( 'is-active' ).filter( '[data-cat="all"]' ).addClass( 'is-active' );
			$overlay.find( '.ff-modal__search-input' ).val( '' );
			filterTypes( '' );
			$overlay.addClass( 'is-open' );
			$overlay.find( '.ff-modal__search-input' ).trigger( 'focus' );
		}

		function close() {
			if ( $overlay ) {
				$overlay.removeClass( 'is-open' );
			}
			$( document ).off( 'keydown.ffModal' );
		}

		return { open: open, close: close };
	}() );

	// -----------------------------------------------------------------------
	// Field Group Editor — field rows
	// -----------------------------------------------------------------------

	function buildFieldRow( index, defaults ) {
		defaults = defaults || {};
		var key   = defaults.key || uniqueId();
		var p     = 'fieldforge_fields[' + index + ']';
		var type  = defaults.type || 'text';
		var label = defaults.label || '';
		var name  = defaults.name || '';
		var cat   = getCategoryForType( type );

		var typeMap   = data.types || {};
		var typeLabel = typeMap[ type ] || type;
		var typeIcon  = TYPE_ICONS[ type ] || 'dashicons-admin-generic';

		var $row = $( [
			'<div class="fieldforge-field-row" data-index="' + index + '">',
			'  <div class="fieldforge-field-row-header">',
			'    <span class="fieldforge-drag-handle" title="Drag to reorder">',
			'      <span class="dashicons dashicons-menu"></span>',
			'    </span>',
			'    <span class="fieldforge-field-type-icon ff-type-icon--' + cat + '">',
			'      <span class="dashicons ' + typeIcon + '"></span>',
			'    </span>',
			'    <span class="fieldforge-field-label-preview">' + escAttr( label || i18n.newField || 'New Field' ) + '</span>',
			'    <span class="fieldforge-field-name-preview ff-muted">' + escAttr( name ? '(' + name + ')' : '' ) + '</span>',
			'    <span class="fieldforge-field-type-badge ff-badge--' + cat + '">' + escAttr( typeLabel ) + '</span>',
			'    <div class="fieldforge-field-row-actions">',
			'      <button type="button" class="ff-btn-icon fieldforge-duplicate-field" title="Duplicate">',
			'        <span class="dashicons dashicons-admin-page"></span>',
			'      </button>',
			'      <button type="button" class="ff-btn-icon fieldforge-toggle-field" title="Expand/Collapse">',
			'        <span class="dashicons dashicons-arrow-down-alt2"></span>',
			'      </button>',
			'      <button type="button" class="ff-btn-icon ff-btn-icon--danger fieldforge-remove-field" title="Delete">',
			'        <span class="dashicons dashicons-trash"></span>',
			'      </button>',
			'    </div>',
			'  </div>',
			'  <div class="fieldforge-field-row-body">',
			'    <input type="hidden" name="' + p + '[key]" value="' + escAttr( key ) + '" />',
			'    <div class="ff-field-settings-grid">',
			'      <div class="ff-field-setting">',
			'        <label>Field Label</label>',
			'        <input type="text" name="' + p + '[label]" value="' + escAttr( label ) + '" class="widefat fieldforge-field-label-input" />',
			'      </div>',
			'      <div class="ff-field-setting">',
			'        <label>Field Name <span class="ff-muted">(slug)</span></label>',
			'        <input type="text" name="' + p + '[name]" value="' + escAttr( name ) + '" class="widefat fieldforge-field-name-input" />',
			'        <p class="ff-field-setting-help ff-name-error" style="display:none;color:#d63638"></p>',
			'      </div>',
			'      <div class="ff-field-setting">',
			'        <label>Field Type</label>',
			'        <div class="ff-type-picker-trigger">',
			'          <span class="ff-type-picker-icon dashicons ' + typeIcon + '"></span>',
			'          <span class="ff-type-picker-label">' + escAttr( typeLabel ) + '</span>',
			'          <span class="dashicons dashicons-arrow-down-alt2"></span>',
			'          <input type="hidden" name="' + p + '[type]" value="' + escAttr( type ) + '" class="fieldforge-type-hidden" />',
			'        </div>',
			'      </div>',
			'      <div class="ff-field-setting">',
			'        <label>Instructions</label>',
			'        <textarea name="' + p + '[instructions]" rows="2" class="widefat">' + escAttr( defaults.instructions || '' ) + '</textarea>',
			'      </div>',
			'      <div class="ff-field-setting ff-field-setting--inline">',
			'        <label><input type="checkbox" name="' + p + '[required]" value="1"' + ( defaults.required ? ' checked' : '' ) + ' /> Required field</label>',
			'      </div>',
			'      <div class="ff-field-setting">',
			'        <label>Default Value</label>',
			'        <input type="text" name="' + p + '[default_value]" value="' + escAttr( defaults.default_value || '' ) + '" class="widefat" />',
			'      </div>',
			'      <div class="ff-field-setting">',
			'        <label>Placeholder</label>',
			'        <input type="text" name="' + p + '[placeholder]" value="' + escAttr( defaults.placeholder || '' ) + '" class="widefat" />',
			'      </div>',
			'    </div>',
			'    <div class="ff-conditional-logic-builder" data-prefix="' + escAttr( p ) + '">',
			'      <div class="ff-cl-toggle">',
			'        <label class="ff-toggle-label">',
			'          <input type="checkbox" class="ff-cl-enable" name="' + p + '[conditional_logic]" value="1"' + ( defaults.conditional_logic ? ' checked' : '' ) + ' />',
			'          <span class="ff-toggle-track"></span>',
			'          <span class="ff-toggle-text">Conditional Logic</span>',
			'        </label>',
			'      </div>',
			'      <div class="ff-cl-rules" style="' + ( defaults.conditional_logic ? '' : 'display:none' ) + '">',
			'        <p class="ff-cl-intro">Show this field if</p>',
			'        <div class="ff-cl-rules-list"></div>',
			'        <button type="button" class="ff-btn ff-btn--sm ff-cl-add-rule">+ Add Rule</button>',
			'      </div>',
			'    </div>',
			'  </div>',
			'</div>'
		].join( '' ) );

		// Seed existing conditional logic rules if editing.
		if ( defaults.conditional_logic_rules && defaults.conditional_logic_rules.length ) {
			var $ruleList = $row.find( '.ff-cl-rules-list' );
			defaults.conditional_logic_rules.forEach( function ( rule, ri ) {
				$ruleList.append( buildConditionalRuleRow( p, ri, rule ) );
			} );
		}

		return $row;
	}

	function getCategoryForType( type ) {
		for ( var i = 0; i < FIELD_CATEGORIES.length; i++ ) {
			if ( FIELD_CATEGORIES[ i ].types.indexOf( type ) !== -1 ) {
				return FIELD_CATEGORIES[ i ].id;
			}
		}
		return 'content';
	}

	// Open type picker when the trigger is clicked.
	$( document ).on( 'click', '.ff-type-picker-trigger', function () {
		var $trigger = $( this );
		TypePickerModal.open( function ( slug, label ) {
			var cat  = getCategoryForType( slug );
			var icon = TYPE_ICONS[ slug ] || 'dashicons-admin-generic';

			$trigger.find( '.ff-type-picker-icon' )
				.attr( 'class', 'ff-type-picker-icon dashicons ' + icon );
			$trigger.find( '.ff-type-picker-label' ).text( label );
			$trigger.find( '.fieldforge-type-hidden' ).val( slug );

			var $row = $trigger.closest( '.fieldforge-field-row' );
			$row.find( '.fieldforge-field-type-badge' )
				.text( label )
				.attr( 'class', 'fieldforge-field-type-badge ff-badge--' + cat );
			$row.find( '.fieldforge-field-type-icon' )
				.attr( 'class', 'fieldforge-field-type-icon ff-type-icon--' + cat )
				.find( '.dashicons' ).attr( 'class', 'dashicons ' + icon );
		} );
	} );

	// Add field button.
	$( document ).on( 'click', '.fieldforge-add-field', function () {
		var $list  = $( '#fieldforge-fields-list' );
		var index  = $list.find( '.fieldforge-field-row' ).length;
		var $row   = buildFieldRow( index );

		$list.find( '.fieldforge-no-fields, .fieldforge-empty-state' ).remove();
		$list.append( $row );
		$row.find( '.fieldforge-field-row-body' ).slideDown( 200 );
		$row.find( '.fieldforge-field-label-input' ).trigger( 'focus' );
		ffToast.show( 'New field added', 'success' );
	} );

	// Toggle field body visibility.
	$( document ).on( 'click', '.fieldforge-toggle-field', function () {
		var $body  = $( this ).closest( '.fieldforge-field-row' ).find( '.fieldforge-field-row-body' );
		var $icon  = $( this ).find( '.dashicons' );
		var open   = $body.is( ':hidden' );

		if ( open ) {
			$body.slideDown( 180 );
			$icon.removeClass( 'dashicons-arrow-down-alt2' ).addClass( 'dashicons-arrow-up-alt2' );
		} else {
			$body.slideUp( 180 );
			$icon.removeClass( 'dashicons-arrow-up-alt2' ).addClass( 'dashicons-arrow-down-alt2' );
		}
	} );

	// Collapse all fields by default on load — except when there's only one.
	$( function () {
		var $rows = $( '#fieldforge-fields-list .fieldforge-field-row' );
		if ( $rows.length > 1 ) {
			$rows.find( '.fieldforge-field-row-body' ).hide();
			$rows.find( '.fieldforge-toggle-field .dashicons' )
				.removeClass( 'dashicons-arrow-up-alt2' )
				.addClass( 'dashicons-arrow-down-alt2' );
		}
	} );

	// Remove a field row.
	$( document ).on( 'click', '.fieldforge-remove-field', function () {
		var $row = $( this ).closest( '.fieldforge-field-row' );
		var name = $row.find( '.fieldforge-field-label-preview' ).text();

		if ( ! window.confirm( ( i18n.confirmDelete || 'Delete field "%s"?' ).replace( '%s', name ) ) ) {
			return;
		}

		$row.slideUp( 180, function () {
			$row.remove();
			reindexFields();
			checkEmptyState();
		} );
	} );

	// Duplicate a field row.
	$( document ).on( 'click', '.fieldforge-duplicate-field', function () {
		var $row   = $( this ).closest( '.fieldforge-field-row' );
		var $list  = $( '#fieldforge-fields-list' );
		var index  = $list.find( '.fieldforge-field-row' ).length;

		// Collect current values.
		var defaults = {
			key:          uniqueId(),
			label:        $row.find( '.fieldforge-field-label-input' ).val() + ' (Copy)',
			name:         $row.find( '.fieldforge-field-name-input' ).val() + '_copy',
			type:         $row.find( '.fieldforge-type-hidden' ).val(),
			instructions: $row.find( '[name$="[instructions]"]' ).val(),
			required:     $row.find( '[name$="[required]"]' ).is( ':checked' ),
			default_value: $row.find( '[name$="[default_value]"]' ).val(),
			placeholder:  $row.find( '[name$="[placeholder]"]' ).val()
		};

		var $newRow = buildFieldRow( index, defaults );
		$list.append( $newRow );
		$newRow.find( '.fieldforge-field-row-body' ).slideDown( 200 );
		reindexFields();
		ffToast.show( 'Field duplicated', 'success' );
	} );

	// Auto-generate field name from label.
	$( document ).on( 'input', '.fieldforge-field-label-input', function () {
		var $row   = $( this ).closest( '.fieldforge-field-row' );
		var label  = $( this ).val();
		var slug   = slugify( label );

		$row.find( '.fieldforge-field-label-preview' ).text( label || i18n.newField || 'New Field' );

		var $nameInput = $row.find( '.fieldforge-field-name-input' );
		if ( ! $nameInput.data( 'manual' ) ) {
			$nameInput.val( slug );
			$row.find( '.fieldforge-field-name-preview' ).text( slug ? '(' + slug + ')' : '' );
		}
	} );

	$( document ).on( 'input', '.fieldforge-field-name-input', function () {
		var $row  = $( this ).closest( '.fieldforge-field-row' );
		var val   = $( this ).val();
		var slug  = slugify( val );

		$( this ).data( 'manual', true );

		// Live clean-up hint.
		var $err = $row.find( '.ff-name-error' );
		if ( val && val !== slug ) {
			$err.text( 'Will be saved as: ' + slug ).show();
		} else if ( hasDuplicateName( $( this ) ) ) {
			$err.text( 'This name is already used by another field.' ).show();
		} else {
			$err.hide();
		}

		$row.find( '.fieldforge-field-name-preview' ).text( slug ? '(' + slug + ')' : '' );
	} );

	function hasDuplicateName( $input ) {
		var val   = slugify( $input.val() );
		if ( ! val ) {
			return false;
		}
		var dupe  = false;
		$( '.fieldforge-field-name-input' ).not( $input ).each( function () {
			if ( slugify( $( this ).val() ) === val ) {
				dupe = true;
				return false;
			}
		} );
		return dupe;
	}

	function reindexFields() {
		$( '#fieldforge-fields-list .fieldforge-field-row' ).each( function ( i ) {
			$( this ).attr( 'data-index', i ).find( '[name]' ).each( function () {
				var n = $( this ).attr( 'name' );
				if ( n ) {
					$( this ).attr( 'name', n.replace( /fieldforge_fields\[\d+\]/, 'fieldforge_fields[' + i + ']' ) );
				}
			} );
		} );
	}

	function checkEmptyState() {
		var $list = $( '#fieldforge-fields-list' );
		if ( $list.find( '.fieldforge-field-row' ).length === 0 ) {
			$list.html(
				'<div class="fieldforge-empty-state">' +
				'<span class="dashicons dashicons-editor-table fieldforge-empty-state__icon"></span>' +
				'<p class="fieldforge-empty-state__title">No fields yet</p>' +
				'<p class="fieldforge-empty-state__desc">Click <strong>Add Field</strong> to define the first field in this group.</p>' +
				'</div>'
			);
		}
	}

	// Sortable field rows.
	if ( $.fn.sortable ) {
		$( '#fieldforge-fields-list' ).sortable( {
			handle:   '.fieldforge-drag-handle',
			items:    '.fieldforge-field-row',
			axis:     'y',
			cursor:   'grabbing',
			helper:   function ( e, $item ) {
				$item.children().each( function () {
					$( this ).width( $( this ).width() );
				} );
				return $item;
			},
			update: reindexFields
		} );
	}

	// -----------------------------------------------------------------------
	// Conditional Logic UI builder
	// -----------------------------------------------------------------------

	function buildConditionalRuleRow( prefix, ruleIndex, defaults ) {
		defaults = defaults || {};
		var p    = prefix + '[cl_rules][' + ruleIndex + ']';

		// Collect available fields in this group.
		var fieldOptions = '<option value="">— Select field —</option>';
		$( '#fieldforge-fields-list .fieldforge-field-row' ).each( function () {
			var fieldName  = $( this ).find( '.fieldforge-field-name-input' ).val();
			var fieldLabel = $( this ).find( '.fieldforge-field-label-preview' ).text();
			if ( fieldName ) {
				var sel = defaults.field === fieldName ? ' selected' : '';
				fieldOptions += '<option value="' + escAttr( fieldName ) + '"' + sel + '>' + escAttr( fieldLabel ) + '</option>';
			}
		} );

		var ops = [
			[ '==',         'is equal to' ],
			[ '!=',         'is not equal to' ],
			[ '>',          'is greater than' ],
			[ '<',          'is less than' ],
			[ '==empty',    'is empty' ],
			[ '!=empty',    'is not empty' ],
			[ '==contains', 'contains' ],
			[ '!=contains', 'does not contain' ]
		];

		var opOptions = ops.map( function ( o ) {
			var sel = defaults.operator === o[0] ? ' selected' : '';
			return '<option value="' + o[0] + '"' + sel + '>' + o[1] + '</option>';
		} ).join( '' );

		var needsValue = [ '==empty', '!=empty' ].indexOf( defaults.operator ) === -1;

		return $(
			'<div class="ff-cl-rule">' +
			'<select class="ff-cl-field-select" name="' + p + '[field]">' + fieldOptions + '</select>' +
			'<select class="ff-cl-op-select" name="' + p + '[operator]">' + opOptions + '</select>' +
			'<input type="text" class="ff-cl-value-input" name="' + p + '[value]" value="' + escAttr( defaults.value || '' ) + '" placeholder="Value"' + ( needsValue ? '' : ' style="display:none"' ) + ' />' +
			'<button type="button" class="ff-btn-icon ff-btn-icon--danger ff-cl-remove-rule" title="Remove rule"><span class="dashicons dashicons-minus"></span></button>' +
			'</div>'
		);
	}

	// Toggle conditional logic rules pane.
	$( document ).on( 'change', '.ff-cl-enable', function () {
		var $rules = $( this ).closest( '.ff-conditional-logic-builder' ).find( '.ff-cl-rules' );
		if ( $( this ).is( ':checked' ) ) {
			$rules.slideDown( 180 );
		} else {
			$rules.slideUp( 180 );
		}
	} );

	// Add conditional rule.
	$( document ).on( 'click', '.ff-cl-add-rule', function () {
		var $builder  = $( this ).closest( '.ff-conditional-logic-builder' );
		var prefix    = $builder.data( 'prefix' );
		var $ruleList = $builder.find( '.ff-cl-rules-list' );
		var ruleIndex = $ruleList.find( '.ff-cl-rule' ).length;

		$ruleList.append( buildConditionalRuleRow( prefix, ruleIndex, {} ) );
	} );

	// Remove conditional rule.
	$( document ).on( 'click', '.ff-cl-remove-rule', function () {
		$( this ).closest( '.ff-cl-rule' ).remove();
	} );

	// Toggle value input visibility based on operator.
	$( document ).on( 'change', '.ff-cl-op-select', function () {
		var op        = $( this ).val();
		var $valueInput = $( this ).closest( '.ff-cl-rule' ).find( '.ff-cl-value-input' );
		var noValue   = [ '==empty', '!=empty' ].indexOf( op ) !== -1;
		$valueInput.toggle( ! noValue );
	} );

	// -----------------------------------------------------------------------
	// Conditional logic evaluation — meta box (front-facing post edit screen)
	// -----------------------------------------------------------------------

	function evaluateConditionalLogic() {
		var logic = window.fieldforgeConditionalLogic;
		if ( ! logic ) {
			return;
		}

		$.each( logic, function ( fieldName, rules ) {
			var $field = $( '[data-field-name="' + fieldName + '"]' ).closest( '.fieldforge-meta-field' );
			if ( ! $field.length ) {
				return;
			}

			var visible = evaluateRules( rules );
			if ( visible ) {
				$field.slideDown( 150 );
			} else {
				$field.slideUp( 150 );
			}
		} );
	}

	function evaluateRules( orGroups ) {
		for ( var i = 0; i < orGroups.length; i++ ) {
			var andGroup = orGroups[ i ];
			var andMatch = true;
			for ( var j = 0; j < andGroup.length; j++ ) {
				if ( ! evaluateSingleRule( andGroup[ j ] ) ) {
					andMatch = false;
					break;
				}
			}
			if ( andMatch ) {
				return true;
			}
		}
		return false;
	}

	function evaluateSingleRule( rule ) {
		var $targetField = $( '[name="' + rule.field + '"], [name="' + rule.field + '[]"]' ).first();
		var actual       = '';

		if ( $targetField.is( ':checkbox' ) ) {
			actual = $targetField.is( ':checked' ) ? '1' : '0';
		} else if ( $targetField.is( 'select[multiple]' ) ) {
			actual = $targetField.val() ? $targetField.val().join( ',' ) : '';
		} else {
			actual = $targetField.val() || '';
		}

		var op    = rule.operator;
		var value = rule.value || '';

		switch ( op ) {
			case '==':        return String( actual ) === String( value );
			case '!=':        return String( actual ) !== String( value );
			case '>':         return parseFloat( actual ) > parseFloat( value );
			case '<':         return parseFloat( actual ) < parseFloat( value );
			case '>=':        return parseFloat( actual ) >= parseFloat( value );
			case '<=':        return parseFloat( actual ) <= parseFloat( value );
			case '==empty':   return '' === String( actual ).trim();
			case '!=empty':   return '' !== String( actual ).trim();
			case '==contains': return String( actual ).indexOf( value ) !== -1;
			case '!=contains': return String( actual ).indexOf( value ) === -1;
			default:          return true;
		}
	}

	// Re-evaluate when any meta field value changes.
	$( document ).on( 'change input', '.fieldforge-meta-field input, .fieldforge-meta-field select, .fieldforge-meta-field textarea', function () {
		evaluateConditionalLogic();
	} );

	// Initial evaluation on page load.
	$( function () {
		evaluateConditionalLogic();
	} );

	// -----------------------------------------------------------------------
	// Repeater — post edit screen
	// -----------------------------------------------------------------------

	$( document ).on( 'click', '.fieldforge-repeater-add-row', function () {
		var $repeater  = $( this ).closest( '.fieldforge-repeater' );
		var name       = $repeater.data( 'name' );
		var max        = parseInt( $repeater.data( 'max' ), 10 ) || 0;
		var $rows      = $repeater.find( '.fieldforge-repeater-rows' );
		var rowCount   = $rows.find( '.fieldforge-repeater-row' ).length;
		var $tmplEl    = $repeater.find( '.fieldforge-repeater-template' );
		var subFields  = [];

		try {
			subFields = JSON.parse( $tmplEl.text() );
		} catch ( e ) {
			return;
		}

		if ( max > 0 && rowCount >= max ) {
			ffToast.show( i18n.maxRows || 'Maximum number of rows reached.', 'warning' );
			return;
		}

		var $row = buildRepeaterRow( name, rowCount, subFields, $repeater.data( 'layout' ) || 'table' );
		$rows.find( '.fieldforge-repeater-empty' ).remove();
		$rows.append( $row );
		$row.hide().slideDown( 180 );
		initMediaButtons( $row );
		initAllPickers( $row );
	} );

	$( document ).on( 'click', '.fieldforge-repeater-row-toggle', function () {
		var $btn  = $( this );
		var $body = $btn.closest( '.fieldforge-repeater-row' ).find( '.fieldforge-repeater-row-body' );
		var $icon = $btn.find( '.dashicons' );
		if ( $body.is( ':visible' ) ) {
			$body.slideUp( 150 );
			$icon.removeClass( 'dashicons-arrow-up-alt2' ).addClass( 'dashicons-arrow-down-alt2' );
			$btn.attr( 'title', 'Expand row' );
		} else {
			$body.slideDown( 150 );
			$icon.removeClass( 'dashicons-arrow-down-alt2' ).addClass( 'dashicons-arrow-up-alt2' );
			$btn.attr( 'title', 'Collapse row' );
		}
	} );

	$( document ).on( 'click', '.fieldforge-repeater-remove-row', function () {
		var $repeater = $( this ).closest( '.fieldforge-repeater' );
		var $rows     = $repeater.find( '.fieldforge-repeater-rows' );
		var $row      = $( this ).closest( '.fieldforge-repeater-row' );
		var min       = parseInt( $repeater.data( 'min' ), 10 ) || 0;
		var rowCount  = $rows.find( '.fieldforge-repeater-row' ).length;

		if ( min > 0 && rowCount <= min ) {
			ffToast.show( i18n.minRows || 'Minimum number of rows reached.', 'warning' );
			return;
		}

		$row.slideUp( 180, function () {
			$row.remove();
			reindexRepeaterRows( $repeater );
			if ( $rows.find( '.fieldforge-repeater-row' ).length === 0 ) {
				$rows.append( '<p class="fieldforge-repeater-empty">' + ( i18n.noRows || 'No rows yet. Click "Add Row" to start.' ) + '</p>' );
			}
		} );
	} );

	$( document ).on( 'fieldforge:repeaterInit', function ( e, $repeater ) {
		if ( $.fn.sortable ) {
			$repeater.find( '.fieldforge-repeater-rows' ).sortable( {
				handle:      '.fieldforge-repeater-drag',
				items:       '.fieldforge-repeater-row',
				cursor:      'grabbing',
				placeholder: 'fieldforge-repeater-row-placeholder',
				update: function () {
					reindexRepeaterRows( $repeater );
				}
			} );
		}
	} );

	$( '.fieldforge-repeater' ).each( function () {
		$( this ).trigger( 'fieldforge:repeaterInit', [ $( this ) ] );
	} );

	function buildRepeaterRow( name, index, subFields, layout ) {
		var $row     = $( '<div class="fieldforge-repeater-row"></div>' ).attr( 'data-row', index );
		var $cells   = $( '<div class="fieldforge-repeater-row-cells"></div>' );
		var $drag    = $( '<span class="fieldforge-repeater-drag dashicons dashicons-menu" title="Drag to reorder"></span>' );
		var $del     = $( '<button type="button" class="ff-btn-icon ff-btn-icon--danger fieldforge-repeater-remove-row" title="Remove row"><span class="dashicons dashicons-trash"></span></button>' );
		var $toggle  = $( '<button type="button" class="fieldforge-repeater-row-toggle button-link" title="Collapse row"><span class="dashicons dashicons-arrow-up-alt2"></span></button>' );
		var $label   = $( '<span class="fieldforge-repeater-row-label">Row ' + ( index + 1 ) + '</span>' );
		var $header  = $( '<div class="fieldforge-repeater-row-header"></div>' ).append( $drag ).append( $label ).append( $( '<div class="fieldforge-repeater-row-actions"></div>' ).append( $toggle ).append( $del ) );
		var $body    = $( '<div class="fieldforge-repeater-row-body"></div>' );

		subFields.forEach( function ( sub ) {
			var $cell  = $( '<div class="fieldforge-repeater-cell"></div>' );
			if ( 'table' === layout ) {
				$cell.append( '<span class="fieldforge-repeater-cell-label">' + escAttr( sub.label || sub.name ) + '</span>' );
			}
			var $input = buildSubFieldInput( sub, name + '_' + index + '_' + sub.name );
			$cell.append( $input );
			$cells.append( $cell );
		} );

		$body.append( $cells );
		$row.append( $header ).append( $body );
		return $row;
	}

	function buildSubFieldInput( sub, fieldName ) {
		var type  = sub.type || 'text';
		var attrs = 'name="' + escAttr( fieldName ) + '" id="fieldforge_field_' + escAttr( fieldName ) + '"';

		switch ( type ) {
			case 'textarea':
				return $( '<textarea ' + attrs + ' class="widefat" rows="3"></textarea>' );
			case 'wysiwyg':
				return $( '<textarea ' + attrs + ' class="widefat fieldforge-wysiwyg-sub" rows="6"></textarea>' );
			case 'true_false':
				return $( '<label class="ff-toggle-label"><input type="hidden" name="' + escAttr( fieldName ) + '" value="0" /><input type="checkbox" ' + attrs + ' value="1" /><span class="ff-toggle-track"></span> ' + escAttr( sub.label || '' ) + '</label>' );
			case 'checkbox': {
				var $cbWrap = $( '<div class="fieldforge-checkbox-sub"></div>' );
				$.each( sub.choices || {}, function ( val, lbl ) {
					$cbWrap.append( $( '<label></label>' ).append(
						$( '<input type="checkbox" class="widefat" />' ).attr( 'name', fieldName + '[]' ).val( val )
					).append( ' ' + escAttr( lbl ) ) );
				} );
				return $cbWrap;
			}
			case 'radio': {
				var $rdWrap = $( '<div class="fieldforge-radio-sub"></div>' );
				$.each( sub.choices || {}, function ( val, lbl ) {
					$rdWrap.append( $( '<label></label>' ).append(
						$( '<input type="radio" />' ).attr( 'name', fieldName ).val( val )
					).append( ' ' + escAttr( lbl ) ) );
				} );
				return $rdWrap;
			}
			case 'select': {
				var $sel = $( '<select ' + attrs + ' class="widefat"></select>' );
				$.each( sub.choices || {}, function ( val, lbl ) {
					$sel.append( $( '<option></option>' ).val( val ).text( lbl ) );
				} );
				return $sel;
			}
			case 'number':
				return $( '<input type="number" ' + attrs + ' class="widefat" value="" />' );
			case 'email':
				return $( '<input type="email" ' + attrs + ' class="widefat" value="" />' );
			case 'url':
				return $( '<input type="url" ' + attrs + ' class="widefat" value="" />' );
			case 'password':
				return $( '<input type="password" ' + attrs + ' class="widefat" value="" />' );
			case 'color_picker':
				return $( '<input type="color" ' + attrs + ' class="fieldforge-color-input" value="#000000" />' );
			case 'date_picker':
				return $( '<input type="date" ' + attrs + ' class="widefat" value="" />' );
			case 'time_picker':
				return $( '<input type="time" ' + attrs + ' class="widefat" value="" />' );
			case 'image': {
				var $imgWrap = $( '<div class="fieldforge-image-field fieldforge-image-field--sub"></div>' );
				$imgWrap.append( $( '<input type="hidden" class="fieldforge-image-id" />' ).attr( 'name', fieldName ) );
				$imgWrap.append( '<img class="fieldforge-image-preview" style="display:none;max-width:80px;height:auto" alt="" />' );
				$imgWrap.append( ' <button type="button" class="button fieldforge-image-select">' + ( i18n.selectImage || 'Select Image' ) + '</button>' );
				$imgWrap.append( ' <button type="button" class="button-link-delete fieldforge-image-remove" style="display:none">Remove</button>' );
				return $imgWrap;
			}
			case 'file': {
				var $fileWrap = $( '<div class="fieldforge-file-field fieldforge-file-field--sub"></div>' );
				$fileWrap.append( $( '<input type="hidden" class="fieldforge-file-id" />' ).attr( 'name', fieldName ) );
				$fileWrap.append( '<span class="fieldforge-file-info" style="display:none"></span>' );
				$fileWrap.append( ' <button type="button" class="button fieldforge-file-select">' + ( i18n.selectFile || 'Select File' ) + '</button>' );
				$fileWrap.append( ' <button type="button" class="button-link-delete fieldforge-file-remove" style="display:none">Remove</button>' );
				return $fileWrap;
			}
			case 'post_object':
			case 'user':
			case 'taxonomy': {
				var pickerType = type === 'user' ? 'user' : ( type === 'taxonomy' ? 'taxonomy' : 'post' );
				var $picker = $(
					'<div class="fieldforge-picker" data-type="' + pickerType + '" data-multiple="0" data-field-name="' + escAttr( fieldName ) + '">' +
					'<input type="text" class="fieldforge-picker-search widefat" placeholder="Search…" autocomplete="off" />' +
					'<div class="fieldforge-picker-dropdown" style="display:none"></div>' +
					'<div class="fieldforge-picker-tags"><input type="hidden" name="' + escAttr( fieldName ) + '" value="" /></div>' +
					'</div>'
				);
				return $picker;
			}
			default:
				return $( '<input type="text" ' + attrs + ' class="widefat" value="" />' );
		}
	}

	function reindexRepeaterRows( $repeater ) {
		var name = $repeater.data( 'name' );
		var re   = new RegExp( '^' + name.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '_(\\d+)_' );
		$repeater.find( '.fieldforge-repeater-row' ).each( function ( i ) {
			$( this ).attr( 'data-row', i ).find( '[name]' ).each( function () {
				var n = $( this ).attr( 'name' );
				if ( n ) {
					$( this ).attr( 'name', n.replace( re, name + '_' + i + '_' ) );
				}
			} );
		} );
	}

	// -----------------------------------------------------------------------
	// Flexible Content — layout picker on post edit screen
	// -----------------------------------------------------------------------

	$( document ).on( 'click', '.fieldforge-fc-add-btn', function () {
		var $fc      = $( this ).closest( '.fieldforge-flexible-content' );
		var $picker  = $fc.find( '.fieldforge-fc-layout-picker' );

		// Toggle the dropdown picker.
		$picker.toggleClass( 'is-open' );
	} );

	$( document ).on( 'click', '.fieldforge-fc-pick-layout', function () {
		var $fc        = $( this ).closest( '.fieldforge-flexible-content' );
		var $rows      = $fc.find( '.fieldforge-fc-rows' );
		var name       = $fc.data( 'name' );
		var max        = parseInt( $fc.data( 'max' ), 10 ) || 0;
		var rowCount   = $rows.find( '.fieldforge-fc-row' ).length;
		var layoutName = $( this ).data( 'layout' );
		var $tmplEl    = $fc.find( '.fieldforge-fc-layouts-template' );
		var subFields  = [];

		$fc.find( '.fieldforge-fc-layout-picker' ).removeClass( 'is-open' );

		if ( max > 0 && rowCount >= max ) {
			ffToast.show( i18n.maxRows || 'Maximum rows reached.', 'warning' );
			return;
		}

		try {
			var allLayouts = JSON.parse( $tmplEl.text() );
			var matched    = null;
			allLayouts.forEach( function ( l ) {
				if ( l.name === layoutName ) {
					matched = l;
				}
			} );
			subFields = matched ? ( matched.sub_fields || [] ) : [];
		} catch ( e ) {
			return;
		}

		var index = rowCount;
		var $row  = buildFlexContentRow( name, index, layoutName, subFields );
		$rows.find( '.fieldforge-fc-empty' ).remove();
		$rows.append( $row );
		$row.hide().slideDown( 180 );
		initMediaButtons( $row );
		initAllPickers( $row );
		reindexFcRows( $fc );
	} );

	$( document ).on( 'click', '.fieldforge-fc-remove-row', function () {
		var $fc  = $( this ).closest( '.fieldforge-flexible-content' );
		var $row = $( this ).closest( '.fieldforge-fc-row' );
		var name = $( this ).closest( '.fieldforge-fc-row' ).find( '.fieldforge-fc-row-header strong' ).text();

		if ( ! window.confirm( 'Remove this "' + name + '" row?' ) ) {
			return;
		}

		$row.slideUp( 180, function () {
			$row.remove();
			reindexFcRows( $fc );
		} );
	} );

	$( document ).on( 'click', '.fieldforge-fc-toggle-row', function () {
		var $body = $( this ).closest( '.fieldforge-fc-row' ).find( '.fieldforge-fc-row-body' );
		$body.slideToggle( 150 );
	} );

	function buildFlexContentRow( name, index, layoutName, subFields ) {
		var $row  = $( '<div class="fieldforge-fc-row" data-row="' + index + '"></div>' );
		var $header = $(
			'<div class="fieldforge-fc-row-header">' +
			'<span class="fieldforge-repeater-drag dashicons dashicons-menu"></span>' +
			'<strong>' + escAttr( layoutName ) + '</strong>' +
			'<div class="fieldforge-fc-row-actions">' +
			'<button type="button" class="ff-btn-icon fieldforge-fc-toggle-row" title="Toggle"><span class="dashicons dashicons-arrow-down-alt2"></span></button>' +
			'<button type="button" class="ff-btn-icon ff-btn-icon--danger fieldforge-fc-remove-row" title="Remove"><span class="dashicons dashicons-trash"></span></button>' +
			'</div>' +
			'</div>'
		);

		var $body  = $( '<div class="fieldforge-fc-row-body"></div>' );
		var $cells = $( '<div class="fieldforge-repeater-row-cells"></div>' );

		$body.append(
			'<input type="hidden" name="' + escAttr( name ) + '_' + index + '_acf_fc_layout" value="' + escAttr( layoutName ) + '" />'
		);

		subFields.forEach( function ( sub ) {
			var $cell  = $( '<div class="fieldforge-repeater-cell"></div>' );
			$cell.append( '<span class="fieldforge-repeater-cell-label">' + escAttr( sub.label || sub.name ) + '</span>' );
			var fieldKey = name + '_' + index + '_' + sub.name;
			$cell.append( buildSubFieldInput( sub, fieldKey ) );
			$cells.append( $cell );
		} );

		$body.append( $cells );
		$row.append( $header ).append( $body );
		return $row;
	}

	function reindexFcRows( $fc ) {
		var name  = $fc.data( 'name' );
		var re    = new RegExp( '^' + name.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) + '_(\\d+)_' );
		$fc.find( '.fieldforge-fc-row' ).each( function ( i ) {
			$( this ).attr( 'data-row', i ).find( '[name]' ).each( function () {
				var n = $( this ).attr( 'name' );
				if ( n ) {
					$( this ).attr( 'name', n.replace( re, name + '_' + i + '_' ) );
				}
			} );
		} );
	}

	// Sortable flex content rows.
	$( '.fieldforge-flexible-content' ).each( function () {
		if ( $.fn.sortable ) {
			$( this ).find( '.fieldforge-fc-rows' ).sortable( {
				handle: '.fieldforge-repeater-drag',
				items:  '.fieldforge-fc-row',
				cursor: 'grabbing',
				update: function () {
					reindexFcRows( $( this ).closest( '.fieldforge-flexible-content' ) );
				}
			} );
		}
	} );

	// Close layout picker when clicking outside.
	$( document ).on( 'click', function ( e ) {
		if ( ! $( e.target ).closest( '.fieldforge-fc-add-btn, .fieldforge-fc-layout-picker' ).length ) {
			$( '.fieldforge-fc-layout-picker' ).removeClass( 'is-open' );
		}
	} );

	// -----------------------------------------------------------------------
	// Media library — Image, File, Gallery
	// -----------------------------------------------------------------------

	function initMediaButtons( $context ) {
		$context.find( '.fieldforge-image-select' ).off( 'click' ).on( 'click', function () {
			var $field   = $( this ).closest( '.fieldforge-image-field' );
			var $idInput = $field.find( '.fieldforge-image-id' );
			var $preview = $field.find( '.fieldforge-image-preview' );

			var frame = wp.media( {
				title:    i18n.selectImage || 'Select Image',
				button:   { text: i18n.useImage || 'Use Image' },
				multiple: false,
				library:  { type: 'image' }
			} );

			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$idInput.val( att.id );
				$preview.attr( 'src', att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url ).show();
				$field.find( '.fieldforge-image-remove' ).show();
			} );

			frame.open();
		} );

		$context.find( '.fieldforge-image-remove' ).off( 'click' ).on( 'click', function () {
			var $field = $( this ).closest( '.fieldforge-image-field' );
			$field.find( '.fieldforge-image-id' ).val( '' );
			$field.find( '.fieldforge-image-preview' ).hide().attr( 'src', '' );
			$( this ).hide();
		} );

		$context.find( '.fieldforge-file-select' ).off( 'click' ).on( 'click', function () {
			var $field   = $( this ).closest( '.fieldforge-file-field' );
			var $idInput = $field.find( '.fieldforge-file-id' );
			var $info    = $field.find( '.fieldforge-file-info' );

			var frame = wp.media( {
				title:    i18n.selectFile || 'Select File',
				button:   { text: i18n.useFile || 'Use File' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$idInput.val( att.id );
				$info.html( '<a href="' + escAttr( att.url ) + '" target="_blank">' + escAttr( att.filename ) + '</a>' ).show();
				$field.find( '.fieldforge-file-remove' ).show();
			} );

			frame.open();
		} );

		$context.find( '.fieldforge-file-remove' ).off( 'click' ).on( 'click', function () {
			var $field = $( this ).closest( '.fieldforge-file-field' );
			$field.find( '.fieldforge-file-id' ).val( '' );
			$field.find( '.fieldforge-file-info' ).hide().html( '' );
			$( this ).hide();
		} );

		$context.find( '.fieldforge-gallery-add' ).off( 'click' ).on( 'click', function () {
			var $field = $( this ).closest( '.fieldforge-gallery-field' );
			var name   = $field.data( 'field-name' );
			var $list  = $field.find( '.fieldforge-gallery-list' );

			var frame = wp.media( {
				title:    i18n.selectImages || 'Select Images',
				button:   { text: i18n.addToGallery || 'Add to Gallery' },
				multiple: true,
				library:  { type: 'image' }
			} );

			frame.on( 'select', function () {
				frame.state().get( 'selection' ).each( function ( attModel ) {
					var att = attModel.toJSON();
					var src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
					var $li = $( '<li></li>' ).attr( 'data-id', att.id );
					$li.append( '<img src="' + escAttr( src ) + '" width="80" height="80" alt="" />' );
					$li.append( '<input type="hidden" name="' + escAttr( name ) + '[]" value="' + att.id + '" />' );
					$li.append( '<button type="button" class="fieldforge-gallery-remove" aria-label="Remove">&times;</button>' );
					$list.append( $li );
				} );
			} );

			frame.open();
		} );

		$context.find( '.fieldforge-gallery-list' ).on( 'click', '.fieldforge-gallery-remove', function () {
			$( this ).closest( 'li' ).remove();
		} );
	}

	initMediaButtons( $( document ) );

	if ( $.fn.sortable ) {
		$( '.fieldforge-gallery-list' ).sortable( { cursor: 'grabbing' } );
	}

	// -----------------------------------------------------------------------
	// AJAX searchable picker — post_object, user, taxonomy
	// -----------------------------------------------------------------------

	function initPicker( $picker ) {
		var pickerType = $picker.data( 'type' );
		var multiple   = '1' === String( $picker.data( 'multiple' ) );
		var $search    = $picker.find( '.fieldforge-picker-search' );
		var $dropdown  = $picker.find( '.fieldforge-picker-dropdown' );
		var $tags      = $picker.find( '.fieldforge-picker-tags' );
		var searchTimer;

		$search.on( 'input', function () {
			clearTimeout( searchTimer );
			var q = $( this ).val().trim();
			searchTimer = setTimeout( function () {
				doSearch( q );
			}, 250 );
		} );

		$search.on( 'focus', function () {
			doSearch( $( this ).val().trim() );
		} );

		$search.on( 'keydown', function ( e ) {
			if ( 27 === e.which ) {
				$dropdown.hide().empty();
			}
		} );

		$( document ).on( 'click.ffPicker', function ( e ) {
			if ( ! $( e.target ).closest( $picker ).length ) {
				$dropdown.hide().empty();
			}
		} );

		$picker.on( 'click', '.fieldforge-picker-option', function () {
			var id    = $( this ).data( 'id' );
			var title = $( this ).data( 'title' );
			selectItem( id, title );
			$dropdown.hide().empty();
			$search.val( '' );
		} );

		$picker.on( 'click', '.fieldforge-picker-tag-remove', function () {
			$( this ).closest( '.fieldforge-picker-tag' ).remove();
		} );

		function getSelectedIds() {
			var ids = [];
			$tags.find( 'input[type="hidden"]' ).each( function () {
				var v = $( this ).val();
				if ( v ) {
					ids.push( String( v ) );
				}
			} );
			return ids;
		}

		function selectItem( id, title ) {
			if ( ! multiple ) {
				$tags.empty();
				$tags.append( buildTag( id, title ) );
			} else {
				if ( getSelectedIds().indexOf( String( id ) ) === -1 ) {
					$tags.append( buildTag( id, title ) );
				}
			}
		}

		function buildTag( id, title ) {
			var fieldName = $picker.data( 'field-name' ) || $tags.find( 'input[type="hidden"]' ).first().attr( 'name' ) || '';
			return $(
				'<span class="fieldforge-picker-tag">' +
				'<input type="hidden" name="' + escAttr( fieldName ) + '" value="' + escAttr( id ) + '" />' +
				escAttr( title ) +
				'<button type="button" class="fieldforge-picker-tag-remove" aria-label="Remove">&times;</button>' +
				'</span>'
			);
		}

		function doSearch( q ) {
			var ajaxAction = 'fieldforge_search_posts';
			var extraData  = {};

			if ( 'user' === pickerType ) {
				ajaxAction = 'fieldforge_search_users';
				extraData.role = $picker.data( 'role' ) || '';
			} else if ( 'taxonomy' === pickerType ) {
				ajaxAction = 'fieldforge_search_terms';
				extraData.taxonomy = $picker.data( 'taxonomy' ) || 'category';
			} else {
				extraData.post_types = $picker.data( 'post-types' ) || 'post';
			}

			$.post( data.ajaxUrl, $.extend( {
				action: ajaxAction,
				nonce:  data.nonce,
				search: q
			}, extraData ) )
			.done( function ( res ) {
				$dropdown.empty();
				if ( ! res.success || ! res.data.length ) {
					$dropdown.html( '<p class="fieldforge-picker-empty">' + ( q ? 'No results found.' : 'Type to search…' ) + '</p>' ).show();
					return;
				}
				var existing = getSelectedIds();
				res.data.forEach( function ( item ) {
					if ( ! multiple && existing.indexOf( String( item.id ) ) !== -1 ) {
						return;
					}
					var $opt = $(
						'<div class="fieldforge-picker-option" tabindex="0"></div>'
					).attr( 'data-id', item.id ).attr( 'data-title', item.title ).text( item.title );
					$dropdown.append( $opt );
				} );
				$dropdown.show();
			} );
		}
	}

	function initAllPickers( $context ) {
		$context.find( '.fieldforge-picker' ).each( function () {
			initPicker( $( this ) );
		} );
	}

	initAllPickers( $( document ) );

	// -----------------------------------------------------------------------
	// Tools page — import / export
	// -----------------------------------------------------------------------

	$( '#fieldforge-do-import' ).on( 'click', function () {
		var json    = $( '#fieldforge-import-json' ).val().trim();
		var $btn    = $( this );
		var $result = $( '#fieldforge-import-result' );

		if ( ! json ) {
			ffToast.show( 'Please paste JSON first.', 'error' );
			return;
		}

		$btn.prop( 'disabled', true ).text( 'Importing…' );

		$.post( data.ajaxUrl, {
			action: 'fieldforge_import_acf',
			nonce:  data.nonce,
			json:   json
		} )
		.done( function ( res ) {
			if ( res.success ) {
				ffToast.show( res.data.message || 'Import successful.', 'success' );
				$result.html( '<span style="color:#00a32a">&#10003; ' + escAttr( res.data.message || 'Imported.' ) + '</span>' );
			} else {
				var msg = ( res.data && res.data.message ) || 'Import failed.';
				ffToast.show( msg, 'error' );
				$result.html( '<span style="color:#d63638">&#10005; ' + escAttr( msg ) + '</span>' );
			}
		} )
		.fail( function () {
			ffToast.show( 'Request failed. Check your connection.', 'error' );
		} )
		.always( function () {
			$btn.prop( 'disabled', false ).text( 'Import' );
		} );
	} );

	$( '#fieldforge-do-export' ).on( 'click', function () {
		var id      = $( '#fieldforge-export-select' ).val();
		var $result = $( '#fieldforge-export-result' );
		var $btn    = $( this );

		$btn.prop( 'disabled', true );

		$.post( data.ajaxUrl, {
			action: 'fieldforge_export_group',
			nonce:  data.nonce,
			id:     id
		} )
		.done( function ( res ) {
			if ( res.success ) {
				$result.text( res.data.json ).show();
				ffToast.show( 'Field group exported.', 'success' );
			}
		} )
		.always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );

	// Update the single-group download link when the selector changes.
	$( '#fieldforge-export-select' ).on( 'change', function () {
		var id   = $( this ).val();
		var $dl  = $( '#fieldforge-download-one' );
		if ( $dl.length && id ) {
			var href = $dl.attr( 'href' );
			$dl.attr( 'href', href.replace( /fieldforge_dl_id=\d+/, 'fieldforge_dl_id=' + id ) );
		}
	} );

	// Copy export result to clipboard.
	$( document ).on( 'click', '#fieldforge-copy-export', function () {
		var text = $( '#fieldforge-export-result' ).text();
		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( text ).then( function () {
				ffToast.show( 'JSON copied to clipboard.', 'success' );
			} );
		}
	} );

	// -----------------------------------------------------------------------
	// Location editor
	// -----------------------------------------------------------------------

	var LOCATION_PARAMS = [
		{ value: 'post_type',    label: 'Post Type' },
		{ value: 'post_status',  label: 'Post Status' },
		{ value: 'page_template', label: 'Page Template' },
		{ value: 'page_parent',  label: 'Page Parent' },
		{ value: 'post_taxonomy', label: 'Post Taxonomy' },
		{ value: 'post_format',  label: 'Post Format' },
		{ value: 'user_role',    label: 'User Role' },
		{ value: 'current_user', label: 'Current User' },
		{ value: 'taxonomy',     label: 'Taxonomy' },
		{ value: 'attachment',   label: 'Attachment' },
		{ value: 'comment',      label: 'Comment' },
		{ value: 'options_page', label: 'Options Page' },
		{ value: 'nav_menu',     label: 'Nav Menu' }
	];

	function buildLocationRule( groupIndex, ruleIndex, defaults ) {
		defaults = defaults || {};
		var p    = 'fieldforge_location[' + groupIndex + '][' + ruleIndex + ']';

		var paramOpts = LOCATION_PARAMS.map( function ( pt ) {
			var sel = defaults.param === pt.value ? ' selected' : '';
			return '<option value="' + pt.value + '"' + sel + '>' + pt.label + '</option>';
		} ).join( '' );

		var opOpts =
			'<option value="=="' + ( '==' === defaults.operator ? ' selected' : '' ) + '>is equal to</option>' +
			'<option value="!="' + ( '!=' === defaults.operator ? ' selected' : '' ) + '>is not equal to</option>';

		// Build value select from postTypes or a text fallback.
		var valueHtml;
		if ( ! defaults.param || 'post_type' === defaults.param ) {
			var ptOpts = ( data.postTypes || [] ).map( function ( pt ) {
				var sel = defaults.value === pt.value ? ' selected' : '';
				return '<option value="' + escAttr( pt.value ) + '"' + sel + '>' + escAttr( pt.label ) + '</option>';
			} ).join( '' );
			valueHtml = '<select class="ff-location-value-select" name="' + p + '[value]">' + ptOpts + '</select>';
		} else {
			valueHtml = '<input type="text" class="ff-location-value-text" name="' + p + '[value]" value="' + escAttr( defaults.value || '' ) + '" />';
		}

		return $(
			'<div class="fieldforge-location-rule">' +
			'<select class="ff-location-param" name="' + p + '[param]">' + paramOpts + '</select>' +
			'<select name="' + p + '[operator]">' + opOpts + '</select>' +
			valueHtml +
			'<button type="button" class="ff-btn-icon ff-btn-icon--danger fieldforge-remove-rule" title="Remove rule"><span class="dashicons dashicons-minus"></span></button>' +
			'</div>'
		);
	}

	$( '#fieldforge-location-editor' )
		.on( 'click', '.fieldforge-add-rule', function () {
			var $group     = $( this ).closest( '.fieldforge-location-group' );
			var groupIndex = $group.data( 'group' );
			var $rules     = $group.find( '.fieldforge-location-rules' );
			var ruleIndex  = $rules.find( '.fieldforge-location-rule' ).length;

			$rules.append( buildLocationRule( groupIndex, ruleIndex, {} ) );
		} )
		.on( 'click', '.fieldforge-remove-rule', function () {
			$( this ).closest( '.fieldforge-location-rule' ).remove();
		} )
		.on( 'click', '.fieldforge-remove-group', function () {
			$( this ).closest( '.fieldforge-location-group, .ff-location-group-wrap' ).remove();
		} )
		.on( 'click', '.fieldforge-add-location-group', function () {
			var $editor    = $( '#fieldforge-location-editor' );
			var groupIndex = $editor.find( '.fieldforge-location-group' ).length;

			var $group = $( [
				'<div class="ff-location-group-wrap">',
				'<div class="fieldforge-location-group" data-group="' + groupIndex + '">',
				'<div class="fieldforge-location-rules"></div>',
				'<div class="ff-location-group-footer">',
				'<button type="button" class="ff-btn ff-btn--sm fieldforge-add-rule">+ and</button>',
				'<button type="button" class="ff-btn ff-btn--sm ff-btn--danger fieldforge-remove-group">Remove Group</button>',
				'</div>',
				'</div>',
				'<div class="fieldforge-location-or"><span>or</span></div>',
				'</div>'
			].join( '' ) );

			$( this ).before( $group );

			// Append first rule.
			$group.find( '.fieldforge-location-rules' ).append( buildLocationRule( groupIndex, 0, {} ) );
		} )
		.on( 'change', '.ff-location-param', function () {
			// Swap value widget based on param.
			var param   = $( this ).val();
			var $rule   = $( this ).closest( '.fieldforge-location-rule' );
			var p       = $rule.find( 'select[name$="[param]"]' ).attr( 'name' ).replace( '[param]', '[value]' );
			var $valWrap = $rule.find( '.ff-location-value-select, .ff-location-value-text' );

			if ( 'post_type' === param ) {
				var ptOpts = ( data.postTypes || [] ).map( function ( pt ) {
					return '<option value="' + escAttr( pt.value ) + '">' + escAttr( pt.label ) + '</option>';
				} ).join( '' );
				$valWrap.replaceWith( '<select class="ff-location-value-select" name="' + p + '">' + ptOpts + '</select>' );
			} else {
				$valWrap.replaceWith( '<input type="text" class="ff-location-value-text" name="' + p + '" value="" />' );
			}
		} );

	// -----------------------------------------------------------------------
	// Local JSON sync notice
	// -----------------------------------------------------------------------

	$( document ).on( 'click', '#fieldforge-sync-json', function () {
		var $btn = $( this );
		$btn.prop( 'disabled', true ).text( 'Syncing…' );

		$.post( data.ajaxUrl, {
			action: 'fieldforge_sync_json',
			nonce:  data.nonce
		} )
		.done( function ( res ) {
			if ( res.success ) {
				ffToast.show( res.data.message || 'JSON synced.', 'success' );
				$( '.fieldforge-sync-notice' ).fadeOut();
			} else {
				ffToast.show( ( res.data && res.data.message ) || 'Sync failed.', 'error' );
			}
		} )
		.fail( function () {
			ffToast.show( 'Sync request failed.', 'error' );
		} )
		.always( function () {
			$btn.prop( 'disabled', false ).text( 'Sync' );
		} );
	} );

	// -----------------------------------------------------------------------
	// Required-field validation — blocks post save if required fields are empty
	// -----------------------------------------------------------------------

	function validateRequiredFields() {
		var errors = [];

		$( '.fieldforge-meta-field .fieldforge-required' ).each( function () {
			var $wrapper = $( this );
			var label    = $wrapper.find( '.fieldforge-label' ).text().replace( '*', '' ).trim();

			// Clear previous error state.
			$wrapper.removeClass( 'fieldforge-field--error' );
			$wrapper.find( '.fieldforge-required-error' ).remove();

			var isEmpty = false;

			// Detect value by field control type.
			var $control = $wrapper.find( '.fieldforge-field-control' );
			var $inputs  = $control.find( 'input, select, textarea' ).not( '[type="hidden"]' );

			if ( $inputs.filter( 'input[type="checkbox"], input[type="radio"]' ).length ) {
				isEmpty = $inputs.filter( ':checked' ).length === 0;
			} else if ( $inputs.filter( 'select[multiple]' ).length ) {
				var vals = $inputs.filter( 'select[multiple]' ).val();
				isEmpty = ! vals || vals.length === 0;
			} else {
				var val = $inputs.first().val();
				isEmpty = ! val || '' === String( val ).trim();
			}

			if ( isEmpty ) {
				errors.push( label );
				$wrapper.addClass( 'fieldforge-field--error' );
				$wrapper.find( '.fieldforge-field-control' ).after(
					'<p class="fieldforge-required-error">' +
					escAttr( ( i18n.requiredMsg || 'Required: %s' ).replace( '%s', label ) ) +
					'</p>'
				);
			}
		} );

		return errors;
	}

	// Hook into the classic-editor publish/update buttons.
	$( '#post' ).on( 'submit', function ( e ) {
		var errors = validateRequiredFields();
		if ( errors.length ) {
			e.preventDefault();
			ffToast.show(
				( i18n.requiredFail || 'Please fill in all required fields before saving.' ),
				'error',
				6000
			);
			// Scroll to first error.
			var $first = $( '.fieldforge-field--error' ).first();
			if ( $first.length ) {
				$( 'html, body' ).animate( { scrollTop: $first.offset().top - 60 }, 300 );
			}
			return false;
		}
	} );

	// -----------------------------------------------------------------------
	// Unsaved-changes warning
	// -----------------------------------------------------------------------

	var ffDirty = false;

	$( '#post' ).on( 'change input', ':input', function () {
		ffDirty = true;
	} );

	$( '#post' ).on( 'submit', function () {
		ffDirty = false;
	} );

	$( window ).on( 'beforeunload', function () {
		if ( ffDirty ) {
			return 'You have unsaved changes. Leave anyway?';
		}
	} );

} )( jQuery, window.fieldforgeData || {} );
