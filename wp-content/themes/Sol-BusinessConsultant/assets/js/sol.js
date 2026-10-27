/* SOL SCF Site — front-end behaviour. No dependencies besides Lucide icons. */
(function () {
	'use strict';

	var $ = function (sel, root) { return (root || document).querySelector(sel); };
	var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
	var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
	var icons = function () { if (window.lucide && window.lucide.createIcons) { window.lucide.createIcons({ attrs: { 'aria-hidden': 'true' } }); } };
	var aud = function (n) { return new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD' }).format(n || 0); };
	var store = {
		get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
		set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* private mode */ } }
	};

	/* ---------- Header: mega menu + mobile drawer ---------- */
	function header() {
		var toggle = $('[data-sol-mega-toggle]');
		var mega = $('[data-sol-mega]');
		if (toggle && mega) {
			var item = toggle.parentNode;
			var open = function (yes) { mega.classList.toggle('is-open', yes); toggle.setAttribute('aria-expanded', yes ? 'true' : 'false'); };
			var timer;
			toggle.addEventListener('click', function () { open(!mega.classList.contains('is-open')); });
			item.addEventListener('mouseenter', function () { clearTimeout(timer); open(true); });
			item.addEventListener('mouseleave', function () { timer = setTimeout(function () { open(false); }, 150); });
			document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { open(false); } });
			document.addEventListener('click', function (e) { if (!item.contains(e.target)) { open(false); } });
		}
		var burger = $('[data-sol-burger]');
		var drawer = $('[data-sol-drawer]');
		if (burger && drawer) {
			burger.addEventListener('click', function () {
				var show = drawer.hidden;
				drawer.hidden = !show;
				burger.setAttribute('aria-expanded', show ? 'true' : 'false');
				burger.setAttribute('aria-label', show ? 'Close menu' : 'Open menu');
			});
			$$('a', drawer).forEach(function (a) { a.addEventListener('click', function () { drawer.hidden = true; burger.setAttribute('aria-expanded', 'false'); }); });
		}
	}

	/* ---------- Tabs ---------- */
	function tabs() {
		$$('[data-sol-tabs]').forEach(function (wrap) {
			var list = $$('[role="tab"]', wrap);
			var select = function (tab, focus) {
				list.forEach(function (t) {
					var on = t === tab;
					t.setAttribute('aria-selected', on ? 'true' : 'false');
					t.tabIndex = on ? 0 : -1;
					var panel = document.getElementById(t.getAttribute('aria-controls'));
					if (panel) { panel.hidden = !on; }
				});
				if (focus) { tab.focus(); }
			};
			list.forEach(function (t, i) {
				t.addEventListener('click', function () { select(t); });
				t.addEventListener('keydown', function (e) {
					if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
						e.preventDefault();
						select(list[(i + (e.key === 'ArrowRight' ? 1 : list.length - 1)) % list.length], true);
					}
				});
			});
		});
	}

	/* ---------- Forms (AJAX with normal POST fallback) ---------- */
	function submitForm(form, onSuccess) {
		var status = $('.sol-form__status', form);
		var data = new FormData(form);
		data.append('_sol_ajax', '1');
		form.classList.add('is-sending');
		if (status) { status.className = 'sol-form__status'; status.textContent = 'Sending…'; }
		return fetch(form.getAttribute('action'), { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json().catch(function () { return { success: false, data: { message: 'Something went wrong. Please try again.' } }; }); })
			.then(function (res) {
				form.classList.remove('is-sending');
				if (res && res.success) {
					var msg = form.getAttribute('data-success') || (res.data && res.data.message) || (window.SOL && SOL.success);
					if (status) { status.className = 'sol-form__status is-success'; status.textContent = msg; }
					form.reset();
					if (onSuccess) { onSuccess(); }
				} else if (status) {
					status.className = 'sol-form__status is-error';
					status.textContent = (res && res.data && res.data.message) || 'Please check the form and try again.';
				}
			})
			.catch(function () {
				form.classList.remove('is-sending');
				if (status) { status.className = 'sol-form__status is-error'; status.textContent = 'Network error. Please try again or call us.'; }
			});
	}
	function forms() {
		$$('form[data-sol-form]').forEach(function (form) {
			if (form.closest('[data-sol-quiz]')) { return; } // handled by the quiz
			form.addEventListener('submit', function (e) {
				if (!window.fetch || !window.FormData) { return; }
				e.preventDefault();
				submitForm(form);
			});
		});
	}

	/* ---------- Quiz / assessment ---------- */
	function quizzes() {
		$$('[data-sol-quiz]').forEach(function (root) {
			var cfgEl = $('[data-sol-quiz-config]', root);
			if (!cfgEl) { return; }
			var cfg = JSON.parse(cfgEl.textContent || '{}');
			var qs = cfg.questions || [];
			var answers = [];
			var step = 0;
			var show = function (name) {
				$$('[data-step]', root).forEach(function (el) { el.hidden = el.getAttribute('data-step') !== name; });
			};
			var maxScore = function (q) { return q.options.reduce(function (m, o) { return Math.max(m, o.score || 0); }, 0); };

			var renderQ = function () {
				var q = qs[step];
				$('[data-sol-quiz-count]', root).textContent = 'Question ' + (step + 1) + ' of ' + qs.length + ' · ' + Math.round((step / qs.length) * 100) + '% complete';
				$('[data-sol-quiz-bar]', root).style.width = ((step / qs.length) * 100) + '%';
				$('[data-sol-quiz-group]', root).textContent = q.group || '';
				var h = $('[data-sol-quiz-q]', root);
				h.textContent = q.question;
				var box = $('[data-sol-quiz-options]', root);
				box.innerHTML = '';
				q.options.forEach(function (o, i) {
					var b = document.createElement('button');
					b.type = 'button';
					b.className = 'sol-quiz__option' + (answers[step] === i ? ' is-selected' : '');
					b.textContent = o.label;
					b.addEventListener('click', function () {
						answers[step] = i;
						if (step < qs.length - 1) { step++; renderQ(); }
						else if (cfg.lead) { show('lead'); }
						else { result(); }
					});
					box.appendChild(b);
				});
				$('[data-sol-quiz-back]', root).hidden = step === 0;
				show('question');
				h.focus({ preventScroll: true });
			};

			var compute = function () {
				var total = 0, max = 0, domains = {};
				qs.forEach(function (q, i) {
					var o = q.options[answers[i]] || { score: 0 };
					var m = maxScore(q);
					total += o.score || 0; max += m;
					if (q.group) {
						domains[q.group] = domains[q.group] || { score: 0, max: 0 };
						domains[q.group].score += o.score || 0; domains[q.group].max += m;
					}
				});
				var pct = max ? (total / max) * 100 : 0;
				var res;
				if (cfg.mode === 'recommend') {
					var first = qs[0] && qs[0].options[answers[0]];
					var key = first ? first.value : '';
					res = (cfg.results || []).filter(function (r) { return r.key === key; })[0] || (cfg.results || [])[0];
				} else {
					var sorted = (cfg.results || []).slice().sort(function (a, b) { return b.min - a.min; });
					res = sorted.filter(function (r) { return pct >= r.min; })[0] || sorted[sorted.length - 1];
				}
				return { total: total, max: max, pct: pct, res: res || {}, domains: domains };
			};

			var summary = function (c) {
				var lines = [(cfg.title || 'Quiz') + ': ' + (c.res.title || '')];
				if (cfg.mode !== 'recommend') { lines.push('Score: ' + c.total + ' / ' + c.max + ' (' + Math.round(c.pct) + '%)'); }
				qs.forEach(function (q, i) { var o = q.options[answers[i]]; lines.push('- ' + q.question + ' → ' + (o ? o.label : '')); });
				return lines.join('\n');
			};

			var result = function () {
				var c = compute();
				$('[data-sol-result-sub]', root).textContent = c.res.subtitle || '';
				var t = $('[data-sol-result-title]', root);
				t.textContent = c.res.title || '';
				$('[data-sol-result-desc]', root).textContent = c.res.description || '';
				$('[data-sol-result-score]', root).textContent = cfg.mode === 'recommend' ? '' : 'Your readiness score: ' + c.total + ' / ' + c.max + ' (' + Math.round(c.pct) + '%)';
				$('[data-sol-result-score]', root).hidden = cfg.mode === 'recommend';
				var bd = $('[data-sol-result-breakdown]', root);
				bd.innerHTML = '';
				Object.keys(c.domains).forEach(function (name) {
					var d = c.domains[name];
					var p = d.max ? Math.round((d.score / d.max) * 100) : 0;
					var status = p >= 85 ? 'Ready' : p >= 55 ? 'Partial' : 'Gap';
					bd.insertAdjacentHTML('beforeend', '<div class="sol-quiz__domain"><div><span>' + esc(name) + '</span><strong>' + p + '% · ' + status + '</strong></div><span class="sol-quiz__bar"><span style="width:' + p + '%"></span></span></div>');
				});
				var cta = $('[data-sol-result-cta]', root);
				cta.innerHTML = '';
				if (c.res.link && c.res.link.url) {
					cta.insertAdjacentHTML('beforeend', '<a class="sol-btn sol-btn--primary" href="' + esc(c.res.link.url) + '">' + esc(c.res.link.title || 'Continue') + '</a>');
				}
				show('result');
				t.focus({ preventScroll: true });
				root.scrollIntoView({ behavior: 'smooth', block: 'start' });
			};

			var start = $('[data-sol-quiz-start]', root);
			if (start) { start.addEventListener('click', function () { step = 0; answers = []; renderQ(); }); }
			var back = $('[data-sol-quiz-back]', root);
			if (back) { back.addEventListener('click', function () { if (step > 0) { step--; renderQ(); } }); }
			var restart = $('[data-sol-quiz-restart]', root);
			if (restart) { restart.addEventListener('click', function () { show('start'); }); }
			var skip = $('[data-sol-quiz-skip]', root);
			if (skip) { skip.addEventListener('click', result); }
			var lead = $('form[data-sol-form]', root);
			if (lead) {
				lead.addEventListener('submit', function (e) {
					var sum = $('[data-sol-quiz-summary]', lead);
					if (sum) { sum.value = summary(compute()); }
					if (!window.fetch) { return; }
					e.preventDefault();
					submitForm(lead, result);
				});
			}
		});
	}

	/* ---------- NDIS package finder ---------- */
	function finder() {
		$$('[data-sol-finder]').forEach(function (form) {
			var cfgEl = $('[data-sol-finder-config]', form);
			if (!cfgEl) { return; }
			var cfg = JSON.parse(cfgEl.textContent || '{}');
			var steps = $$('[data-finder-step]', form);
			var go = function (n) {
				steps.forEach(function (s) { s.hidden = s.getAttribute('data-finder-step') !== String(n); });
				form.scrollIntoView({ behavior: 'smooth', block: 'start' });
			};
			var err = function (stepEl, msg) { var e = $('[data-finder-error]', stepEl); if (e) { e.textContent = msg || ''; e.hidden = !msg; } };
			var checked = function (name) { return $$('input[name="' + name + '"]:checked', form).map(function (i) { return i.value; }); };

			$('[data-finder-next]', form).addEventListener('click', function () {
				var s = steps[0];
				if (!form.entity.value) { return err(s, 'Please choose your legal entity type.'); }
				if (!checked('states[]').length) { return err(s, 'Please select at least one state.'); }
				err(s, ''); go(2);
			});
			$('[data-finder-back]', form).addEventListener('click', function () { go(1); });
			$('[data-finder-restart]', form).addEventListener('click', function () { go(2); });

			var search = $('[data-finder-search]', form);
			if (search) {
				search.addEventListener('input', function () {
					var q = search.value.trim().toLowerCase();
					$$('[data-finder-item]', form).forEach(function (el) { el.classList.toggle('is-hidden', q && el.textContent.toLowerCase().indexOf(q) === -1); });
				});
			}

			$('[data-finder-calc]', form).addEventListener('click', function () {
				var s = steps[1];
				var codes = checked('groups[]');
				if (!codes.length) { return err(s, 'Select at least one registration group.'); }
				err(s, '');
				var entity = form.entity.value;
				var tier = 0, manual = false;
				(cfg.items || []).forEach(function (it) {
					if (codes.indexOf(it.code) === -1) { return; }
					var t = it.entityDependent && entity === 'company' ? it.companyTier : it.tier;
					tier = Math.max(tier, t);
					if (it.manual) { manual = true; }
				});
				var certGroups = (cfg.items || []).filter(function (it) { return codes.indexOf(it.code) !== -1 && it.tier >= 2; }).length;
				var states = checked('states[]');
				if (certGroups > 8 || (codes.indexOf('0138') !== -1 && states.length > 1)) { manual = true; }
				var pkg = (cfg.packages || []).filter(function (p) { return p.tier === tier; })[0] || (cfg.packages || [])[0];
				if (!pkg) { return; }

				var h = $('[data-finder-name]', form);
				h.textContent = pkg.name;
				$('[data-finder-pathway]', form).textContent = pkg.pathway + (pkg.bestFor ? ' — ' + pkg.bestFor : '');
				$('[data-finder-features]', form).innerHTML = pkg.features.map(function (f) { return '<li><i data-lucide="check-circle" class="sol-icon sol-checks__icon"></i><span>' + esc(f) + '</span></li>'; }).join('');
				$('[data-finder-total]', form).textContent = aud(pkg.total);
				$('[data-finder-gst]', form).textContent = 'Includes ' + aud(pkg.gst) + ' GST · ' + aud(pkg.priceEx) + ' excluding GST';
				$('[data-finder-count]', form).textContent = codes.length + ' group' + (codes.length === 1 ? '' : 's / modules') + ' selected';
				$('[data-finder-manual]', form).hidden = !manual;

				var pay = $('[data-finder-pay]', form);
				var box = $('[data-finder-options]', form);
				var link = $('[data-finder-pay-link]', form);
				pay.hidden = manual || !pkg.options.length;
				var ref = pkg.name + ' | ' + entity + ' | ' + states.join(',') + ' | ' + codes.join(',') + (form.retainer && form.retainer.checked ? ' | retainer' : '');
				var setLink = function (opt) {
					if (!opt || !opt.url) { link.hidden = true; return; }
					link.hidden = false;
					link.href = opt.url + (opt.url.indexOf('?') === -1 ? '?' : '&') + 'reference=' + encodeURIComponent(ref);
				};
				box.innerHTML = '';
				pkg.options.forEach(function (o, i) {
					var id = 'sol-pay-' + i;
					box.insertAdjacentHTML('beforeend', '<label class="sol-pay-option" for="' + id + '"><span><input type="radio" name="pay_option" id="' + id + '" value="' + i + '"' + (i === 0 ? ' checked' : '') + '>' + esc(o.label) + '</span><strong>' + aud(o.amount) + '</strong></label>');
				});
				$$('input[name="pay_option"]', box).forEach(function (r) { r.addEventListener('change', function () { setLink(pkg.options[+r.value]); }); });
				setLink(pkg.options[0]);
				go(3);
				icons();
				h.focus({ preventScroll: true });
			});
		});
	}

	/* ---------- Directory + post filters ---------- */
	function filters() {
		$$('[data-sol-directory]').forEach(function (root) {
			var input = $('[data-sol-directory-search]', root);
			var count = $('[data-sol-directory-count]', root);
			var empty = $('[data-sol-directory-empty]', root);
			var group = '*';
			var apply = function () {
				var q = input ? input.value.trim().toLowerCase() : '';
				var visible = 0;
				$$('.sol-directory__group', root).forEach(function (g) {
					var inGroup = group === '*' || g.getAttribute('data-group') === group;
					var shown = 0;
					$$('[data-sol-directory-item]', g).forEach(function (li) {
						var ok = inGroup && (!q || li.textContent.toLowerCase().indexOf(q) !== -1);
						li.classList.toggle('is-hidden', !ok);
						if (ok) { shown++; }
					});
					g.classList.toggle('is-hidden', !shown);
					visible += shown;
				});
				if (count) { count.textContent = (q || group !== '*') ? 'Showing ' + visible + ' of ' + count.getAttribute('data-total') + ' services' : count.getAttribute('data-total') + ' services'; }
				if (empty) { empty.hidden = visible > 0; }
			};
			if (input) { input.addEventListener('input', apply); }
			$$('[data-sol-directory-group]', root).forEach(function (b) {
				b.addEventListener('click', function () {
					group = b.getAttribute('data-sol-directory-group');
					$$('[data-sol-directory-group]', root).forEach(function (x) { x.classList.toggle('is-active', x === b); });
					apply();
				});
			});
		});
		$$('[data-sol-filter-scope]').forEach(function (root) {
			$$('[data-sol-filter]', root).forEach(function (b) {
				b.addEventListener('click', function () {
					var f = b.getAttribute('data-sol-filter');
					$$('[data-sol-filter]', root).forEach(function (x) { x.classList.toggle('is-active', x === b); });
					$$('[data-sol-filter-item]', root).forEach(function (it) {
						var cats = (it.getAttribute('data-sol-filter-item') || '').split(' ');
						it.classList.toggle('is-hidden', f !== '*' && cats.indexOf(f) === -1);
					});
				});
			});
		});
	}

	/* ---------- Widgets ---------- */
	function widgets() {
		var cookie = $('[data-sol-cookie]');
		if (cookie && store.get('sol_cookie_consent') !== '1') {
			cookie.hidden = false;
			$('[data-sol-cookie-accept]', cookie).addEventListener('click', function () { store.set('sol_cookie_consent', '1'); cookie.hidden = true; });
		}
		var top = $('[data-sol-totop]');
		if (top) {
			window.addEventListener('scroll', function () { top.hidden = window.scrollY < 600; }, { passive: true });
			top.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
		}
		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); } });
			}, { rootMargin: '0px 0px -10% 0px' });
			$$('.sol-section .sol-head, .sol-card, .sol-package, .sol-step, .sol-panel').forEach(function (el) { el.classList.add('sol-reveal'); io.observe(el); });
		}
	}

	function init() { icons(); header(); tabs(); forms(); quizzes(); finder(); filters(); widgets(); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
