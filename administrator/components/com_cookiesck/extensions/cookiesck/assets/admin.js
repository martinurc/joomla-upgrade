"use strict";

var COOKIESCK_READY = false;
var COOKIESCK_LIST;

/**
 * Launched from the iframe, set the list of existing cookies
 */
function ckGetScannedCookies(list) {
	var listByCategories = new Array();

	list.forEach(function(cookie) {
		cookie = cookie.split('|CK|');
		if (! listByCategories[cookie[2]]) listByCategories[cookie[2]] = new Array();
		if (! listByCategories[cookie[2]][cookie[1]]) listByCategories[cookie[2]][cookie[1]] = new Array();
		if (! listByCategories[cookie[2]][cookie[1]]) listByCategories[cookie[2]][cookie[1]] = new Array();
		if (! listByCategories[cookie[2]][cookie[1]][cookie[0]]) listByCategories[cookie[2]][cookie[1]][cookie[0]] = cookie;
	});

	COOKIESCK_LIST = listByCategories;
}

/**
 * Launch the scan in the iframe
 */
function ckStartCookiesScan() {
	COOKIESCK_READY = false;

	var iframe = document.getElementById('cookiesckwizardiframe');
	var iframesrc = document.getElementById('scan_url').value;
	iframesrc = iframesrc ? iframesrc : iframe.getAttribute('data-uriroot');
	const iframehtml = iframe.outerHTML.replace('<div', '<iframe src="' + iframesrc + '?cookiesck=scan"').replace('</div>', '</iframe>');
	iframe.outerHTML = iframehtml;

	const wiz = document.getElementById('cookiesckwizard');
	wiz.style.display = 'block';

	const meter = document.getElementById('cookiesckmeter');
	meter.style.display = 'block';

	iframe = document.getElementById('cookiesckwizardiframe');

	var loaded = 0;

	var qty = 0;
	iframe.onload = function() {
		var result = [false, 0];
		loaded++; 
		document.querySelector('#cookiesckmeter .ckmeter > span').style.width = 20*(loaded+1) + '%';
		if (loaded < 3) {
			clearTimeout(x);
			var x = setTimeout(function(){ iframe.src = iframe.src; }, 2000);
		} else {
			COOKIESCK_READY = true;
		}
		if (COOKIESCK_READY === true) {
			result = ckManageCookiesList();
			if (result[1] > 0) qty = result[1];
		}
		if (loaded < 5) {
			clearTimeout(x);
			var x = setTimeout(function(){ iframe.src = iframe.src; }, 2000);
		} else {
			document.getElementById('cookiesckmeter').style.display = 'none';
			if (! result[0]) {
				alert('No cookie found ! Please retry or check the documentation');
			} else {
				if (qty > 0) {
					alert(qty + ' additional cookie(s) found.');
				} else {
					alert('No additional cookie found.');
				}
			}

			var nbiframes = iframe.contentWindow.document.querySelectorAll('iframe').length;
			if (nbiframes) {
				alert(nbiframes + ' iframes found. You shall activate the option to block the iframes.');
			}
		}
	};
}

/**
 * Create the list of categories and cookies
 */
function ckManageCookiesList() {
	var qty = 0;
	var wiz = document.getElementById('cookiesckwizard');

	var hasCookie = false;
	for (var category in COOKIESCK_LIST) {
		if (category == 'undefined') continue;
		if (! wiz.querySelector('.cookiescklist-category[data-category="' + category + '"]')) {
			let html = COOKIESCK_TEMPLATE_CATEGORY;
			html = html.replace(/\[CATEGORY\]/g, category);
			html = html.replace(/\[DESC\]/g, '');
			wiz.innerHTML += html;
		}
		var categoryNode = wiz.querySelector('.cookiescklist-category[data-category="' + category + '"]');

		for (var platform in COOKIESCK_LIST[category]) {
			let platformExists = wiz.querySelector('.cookiescklist-platform[data-platform="' + platform + '"]');
			if (! platformExists) {
				let html = COOKIESCK_TEMPLATE_PLATFORM;
				html = html.replace(/\[CATEGORY\]/g, category);
				html = html.replace(/\[PLATFORM\]/g, platform);
				html = html.replace(/\[DESC\]/g, '');
				html = html.replace(/\|SQ\|/g, "'");
				categoryNode.innerHTML += html;
			}
			var platformNode = wiz.querySelector('.cookiescklist-platform[data-platform="' + platform + '"]');

			for (var cookie in COOKIESCK_LIST[category][platform]) {
				let key = COOKIESCK_LIST[category][platform][cookie][3];
				let cookieExists = wiz.querySelector('.cookiescklist-item[data-key="' + key + '"]');
				if (! cookieExists) {
					let html = COOKIESCK_TEMPLATE_ITEM;
					html = html.replace(/\[CATEGORY\]/g, category);
					html = html.replace(/\[PLATFORM\]/g, platform);
					html = html.replace(/\[COOKIE\]/g, cookie);
					html = html.replace(/\[KEY\]/g, key);
					html = html.replace(/\[DESC\]/g, COOKIESCK_LIST[category][platform][cookie][5]);
					platformNode.innerHTML += html;
					qty++;
				}
				hasCookie = true;
			}
		}
	}

	return [hasCookie, qty];
}

/**
 * Read the list of the categories and cookies and store it in the plugin field
 */
function ckUpdateCookiesField() {
	// clean the editable fields
	let fields = document.querySelectorAll('.cookiesck-editable');
	fields.forEach(function(field) {
		ckCookiesSaveText(field);
	});

	var data = new Object();
	let categories = document.querySelectorAll('#cookiesckwizard .cookiescklist-category');

	categories.forEach(function(cat) {
		var categoryName = cat.getAttribute('data-category');
		data[categoryName] = new Object();
		data[categoryName]['name'] = cat.querySelector('.cookiescklist-category-name').innerHTML;
		data[categoryName]['desc'] = cat.querySelector('.cookiescklist-category-desc').innerHTML;
		data[categoryName]['platforms'] = new Object();
		let platforms = cat.querySelectorAll('.cookiescklist-platform');
		platforms.forEach(function(platform) {
			var platformName = platform.getAttribute('data-platform');
			data[categoryName]['platforms'][platformName] = new Object();
			data[categoryName]['platforms'][platformName]['name'] = platform.querySelector('.cookiescklist-platform-name').innerHTML;
			data[categoryName]['platforms'][platformName]['desc'] = platform.querySelector('.cookiescklist-platform-desc').innerHTML;
			data[categoryName]['platforms'][platformName]['legal'] = platform.querySelector('.cookiescklist-platform-legal + .cookiescklist-platform-legal select').selectedIndex;
			data[categoryName]['platforms'][platformName]['cookies'] = new Object();

			let cookies = platform.querySelectorAll('.cookiescklist-item');
			cookies.forEach(function(cookie) {
				var cookieName = cookie.getAttribute('data-item');
				data[categoryName]['platforms'][platformName]['cookies'][cookieName] = new Object();
				data[categoryName]['platforms'][platformName]['cookies'][cookieName]['id'] = cookieName;
				data[categoryName]['platforms'][platformName]['cookies'][cookieName]['key'] = cookie.querySelector('.cookiescklist-item-key').innerHTML;
				data[categoryName]['platforms'][platformName]['cookies'][cookieName]['desc'] = cookie.querySelector('.cookiescklist-item-desc').innerHTML;
			});
		});
	});

	let jsonData = JSON.stringify(data).replace(new RegExp('"', 'g'), '|QQ|');
	document.getElementById('jform_params_ckcookieswizard').value = jsonData;
}

function ckCookiesAddCategory() {
	let category = prompt('Category name ?');
	if (! category) return;
	const wiz = document.getElementById('cookiesckwizard');
	let html = ckCookiesDoReplacement(COOKIESCK_TEMPLATE_CATEGORY, category, '', '', '', '');
	wiz.innerHTML = html + wiz.innerHTML;
}

function ckCookiesAddPlatform(btn) {
	let platform = prompt('Platform name ?');
	if (! platform) return;
	let categoryParent = btn.closest('.cookiescklist-category');
	let category = categoryParent.getAttribute('data-category');
	const wiz = document.getElementById('cookiesckwizard');
	let html = ckCookiesDoReplacement(COOKIESCK_TEMPLATE_PLATFORM, category, platform, '', '', '');
	html = html.replace(/\|SQ\|/g, "'");
	categoryParent.innerHTML = categoryParent.innerHTML + html;
}

function ckCookiesAddCookie(btn) {
	let cookie = prompt('Cookie key ?');
	if (! cookie) return;
	let platformParent = btn.closest('.cookiescklist-platform');
	let category = platformParent.getAttribute('data-category');
	let platform = platformParent.getAttribute('data-platform');
	const wiz = document.getElementById('cookiesckwizard');
	let html = ckCookiesDoReplacement(COOKIESCK_TEMPLATE_ITEM, category, platform, Date.now(), cookie, '');
	platformParent.innerHTML = platformParent.innerHTML + html;
}

function ckCookiesDoReplacement(html, category, platform, cookie, key, desc) {
	html = html.replace(/\[CATEGORY\]/g, category);
	html = html.replace(/\[PLATFORM\]/g, platform);
	html = html.replace(/\[COOKIE\]/g, cookie);
	html = html.replace(/\[KEY\]/g, key);
	html = html.replace(/\[DESC\]/g, desc);

	return html;
}

function ckCookiesRemoveCategory(btn) {
	ckCookiesRemove(btn, 'category');
}

function ckCookiesRemovePlatform(btn) {
	ckCookiesRemove(btn, 'platform');
}

function ckCookiesRemoveItem(btn) {
	ckCookiesRemove(btn, 'item');
}

function ckCookiesRemove(btn, type) {
	let validate = confirm('Are you sure ?');
	if (! validate) return;
	if (type == 'category') {
		var item = btn.closest('.cookiescklist-category');
	} else if (type == 'platform') {
		var item = btn.closest('.cookiescklist-platform');
	} else if (type == 'item') {
		var item = btn.closest('.cookiescklist-item');
	}
	item.remove();
}

function ckCookiesEditCategory(btn) {
	ckCookiesEdit(btn, 'category');
}

function ckCookiesEditPlatform(btn) {
	ckCookiesEdit(btn, 'platform');
}

function ckCookiesEditItem(btn) {
	ckCookiesEdit(btn, 'item');
}

function ckCookiesEdit(btn, type) {
	if (type == 'category') {
		var item = btn.closest('.cookiescklist-category');
	} else if (type == 'platform') {
		var item = btn.closest('.cookiescklist-platform');
	} else if (type == 'item') {
		var item = btn.closest('.cookiescklist-item');
	}
	let fields = item.querySelectorAll('.cookiesck-editable');
	fields.forEach(function(field) {
		if (field.parentNode !== item) return false;
		ckCookiesEditText(field);
	});
	if (item.className == 'cookiescklist-platform' && item.parentNode.getAttribute('data-category').toLowerCase() != 'essential') {
		let field1 = item.querySelector('.cookiesck-dropdown');
		if (field1.parentNode !== item) return false;
		field1.style.display = 'none';
		var field2 = item.querySelector('.cookiesck-dropdown + .cookiesck-dropdown');
		if (field2.parentNode !== item) return false;
		field2.style.display = 'block';
		field2 = field2.querySelector('select');
		field2.value = field1.innerHTML;
		field2.dispatchEvent(new Event('liszt:updated'));
	}
	item.querySelector('.cookiescklist-save').style.display = 'inline-block';
	item.querySelector('[class*="-edit"]').style.display = 'none';
}

function ckCookiesEditText(field) {
	field.innerHTML = '<input type="text" value="' + field.innerHTML + '" />';
}

function ckCookiesSaveText(btn) {
	let item = btn.parentNode;
	let fields = item.querySelectorAll('.cookiesck-editable');
	fields.forEach(function(field) {
		if (field.parentNode !== item) return false;
		let inputfield = field.querySelector('input');
		if (! inputfield) return;
		let texte = inputfield.value;
		field.innerHTML = texte;
	});
	if (item.className == 'cookiescklist-platform' && item.parentNode.getAttribute('data-category').toLowerCase() != 'essential') {
		let field1 = item.querySelector('.cookiesck-dropdown');
		if (field1.parentNode !== item) return false;
		field1.style.display = 'block';
		var field2 = item.querySelector('.cookiesck-dropdown + .cookiesck-dropdown');
		if (field2.parentNode !== item) return false;
		field2.style.display = 'none';
		field2 = field2.querySelector('select');
		field1.innerHTML = field2.value;
	}
	item.querySelector('.cookiescklist-save').style.display = 'none';
	item.querySelector('[class*="-edit"]').style.display = 'inline-block';
}

function ckCookiesMoveUp(btn) {
	const row = btn.closest('.cookiescklist-category');
	if (row && row.previousElementSibling) {
		row.parentNode.insertBefore(row, row.previousElementSibling);
	}
}
function ckCookiesMoveDown(btn) {
	const row = btn.closest('.cookiescklist-category');
	if (row && row.nextElementSibling) {
		row.parentNode.insertBefore(row.nextElementSibling, row);
	}
}
