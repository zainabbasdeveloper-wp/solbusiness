/* Accessibility toolbar (inc/a11y.php). Saved in localStorage "sol-a11y"; the <head> script reapplies it on load. */
(function () {
  'use strict';
  var root = document.querySelector('[data-sol-a11y]');
  if (!root) return;

  var html = document.documentElement;
  var panel = root.querySelector('.sol-a11y__panel');
  var toggle = root.querySelector('[data-a11y-toggle]');
  var sizeOut = root.querySelector('[data-a11y-size]');
  var hint = root.querySelector('[data-a11y-hint]');
  var KEY = 'sol-a11y';
  var MIN = -2, MAX = 5;               // 80% to 150%
  var MODES = ['contrast', 'negative', 'light']; // only one colour mode at a time
  var state = load();

  function load() {
    try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; }
  }
  function save() {
    var keep = {};
    Object.keys(state).forEach(function (k) { if (state[k] && k !== 'read') keep[k] = state[k]; });
    try { Object.keys(keep).length ? localStorage.setItem(KEY, JSON.stringify(keep)) : localStorage.removeItem(KEY); } catch (e) {}
  }

  /* ---------- Apply ---------- */
  function apply() {
    var t = state.text || 0;
    html.classList.toggle('a11y-text', t !== 0);
    if (t) html.style.setProperty('--a11y-zoom', String(1 + t / 10)); else html.style.removeProperty('--a11y-zoom');
    sizeOut.textContent = (100 + t * 10) + '%';
    root.querySelector('[data-a11y-text="-1"]').disabled = t <= MIN;
    root.querySelector('[data-a11y-text="1"]').disabled = t >= MAX;

    ['gray', 'contrast', 'negative', 'light', 'links', 'read'].forEach(function (k) {
      html.classList.toggle('a11y-' + k, !!state[k]);
      var b = root.querySelector('[data-a11y-opt="' + k + '"]');
      if (b) { b.setAttribute('aria-pressed', state[k] ? 'true' : 'false'); b.classList.toggle('is-on', !!state[k]); }
    });
    hint.hidden = !state.read;
    var active = Object.keys(state).some(function (k) { return state[k]; });
    root.classList.toggle('has-active', active);
    save();
  }

  /* ---------- Read aloud ---------- */
  var synth = window.speechSynthesis;
  var voice = null;
  var READABLE = 'p, h1, h2, h3, h4, h5, h6, li, blockquote, figcaption, td, th, dt, dd, label, summary, a, button, .sol-btn';
  var lit = null;

  function pickVoice() {
    if (!synth) return;
    var v = synth.getVoices();
    voice = v.filter(function (x) { return /en-AU/i.test(x.lang); })[0] ||
      v.filter(function (x) { return /en-GB/i.test(x.lang); })[0] ||
      v.filter(function (x) { return /^en/i.test(x.lang); })[0] || null;
  }
  if (synth) { pickVoice(); if ('onvoiceschanged' in synth) synth.onvoiceschanged = pickVoice; }
  else { var rb = root.querySelector('[data-a11y-opt="read"]'); if (rb) rb.hidden = true; }

  function speak(text) {
    if (!synth) return;
    text = String(text || '').replace(/\s+/g, ' ').trim();
    if (!text) return;
    synth.cancel();
    var u = new SpeechSynthesisUtterance(text.slice(0, 4000));
    if (voice) u.voice = voice;
    u.lang = voice ? voice.lang : 'en-AU';
    u.rate = 1;
    synth.speak(u);
  }
  function textOf(el) {
    return el.getAttribute('aria-label') || el.innerText || el.textContent || el.getAttribute('alt') || '';
  }
  function outside(el) { return el && !root.contains(el); }
  function highlight(el) {
    if (lit === el) return;
    if (lit) lit.classList.remove('sol-a11y-reading');
    lit = el;
    if (lit) lit.classList.add('sol-a11y-reading');
  }

  document.addEventListener('mouseover', function (e) {
    if (!state.read) return;
    var el = e.target.closest && e.target.closest(READABLE);
    highlight(outside(el) ? el : null);
  });
  document.addEventListener('click', function (e) {
    if (!state.read) return;
    var sel = window.getSelection && String(window.getSelection());
    if (sel && sel.trim()) return; // handled on mouseup
    var el = e.target.closest && e.target.closest(READABLE);
    if (outside(el)) speak(textOf(el));
  }, true);
  document.addEventListener('mouseup', function (e) {
    if (!state.read || !outside(e.target)) return;
    var sel = window.getSelection && String(window.getSelection());
    if (sel && sel.trim()) speak(sel);
  });
  document.addEventListener('focusin', function (e) {
    if (state.read && outside(e.target) && e.target.matches && e.target.matches('a, button, input, select, textarea, [tabindex]')) {
      speak(textOf(e.target) || e.target.getAttribute('placeholder'));
    }
  });

  /* ---------- Controls ---------- */
  root.querySelectorAll('[data-a11y-text]').forEach(function (b) {
    b.addEventListener('click', function () {
      state.text = Math.max(MIN, Math.min(MAX, (state.text || 0) + Number(b.getAttribute('data-a11y-text'))));
      apply();
    });
  });
  root.querySelectorAll('[data-a11y-opt]').forEach(function (b) {
    b.addEventListener('click', function () {
      var k = b.getAttribute('data-a11y-opt');
      var on = !state[k];
      if (on && MODES.indexOf(k) > -1) MODES.forEach(function (m) { state[m] = false; });
      state[k] = on;
      if (k === 'read') {
        if (on) speak('Read aloud is on. Click any text to hear it.');
        else { if (synth) synth.cancel(); highlight(null); }
      }
      apply();
    });
  });
  root.querySelector('[data-a11y-reset]').addEventListener('click', function () {
    if (synth) synth.cancel();
    highlight(null);
    state = {};
    apply();
  });

  /* ---------- Open / close ---------- */
  function open() {
    panel.hidden = false;
    root.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    setTimeout(function () { root.classList.add('is-shown'); }, 16);
    var first = panel.querySelector('button');
    if (first) first.focus({ preventScroll: true });
  }
  function close(refocus) {
    root.classList.remove('is-shown', 'is-open');
    toggle.setAttribute('aria-expanded', 'false');
    setTimeout(function () { if (!root.classList.contains('is-open')) panel.hidden = true; }, 220);
    if (refocus) toggle.focus();
  }
  toggle.addEventListener('click', function () { root.classList.contains('is-open') ? close(false) : open(); });
  root.querySelector('[data-a11y-close]').addEventListener('click', function () { close(true); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && root.classList.contains('is-open')) close(true);
  });
  document.addEventListener('click', function (e) {
    if (root.classList.contains('is-open') && !root.contains(e.target)) close(false);
  });

  apply();
})();
