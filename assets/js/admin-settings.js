/* global lockboxAdmin */

(function () {
	'use strict';

	const form      = document.getElementById( 'lockbox-settings-form' );
	const saveBtn   = form ? form.querySelector( '.lockbox-save-btn' ) : null;
	const statusEl  = form ? form.querySelector( '.lockbox-save-status' ) : null;

	// ---------------------------------------------------------------------------
	// Module toggle — show/hide sub-fields when checkbox changes
	// ---------------------------------------------------------------------------
	function initModuleToggles() {
		document.querySelectorAll( '.lockbox-module-toggle' ).forEach( function ( checkbox ) {
			checkbox.addEventListener( 'change', function () {
				const module = checkbox.closest( '.lockbox-module' );
				if ( ! module ) return;

				const fields = module.querySelector( '.lockbox-module__fields' );
				if ( ! fields ) return;

				if ( checkbox.checked ) {
					fields.classList.remove( 'is-hidden' );
					module.classList.add( 'is-enabled' );
				} else {
					fields.classList.add( 'is-hidden' );
					module.classList.remove( 'is-enabled' );
				}
			} );
		} );
	}

	// ---------------------------------------------------------------------------
	// Save settings via AJAX
	// ---------------------------------------------------------------------------
	function collectSettings() {
		const settings = {};

		document.querySelectorAll( '.lockbox-module' ).forEach( function ( moduleEl ) {
			const slug    = moduleEl.dataset.module;
			if ( ! slug ) return;

			settings[ slug ] = {};

			// Enabled toggle
			const toggle = moduleEl.querySelector( '.lockbox-module-toggle' );
			settings[ slug ].enabled = toggle ? toggle.checked : false;

			// Number inputs
			moduleEl.querySelectorAll( 'input[type="number"]' ).forEach( function ( input ) {
				const key = extractKey( input.name, slug );
				if ( key ) settings[ slug ][ key ] = input.value;
			} );

			// Textarea
			moduleEl.querySelectorAll( 'textarea' ).forEach( function ( textarea ) {
				const key = extractKey( textarea.name, slug );
				if ( key ) settings[ slug ][ key ] = textarea.value;
			} );

			// Role checkboxes (multi-value)
			const roleKeys = {};
			moduleEl.querySelectorAll( 'input[type="checkbox"]:not(.lockbox-module-toggle)' ).forEach( function ( cb ) {
				const key = extractKey( cb.name, slug, true );
				if ( ! key ) return;
				if ( ! roleKeys[ key ] ) roleKeys[ key ] = [];
				if ( cb.checked ) roleKeys[ key ].push( cb.value );
			} );
			Object.assign( settings[ slug ], roleKeys );
		} );

		return settings;
	}

	/**
	 * Extract the setting key from an input name like settings[slug][key] or settings[slug][key][]
	 */
	function extractKey( name, slug, stripArraySuffix ) {
		const pattern = new RegExp( 'settings\\[' + slug + '\\]\\[([^\\]]+)\\](\\[\\])?' );
		const match   = name.match( pattern );
		if ( ! match ) return null;
		return match[1];
	}

	function setStatus( message, isError ) {
		if ( ! statusEl ) return;
		statusEl.textContent = message;
		statusEl.classList.toggle( 'is-error', !! isError );
	}

	function initSaveForm() {
		if ( ! form ) return;

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			if ( saveBtn ) {
				saveBtn.disabled  = true;
				saveBtn.textContent = saveBtn.dataset.saving || 'Saving…';
			}
			setStatus( '' );

			const settings = collectSettings();
			const body     = new FormData();
			body.append( 'action',   'lockbox_save_settings' );
			body.append( 'nonce',    lockboxAdmin.nonce );
			body.append( 'settings', JSON.stringify( settings ) );

			fetch( lockboxAdmin.ajaxUrl, { method: 'POST', body: body } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					if ( data.success ) {
						setStatus( data.data.message || 'Settings saved.' );
					} else {
						setStatus( ( data.data && data.data.message ) || 'An error occurred.', true );
					}
				} )
				.catch( function () {
					setStatus( 'Request failed. Please try again.', true );
				} )
				.finally( function () {
					if ( saveBtn ) {
						saveBtn.disabled    = false;
						saveBtn.textContent = saveBtn.dataset.label || 'Save Settings';
					}
					// Clear status message after 4s
					setTimeout( function () { setStatus( '' ); }, 4000 );
				} );
		} );
	}

	// ---------------------------------------------------------------------------
	// Dismiss recommended plugin notices
	// ---------------------------------------------------------------------------
	function initDismissNotices() {
		document.querySelectorAll( '.lockbox-dismiss-notice' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const noticeId = btn.dataset.noticeId;
				const notice   = btn.closest( '.lockbox-notice' );

				const body = new FormData();
				body.append( 'action',    'lockbox_dismiss_notice' );
				body.append( 'nonce',     lockboxAdmin.nonce );
				body.append( 'notice_id', noticeId );

				fetch( lockboxAdmin.ajaxUrl, { method: 'POST', body: body } )
					.catch( function () {} ); // Fire-and-forget is fine here

				if ( notice ) {
					notice.style.transition = 'opacity 0.2s';
					notice.style.opacity    = '0';
					setTimeout( function () { notice.remove(); }, 250 );
				}
			} );
		} );
	}

	// ---------------------------------------------------------------------------
	// Init
	// ---------------------------------------------------------------------------
	document.addEventListener( 'DOMContentLoaded', function () {
		initModuleToggles();
		initSaveForm();
		initDismissNotices();
	} );

})();
