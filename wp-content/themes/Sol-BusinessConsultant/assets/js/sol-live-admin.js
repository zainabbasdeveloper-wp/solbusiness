/* Live Chat inbox (WordPress admin → Live Chat). Config: SOL_LIVE (inc/live-chat.php). */
(function () {
  'use strict';
  var L = window.SOL_LIVE;
  var wrap = document.querySelector('.sol-live');
  if (!L || !wrap) return;

  var listEl = wrap.querySelector('[data-live-list]');
  var convEl = wrap.querySelector('[data-live-conv]');
  var availEl = wrap.querySelector('[data-live-avail]');
  var onlineEl = wrap.querySelector('[data-live-online]');
  var notifyBtn = wrap.querySelector('[data-live-notify]');
  var placeholderHtml = convEl.innerHTML;
  var baseTitle = document.title;

  var chats = [];
  var known = null; // ids of waiting chats and unread counts last time, to spot new ones
  var cur = null;   // { id, after, chat, timer, typedAt, polling }
  var listTimer = null;
  var flashTimer = null;
  var availChanged = 0; // ignore list replies that started before the last availability change

  function api(path, body) {
    return fetch(L.api + path, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': L.nonce },
      credentials: 'same-origin',
      body: JSON.stringify(body || {})
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, status: r.status, d: d || {} }; });
    }, function () { return { ok: false, status: 0, d: {} }; });
  }
  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }
  function ago(ts) {
    var s = Math.max(0, Math.floor(Date.now() / 1000 - ts));
    if (s < 60) return 'just now';
    if (s < 3600) return Math.floor(s / 60) + ' min ago';
    if (s < 86400) return Math.floor(s / 3600) + ' h ago';
    return new Date(ts * 1000).toLocaleDateString();
  }
  function clock(ts) {
    return new Date(ts * 1000).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
  }
  var STATUS = { waiting: 'Waiting', live: 'Active', closed: 'Ended' };

  /* ---------- Alerts ---------- */
  function beep() {
    try {
      var a = new (window.AudioContext || window.webkitAudioContext)();
      [0, 0.18].forEach(function (t) {
        var o = a.createOscillator(), g = a.createGain();
        o.connect(g); g.connect(a.destination);
        o.frequency.value = 880; g.gain.value = 0.08;
        o.start(a.currentTime + t); o.stop(a.currentTime + t + 0.12);
      });
    } catch (e) {}
  }
  function alertNew(text) {
    beep();
    if (document.hidden) {
      clearInterval(flashTimer);
      var on = false;
      flashTimer = setInterval(function () { document.title = (on = !on) ? '💬 ' + text : baseTitle; }, 1000);
      if (window.Notification && Notification.permission === 'granted') {
        try { new Notification('SOL Live Chat', { body: text }); } catch (e) {}
      }
    }
  }
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { clearInterval(flashTimer); document.title = baseTitle; }
  });
  if (window.Notification && Notification.permission === 'default') {
    notifyBtn.hidden = false;
    notifyBtn.addEventListener('click', function () {
      Notification.requestPermission().then(function () { notifyBtn.hidden = true; });
    });
  }

  /* ---------- Chat list ---------- */
  function loadList() {
    clearTimeout(listTimer);
    api('chats').then(function (res) {
      if (res.ok) {
        chats = res.d.chats || [];
        if (Date.now() - availChanged > 6000) availEl.checked = !res.d.away;
        onlineEl.textContent = res.d.online ? '● ' + res.d.online + ' team member' + (res.d.online > 1 ? 's' : '') + ' online' : '○ No one online: visitors can leave a message';
        onlineEl.classList.toggle('is-on', !!res.d.online);
        spotNew();
        renderList();
      } else if (res.status === 401 || res.status === 403) {
        listEl.innerHTML = '<p class="sol-live__empty">Your login has expired. Please reload the page.</p>';
        return;
      }
      listTimer = setTimeout(loadList, 4000);
    });
  }
  function spotNew() {
    var now = {};
    chats.forEach(function (c) { now[c.id] = { waiting: c.status === 'waiting', unread: c.unread }; });
    if (known) {
      chats.forEach(function (c) {
        var was = known[c.id];
        if (c.status === 'waiting' && (!was || !was.waiting)) alertNew(c.name + ' is waiting in the chat');
        else if (was && c.unread > was.unread && (!cur || cur.id !== c.id || document.hidden)) alertNew('New message from ' + c.name);
      });
    }
    known = now;
  }
  function renderList() {
    listEl.innerHTML = '';
    var groups = [['waiting', 'Waiting for a reply'], ['live', 'Active chats'], ['closed', 'Ended']];
    var any = false;
    groups.forEach(function (g) {
      var items = chats.filter(function (c) { return c.status === g[0]; });
      if (!items.length) return;
      any = true;
      listEl.appendChild(el('h2', 'sol-live__group', g[1] + ' (' + items.length + ')'));
      items.forEach(function (c) {
        var b = el('button', 'sol-live__item is-' + c.status + (cur && cur.id === c.id ? ' is-current' : ''));
        b.type = 'button';
        var top = el('span', 'sol-live__item-top');
        var nm = el('strong', '', c.name);
        if (c.here && c.status !== 'closed') nm.appendChild(el('i', 'sol-live__here', ''));
        top.appendChild(nm);
        top.appendChild(el('span', 'sol-live__time', c.last ? ago(c.last.at) : ago(c.started)));
        b.appendChild(top);
        var prev = c.last ? (c.last.role === 'agent' ? 'You: ' : c.last.role === 'bot' ? 'AI: ' : '') + c.last.text : '';
        b.appendChild(el('span', 'sol-live__preview', prev));
        var meta = el('span', 'sol-live__meta');
        meta.appendChild(el('span', 'sol-live__pill is-' + c.status, c.offline && c.status === 'waiting' ? 'Left a message' : STATUS[c.status]));
        if (c.agent) meta.appendChild(el('span', '', c.agent.name));
        if (c.unread && (!cur || cur.id !== c.id)) meta.appendChild(el('span', 'sol-live__unread', String(c.unread)));
        b.appendChild(meta);
        b.addEventListener('click', function () { openChat(c.id); });
        listEl.appendChild(b);
      });
    });
    if (!any) listEl.innerHTML = '<p class="sol-live__empty">No chats yet. When a visitor asks to talk to a person, they appear here.</p>';
  }

  /* ---------- One conversation ---------- */
  function openChat(id) {
    if (cur) clearTimeout(cur.timer);
    cur = { id: id, after: 0, chat: null, timer: null, typedAt: 0, polling: false };
    convEl.innerHTML = '';
    var head = el('div', 'sol-live__head');
    var log = el('div', 'sol-live__log');
    var typing = el('p', 'sol-live__typing', 'Visitor is typing…');
    typing.hidden = true;
    var form = el('form', 'sol-live__compose');
    var ta = el('textarea');
    ta.rows = 2;
    ta.placeholder = 'Type your reply… (Enter to send, Shift + Enter for a new line)';
    var sendBtn = el('button', 'button button-primary', 'Send');
    sendBtn.type = 'submit';
    form.appendChild(ta);
    form.appendChild(sendBtn);
    convEl.appendChild(head);
    convEl.appendChild(log);
    convEl.appendChild(typing);
    convEl.appendChild(form);
    cur.els = { head: head, log: log, typing: typing, form: form, ta: ta, send: sendBtn };

    ta.addEventListener('input', function () { cur.typedAt = Date.now(); });
    ta.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit')); }
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var text = ta.value.trim();
      if (!text || !cur || cur.id !== id) return;
      sendBtn.disabled = true;
      api('send', { id: id, text: text }).then(function (res) {
        sendBtn.disabled = false;
        if (res.ok) { ta.value = ''; pollChat(); loadList(); } else { window.alert(res.d.message || 'Not sent. Please try again.'); }
        ta.focus();
      });
    });
    renderList();
    pollChat();
    if (history.replaceState) history.replaceState(null, '', location.pathname + '?page=sol-live-chat&chat=' + id);
  }
  function renderHead() {
    var c = cur.chat, h = cur.els.head;
    h.innerHTML = '';
    var who = el('div', 'sol-live__who');
    who.appendChild(el('h2', '', c.name));
    var info = el('p', 'sol-live__info');
    if (c.email) { var a = el('a', '', c.email); a.href = 'mailto:' + c.email; info.appendChild(a); }
    if (c.phone) { var t = el('a', '', c.phone); t.href = 'tel:' + c.phone.replace(/[^0-9+]/g, ''); info.appendChild(t); }
    if (c.page) { var p = el('a', '', 'Page: ' + c.page.replace(/^https?:\/\/[^/]+/, '')); p.href = c.page; p.target = '_blank'; p.rel = 'noopener'; info.appendChild(p); }
    who.appendChild(info);
    var st = el('p', 'sol-live__state');
    st.appendChild(el('span', 'sol-live__pill is-' + c.status, STATUS[c.status]));
    st.appendChild(document.createTextNode(c.status === 'closed' ? '' : c.here ? ' Visitor is on the site' : ' Visitor has left the page or closed the chat'));
    who.appendChild(st);
    h.appendChild(who);
    var tools = el('div', 'sol-live__tools');
    if (c.status === 'waiting') {
      var join = el('button', 'button button-primary', 'Join chat');
      join.type = 'button';
      join.addEventListener('click', function () { api('join', { id: c.id }).then(function () { pollChat(); loadList(); }); });
      tools.appendChild(join);
    }
    if (c.status !== 'closed') {
      var end = el('button', 'button', 'End chat');
      end.type = 'button';
      end.addEventListener('click', function () {
        if (window.confirm('End this chat? The visitor will see that the chat has ended.')) api('close', { id: c.id }).then(function () { pollChat(); loadList(); });
      });
      tools.appendChild(end);
    } else if (c.email) {
      var mail = el('a', 'button', 'Reply by email');
      mail.href = 'mailto:' + c.email + '?subject=' + encodeURIComponent('Your chat with SOL Business Consultant');
      tools.appendChild(mail);
    }
    h.appendChild(tools);
    var closed = c.status === 'closed';
    cur.els.ta.disabled = closed;
    cur.els.send.disabled = closed;
    cur.els.ta.placeholder = closed ? 'This chat has ended. Use “Reply by email” to follow up.' : 'Type your reply… (Enter to send, Shift + Enter for a new line)';
  }
  function addMsg(m) {
    var log = cur.els.log;
    if (m.role === 'note') { log.appendChild(el('p', 'sol-live__note', m.text + ' · ' + clock(m.at))); return; }
    var row = el('div', 'sol-live__msg is-' + m.role);
    var label = m.role === 'bot' ? 'AI assistant' : m.name || (m.role === 'agent' ? 'Team' : 'Visitor');
    row.appendChild(el('span', 'sol-live__by', label + ' · ' + clock(m.at)));
    row.appendChild(el('div', 'sol-live__bubble', m.role === 'bot' ? m.text.replace(/\*\*([^*]+)\*\*/g, '$1').replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, '$1 ($2)') : m.text));
    log.appendChild(row);
  }
  function pollChat() {
    if (!cur) return;
    var me = cur;
    clearTimeout(me.timer);
    if (me.polling) { me.again = true; return; }
    me.polling = true;
    api('chat', { id: me.id, after: me.after, typing: Date.now() - me.typedAt < 4000 }).then(function (res) {
      me.polling = false;
      if (cur !== me) return;
      if (res.ok) {
        var log = me.els.log;
        var atBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 60;
        var prevStatus = me.chat ? me.chat.status + me.chat.here + (me.chat.agent ? me.chat.agent.id : '') : '';
        me.chat = res.d.chat;
        if (prevStatus !== me.chat.status + me.chat.here + (me.chat.agent ? me.chat.agent.id : '')) renderHead();
        (res.d.messages || []).forEach(function (m) {
          if (m.id <= me.after) return;
          me.after = m.id;
          addMsg(m);
        });
        if ((res.d.messages || []).length && (atBottom || me.after === res.d.messages[res.d.messages.length - 1].id)) log.scrollTop = log.scrollHeight;
        me.els.typing.hidden = !res.d.typing;
      } else if (res.status === 404) {
        convEl.innerHTML = placeholderHtml;
        cur = null;
        return;
      }
      if (me.again) { me.again = false; pollChat(); return; }
      me.timer = setTimeout(pollChat, document.hidden ? 6000 : 2500);
    });
  }

  availEl.addEventListener('change', function () {
    availChanged = Date.now();
    api('away', { away: !availEl.checked }).then(loadList);
  });

  loadList();
  if (+L.open) openChat(+L.open); // wp_localize_script sends numbers as strings
})();
