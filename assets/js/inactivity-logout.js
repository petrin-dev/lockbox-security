/* global lockboxInactivity */

(function () {
	'use strict';

	const cfg = lockboxInactivity;

	let lastActivityAt  = Date.now();
	let heartbeatTimer  = null;
	let checkTimer      = null;
	let lockboxLogout   = false; // Flag: WE are the ones navigating away

	// ---------------------------------------------------------------------------
	// Suppress beforeunload warnings when we trigger the redirect.
	// Runs in capture phase so it fires before WP/Gutenberg's own handlers.
	// ---------------------------------------------------------------------------
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( lockboxLogout ) {
			delete e.returnValue;
		}
	}, true );

	// ---------------------------------------------------------------------------
	// Activity tracking — reset the clock on any user interaction
	// ---------------------------------------------------------------------------
	const activityEvents = [ 'mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click' ];

	function resetActivity() {
		lastActivityAt = Date.now();
	}

	activityEvents.forEach( function ( eventName ) {
		document.addEventListener( eventName, resetActivity, { passive: true } );
	} );

	// ---------------------------------------------------------------------------
	// Build the logout URL at the moment of logout, capturing the current page
	// so the user is returned here after logging back in.
	// Chain: logout → login page (with timeout notice) → original page
	// ---------------------------------------------------------------------------
	function buildLogoutUrl() {
		const returnTo  = window.location.href;
		const loginUrl  = cfg.loginBaseUrl + '?lockbox_timeout=1&redirect_to=' + encodeURIComponent( returnTo );
		return cfg.logoutBaseUrl + '&redirect_to=' + encodeURIComponent( loginUrl );
	}

	// ---------------------------------------------------------------------------
	// Inactivity check — runs every 10 seconds, logs out if over threshold
	// ---------------------------------------------------------------------------
	function checkInactivity() {
		const idleMs = Date.now() - lastActivityAt;

		if ( idleMs >= cfg.timeoutMs ) {
			clearTimers();
			lockboxLogout = true;
			window.location.href = buildLogoutUrl();
		}
	}

	// ---------------------------------------------------------------------------
	// Server heartbeat — keeps the WP session alive while the user is active,
	// and lets us detect if the server has already expired the session.
	// ---------------------------------------------------------------------------
	function sendHeartbeat() {
		const idleMs = Date.now() - lastActivityAt;

		// Only ping the server if the user has been active in the last heartbeat window
		if ( idleMs > cfg.heartbeatMs ) {
			return;
		}

		const body = new FormData();
		body.append( 'action', 'lockbox_heartbeat' );
		body.append( 'nonce',  cfg.nonce );

		fetch( cfg.ajaxUrl, { method: 'POST', body: body } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( ! data.success && data.data && data.data.logged_out ) {
					clearTimers();
					lockboxLogout = true;
					window.location.href = buildLogoutUrl();
				}
			} )
			.catch( function () {
				// Network error — do nothing, let the inactivity timer handle it
			} );
	}

	// ---------------------------------------------------------------------------
	// Timers
	// ---------------------------------------------------------------------------
	function clearTimers() {
		clearInterval( heartbeatTimer );
		clearInterval( checkTimer );
	}

	function startTimers() {
		// Check inactivity every 10 seconds
		checkTimer = setInterval( checkInactivity, 10000 );

		// Server heartbeat at configured interval (default 60s)
		heartbeatTimer = setInterval( sendHeartbeat, cfg.heartbeatMs );
	}

	// ---------------------------------------------------------------------------
	// Visibility API — pause checks when tab is hidden, resume when visible
	// ---------------------------------------------------------------------------
	document.addEventListener( 'visibilitychange', function () {
		if ( document.hidden ) {
			clearTimers();
		} else {
			// When tab becomes visible again, immediately check
			checkInactivity();
			startTimers();
		}
	} );

	// ---------------------------------------------------------------------------
	// Init
	// ---------------------------------------------------------------------------
	startTimers();

})();
