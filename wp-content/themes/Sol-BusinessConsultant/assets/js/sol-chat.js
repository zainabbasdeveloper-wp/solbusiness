/* SOL Assistant chat widget. Config comes from SOL_CHAT (inc/chat.php). */
(function () {
  'use strict';
  var C = window.SOL_CHAT;
  var root = document.querySelector('[data-sol-chat]');
  if (!C || !root) return;

  var panel = root.querySelector('.sol-chat__panel');
  var toggle = root.querySelector('[data-chat-toggle]');
  var hello = root.querySelector('[data-chat-open]');
  var badge = root.querySelector('[data-chat-badge]');
  var log = root.querySelector('[data-chat-log]');
  var form = root.querySelector('[data-chat-form]');
  var input = root.querySelector('[data-chat-input]');
  var send = root.querySelector('[data-chat-send]');
  var KEY = 'sol-chat-v1';
  var messages = [];
  var busy = false;

  // The conversation survives page changes within the visit.
  var store = {
    get: function (k) { try { return JSON.parse(sessionStorage.getItem(k)); } catch (e) { return null; } },
    set: function (k, v) { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
    del: function (k) { try { sessionStorage.removeItem(k); } catch (e) {} }
  };

  /* ---------- Safe, small Markdown: links, bold, italics, lists, paragraphs ---------- */
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function safeUrl(u) {
    u = u.replace(/&amp;/g, '&');
    if (/^(https?:\/\/|\/|mailto:|tel:)/i.test(u)) return u;
    return '';
  }
  function linkTag(url, label) {
    var u = safeUrl(url);
    if (!u) return label;
    var external = /^https?:\/\//i.test(u) && u.indexOf(location.host) === -1;
    return '<a href="' + esc(u) + '"' + (external ? ' target="_blank" rel="noopener noreferrer"' : '') + '>' + label + '</a>';
  }
  function inline(s) {
    var out = esc(s);
    var keep = [];
    out = out.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, function (m, label, url) {
      keep.push(linkTag(url, label));
      return '\u0000' + (keep.length - 1) + '\u0000';
    });
    out = out.replace(/(https?:\/\/[^\s<]+[^\s<.,;:!?)])/g, function (m, url) {
      keep.push(linkTag(url, url.replace(/^https?:\/\/(www\.)?/, '')));
      return '\u0000' + (keep.length - 1) + '\u0000';
    });
    out = out.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/(^|[^*])\*([^*\s][^*]*)\*/g, '$1<em>$2</em>')
      .replace(/`([^`]+)`/g, '<code>$1</code>');
    return out.replace(/\u0000(\d+)\u0000/g, function (m, i) { return keep[+i]; });
  }
  function markdown(text) {
    var lines = String(text).replace(/\r/g, '').split('\n');
    var html = '';
    var list = null;
    var para = [];
    function flushPara() { if (para.length) { html += '<p>' + para.map(inline).join('<br>') + '</p>'; para = []; } }
    function flushList() { if (list) { html += '</' + list + '>'; list = null; } }
    lines.forEach(function (raw) {
      var line = raw.trim();
      var ul = line.match(/^[-*•]\s+(.*)$/);
      var ol = line.match(/^\d+[.)]\s+(.*)$/);
      if (ul || ol) {
        flushPara();
        var type = ul ? 'ul' : 'ol';
        if (list !== type) { flushList(); html += '<' + type + '>'; list = type; }
        html += '<li>' + inline((ul || ol)[1]) + '</li>';
      } else if (!line) {
        flushPara(); flushList();
      } else {
        flushList();
        para.push(line.replace(/^#{1,6}\s+/, ''));
      }
    });
    flushPara(); flushList();
    return html;
  }

  /* ---------- Rendering ---------- */
  function bubble(role, content, isError) {
    var row = document.createElement('div');
    row.className = 'sol-chat__msg sol-chat__msg--' + (role === 'user' ? 'user' : 'bot') + (isError ? ' is-error' : '');
    var b = document.createElement('div');
    b.className = 'sol-chat__bubble';
    if (role === 'user') b.textContent = content; else b.innerHTML = markdown(content);
    row.appendChild(b);
    log.appendChild(row);
    return row;
  }
  function render() {
    log.innerHTML = '';
    bubble('assistant', C.greeting);
    if (!messages.length && C.questions && C.questions.length) {
      var wrap = document.createElement('div');
      wrap.className = 'sol-chat__chips';
      var label = document.createElement('p');
      label.className = 'sol-chat__chips-label';
      label.textContent = 'Choose an option';
      wrap.appendChild(label);
      C.questions.forEach(function (q) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'sol-chat__chip';
        btn.textContent = q;
        btn.addEventListener('click', function () { ask(q); });
        wrap.appendChild(btn);
      });
      log.appendChild(wrap);
    }
    messages.forEach(function (m) { bubble(m.role, m.content, m.isError); });
    scroll();
  }
  function scroll() { log.scrollTop = log.scrollHeight; }
  function typing(on) {
    var t = log.querySelector('.sol-chat__typing');
    if (on && !t) {
      t = document.createElement('div');
      t.className = 'sol-chat__msg sol-chat__msg--bot sol-chat__typing';
      t.innerHTML = '<div class="sol-chat__bubble" aria-label="Typing"><i></i><i></i><i></i></div>';
      log.appendChild(t);
      scroll();
    } else if (!on && t) {
      t.remove();
    }
  }
  function save() { store.set(KEY, { messages: messages.slice(-30) }); }

  /* ---------- Sending ---------- */
  function ask(text) {
    text = String(text || '').trim();
    if (!text || busy) return;
    var chips = log.querySelector('.sol-chat__chips');
    if (chips) chips.remove();
    messages.push({ role: 'user', content: text });
    bubble('user', text);
    save();
    input.value = '';
    grow();
    setBusy(true);
    typing(true);

    var history = messages.filter(function (m) { return !m.isError; })
      .map(function (m) { return { role: m.role, content: m.content }; }).slice(-12);

    fetch(C.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ messages: history })
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, d: d }; });
    }).then(function (res) {
      typing(false);
      if (res.ok && res.d && res.d.reply) {
        messages.push({ role: 'assistant', content: res.d.reply });
        bubble('assistant', res.d.reply);
        if (panel.hidden) { badge.hidden = false; }
      } else {
        var msg = (res.d && res.d.message) || C.error;
        messages.push({ role: 'assistant', content: msg, isError: true });
        bubble('assistant', msg, true);
      }
    }).catch(function () {
      typing(false);
      messages.push({ role: 'assistant', content: C.error, isError: true });
      bubble('assistant', C.error, true);
    }).then(function () {
      setBusy(false);
      save();
      scroll();
    });
  }
  function setBusy(on) {
    busy = on;
    root.classList.toggle('is-busy', on);
    send.disabled = on || !input.value.trim();
  }
  function grow() {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    send.disabled = busy || !input.value.trim();
  }

  /* ---------- Open / close ---------- */
  function open() {
    panel.hidden = false;
    hello.hidden = true;
    badge.hidden = true;
    root.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    store.set('sol-chat-hello', 1);
    setTimeout(function () { root.classList.add('is-shown'); scroll(); }, 16);
    if (window.matchMedia('(min-width: 640px)').matches) input.focus();
  }
  function close() {
    root.classList.remove('is-shown', 'is-open');
    toggle.setAttribute('aria-expanded', 'false');
    setTimeout(function () { if (!root.classList.contains('is-open')) panel.hidden = true; }, 220);
    toggle.focus();
  }

  toggle.addEventListener('click', function () { root.classList.contains('is-open') ? close() : open(); });
  hello.addEventListener('click', open);
  root.querySelector('[data-chat-close]').addEventListener('click', close);
  root.querySelector('[data-chat-reset]').addEventListener('click', function () {
    if (busy) return;
    messages = [];
    store.del(KEY);
    render();
    input.focus();
  });
  form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
  input.addEventListener('input', grow);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); ask(input.value); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && root.classList.contains('is-open')) close();
  });

  // Restore this visit's conversation.
  var saved = store.get(KEY);
  if (saved && Array.isArray(saved.messages)) messages = saved.messages;
  render();

  // A friendly "Hi, I'm online" nudge after a few seconds, once per visit.
  if (!store.get('sol-chat-hello')) {
    setTimeout(function () {
      if (!root.classList.contains('is-open')) { hello.hidden = false; }
    }, 4000);
  }
})();
