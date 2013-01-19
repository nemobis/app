/*global _gaq: true */
window.WikiaTracker = (function(){
	/** @private **/

	/**
	 * DO NOT ADD TO THIS LIST WITHOUT CONSULTATION FROM TRACKING TEAM LEADS
	 * Keep it in alphabetical order
	 */
	var key,
		actions = {
			// Generic add
			ADD: 'add',

			// Generic click, mostly javascript clicks
			CLICK: 'click',

			// Click on navigational button
			CLICK_LINK_BUTTON: 'click-link-button',

			// Click on image link
			CLICK_LINK_IMAGE: 'click-link-image',

			// Click on text link
			CLICK_LINK_TEXT: 'click-link-text',

			// impression of item on page/module
			IMPRESSION: 'impression',

			// Video play
			PLAY_VIDEO: 'play-video',

			// Removal
			REMOVE: 'remove',

			// Generic paginate
			PAGINATE: 'paginate',

			// Sharing view email, social network, etc
			SHARE: 'share',

			// Form submit, usually a post method
			SUBMIT: 'submit',

			// General swipe event
			SWIPE: 'swipe',

			// Action to take a survey
			TAKE_SURVEY: 'take-survey',

			// View
			VIEW: 'view'
		},
		actionsReverse = {},
		dataKeyMap = {
			action: 'ga_action',
			category: 'ga_category',
			label: 'ga_label',
			value: 'ga_value'
		},
		gaPushOrder = [ 'ga_category', 'ga_action', 'ga_label', 'ga_value' ],
		logGroup = 'WikiaTracker',
		purgeFromData = [ 'browserEvent', 'eventName', 'trackingMethod' ],
		rDoubleSlash = /\/\//g,
		slice = [].slice;

	for ( key in actions ) {
		actionsReverse[ actions[ key ] ] = key;
	}

	/**
	 * Unique entry point to track events to the internal datawarehouse and GA.
	 *
	 *     WikiaTracker.track('ga', {
	 *         category: 'myCategory',
	 *         label: 'myLabel'
	 *     });
	 *
	 * @param String trackingMethod (required)
	 *        Where to track the event to ("both", "ga", "internal").
	 *        This can optionally be passed in the data object instead.
	 * @param String eventName (required)
	 *        The name of the event. Defaults to "trackingevent".
	 *        This can optionally be passed in the data object instead.
	 *        Please speak with a tracking team lead before introducing new event names.
	 * @param Object data (optional) ... dataN (optional)
	 *        A key-value hash of parameters. If multiple hashes are passed in
	 *        with matching keys, the values in the later hashes will override
	 *        the previous values.
	 *
	 *        keys: (Please ping tracking team leads before adding new ones)
	 *            action (required for GA tracking)
	 *                One of the values in WikiaTracker.ACTIONS.
	 *            browserEvent (optional)
	 *                The browser event object that triggered this tracking call.
	 *            category (required for GA tracking)
	 *                The category for the event.
	 *            eventName (optional)
	 *                See @param String eventname
	 *            href (optional)
	 *                The href string for a link. This should be used by outbound links
	 *                to ensure tracking execution.
	 *            label (optional for GA tracking)
	 *                The label for the event.
	 *            trackingMethod (optional)
	 *                See @param String trackingMethod
	 *            value (optional for GA tracking)
	 *                The integer value for the event.
	 *
	 * @author Kyle Florence <kflorence@wikia-inc.com>
	 * @author Hyun Lim <hyun(at)wikia-inc.com>
	 * @author Federico "Lox" Lucignano <federico(at)wikia-inc.com>
	 */
	function track( trackingMethod, eventName, data /* , ..., dataN */ ) {
		var additionalData,
			browserEvent,
			gaqArgs = [],
			key,
			i,
			l,
			sliceAt = 3,
			tracking = {},
			value;

		// Args: data
		if ( typeof trackingMethod === 'object' ) {
			data = trackingMethod;
			eventName = undefined;
			trackingMethod = undefined;
			sliceAt = 1;

		// Args: trackingMethod, data
		} else if ( typeof eventName === 'object' ) {
			data = eventName;
			eventName = undefined;
			sliceAt = 2;
		}

		data = data || {};
		additionalData = slice.call( arguments, sliceAt );

		// Merge in additional data
		for ( i = 0, l = additionalData.length; i < l; i++ ) {
			extendObject( data, additionalData[ i ] );
		}

		// Remap keys for data consistency
		for ( key in dataKeyMap ) {
			if ( ( value = data[ key ] ) !== undefined ) {
				data[ dataKeyMap[ key ] ] = value;
				delete data[ key ];
			}
		}

		// Set defaults
		browserEvent = data.browserEvent || window.event;
		eventName = eventName || data.eventName || 'trackingevent';
		trackingMethod = trackingMethod || data.trackingMethod || 'none';

		tracking[ trackingMethod ] = true;
		if ( tracking.both ) {
			tracking.ga = tracking.internal = true;
		}

		// Verify parameters; category and action are compulsory for "both" and "ga"
		if ( tracking.none ||
			( tracking.ga && ( !data.ga_category || !data.ga_action || !actionsReverse[ data.ga_action ] ) )
		) {
			Wikia.log( 'Missing or invalid parameters', 'error', logGroup );
			return;
		}

		// Get rid of unnecessary data
		for ( i = 0, l = purgeFromData.length; i < l; i++ ) {
			delete data[ purgeFromData[ i ] ];
		}

		// Enqueue GA parameters in the proper order
		for ( i = 0, l = gaPushOrder.length; i < l; i++ ) {
			gaqArgs.push( data[ gaPushOrder[ i ] ] );
		}

		Wikia.log( eventName + ' ' +
			gaqArgs.join( '/' ).replace( rDoubleSlash, '/' ) +
			' [' + trackingMethod + ' track]', 'info', logGroup );

		if ( tracking.ga && window.gaTrackEvent ) {
			window.gaTrackEvent.apply( null, gaqArgs.concat( true ) );
		}

		if ( tracking.internal ) {
			internalTrack( eventName, data );
		}

		// Handle links which navigate away from the current page
		if ( data.href && ( !browserEvent || !isMiddleClick( browserEvent ) && !isCtrlLeftClick( browserEvent ) ) ) {
			if ( browserEvent && typeof browserEvent.preventDefault === 'function' ) {
				browserEvent.preventDefault();
			}

			// Delay at the end to make sure all of the above was at least invoked
			// FIXME: there must be a better way to do this that avoids using setTimeout.
			setTimeout(function() {
				document.location = data.href;
			}, 100);
		}
	}

	/**
	 * Function factory for building custom tracking methods with default parameters.
	 *
	 *     var track = WikiaTracker.buildTrackingFunction({
	 *         category: 'myCategory',
	 *         trackingMethod: 'ga'
	 *     });
	 *
	 *     track({
	 *         label: 'myLabel'
	 *     });
	 *
	 * @params Object defaults (required)
	 *         See the track method above for hash key parameter information.
	 *
	 * @author Kyle Florence <kflorence@wikia-inc.com>
	 */
	function buildTrackingFunction() {
		var args = slice.call( arguments );

		return function() {
			return track.apply( null, args.concat( slice.call( arguments ) ) );
		};
	}

	/**
	 * Detects if an action made on event target was left mouse button click with ctrl key pressed
	 *
	 * @param browserEvent
	 * @return Boolean
	 */
	function isCtrlLeftClick(browserEvent) {
		//bugId:45483
		var result = false;

		if( browserEvent && browserEvent.ctrlKey ) {
			if( browserEvent.button === 1 ) {
			//Microsoft left mouse button === 1
				result = true;
			} else if( browserEvent.button == 0 ) {
				result = true;
			}
		}

		return result;
	}

	/**
	 * Detects if an action made on event target was middle mouse button click in a webkit browser
	 *
	 * @param browserEvent
	 * @return Boolean
	 */
	function isMiddleClick(browserEvent) {
		//bugId:31900
		//only webkit fires click event on middle mouse button click
		//so, we don't care about other browsers (Microsoft has 4 assigned to middle click)
		return (browserEvent && browserEvent.button === 1) ? true : false;
	}

	/**
	 * @brief Internal Wikia tracking set up by Garth Webb
	 *
	 * @param string event Name of event
	 * @param object data Extra parameters to track
	 * @param object successCallback callback function on success (optional)
	 * @param object errorCallback callback function on failure (optional)
	 * @param integer timeout How long to wait before declaring the tracking request as failed (optional)
	 *
	 * @author Christian
	 * @author Federico "Lox" Lucignano <federico(at)wikia-inc.com>
	 */
	function internalTrack(event, data, successCallback /* unused */, errorCallback /* unused */, timeout) {
		if (!event) {
			Wikia.log('missing required argument: event', 'error', logGroup);
			return;
		}

		Wikia.log(event + ' [event name]', 'trace', logGroup);

		if(data) {
			Wikia.log(data, 'trace', logGroup);
		}

		// Set up params object - this should stay in sync with /extensions/wikia/Track/Track.php
		var params = {
			'c': wgCityId,
			'x': wgDBname,
			'a': wgArticleId,
			'lc': wgContentLanguage,
			'n': wgNamespaceNumber,
			'u': window.trackID || window.wgTrackID || 0,
			's': skin,
			'beacon': window.beacon_id || '',
			'cb': Math.floor(Math.random()*99999)
		};

		// Add data object to params object
		extendObject(params, data);
		var head = document.head || document.getElementsByTagName('head')[0] || document.documentElement,
			script = document.createElement( "script" ),
			callbackDelay = 200,
			timeout = timeout || 3000,
			requestUrl = 'http://a.wikia-beacon.com/__track/special/' + encodeURIComponent(event),
			requestParameters = [],
			p;

		for(p in params) {
			requestParameters.push(encodeURIComponent(p) + '=' + encodeURIComponent(params[p]));
		}

		requestUrl += '?' + requestParameters.join('&');

		if("async" in script) {
			script.async = "async";
		}

		script.src = requestUrl;

		script.onload = script.onreadystatechange = function(abort){
			if(abort || !script.readyState || /loaded|complete/.test(script.readyState)){

				//Handle memory leak in IE
				script.onload = script.onreadystatechange = null;

				//Remove the script
				if(head && script.parentNode)
					head.removeChild(script);

				//Dereference the script
				script = undefined;

				var callback;

				if(!abort && typeof successCallback == 'function') {
					setTimeout(successCallback, callbackDelay);
				} else if(abort && typeof errorCallback == 'function') {
					setTimeout(errorCallback, callbackDelay);
				}
			}
		};

		//Use insertBefore instead of appendChild  to circumvent an IE6 bug.
		//This arises when a base node is used (#2709 and #4378).
		head.insertBefore(script, head.firstChild);

		if(timeout > 0){
			setTimeout(function(){
					if(script)
						script.onload(true);
				},
				timeout
			);
		}
	};

	// Adds the info from the second hash into the first.
	// If the same key is in both, the key in the second object overrides what's in the first object.
	function extendObject(obj, ext){
		for(var p in ext){
			obj[p] = ext[p];
		}

		return obj;
	}

	//init
	//if there were any tracking events in the spool from before this file loaded, replay them.
	if (typeof wikiaTrackingSpool !== 'undefined') {
		for(var x = 0, y = wikiaTrackingSpool.length; x < y; x++){
			eventData = wikiaTrackingSpool[x];

			Wikia.log('Sending previously-spooled tracking event', 'trace', 'WikiaTracker');
			Wikia.log(eventData, 'trace', 'WikiaTracker');

			trackEvent.apply(this, eventData);
		}

		wikiaTrackingSpool = null;
	}

	/** @public **/

	return {
		ACTIONS: actions,
		ACTIONS_REVERSE: actionsReverse,
		buildTrackingFunction: buildTrackingFunction,
		track: track
	};
})();

// TODO refactor back into trackEvent
WikiaTracker.trackAdEvent = function(eventName, data, trackingMethod) {
	var logGroup = 'WikiaTracker',
	gaqArgs = [];

	var ga_category = data['ga_category'],
		ga_action = data['ga_action'],
		ga_label = data['ga_label'],
		ga_value = data['ga_value'];

	//GA parameters need to be enqueued in the correct order
	if(ga_category)
		gaqArgs.push(ga_category);

	if(ga_action)
		gaqArgs.push(ga_action);

	if(ga_label)
		gaqArgs.push(ga_label);

	if(ga_value)
		gaqArgs.push(ga_value);

	if(trackingMethod == 'ga' || trackingMethod == 'both') {
		Wikia.log(eventName + ' ' + gaqArgs.join('/') + ' [GA track]', 'info', logGroup);

		window.gaTrackAdEvent(ga_category, ga_action, ga_label, ga_value, true);
	}
};