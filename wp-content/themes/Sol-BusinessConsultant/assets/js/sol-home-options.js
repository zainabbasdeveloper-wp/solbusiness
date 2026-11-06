/* Home page options (/home-options/): reveals, tabs, rotating words, scrollers and hover glow. */
(function () {
	'use strict';
	var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
	var still = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
	var root = document.documentElement;

	/* Fade/slide in as things scroll into view. Content shows normally if this never runs. */
	// Nothing is ever hidden up front: an element only gets the rise-in animation at the moment it is about
	// to scroll into view. If this never runs (old browser, fast scroll, screenshot tool), everything simply shows.
	function reveal() {
		if (still || !('IntersectionObserver' in window)) { return; }
		var io = new IntersectionObserver(function (es) {
			es.forEach(function (e) {
				if (!e.isIntersecting) { return; }
				io.unobserve(e.target);
				e.target.classList.add('is-in');
			});
		}, { rootMargin: '0px 0px 80px 0px', threshold: 0 });
		$$('[data-reveal]').forEach(function (el) {
			var r = el.getBoundingClientRect();
			if (r.top < innerHeight && r.bottom > 0) { return; } // on screen at load: no animation
			var g = el.parentElement && el.parentElement.closest('[data-reveal-group]');
			if (g) { el.style.setProperty('--ho-d', Array.prototype.indexOf.call(g.querySelectorAll('[data-reveal]'), el) % 6); }
			io.observe(el);
		});
	}

	/* Tabs: [data-ho-tabs] > [data-ho-tab=key] buttons and [data-ho-panel=key] panels. */
	function tabs() {
		$$('[data-ho-tabs]').forEach(function (box) {
			var btns = $$('[data-ho-tab]', box), panels = $$('[data-ho-panel]', box);
			var show = function (key, focus) {
				btns.forEach(function (b) {
					var on = b.getAttribute('data-ho-tab') === key;
					b.setAttribute('aria-selected', on ? 'true' : 'false');
					b.tabIndex = on ? 0 : -1;
					if (on && focus) { b.focus(); }
				});
				panels.forEach(function (p) { p.hidden = p.getAttribute('data-ho-panel') !== key; });
			};
			btns.forEach(function (b, i) {
				b.addEventListener('click', function () { show(b.getAttribute('data-ho-tab')); });
				b.addEventListener('keydown', function (e) {
					var n = e.key === 'ArrowRight' ? 1 : e.key === 'ArrowLeft' ? -1 : 0;
					if (!n) { return; }
					e.preventDefault();
					show(btns[(i + n + btns.length) % btns.length].getAttribute('data-ho-tab'), true);
				});
			});
		});
	}

	/* Rotating word: <span data-ho-rotate='["a","b"]'>a</span> */
	function rotate() {
		$$('[data-ho-rotate]').forEach(function (el) {
			var words;
			try { words = JSON.parse(el.getAttribute('data-ho-rotate')); } catch (e) { return; }
			if (!words || words.length < 2 || still) { return; }
			var i = 0;
			setInterval(function () {
				el.classList.add('is-out');
				setTimeout(function () { i = (i + 1) % words.length; el.textContent = words[i]; el.classList.remove('is-out'); }, 320);
			}, 2600);
		});
	}

	/* Horizontal scrollers with previous/next buttons. */
	function scrollers() {
		$$('[data-ho-scroller]').forEach(function (box) {
			var track = box.querySelector('[data-ho-track]');
			if (!track) { return; }
			var step = function (dir) {
				var card = track.firstElementChild;
				var w = card ? card.getBoundingClientRect().width + 20 : track.clientWidth * 0.8;
				track.scrollBy({ left: dir * w, behavior: still ? 'auto' : 'smooth' });
			};
			$$('[data-ho-prev]', box).forEach(function (b) { b.addEventListener('click', function () { step(-1); }); });
			$$('[data-ho-next]', box).forEach(function (b) { b.addEventListener('click', function () { step(1); }); });
		});
	}

	/* Soft light that follows the pointer over [data-ho-glow] elements. */
	function glow() {
		if (!window.matchMedia || !matchMedia('(hover: hover)').matches) { return; }
		$$('[data-ho-glow]').forEach(function (el) {
			el.addEventListener('pointermove', function (e) {
				var r = el.getBoundingClientRect();
				el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
				el.style.setProperty('--my', (e.clientY - r.top) + 'px');
			});
		});
	}

	function init() { reveal(); tabs(); rotate(); scrollers(); glow(); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
