(function () {
	'use strict';

	var overlay = document.getElementById('fvcp-overlay');
	if (!overlay || typeof FVCP === 'undefined') {
		return;
	}

	var COOKIE = 'fvcp_seen';
	var form = overlay.querySelector('#fvcp-form');
	var errorEl = overlay.querySelector('.fvcp-error');
	var successEl = overlay.querySelector('.fvcp-success');
	var closeBtn = overlay.querySelector('.fvcp-close');
	var submitBtn = overlay.querySelector('.fvcp-submit');
	var shownAt = 0;
	var lastFocus = null;

	function hasSeen() {
		return document.cookie.split('; ').some(function (c) {
			return c.indexOf(COOKIE + '=') === 0;
		});
	}

	function markSeen() {
		var days = FVCP.cookieDays > 0 ? FVCP.cookieDays : 30;
		var expires = new Date(Date.now() + days * 864e5).toUTCString();
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = COOKIE + '=1; expires=' + expires + '; path=/; SameSite=Lax' + secure;
	}

	function open() {
		lastFocus = document.activeElement;
		overlay.hidden = false;
		document.body.classList.add('fvcp-open');
		shownAt = Date.now();
		var first = form.querySelector('input[name="name"]');
		if (first) {
			first.focus();
		}
	}

	function close() {
		overlay.hidden = true;
		document.body.classList.remove('fvcp-open');
		if (!FVCP.preview) {
			markSeen();
		}
		if (lastFocus && lastFocus.focus) {
			lastFocus.focus();
		}
	}

	function showError(msg) {
		errorEl.textContent = msg;
		errorEl.hidden = false;
	}

	closeBtn.addEventListener('click', close);

	overlay.addEventListener('click', function (e) {
		if (e.target === overlay) {
			close();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && !overlay.hidden) {
			close();
		}
	});

	form.addEventListener('submit', function (e) {
		e.preventDefault();
		errorEl.hidden = true;

		var name = form.elements.name.value.trim();
		var email = form.elements.email.value.trim();
		if (!name || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
			showError('Please enter your name and a valid email.');
			return;
		}

		var data = new FormData(form);
		data.append('action', 'fvcp_submit');
		data.append('elapsed', String(Date.now() - shownAt));
		data.append('page', location.href);

		submitBtn.disabled = true;

		fetch(FVCP.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res && res.success) {
					form.hidden = true;
					successEl.hidden = false;
					if (!FVCP.preview) {
						markSeen();
					}
					setTimeout(close, 2500);
				} else {
					submitBtn.disabled = false;
					showError((res && res.data && res.data.message) || FVCP.error);
				}
			})
			.catch(function () {
				submitBtn.disabled = false;
				showError(FVCP.error);
			});
	});

	if (FVCP.preview || !hasSeen()) {
		setTimeout(open, Math.max(0, FVCP.delay) * 1000);
	}
})();
