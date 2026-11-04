/**
 * Google reCAPTCHA v3 for the site's forms (settings: SOL Site → Spam protection, server checks: inc/recaptcha.php).
 * Google's script loads only when someone starts filling in a protected form, so pages stay fast.
 * A fresh token is fetched on every submit and sent as "sol_rc_token".
 * SOL website forms call SOLRC.token() from assets/js/sol.js; other forms (login, sign-up, payment,
 * comments) are caught here before their own submit handlers run.
 */
(function () {
	var cfg = window.SOL_RC || {};
	if (!cfg.key) { return; }
	var rules = cfg.forms || [];
	var loading = null;

	function load() {
		if (loading) { return loading; }
		loading = new Promise(function (resolve) {
			if (window.grecaptcha && window.grecaptcha.execute) { resolve(); return; }
			var s = document.createElement('script');
			s.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(cfg.key);
			s.async = true;
			s.onload = function () { resolve(); };
			s.onerror = function () { loading = null; resolve(); }; // the server explains what to do
			document.head.appendChild(s);
		});
		return loading;
	}

	function field(form) {
		var f = form.querySelector('input[name="sol_rc_token"]');
		if (!f) {
			f = document.createElement('input');
			f.type = 'hidden';
			f.name = 'sol_rc_token';
			form.appendChild(f);
		}
		return f;
	}

	/** Puts a fresh token in the form. Resolves with the token ('' if Google couldn't be used). */
	function token(form, action) {
		var f = field(form);
		f.value = '';
		action = String(action || 'submit').replace(/[^A-Za-z_\/]/g, '_');
		return load().then(function () {
			return new Promise(function (resolve) {
				var done = false;
				var finish = function (t) { if (!done) { done = true; f.value = t || ''; resolve(f.value); } };
				setTimeout(function () { finish(''); }, 10000);
				if (!window.grecaptcha || !window.grecaptcha.ready) { finish(''); return; }
				window.grecaptcha.ready(function () {
					try {
						window.grecaptcha.execute(cfg.key, { action: action }).then(finish, function () { finish(''); });
					} catch (e) { finish(''); }
				});
			});
		});
	}

	function rule(form) {
		for (var i = 0; i < rules.length; i++) {
			try { if (form.matches(rules[i].sel)) { return rules[i]; } } catch (e) { /* bad selector */ }
		}
		return null;
	}
	function protectedForm(form) {
		return !!form && form.nodeName === 'FORM' && ((cfg.sol && form.hasAttribute('data-sol-form')) || !!rule(form));
	}

	// Google's required notice, shown under each protected form instead of the floating badge.
	function note(form) {
		if (form.querySelector('.sol-rc-note')) { return; }
		var p = document.createElement('p');
		p.className = 'sol-rc-note';
		p.innerHTML = 'This site is protected by reCAPTCHA and the Google <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy Policy</a> and <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Terms of Service</a> apply.';
		var status = form.querySelector('.sol-form__status[role="status"]');
		if (status && status.parentNode === form) { form.insertBefore(p, status); } else { form.appendChild(p); }
	}

	// Start loading Google's script as soon as someone begins filling in a protected form.
	['focusin', 'pointerdown', 'touchstart'].forEach(function (type) {
		document.addEventListener(type, function (e) {
			var form = e.target && e.target.closest ? e.target.closest('form') : null;
			if (protectedForm(form)) { load(); }
		}, { passive: true, capture: true });
	});

	// Other forms: hold the submit, get a token, then submit again so the form's own handlers
	// (WooCommerce, WordPress) run as normal with the token included.
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || form.nodeName !== 'FORM' || form.hasAttribute('data-sol-form')) { return; }
		var r = rule(form);
		if (!r) { return; }
		if (form._solRcPass) { form._solRcPass = false; return; }
		e.preventDefault();
		e.stopImmediatePropagation();
		if (form._solRcBusy) { return; }
		form._solRcBusy = true;
		var submitter = e.submitter && e.submitter.form === form ? e.submitter : null;
		token(form, r.action).then(function () {
			form._solRcBusy = false;
			form._solRcPass = true;
			if (form.requestSubmit) {
				if (submitter) { form.requestSubmit(submitter); } else { form.requestSubmit(); }
				return;
			}
			if (submitter && submitter.name) { // older browsers: keep the clicked button's name, e.g. WooCommerce "login"
				var h = document.createElement('input');
				h.type = 'hidden'; h.name = submitter.name; h.value = submitter.value;
				form.appendChild(h);
			}
			HTMLFormElement.prototype.submit.call(form);
		});
	}, true);

	function init() {
		Array.prototype.forEach.call(document.forms, function (form) {
			if (protectedForm(form)) { note(form); }
		});
	}
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }

	window.SOLRC = {
		sol: !!cfg.sol,
		token: token,
		protects: protectedForm
	};
})();
