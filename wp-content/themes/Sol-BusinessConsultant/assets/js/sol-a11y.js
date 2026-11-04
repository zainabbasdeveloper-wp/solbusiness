/* Accessibility menu (inc/a11y.php).
 * Saved in localStorage "sol-a11y" as {s: choices, c: classes, v: CSS variables}; the <head> script copies c and v onto <html> before paint.
 * Profiles switch on a bundle of options. A profile's options sit on top of the visitor's own choices, so turning a profile off
 * only removes what it added. */
(function () {
  'use strict';
  var root = document.querySelector('[data-sol-a11y]');
  if (!root) return;

  var html = document.documentElement;
  var panel = root.querySelector('.sol-a11y__panel');
  var toggle = root.querySelector('[data-a11y-toggle]');
  var KEY = 'sol-a11y';
  var STEPS = {
    scale: { min: -2, max: 5, show: function (n) { return (100 + n * 10) + '%'; } },
    font: { min: -2, max: 5, show: function (n) { return (100 + n * 10) + '%'; } },
    line: { min: 0, max: 4, show: function (n) { return ['Default', '1.6', '1.9', '2.2', '2.5'][n]; } },
    space: { min: 0, max: 4, show: function (n) { return n ? 'Level ' + n : 'Default'; } }
  };
  var LINE = [0, 1.6, 1.9, 2.2, 2.5];
  var FILTER = { contrast: 'contrast(1.4)', high: 'saturate(2)', low: 'saturate(.45)', mono: 'grayscale(1)' };
  var VARS = ['--a11y-zoom', '--a11y-font', '--a11y-lh', '--a11y-ls', '--a11y-ws', '--a11y-filter', '--a11y-txc', '--a11y-ttc', '--a11y-bgc'];
  var BUNDLES = {};
  root.querySelectorAll('[data-a11y-profile]').forEach(function (p) {
    try { BUNDLES[p.getAttribute('data-a11y-profile')] = JSON.parse(p.getAttribute('data-a11y-bundle')); } catch (e) {}
  });

  var state = load();
  var eff = {};
  var prev = {};
  var ready = false; // false during the first apply on page load

  function load() {
    var d;
    try { d = JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { d = {}; }
    if (d.s) return d.s;
    // Older toolbar format: {text, gray, contrast, negative, light, links}
    var s = {};
    if (d.text) s.scale = d.text;
    if (d.gray) s.sat = 'mono';
    if (d.contrast) s.color = 'contrast';
    if (d.negative) s.color = 'dark';
    if (d.light) s.color = 'light';
    if (d.links) s.links = true;
    return s;
  }

  /* ---------- Working out what is on ---------- */
  function effective() {
    var e = {};
    Object.keys(state).forEach(function (k) { if (k !== 'p') e[k] = state[k]; });
    var p = state.p || {};
    Object.keys(p).forEach(function (name) {
      if (!p[name] || !BUNDLES[name]) return;
      var b = BUNDLES[name];
      Object.keys(b).forEach(function (k) {
        if (STEPS[k]) e[k] = Math.max(e[k] || 0, b[k]);
        else if (typeof b[k] === 'string') { if (!state[k]) e[k] = b[k]; }
        else e[k] = true;
      });
    });
    return e;
  }
  // Turning off something a profile switched on also switches that profile off.
  function dropProfiles(k, keepAtOrBelow) {
    var p = state.p || {};
    Object.keys(p).forEach(function (name) {
      var b = BUNDLES[name];
      if (!p[name] || !b || !(k in b)) return;
      if (keepAtOrBelow !== undefined && b[k] <= keepAtOrBelow) return;
      p[name] = false;
    });
  }

  function save(classes, vars) {
    var s = {};
    Object.keys(state).forEach(function (k) {
      if (k === 'readmode') return;
      if (k === 'p') {
        var on = {};
        Object.keys(state.p).forEach(function (n) { if (state.p[n]) on[n] = true; });
        if (Object.keys(on).length) s.p = on;
      } else if (state[k]) s[k] = state[k];
    });
    try {
      if (Object.keys(s).length) localStorage.setItem(KEY, JSON.stringify({ s: s, c: classes, v: vars }));
      else localStorage.removeItem(KEY);
    } catch (e) {}
  }

  /* ---------- Apply ---------- */
  function apply() {
    prev = eff;
    eff = effective();
    var c = [];
    var v = {};
    if (eff.scale) { c.push('a11y-scale'); v['--a11y-zoom'] = String(1 + eff.scale / 10); }
    if (eff.font) { c.push('a11y-font'); v['--a11y-font'] = (100 + eff.font * 10) + '%'; }
    if (eff.line) { c.push('a11y-line'); v['--a11y-lh'] = String(LINE[eff.line]); }
    if (eff.space) { c.push('a11y-space'); v['--a11y-ls'] = (eff.space * 0.04).toFixed(2) + 'em'; v['--a11y-ws'] = (eff.space * 0.08).toFixed(2) + 'em'; }
    ['readable', 'titles', 'links', 'noimg', 'still', 'hover', 'focus', 'read', 'magnify'].forEach(function (k) { if (eff[k]) c.push('a11y-' + k); });
    if (eff.align) c.push('a11y-align-' + eff.align);
    if (eff.color === 'dark' || eff.color === 'light') c.push('a11y-' + eff.color);
    if (eff.cursor) c.push('a11y-cursor-' + eff.cursor);
    var f = [];
    if (eff.color === 'contrast') f.push(FILTER.contrast);
    if (eff.sat) f.push(FILTER[eff.sat]);
    if (f.length) { c.push('a11y-filter'); v['--a11y-filter'] = f.join(' '); }
    ['txc', 'ttc', 'bgc'].forEach(function (k) { if (eff[k]) { c.push('a11y-' + k); v['--a11y-' + k] = eff[k]; } });

    Array.prototype.slice.call(html.classList).forEach(function (cl) {
      if (/^a11y-/.test(cl) && cl !== 'a11y-ui-hidden' && cl !== 'a11y-reading-mode' && c.indexOf(cl) < 0) html.classList.remove(cl);
    });
    c.forEach(function (cl) { html.classList.add(cl); });
    VARS.forEach(function (k) { if (v[k]) html.style.setProperty(k, v[k]); else html.style.removeProperty(k); });

    paint();
    effects();
    save(c, v);
  }

  // Updates the menu's own buttons to match.
  function paint() {
    root.querySelectorAll('[data-a11y-set]').forEach(function (b) {
      var parts = b.getAttribute('data-a11y-set').split('=');
      var on = parts.length > 1 ? eff[parts[0]] === parts[1] : !!eff[parts[0]];
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
      b.classList.toggle('is-on', on);
    });
    root.querySelectorAll('[data-a11y-step]').forEach(function (box) {
      var k = box.getAttribute('data-a11y-step');
      var n = eff[k] || 0;
      box.querySelector('output').textContent = n === 0 && k !== 'line' && k !== 'space' ? 'Default' : STEPS[k].show(n);
      box.querySelector('[data-a11y-by="-1"]').disabled = n <= STEPS[k].min;
      box.querySelector('[data-a11y-by="1"]').disabled = n >= STEPS[k].max;
      box.classList.toggle('is-on', n !== 0);
    });
    root.querySelectorAll('[data-a11y-colour]').forEach(function (g) {
      var k = g.getAttribute('data-a11y-colour');
      g.querySelectorAll('[data-a11y-hex]').forEach(function (b) {
        var hex = b.getAttribute('data-a11y-hex');
        if (hex) b.setAttribute('aria-pressed', eff[k] === hex ? 'true' : 'false');
        else b.disabled = !eff[k];
      });
    });
    var p = state.p || {};
    root.querySelectorAll('[data-a11y-profile]').forEach(function (row) {
      var on = !!p[row.getAttribute('data-a11y-profile')];
      row.classList.toggle('is-on', on);
      row.querySelector('[role="switch"]').setAttribute('aria-checked', on ? 'true' : 'false');
    });
    root.querySelectorAll('[data-a11y-hint]').forEach(function (h) { h.hidden = !eff[h.getAttribute('data-a11y-hint')]; });
    root.classList.toggle('has-active', Object.keys(eff).some(function (k) { return eff[k] && k !== 'readmode'; }));
  }

  // Behaviour that needs script rather than CSS.
  function effects() {
    if (ready && eff.read && !prev.read) speak('Read aloud is on. Click any text to hear it.');
    if (!eff.read && prev.read) { if (synth) synth.cancel(); light(null); }
    media();
    ['guide', 'mask', 'magnify'].forEach(function (k) {
      var layer = root.querySelector('[data-a11y-layer="' + k + '"]');
      if (layer) layer.hidden = !eff[k] || (k === 'magnify');
    });
    if (!eff.hover) hovered(null);
    if (eff.readmode && !prev.readmode) openReader();
    if (!eff.readmode && prev.readmode) closeReader();
  }

  /* ---------- Sound and video ---------- */
  function media() {
    document.querySelectorAll('audio, video').forEach(function (m) {
      if (eff.mute && !m.muted) { m.muted = true; m.setAttribute('data-a11y-muted', ''); }
      if (!eff.mute && m.hasAttribute('data-a11y-muted')) { m.muted = false; m.removeAttribute('data-a11y-muted'); }
      if (eff.still && !prev.still && !m.paused && (m.autoplay || m.loop)) m.pause();
    });
  }
  document.addEventListener('play', function (e) {
    if (eff.mute && e.target && !e.target.muted) { e.target.muted = true; e.target.setAttribute('data-a11y-muted', ''); }
  }, true);

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
  else root.querySelectorAll('[data-a11y-set="read"], [data-a11y-profile="screen"]').forEach(function (b) { b.hidden = true; });

  function speak(text) {
    if (!synth) return;
    text = String(text || '').replace(/\s+/g, ' ').trim();
    if (!text) return;
    synth.cancel();
    var u = new SpeechSynthesisUtterance(text.slice(0, 4000));
    if (voice) u.voice = voice;
    u.lang = voice ? voice.lang : 'en-AU';
    synth.speak(u);
  }
  function textOf(el) {
    return el.getAttribute('aria-label') || el.innerText || el.textContent || el.getAttribute('alt') || '';
  }
  function outside(el) { return el && !root.contains(el); }
  function light(el) {
    if (lit === el) return;
    if (lit) lit.classList.remove('sol-a11y-reading');
    lit = el;
    if (lit) lit.classList.add('sol-a11y-reading');
  }

  document.addEventListener('click', function (e) {
    if (!eff.read) return;
    var sel = window.getSelection && String(window.getSelection());
    if (sel && sel.trim()) return; // handled on mouseup
    var el = e.target.closest && e.target.closest(READABLE);
    if (outside(el)) speak(textOf(el));
  }, true);
  document.addEventListener('mouseup', function (e) {
    if (!eff.read || !outside(e.target)) return;
    var sel = window.getSelection && String(window.getSelection());
    if (sel && sel.trim()) speak(sel);
  });
  document.addEventListener('focusin', function (e) {
    if (eff.read && outside(e.target) && e.target.matches && e.target.matches('a, button, input, select, textarea, [tabindex]')) {
      speak(textOf(e.target) || e.target.getAttribute('placeholder'));
    }
  });

  /* ---------- Pointer helpers: hover highlight, reading guide, reading mask, text magnifier ---------- */
  var hoverEl = null;
  function hovered(el) {
    if (hoverEl === el) return;
    if (hoverEl) hoverEl.classList.remove('sol-a11y-hovered');
    hoverEl = el;
    if (hoverEl) hoverEl.classList.add('sol-a11y-hovered');
  }
  var guide = root.querySelector('[data-a11y-layer="guide"]');
  var mask = root.querySelector('[data-a11y-layer="mask"]');
  var lens = root.querySelector('[data-a11y-layer="magnify"]');
  var frame = 0;
  var px = 0;
  var py = 0;
  var target = null;

  document.addEventListener('mouseover', function (e) {
    var t = e.target;
    var el = eff.read && t.closest ? t.closest(READABLE) : null;
    light(outside(el) ? el : null);
    if (eff.hover) hovered(outside(t) && t !== document.body && t !== html ? t : null);
  });
  document.addEventListener('mousemove', function (e) {
    if (!eff.guide && !eff.mask && !eff.magnify) return;
    px = e.clientX;
    py = e.clientY;
    target = e.target;
    if (!frame) frame = requestAnimationFrame(follow);
  }, { passive: true });
  document.addEventListener('mouseleave', function () { if (lens) lens.hidden = true; });

  function follow() {
    frame = 0;
    if (eff.guide) guide.style.transform = 'translateY(' + (py - 6) + 'px)';
    if (eff.mask) {
      var band = 70;
      mask.children[0].style.height = Math.max(0, py - band) + 'px';
      mask.children[1].style.top = (py + band) + 'px';
    }
    if (eff.magnify) {
      var el = target && target.closest ? target.closest(READABLE) : null;
      var text = outside(el) ? textOf(el).replace(/\s+/g, ' ').trim() : '';
      if (!text) { lens.hidden = true; return; }
      lens.textContent = text.length > 280 ? text.slice(0, 280) + '…' : text;
      lens.hidden = false;
      var w = lens.offsetWidth;
      var h = lens.offsetHeight;
      var x = Math.min(px + 18, window.innerWidth - w - 10);
      var y = py + 24 + h > window.innerHeight ? py - h - 16 : py + 24;
      lens.style.transform = 'translate(' + Math.max(10, x) + 'px,' + Math.max(10, y) + 'px)';
    }
  }

  /* ---------- Keyboard navigation shortcuts ---------- */
  var JUMP = {
    h: 'h1, h2, h3, h4, h5, h6',
    l: 'a[href]',
    b: 'button, [role="button"], input[type="submit"], input[type="button"]',
    f: 'input:not([type="hidden"]), select, textarea',
    g: 'img, svg[role="img"], [role="img"]',
    m: 'nav, [role="navigation"], [role="menubar"], [role="menu"]'
  };
  var NAMES = { h: 'heading', l: 'link', b: 'button', f: 'form field', g: 'image', m: 'menu' };
  function visible(el) { return el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden'; }
  document.addEventListener('keydown', function (e) {
    if (!eff.kbd || e.ctrlKey || e.metaKey || e.altKey) return;
    var t = e.target;
    if (t && (t.isContentEditable || /^(INPUT|SELECT|TEXTAREA)$/.test(t.tagName))) return;
    var k = String(e.key || '').toLowerCase();
    if (!JUMP[k]) return;
    var list = Array.prototype.filter.call(document.querySelectorAll(JUMP[k]), function (el) { return outside(el) && visible(el); });
    if (!list.length) return;
    e.preventDefault();
    var here = document.activeElement && document.activeElement !== document.body ? document.activeElement : null;
    var pick;
    if (e.shiftKey) {
      for (var i = list.length - 1; i >= 0; i--) { if (here && (here.compareDocumentPosition(list[i]) & Node.DOCUMENT_POSITION_PRECEDING)) { pick = list[i]; break; } }
      pick = pick || list[list.length - 1];
    } else {
      for (var j = 0; j < list.length; j++) { if (!here || (here.compareDocumentPosition(list[j]) & Node.DOCUMENT_POSITION_FOLLOWING && !here.contains(list[j]))) { pick = list[j]; break; } }
      pick = pick || list[0];
    }
    if (!pick.matches('a[href], button, input, select, textarea, [tabindex]')) pick.setAttribute('tabindex', '-1');
    pick.focus({ preventScroll: true });
    pick.scrollIntoView({ block: 'center', behavior: eff.still ? 'auto' : 'smooth' });
    if (eff.read) speak(NAMES[k] + ': ' + textOf(pick));
  });

  /* ---------- Read mode ---------- */
  var reader = root.querySelector('[data-a11y-reader]');
  var readerBody = root.querySelector('[data-a11y-reader-body]');
  var SKIP = 'header, nav, footer, form, aside, script, style, noscript, [aria-hidden="true"], #wpadminbar, .sol-a11y, .sol-chat, .sol-cookie, .sol-fcart';
  function mainArea() { return document.querySelector('#sol-main, main, [role="main"], #main') || document.body; }
  function openReader() {
    var src = mainArea();
    readerBody.innerHTML = '';
    var h = document.createElement('h1');
    h.textContent = ((document.querySelector('#sol-main h1, main h1, h1') || {}).innerText || document.title).replace(/\s+/g, ' ').trim();
    readerBody.appendChild(h);
    var last = h.textContent.trim();
    var count = 0;
    src.querySelectorAll('h1, h2, h3, h4, h5, h6, p, li, blockquote, figcaption, dt, dd').forEach(function (el) {
      if (count > 500 || el.closest(SKIP) || !visible(el)) return;
      if (/^(LI|DD|BLOCKQUOTE)$/.test(el.tagName) && el.querySelector('p, li, h1, h2, h3, h4, h5, h6')) return; // its children are added instead
      var text = (el.innerText || '').replace(/\s+/g, ' ').trim();
      if (!text || text === last) return;
      last = text;
      var tag = /^H[1-6]$/.test(el.tagName) ? (el.tagName === 'H1' ? 'h2' : el.tagName.toLowerCase()) : 'p';
      var out = document.createElement(tag);
      out.textContent = text;
      if (el.tagName === 'LI') out.className = 'is-item';
      readerBody.appendChild(out);
      count++;
    });
    reader.hidden = false;
    html.classList.add('a11y-reading-mode');
    reader.scrollTop = 0;
    close(false);
    reader.querySelector('[data-a11y-reader-close]').focus();
  }
  function closeReader() {
    reader.hidden = true;
    html.classList.remove('a11y-reading-mode');
  }
  root.querySelector('[data-a11y-reader-close]').addEventListener('click', function () {
    state.readmode = false;
    apply();
    toggle.focus();
  });

  /* ---------- Useful links ---------- */
  var linkPick = root.querySelector('[data-a11y-links]');
  function fillLinks() {
    while (linkPick.options.length > 1) linkPick.remove(1);
    var here = document.createElement('optgroup');
    here.label = 'On this page';
    var n = 0;
    mainArea().querySelectorAll('h2, h3').forEach(function (h) {
      if (n > 30 || h.closest(SKIP) || !visible(h)) return;
      var text = (h.innerText || '').replace(/\s+/g, ' ').trim();
      if (!text) return;
      if (!h.id) h.id = 'sol-a11y-section-' + (++n);
      else n++;
      here.appendChild(new Option(text.slice(0, 70), '#' + h.id));
    });
    var pages = document.createElement('optgroup');
    pages.label = 'Pages';
    var seen = {};
    document.querySelectorAll('header a[href], nav a[href]').forEach(function (a) {
      if (a.closest('.sol-a11y')) return;
      var href = a.href.split('#')[0];
      var text = (a.innerText || a.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim();
      if (!text || seen[href] || !/^https?:/.test(href) || pages.children.length >= 40) return;
      seen[href] = true;
      pages.appendChild(new Option(text.slice(0, 60), href));
    });
    if (here.children.length) linkPick.appendChild(here);
    if (pages.children.length) linkPick.appendChild(pages);
  }
  linkPick.addEventListener('change', function () {
    var to = linkPick.value;
    linkPick.value = '';
    if (!to) return;
    close(false);
    if (to.charAt(0) === '#') {
      // A real link click, so the theme's smooth jump (sol.js) lands below the sticky header.
      var a = document.createElement('a');
      a.href = to;
      a.hidden = true;
      root.appendChild(a);
      a.click();
      a.remove();
    } else {
      window.location.href = to;
    }
  });

  /* ---------- Controls ---------- */
  root.querySelectorAll('[data-a11y-set]').forEach(function (b) {
    b.addEventListener('click', function () {
      var parts = b.getAttribute('data-a11y-set').split('=');
      var k = parts[0];
      if (parts.length > 1) {
        if (eff[k] === parts[1]) { state[k] = ''; dropProfiles(k); } else state[k] = parts[1];
      } else if (eff[k]) { state[k] = false; dropProfiles(k); } else state[k] = true;
      apply();
    });
  });
  root.querySelectorAll('[data-a11y-step]').forEach(function (box) {
    var k = box.getAttribute('data-a11y-step');
    box.querySelectorAll('[data-a11y-by]').forEach(function (b) {
      b.addEventListener('click', function () {
        var by = Number(b.getAttribute('data-a11y-by'));
        var n = Math.max(STEPS[k].min, Math.min(STEPS[k].max, (eff[k] || 0) + by));
        state[k] = n;
        if (by < 0) dropProfiles(k, n);
        apply();
      });
    });
  });
  root.querySelectorAll('[data-a11y-colour]').forEach(function (g) {
    var k = g.getAttribute('data-a11y-colour');
    g.querySelectorAll('[data-a11y-hex]').forEach(function (b) {
      b.addEventListener('click', function () {
        var hex = b.getAttribute('data-a11y-hex');
        state[k] = hex && eff[k] !== hex ? hex : '';
        apply();
      });
    });
  });
  root.querySelectorAll('[data-a11y-profile]').forEach(function (row) {
    var name = row.getAttribute('data-a11y-profile');
    row.querySelector('[role="switch"]').addEventListener('click', function () {
      state.p = state.p || {};
      state.p[name] = !state.p[name];
      apply();
    });
  });
  root.querySelectorAll('[data-a11y-reset]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (synth) synth.cancel();
      light(null);
      state = {};
      apply();
    });
  });
  var chat = root.querySelector('[data-a11y-chat]');
  if (chat) {
    var chatOpen = document.querySelector('[data-chat-open]') || document.querySelector('[data-chat-toggle]');
    if (!chatOpen) chat.hidden = true;
    chat.addEventListener('click', function () {
      close(false);
      if (chatOpen) chatOpen.click();
    });
  }

  /* ---------- Hide the menu for this visit (Alt + A brings it back) ---------- */
  root.querySelector('[data-a11y-hide]').addEventListener('click', function () {
    try { sessionStorage.setItem('sol-a11y-hide', '1'); } catch (e) {}
    close(false);
    html.classList.add('a11y-ui-hidden');
  });

  /* ---------- Open / close ---------- */
  function open() {
    html.classList.remove('a11y-ui-hidden');
    try { sessionStorage.removeItem('sol-a11y-hide'); } catch (e) {}
    fillLinks();
    panel.hidden = false;
    root.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    setTimeout(function () { root.classList.add('is-shown'); }, 16);
    var first = panel.querySelector('button');
    if (first) first.focus({ preventScroll: true });
  }
  function close(refocus) {
    if (!root.classList.contains('is-open')) return;
    root.classList.remove('is-shown', 'is-open');
    toggle.setAttribute('aria-expanded', 'false');
    setTimeout(function () { if (!root.classList.contains('is-open')) panel.hidden = true; }, 220);
    if (refocus) toggle.focus();
  }
  toggle.addEventListener('click', function () { root.classList.contains('is-open') ? close(false) : open(); });
  root.querySelector('[data-a11y-close]').addEventListener('click', function () { close(true); });
  document.addEventListener('keydown', function (e) {
    if (e.altKey && !e.ctrlKey && !e.metaKey && e.code === 'KeyA') {
      e.preventDefault();
      root.classList.contains('is-open') ? close(true) : open();
      return;
    }
    if (e.key !== 'Escape') return;
    if (!reader.hidden) { state.readmode = false; apply(); toggle.focus(); }
    else if (root.classList.contains('is-open')) close(true);
  });
  document.addEventListener('click', function (e) {
    if (root.classList.contains('is-open') && !root.contains(e.target)) close(false);
  });

  apply();
  ready = true;
})();
