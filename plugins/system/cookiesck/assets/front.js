(function() {

	"use strict";

	var opts;

	var Cookiesck = function (options) {
		
		// check if Cookies CK has already been loaded in the html
		if (document.getElementById('cookiesck')) return;

		//set default options  
		var defaults = {
			lifetime: ''
			,layout: ''
			,reload: ''
		};

		if (!(this instanceof Cookiesck)) return new Cookiesck(options);

		opts = Object.assign(defaults, options);

		initHtml();
		initInterface();
		initEvents();
		initIframes();
	}

	function initHtml() {
		// remove the banner from the page if exists
		let banner = document.getElementById('cookiesck');
		if (banner) banner.remove();

		// create the banner in the page
		banner = document.createElement('div');
		banner.id = 'cookiesck';
		banner.setAttribute('data-layout', opts.layout);
		banner.innerHTML = createBannerHtml();
		document.body.appendChild(banner);

		// create the overlay
		let overlay = document.createElement('div');
		overlay.id = 'cookiesck_overlay';
		document.body.appendChild(overlay);

		// create the overlay
		let options = document.createElement('div');
		options.id = 'cookiesck_options';
		options.className = 'cookiesck_options';
		options.setAttribute('role', 'button');
		options.setAttribute('tabindex', '0');
		options.setAttribute('aria-label', COOKIESCK.TEXT.OPTIONS.replace(/"/g, '&quot;'));
		options.innerHTML = '<div class="inner">' + COOKIESCK.TEXT.OPTIONS + '</div>';
		document.body.appendChild(options);
	}

	function createBannerHtml() {
		let html = '<div class="inner">'
			+ '<span id="cookiesck_text">' + COOKIESCK.TEXT.INFO + '</span>'
			+ '<span id="cookiesck_buttons">'
				+ '<a role="button" tabindex="0" class="cookiesck_button" id="cookiesck_accept">' + COOKIESCK.TEXT.ACCEPT_ALL + '</a>'
				+ '<a role="button" tabindex="0" class="cookiesck_button" id="cookiesck_decline">' + COOKIESCK.TEXT.DECLINE_ALL + '</a>'
				+ '<a role="button" tabindex="0" class="cookiesck_button" id="cookiesck_settings">' + COOKIESCK.TEXT.SETTINGS + '</a>'
				+ '<div style="clear:both;"></div>'
			+ '</div>'
			
			+ '</div>';

		return html;
	}

	function initInterface() {
		let cookie = readCookie('cookiesck');
		document.getElementById('cookiesck').style.display = 'none';
		document.getElementById('cookiesck_overlay').style.display = 'none';
		// cookies not set, display the bar
		if(cookie == null) {
			document.getElementById('cookiesck').style.display = 'block';
			document.getElementById('cookiesck_overlay').style.display = 'block';
		} else {
			COOKIESCK.VALUE = decodeURIComponent(decodeURIComponent(cookie));
			debug('COOKIESCK.VALUE : ' + COOKIESCK.VALUE);
		}

		var cookieArray = toObject();
		let interfac = document.getElementById('cookiesck_interface');
		let platforms = interfac.querySelectorAll('.cookiesck-platform');
		debug('platforms :');
		debug(platforms);
		platforms.forEach(function(platform) {
			let platformName = platform.getAttribute('data-platform');
			let buttons = platform.querySelectorAll('.cookiesck_button');

			buttons.forEach(function(button) {
				button.onclick = function() {
					if (this.classList.contains('cookiesck-accept')) {
						doAction(this, 'accept');
					} else {
						doAction(this, 'decline');
					}
					interfac.querySelectorAll('.cookiesck-main-buttons .cookiesck_button').forEach(function(btn) { btn.classList.remove('cookiesck-active'); });
					highlightAllButton();
				}
			});

			// switchers events
			platform.querySelectorAll('input[id^="cookiesck-switch"]').forEach(function(button) {
				button.onchange = function() {
					if (button.checked === true) {
						button.parentNode.querySelector('.cookiesck-accept').click();
					} else {
						button.parentNode.querySelector('.cookiesck-decline').click();
					}
				}
			});
			

			// set active button
			if (cookieArray[platformName]) {
				var switcher = platform.querySelector('input[id^="cookiesck-switch"]');
				if (cookieArray[platformName] == 1) {
					platform.querySelector('.cookiesck-accept').classList.add('cookiesck-active');
					if (switcher) switcher.checked = true;
				} else {
					platform.querySelector('.cookiesck-decline').classList.add('cookiesck-active');
					if (switcher) switcher.checked = false;
				}
			}
		});

		if (COOKIESCK.VALUE === 'yes') {
			interfac.querySelectorAll('.cookiesck-accept').forEach(function(btn) { btn.classList.add('cookiesck-active'); });
		} else if (COOKIESCK.VALUE === 'no' || COOKIESCK.VALUE === 'null') {
			interfac.querySelectorAll('.cookiesck-decline').forEach(function(btn) { btn.classList.add('cookiesck-active'); });
		}

		// force essential to be active
		if (interfac.querySelector('[data-category="essential"]')) {
			interfac.querySelectorAll('[data-category="essential"] .cookiesck-accept').forEach(function(btn) { btn.classList.add('cookiesck-active'); }); 
		}

		highlightAllButton();

		// close the interface
		interfac.querySelector('.cookiesck-main-close').onclick = function() {
			updateCookieValue();
			hideInterface();
			if (opts.reload === '1') location.reload();
		}

		// add accessibility on the buttons
		const interfaceButtons = interfac.querySelectorAll('.cookiesck_button, .cookiesck-main-close, .cookiesck_button_switch');
		const cookieOptions = document.querySelectorAll('.cookiesck_options');
		const buttons = Array.from(interfaceButtons).concat(...cookieOptions);
		buttons.forEach(function(btn) {
			btn.addEventListener('keydown', function(event) {
				// presse Enter key
				if (event.which === 13) {
					event.preventDefault();
					btn.click();
				}
			});
		});

		// close on escape button
		document.addEventListener('keydown', function(event) {
			if (event.which === 27) {
				hideInterface();
			}
		});
	}

	function hideInterface() {
		let interfac = document.getElementById('cookiesck_interface');
			interfac.style.display = 'none';
			document.getElementById('cookiesck_options').style.display = 'block';
			document.getElementById('cookiesck_overlay').style.display = 'none';
		}

	function highlightAllButton() {
		let interfac = document.getElementById('cookiesck_interface');
		if (interfac.querySelectorAll('.cookiesck-category .cookiesck-accept').length === interfac.querySelectorAll('.cookiesck-category .cookiesck-accept.cookiesck-active').length) {
			interfac.querySelectorAll('.cookiesck-accept').forEach(function(btn) { btn.classList.add('cookiesck-active'); });
		} else if (interfac.querySelectorAll('.cookiesck-category .cookiesck-decline').length === interfac.querySelectorAll('.cookiesck-category .cookiesck-decline.cookiesck-active').length) {
			interfac.querySelectorAll('.cookiesck-decline').forEach(function(btn) { btn.classList.add('cookiesck-active'); });
		}
	}

	function initEvents() {
		let acceptButtons = document.querySelectorAll('#cookiesck_accept,.cookiesck-main-buttons .cookiesck-accept');
		acceptButtons.forEach(function(acceptButton) {
			acceptButton.onclick = function() {
				debug('acceptButton.onclick');
				acceptAll();
				setCookie("cookiesck","yes",COOKIESCK.LIFETIME);
				if (COOKIESCK.LOG === '1') {
					// if not unique key has been set, set it now with the session ID
					if (! readCookie('cookiesckuniquekey')) {
						setCookie('cookiesckuniquekey', COOKIESCK.UNIQUE_KEY, opts.lifetime);
					}
				}
//				doAjax(location.href, 'set_cookieck=1'); // don't do that, because it won't update consent. use updateCookieValue() instead
				updateCookieValue();
				document.getElementById('cookiesck').classList.add('cookiesck-hide');
				document.getElementById('cookiesck').style.display = 'none';
				document.getElementById('cookiesck_options').style.display = 'block';
				document.getElementById('cookiesck_overlay').style.display = 'none';
				loadIframes();
				if (opts.reload === '1' && this.id == 'cookiesck_accept') location.reload();
			}
		});
		let declineButtons = document.querySelectorAll('#cookiesck_decline, .cookiesck-main-buttons .cookiesck-decline');
		declineButtons.forEach(function(declineButton) {
			declineButton.onclick = function() {
				debug('declineButton.onclick');
				declineAll();
				// remove the cookies for all platforms
				let interfac = document.getElementById('cookiesck_interface');
				let categories = interfac.querySelectorAll('.cookiesck-category');
				categories.forEach(function(category) {
					let categoryName = category.getAttribute('data-category');
					if (categoryName !== 'Essential') {
						let platforms = category.querySelectorAll('.cookiesck-platform');
						platforms.forEach(function(platform) {
							let platformName = platform.getAttribute('data-platform');
							removeCookie(platformName);
						})
					}
				})
				

				setCookie("cookiesck","no",COOKIESCK.LIFETIME);
				if (COOKIESCK.LOG === '1') {
					// if not unique key has been set, set it now with the session ID
					if (! readCookie('cookiesckuniquekey')) {
						setCookie('cookiesckuniquekey', COOKIESCK.UNIQUE_KEY, opts.lifetime);
					}
				}
//				doAjax(location.href, 'set_cookieck=0'); // don't do that, because it won't update consent. use updateCookieValue() instead
				updateCookieValue();
				document.getElementById('cookiesck').classList.add('cookiesck-hide');
				document.getElementById('cookiesck').style.display = 'none';
				document.getElementById('cookiesck_options').style.display = 'block';
				document.getElementById('cookiesck_overlay').style.display = 'none';
			}
		});
		document.getElementById('cookiesck_settings').onclick = function(){
			document.getElementById('cookiesck_interface').style.display = 'block';
			document.getElementById('cookiesck').style.display = 'none';
		}
		// add management button to update the decision
		document.querySelectorAll('.cookiesck_options').forEach(function(cookiesck_options) {
			cookiesck_options.onclick = function(){
			document.getElementById('cookiesck_interface').style.display = 'block';
			document.getElementById('cookiesck_overlay').style.display = 'block';
			document.getElementById('cookiesck_interface').querySelector('.cookiesck-main-buttons .cookiesck-accept.cookiesck_button').focus();
		}
		});
	}

	function initIframes() {
		let iframes = document.querySelectorAll('.cookiesck-iframe-wrap');
		debug('iframes  : ');
		debug(iframes);
		iframes.forEach(function(iframe) {
			iframe.onclick = function() {
				if (! confirm(COOKIESCK.TEXT.CONFIRM_IFRAMES)) return; 
				this.outerHTML = this.outerHTML.replace('data-cookiesck-src', 'src');
				loadIframes();
				setCookie('cookiesckiframes', '1', COOKIESCK.LIFETIME);
			}
		});
	}

	function setCookie(c_name,value,exdays) {
		debug('setCookie  : {name : ' + c_name + ', value : ' + value + ', exdays : ' + exdays);
		var exdate=new Date(Date.now());
		exdate.setDate(exdate.getDate() + parseInt(exdays));
		var c_value=escape(value) + ((exdays===null) ? "" : "; expires="+exdate.toUTCString()) + "; path=/";
		document.cookie=c_name + "=" + c_value;
	}

	function readCookie(name) {
		debug('readCookie  : ' + name);
		var nameEQ = name + "=";
		var cooks = document.cookie.split(';');
		for(var i=0;i < cooks.length;i++) {
			var c = cooks[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
				if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
			}
		return null;
	}

	function removeCookie(platformName) {
		debug('removeCookie  : ' + platformName);

		var keys = getKeyFromPlatform(platformName);
		var domain = location.hostname.split('.').reverse();
		if (domain.length == 1) {
			domain = '.' + domain[0];
		} else {
			domain = '.' + domain[1] + '.' + domain[0];
		}

		for (var i=0; i < keys.length; i++) {
			debug('Platform Key to remove  : ' + keys[i]);
			let cookie = keys[i] + "=; expires=Thu, 18 Dec 2013 12:00:00 UTC; path=/";
			document.cookie = cookie;
			document.cookie = cookie + '; domain=' + domain;
		}
	}

	function getKeyFromPlatform(platformName) {
		var list = JSON.parse(COOKIESCK.LIST.replace(/\|QQ\|/g, '"'));
		var keys = new Array();
		for (const category in list) {
			for (const platform in list[category]['platforms']) {
				if (platform === platformName) {
					for (var cookie in list[category]['platforms'][platform]['cookies']) {
						keys.push(list[category]['platforms'][platform]['cookies'][cookie]['key']);
					}
				}
			}
		}

		return keys;
	}

	function toObject() {
		let list = new Object();
		let rows = COOKIESCK.VALUE.split('|ck|');
		for (var i=0; i < rows.length; i++) {
			let cols = rows[i].split('|val|');
			list[cols[0]] = cols[1];
		}
		return list;
	}

	function doAction(btn, action) {
		btn.parentNode.querySelectorAll('.cookiesck_button').forEach(function(button) {
			button.classList.remove('cookiesck-active');
		});
		btn.classList.add('cookiesck-active');
		updateCookieValue();
		// if (opts.reload === '1') location.reload();
	}

	function updateCookieValue() {
		debug('updateCookieValue');
		let interfac = document.getElementById('cookiesck_interface');
		let list = new Array();
		let platforms = interfac.querySelectorAll('.cookiesck-platform');

		platforms.forEach(function(platform) {
			let selection = platform.querySelector('.cookiesck_button.cookiesck-active');
			let name = platform.getAttribute('data-platform');
			if (selection && selection.classList.contains('cookiesck-accept')) {
				list.push(name + '|val|1');
				var state = '1';
				// trigger event
				let event = new CustomEvent('cookieckstate', { "detail": {"name": name, "state": "1"} });
				document.dispatchEvent(event);
				debug('cookieckstate  : {name : ' + name + ', state : 1');
			} else {
				list.push(name + '|val|0');
				var state = '0';
				removeCookie(name);
				// trigger event
				let event = new CustomEvent('cookieckstate', { "detail": {"name": name, "state": "0"} });
				document.dispatchEvent(event);
				debug('cookieckstate  : {name : ' + name + ', state : 0');
			}
			// google consent update
			if (name === 'cookiesckgoogleanalytics' || name === 'cookiesckgooglead') {
				consentGrantedAdStorage(name, state);
			}
		});
		COOKIESCK.VALUE = list.join('|ck|');
		setCookie('cookiesck', encodeURIComponent(COOKIESCK.VALUE), opts.lifetime);
		if (COOKIESCK.LOG === '1') {
			// if not unique key has been set, set it now with the session ID
			if (! readCookie('cookiesckuniquekey')) {
				setCookie('cookiesckuniquekey', COOKIESCK.UNIQUE_KEY, opts.lifetime);
			}
		}
		doAjax(location.href, 'set_cookieck=update&cookiesck_vars=' + encodeURIComponent(COOKIESCK.VALUE));
	}

	function acceptAll() {
		let interfac = document.getElementById('cookiesck_interface');
		interfac.querySelectorAll('.cookiesck-active').forEach(function(btn) { btn.classList.remove('cookiesck-active'); });
		interfac.querySelectorAll('.cookiesck-accept').forEach(function(btn) { btn.classList.add('cookiesck-active'); });
		interfac.querySelectorAll('input[id^="cookiesck-switch"]').forEach(function(btn) { btn.checked = 'checked'; });
		loadIframes();
		// trigger event
		let event = new CustomEvent('cookieckstate', { "detail": {"name": "all", "state": "1"} });
		document.dispatchEvent(event);
	}

	function declineAll() {
		let interfac = document.getElementById('cookiesck_interface');
		interfac.querySelectorAll('.cookiesck-active').forEach(function(btn) { btn.classList.remove('cookiesck-active'); });
		interfac.querySelectorAll('.cookiesck-decline').forEach(function(btn) { btn.classList.add('cookiesck-active'); });
		interfac.querySelectorAll('input[id^="cookiesck-switch"]').forEach(function(btn) { btn.checked = ''; });
		// force essential to be active
		if (interfac.querySelector('[data-category="essential"]')) {
			interfac.querySelectorAll('[data-category="essential"] .cookiesck-accept').forEach(function(btn) { btn.classList.add('cookiesck-active'); });
		}
		// trigger event
		let event = new CustomEvent('cookieckstate', { "detail": {"name": "all", "state": "0"} });
		document.dispatchEvent(event);
	}

	function loadIframes() {
		let iframes = document.querySelectorAll('.cookiesck-iframe-wrap');
		iframes.forEach(function(iframetoenable) {
			iframetoenable.closest('.cookiesck-iframe-wrap').className = 'cookiesck-iframe-wrap-allowed';
			iframetoenable.outerHTML = iframetoenable.outerHTML.replace('data-cookiesck-src', 'src');
		});
	}

	function doAjax(url, vars) {
		// ancien code de compatibilité, aujourd’hui inutile
		if (window.XMLHttpRequest) { // Mozilla, Safari, IE7+...
			var httpRequest = new XMLHttpRequest();
		}
		else if (window.ActiveXObject) { // IE 6 et antérieurs
			var httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
		}
		httpRequest.onreadystatechange = function() {
			// instructions de traitement de la réponse
		};
		httpRequest.open('POST', url, true);
		httpRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		httpRequest.send(vars);
	}

	function debug(str) {
		if (COOKIESCK.DEBUG === '1') {
			console.log(str);
		}
	}

	// for Google Tag Manager
	function consentGrantedAdStorage(name, state) {
		var grant = state === '1' ? 'granted' : 'denied';
		if (typeof(gtag) === 'function') {
		if (name === 'cookiesckgoogleanalytics') {
			gtag('consent', 'update', {
				'analytics_storage': grant
			});
		}
		if (name === 'cookiesckgooglead') {
			gtag('consent', 'update', {
				'ad_storage': grant,
				'ad_user_data': grant,
				'ad_personalization': grant
			});
		}
	}
	}
	window.Cookiesck = Cookiesck;
	window.ckInitCookiesckIframes = initIframes;
})();

// initialize the setter and getter for the cookies
if (! document.__defineGetter__) {
	Object.defineProperty(document, 'cookie',{
		get: function g(){ return ''; },
		set: function h(){ return true;}
	});
} else {
	var oldSetter = document.__lookupSetter__('cookie');
	var oldGetter = document.__lookupGetter__('cookie');
	if (oldSetter) {
		Object.defineProperty(document, 'cookie', {
			get: function g(){ return oldGetter.call(document); },
			set: function h(v){
				let name = v.split('=')[0];

				if (COOKIESCK.DEBUG === '1') {
					console.log('v.match(/cookiesck\=/)');
					console.log(v.match(/cookiesck\=/));
					console.log('COOKIESCK.VALUE');
					console.log(COOKIESCK.VALUE);
					console.log('COOKIESCK.ALLOWED');
					console.log(COOKIESCK.ALLOWED);
					console.log('COOKIESCK.ALLOWED.indexOf(name)');
					console.log(COOKIESCK.ALLOWED.indexOf(name));
					console.log('ckCookiesSearchNameIn(name, COOKIESCK.ALLOWED)');
					console.log(ckCookiesSearchNameIn(name, COOKIESCK.ALLOWED));
				}

				if(v.match(/cookiesck\=/) || COOKIESCK.VALUE === 'yes' || COOKIESCK.ALLOWED.indexOf(name) != -1 || ckCookiesSearchNameIn(name, COOKIESCK.ALLOWED)) {
					oldSetter.call(document, v);
					if (COOKIESCK.DEBUG === '1') {
						console.log('>> set cookie allowed :');
						console.log(name);
					}
				} else {
					let cookie = name + "=; expires=Thu, 18 Dec 2013 12:00:00 UTC; path=/;";
					oldSetter.call(document, cookie);
					if (COOKIESCK.DEBUG === '1') {
						console.log('>> set cookie blocked for :');
						console.log(name);
					}
				}
				return true;
			}
		});
	}
}

function ckCookiesSearchNameIn(name, list) {
	for (var i=0; i<list.length; i++) {
		if (name.substr(0, list[i].length) == list[i]) return true;
	}
	return false;
}

// example of how to get the event
//document.addEventListener("cookieckstate", function(e) {
//	console.log(e.detail)
//	console.log(e.detail.name);
//	console.log(e.detail.state);
//});

