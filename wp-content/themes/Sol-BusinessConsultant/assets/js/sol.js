/* SOL SCF Site — front-end behaviour. No dependencies besides Lucide icons. */
(function () {
	'use strict';

	var $ = function (sel, root) { return (root || document).querySelector(sel); };
	var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };
	var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; };
	var icons = function () {
		if (!window.lucide || !window.lucide.createIcons) { return; }
		window.lucide.createIcons({ attrs: { 'aria-hidden': 'true' } });
		// Any name Lucide doesn't know (e.g. a typo in an icon field) gets a neutral icon instead of a blank space.
		var unknown = $$('i[data-lucide]');
		if (unknown.length) {
			unknown.forEach(function (el) { el.setAttribute('data-lucide', 'circle-dot'); });
			window.lucide.createIcons({ attrs: { 'aria-hidden': 'true' } });
		}
	};
	var aud = function (n) { return new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD' }).format(n || 0); };
	var store = {
		get: function (k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
		set: function (k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* private mode */ } }
	};

	/* ---------- Header: mega menu, dropdowns + mobile drawer ---------- */
	function header() {
		var drops = $$('[data-sol-drop]');
		var closeAll = function (except) {
			drops.forEach(function (d) { if (d !== except && d._solOpen) { d._solOpen(false); } });
		};
		var hoverable = window.matchMedia && matchMedia('(hover: hover)').matches;
		drops.forEach(function (item) {
			var toggle = $('[data-sol-drop-toggle]', item);
			var panel = $('[data-sol-drop-panel]', item);
			if (!toggle || !panel) { return; }
			var timer;
			var open = function (yes) {
				clearTimeout(timer);
				if (yes) { closeAll(item); }
				panel.classList.toggle('is-open', yes);
				item.classList.toggle('is-open', yes);
				toggle.setAttribute('aria-expanded', yes ? 'true' : 'false');
			};
			item._solOpen = open;
			toggle.addEventListener('click', function () { open(!panel.classList.contains('is-open')); });
			if (hoverable) {
				item.addEventListener('mouseenter', function () { clearTimeout(timer); timer = setTimeout(function () { open(true); }, 60); });
				item.addEventListener('mouseleave', function () { clearTimeout(timer); timer = setTimeout(function () { open(false); }, 180); });
			}
			item.addEventListener('keydown', function (e) {
				if (e.key === 'Escape' && panel.classList.contains('is-open')) { open(false); toggle.focus(); }
			});
			item.addEventListener('focusout', function (e) {
				if (e.relatedTarget && !item.contains(e.relatedTarget)) { open(false); }
			});

			// Category tabs inside the mega menu: hover or arrow keys switch the panel.
			var tabs = $$('[data-sol-mega-tab]', item);
			var select = function (tab, focus) {
				tabs.forEach(function (t) {
					var on = t === tab;
					t.classList.toggle('is-active', on);
					t.setAttribute('aria-selected', on ? 'true' : 'false');
					t.tabIndex = on ? 0 : -1;
					var p = document.getElementById(t.getAttribute('aria-controls'));
					if (p) { p.classList.toggle('is-active', on); }
				});
				if (focus) { tab.focus(); }
			};
			tabs.forEach(function (t, i) {
				var hov;
				t.addEventListener('click', function () { select(t); });
				t.addEventListener('focus', function () { select(t); });
				if (hoverable) {
					t.addEventListener('mouseenter', function () { hov = setTimeout(function () { select(t); }, 70); });
					t.addEventListener('mouseleave', function () { clearTimeout(hov); });
				}
				t.addEventListener('keydown', function (e) {
					var n = null;
					if (e.key === 'ArrowDown' || e.key === 'ArrowRight') { n = tabs[(i + 1) % tabs.length]; }
					if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') { n = tabs[(i - 1 + tabs.length) % tabs.length]; }
					if (n) { e.preventDefault(); select(n, true); }
				});
			});
		});
		document.addEventListener('click', function (e) {
			drops.forEach(function (d) { if (d._solOpen && !d.contains(e.target)) { d._solOpen(false); } });
		});
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
	}

	/* ---------- Showcase hero: counting stats + mouse spotlight ---------- */
	function showcase() {
		if (!document.documentElement.classList.contains('sol-anim')) { return; }
		var ease = function (t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); };
		// The real number is already in the HTML; count up to it once it is on screen.
		function count(el) {
			var target = parseInt(el.getAttribute('data-sol-count'), 10), node = el.firstChild, start = null;
			if (!node || node.nodeType !== 3 || isNaN(target)) { return; }
			node.nodeValue = '0';
			setTimeout(function () {
				requestAnimationFrame(function step(ts) {
					if (start === null) { start = ts; }
					var p = Math.min((ts - start) / 1800, 1);
					node.nodeValue = String(Math.round(target * ease(p)));
					if (p < 1) { requestAnimationFrame(step); }
				});
			}, el.closest('[data-sol-showcase]') ? 1300 : 150);
		}
		var nums = $$('[data-sol-count]');
		if (nums.length && 'IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (es) {
				es.forEach(function (e) { if (e.isIntersecting) { count(e.target); io.unobserve(e.target); } });
			}, { threshold: 0.6 });
			nums.forEach(function (n) { io.observe(n); });
		}
		if (window.matchMedia && matchMedia('(hover: hover)').matches) {
			$$('[data-sol-showcase]').forEach(function (hero) {
				hero.addEventListener('pointermove', function (e) {
					var r = hero.getBoundingClientRect();
					hero.style.setProperty('--mx', (e.clientX - r.left) + 'px');
					hero.style.setProperty('--my', (e.clientY - r.top) + 'px');
				});
			});
		}
	}

	/* ---------- Sections fade up as they scroll into view ---------- */
	function reveal() {
		var root = document.documentElement;
		// Scroll progress bar under the header.
		var header = $('[data-sol-header]');
		if (header && $('.sol-main .sol-section')) {
			var bar = document.createElement('span');
			bar.className = 'sol-progress';
			bar.setAttribute('aria-hidden', 'true');
			header.appendChild(bar);
			var ticking = false;
			var update = function () {
				var max = document.documentElement.scrollHeight - window.innerHeight;
				bar.style.setProperty('--sol-progress', max > 0 ? Math.min(window.scrollY / max, 1).toFixed(4) : 0);
				ticking = false;
			};
			window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
			update();
		}
		if (!root.classList.contains('sol-anim') || !('IntersectionObserver' in window)) { return; }
		// Nothing is hidden up front: an element only gets the .sol-in animation when it is about to
		// scroll into view, so content always shows even if this code never runs.
		var items = [];
		var add = function (el, delay, kind) {
			if (!el || el._solIn) { return; }
			el._solIn = { d: delay || 0, k: kind || '' };
			items.push(el);
		};
		$$('.sol-main .sol-section').forEach(function (sec) {
			if (sec.classList.contains('sol-hero') || sec.closest('.sol-hero')) { return; }
			add($('.sol-head', sec), 0);
			$$('.sol-grid, .sol-step-list, .sol-resource-list, .sol-accordion, .sol-pills', sec).forEach(function (grid) {
				Array.prototype.slice.call(grid.children).forEach(function (child, i) { add(child, Math.min(i % 8, 6) + 1); });
			});
			$$('.sol-split', sec).forEach(function (split) {
				Array.prototype.slice.call(split.children).forEach(function (child, i) { add(child, i + 1, i === 0 ? 'left' : 'right'); });
			});
			add($('.sol-cta__box', sec), 0, 'zoom');
			// Cards, packages and steps outside those lists.
			$$('.sol-card, .sol-package, .sol-step, .sol-panel', sec).forEach(function (el) {
				add(el, Math.min(Array.prototype.indexOf.call(el.parentNode.children, el), 6) + 1);
			});
		});
		var play = function (el) {
			var o = el._solIn;
			el.style.setProperty('--sol-d', o.d);
			if (o.k) { el.classList.add('sol-in--' + o.k); }
			el.classList.add('sol-in');
			el.addEventListener('animationend', function end(e) {
				if (e.target !== el) { return; }
				el.removeEventListener('animationend', end);
				el.classList.remove('sol-in', 'sol-in--left', 'sol-in--right', 'sol-in--zoom');
				el.style.removeProperty('--sol-d');
			});
		};
		// Trigger a little before the element reaches the screen, so it never shows and then jumps.
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (e) {
				if (e.isIntersecting) { io.unobserve(e.target); play(e.target); }
			});
		}, { rootMargin: '0px 0px 12% 0px', threshold: 0 });
		var vh = window.innerHeight || document.documentElement.clientHeight;
		items.forEach(function (el) {
			var r = el.getBoundingClientRect();
			// Already on screen when the page loads (or above it): leave it as it is.
			if (r.top < vh) { return; }
			io.observe(el);
		});
	}

	/* ---------- Floating cart (left edge) + cart drawer ---------- */
	function fcart() {
		var root = $('[data-sol-fcart]');
		if (!root) { return; }
		var tab = $('[data-sol-fcart-open]', root);
		var drawer = $('.sol-fcart__drawer', root);
		var last = null;
		var open = function (yes) {
			root.classList.toggle('is-open', yes);
			tab.setAttribute('aria-expanded', yes ? 'true' : 'false');
			drawer.setAttribute('aria-hidden', yes ? 'false' : 'true');
			document.documentElement.style.overflow = yes ? 'hidden' : '';
			if (yes) {
				last = document.activeElement;
				setTimeout(function () { var c = $('.sol-fcart__close', root); if (c) { c.focus(); } }, 60);
			} else if (last && last.focus) { last.focus(); }
		};
		tab.addEventListener('click', function () { open(true); });
		$$('[data-sol-fcart-close]', root).forEach(function (b) { b.addEventListener('click', function () { open(false); }); });
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('is-open')) { open(false); } });

		// Count changes arrive as WooCommerce cart fragments (the badge element is replaced).
		var count = function () { var c = $('.sol-fcart__count', root); return c ? parseInt(c.getAttribute('data-count'), 10) || 0 : 0; };
		var prev = count();
		root.classList.toggle('has-items', prev > 0);
		var bump = function () {
			root.classList.remove('is-bump');
			void root.offsetWidth;
			root.classList.add('is-bump');
			setTimeout(function () { root.classList.remove('is-bump'); }, 900);
		};
		if ('MutationObserver' in window) {
			new MutationObserver(function () {
				var now = count();
				if (now !== prev) {
					root.classList.toggle('has-items', now > 0);
					if (now > prev) { bump(); }
					prev = now;
				}
			}).observe(root, { childList: true, subtree: true });
		}
		// Open the drawer right after an AJAX "Add to cart".
		if (window.jQuery) {
			window.jQuery(document.body).on('added_to_cart', function () { setTimeout(function () { open(true); }, 350); });
		}
	}

	/* ---------- Cards: the hover glow follows the cursor ---------- */
	function cardGlow() {
		if (!window.matchMedia || !matchMedia('(hover: hover)').matches) { return; }
		document.addEventListener('pointermove', function (e) {
			var card = e.target.closest && e.target.closest('.sol-card--bordered, .sol-card--numbered, .sol-card--tagged, .sol-card--feature, .sol-steps--cards .sol-step, .sol-steps--numbered .sol-step');
			if (!card) { return; }
			var r = card.getBoundingClientRect();
			card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
			card.style.setProperty('--my', (e.clientY - r.top) + 'px');
		}, { passive: true });
	}

	function init() { icons(); header(); tabs(); forms(); quizzes(); finder(); filters(); widgets(); showcase(); reveal(); fcart(); cardGlow(); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
