/* SOL Assistant chat widget: AI answers first, then a live chat with the SOL team when needed.
   Config comes from SOL_CHAT (inc/chat.php); live chat endpoints are in inc/live-chat.php. */
(function () {
  'use strict';
  var C = window.SOL_CHAT;
  var root = document.querySelector('[data-sol-chat]');
  if (!C || !root) return;

  var $ = function (s) { return root.querySelector(s); };
  var panel = $('.sol-chat__panel');
  var toggle = $('[data-chat-toggle]');
  var hello = $('[data-chat-open]');
  var badge = $('[data-chat-badge]');
  var log = $('[data-chat-log]');
  var form = $('[data-chat-form]');
  var input = $('[data-chat-input]');
  var send = $('[data-chat-send]');
  var humanBtn = $('[data-chat-human]');
  var avatarEl = $('[data-chat-avatar]');
  var nameEl = $('[data-chat-name]');
  var statusEl = $('[data-chat-status] span');
  var footEl = $('[data-chat-foot]');
  var botIcon = avatarEl.innerHTML;
  var footHtml = footEl.innerHTML;
  var placeholder = input.getAttribute('placeholder');

  var KEY = 'sol-chat-v2';
  var LIVE_KEY = 'sol-live-v1';
  var ME_KEY = 'sol-chat-me';
  var WAIT_NOTE_AFTER = 120000; // ms before the "thanks for waiting" note
  // Message roles: user, assistant, agent (team member), note (system line), card (handoff or booking form).
  var messages = [];
  var live = null; // { id, token, after, status, agent, offline, started, waited }
  var busy = false;
  var pollTimer = null;
  var lastTyped = 0;
  var unread = 0;

  var store = {
    get: function (k) { try { return JSON.parse(sessionStorage.getItem(k)); } catch (e) { return null; } },
    set: function (k, v) { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
    del: function (k) { try { sessionStorage.removeItem(k); } catch (e) {} }
  };
  // The live chat survives new tabs and reloads for 12 hours.
  var lstore = {
    get: function (k) { try { var v = JSON.parse(localStorage.getItem(k)); return v && v.until > Date.now() ? v : null; } catch (e) { return null; } },
    set: function (k, v) { try { v.until = Date.now() + 12 * 3600000; localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
    del: function (k) { try { localStorage.removeItem(k); } catch (e) {} }
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

  /* ---------- Small DOM helpers ---------- */
  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }
  function field(label, name, type, opts) {
    opts = opts || {};
    var wrap = el('label', 'sol-chat__field');
    wrap.appendChild(el('span', '', label + (opts.required ? ' *' : '')));
    var f;
    if (type === 'select') {
      f = el('select');
      (opts.options || []).forEach(function (o) {
        var op = el('option', '', o[1]);
        op.value = o[0];
        f.appendChild(op);
      });
    } else if (type === 'textarea') {
      f = el('textarea');
      f.rows = 2;
    } else {
      f = el('input');
      f.type = type;
    }
    f.name = name;
    if (opts.required) f.required = true;
    if (opts.value) f.value = opts.value;
    if (opts.min) f.min = opts.min;
    if (opts.max) f.maxLength = opts.max;
    if (opts.auto) f.autocomplete = opts.auto;
    if (opts.placeholder) f.placeholder = opts.placeholder;
    wrap.appendChild(f);
    return wrap;
  }
  function first(name) { return String(name || '').trim().split(/\s+/)[0]; }
  // The visitor's name, email and phone, remembered on this device to pre-fill the chat forms.
  function me() { try { return JSON.parse(localStorage.getItem(ME_KEY)) || {}; } catch (e) { return {}; } }
  function remember(p) { try { localStorage.setItem(ME_KEY, JSON.stringify(p)); } catch (e) {} }
  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body)
    }).then(function (r) {
      return r.json().catch(function () { return {}; }).then(function (d) { return { ok: r.ok, status: r.status, d: d || {} }; });
    }, function () { return { ok: false, status: 0, d: {} }; });
  }

  /* ---------- Rendering ---------- */
  function bubble(role, content, isError) {
    var row = el('div', 'sol-chat__msg sol-chat__msg--' + (role === 'user' ? 'user' : 'bot') + (isError ? ' is-error' : ''));
    var b = el('div', 'sol-chat__bubble');
    if (role === 'user') b.textContent = content; else b.innerHTML = markdown(content);
    row.appendChild(b);
    return row;
  }
  function agentBubble(m) {
    var row = el('div', 'sol-chat__msg sol-chat__msg--bot sol-chat__msg--agent');
    var col = el('div', 'sol-chat__agent');
    col.appendChild(el('span', 'sol-chat__agent-name', (m.name || 'SOL team') + ' · SOL team'));
    var b = el('div', 'sol-chat__bubble');
    b.textContent = m.content;
    col.appendChild(b);
    row.appendChild(col);
    return row;
  }
  function msgNode(m) {
    if (m.role === 'card') return m.card === 'book' ? bookCard() : handoffCard();
    if (m.role === 'note') return el('p', 'sol-chat__note', m.content);
    if (m.role === 'agent') return agentBubble(m);
    return bubble(m.role, m.content, m.isError);
  }
  function quickReplies(list) {
    var wrap = el('div', 'sol-chat__quick');
    list.forEach(function (q) {
      var b = el('button', 'sol-chat__qr', q);
      b.type = 'button';
      b.addEventListener('click', function () { pick(q); });
      wrap.appendChild(b);
    });
    return wrap;
  }
  function render() {
    log.innerHTML = '';
    log.appendChild(bubble('assistant', C.greeting));
    if (!messages.length && C.questions && C.questions.length) {
      var wrap = el('div', 'sol-chat__chips');
      wrap.appendChild(el('p', 'sol-chat__chips-label', 'Choose an option'));
      C.questions.forEach(function (q) {
        var btn = el('button', 'sol-chat__chip', q);
        btn.type = 'button';
        btn.addEventListener('click', function () { ask(q); });
        wrap.appendChild(btn);
      });
      log.appendChild(wrap);
    }
    messages.forEach(function (m) { log.appendChild(msgNode(m)); });
    var last = messages[messages.length - 1];
    if (!live && last && last.role === 'assistant' && last.options && last.options.length) {
      log.appendChild(quickReplies(last.options));
    }
    scroll();
  }
  function add(m) {
    var q = log.querySelector('.sol-chat__quick');
    if (q) q.remove();
    var chips = log.querySelector('.sol-chat__chips');
    if (chips) chips.remove();
    messages.push(m);
    log.appendChild(msgNode(m));
    if (!live && m.role === 'assistant' && m.options && m.options.length) log.appendChild(quickReplies(m.options));
    save();
    scroll();
  }
  function removeCards(kind) {
    var before = messages.length;
    messages = messages.filter(function (m) { return !(m.role === 'card' && (!kind || m.card === kind)); });
    if (messages.length !== before) { save(); render(); }
  }
  function scroll() { log.scrollTop = log.scrollHeight; }
  function typing(on, label) {
    var t = log.querySelector('.sol-chat__typing');
    if (on && !t) {
      t = el('div', 'sol-chat__msg sol-chat__msg--bot sol-chat__typing');
      t.innerHTML = '<div class="sol-chat__bubble" aria-label="' + esc(label || 'Typing') + '"><i></i><i></i><i></i></div>';
      log.appendChild(t);
      scroll();
    } else if (!on && t) {
      t.remove();
    }
  }
  function save() { store.set(KEY, { messages: messages.slice(-40) }); }

  /* ---------- Header and mode ---------- */
  function paintMode() {
    root.classList.toggle('is-live', !!live);
    if (humanBtn) humanBtn.disabled = !!live;
    if (!live) {
      avatarEl.innerHTML = botIcon;
      nameEl.textContent = C.name;
      statusEl.textContent = 'Online and ready to help';
      footEl.innerHTML = footHtml;
      input.setAttribute('placeholder', placeholder);
      return;
    }
    var a = live.agent;
    if (a) {
      avatarEl.innerHTML = '';
      var img = el('img');
      img.src = a.avatar; img.alt = '';
      avatarEl.appendChild(img);
      nameEl.textContent = a.name;
      statusEl.textContent = 'SOL team · here to help';
    } else {
      avatarEl.innerHTML = botIcon;
      nameEl.textContent = 'SOL team';
      statusEl.textContent = live.offline ? "Offline · we'll reply by email" : 'Connecting you to the team…';
    }
    footEl.textContent = "You're chatting with the SOL team. Type below to reply.";
    input.setAttribute('placeholder', 'Message the team…');
  }

  /* ---------- AI chat ---------- */
  function ask(text) {
    text = String(text || '').trim();
    if (!text || busy) return;
    if (live) { liveSend(text); return; }
    add({ role: 'user', content: text });
    input.value = '';
    grow();
    setBusy(true);
    typing(true);

    var history = messages.filter(function (m) { return (m.role === 'user' || m.role === 'assistant') && !m.isError; })
      .map(function (m) { return { role: m.role, content: m.content }; }).slice(-12);

    post(C.endpoint, { messages: history }).then(function (res) {
      typing(false);
      if (res.ok && res.d.reply) {
        add({ role: 'assistant', content: res.d.reply, options: res.d.options || [] });
        if (res.d.action === 'book') { removeCards('book'); add({ role: 'card', card: 'book' }); }
        if (res.d.action === 'handoff' && C.live) { removeCards('handoff'); add({ role: 'card', card: 'handoff' }); }
        notifyClosed();
      } else {
        add({ role: 'assistant', content: res.d.message || C.error, isError: true });
      }
      setBusy(false);
    });
  }
  // A quick reply: "talk to the team" opens the handover card straight away.
  function pick(text) {
    if (C.live && /\b(talk|speak|chat)\b.*\b(team|person|human|someone|agent|staff)\b|\b(human|real person)\b/i.test(text)) {
      add({ role: 'user', content: text });
      openHandoff();
      return;
    }
    ask(text);
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
  function notifyClosed() {
    if (panel.hidden) { unread++; badge.textContent = unread; badge.hidden = false; }
  }

  /* ---------- Booking card ---------- */
  function guessService() {
    var text = messages.filter(function (m) { return m.role === 'user'; }).map(function (m) { return m.content; }).join(' ').toLowerCase();
    var rules = [
      [/web ?site|web design|landing page/, 'website_development'], [/\bseo\b|search engine|rank/, 'seo'],
      [/google ads|adwords|ppc/, 'google_ads'], [/social media|instagram|facebook|tiktok/, 'social_media'],
      [/payroll/, 'payroll'], [/bookkeep|\bbas\b|xero|myob/, 'bookkeeping_bas'], [/loan/, 'loan_support'],
      [/offshore|outsourc|virtual assistant/, 'offshore_staff'], [/back.?office|admin support/, 'admin_support'],
      [/automation|software|crm/, 'software_automation'], [/machine learning|\bai\b|annotation/, 'ai_machine_learning'],
      [/train|course|support coordinat/, 'support_coordination_training'], [/ndis/, 'ndis_registration'],
      [/abn|register (a |my )?(company|business)|company registration|business name/, 'company_registration'],
      [/partnership/, 'accountancy_partnerships'], [/account|tax/, 'accountancy']
    ];
    for (var i = 0; i < rules.length; i++) if (rules[i][0].test(text) && C.services[rules[i][1]]) return rules[i][1];
    return '';
  }
  function bookCard() {
    var p = me();
    var card = el('form', 'sol-chat__card');
    card.noValidate = true;
    card.appendChild(el('p', 'sol-chat__card-title', 'Book a free consultation'));
    card.appendChild(el('p', 'sol-chat__card-sub', "We'll confirm a time by email within one business day."));
    var services = [['', 'Choose a service…']];
    Object.keys(C.services || {}).forEach(function (k) { services.push([k, C.services[k]]); });
    services.push(['general_enquiry', 'Other / Multiple Services']);
    var times = [['', 'Any time']].concat((C.times || []).map(function (t) { return [t, t]; }));
    var grid = el('div', 'sol-chat__grid');
    grid.appendChild(field('Full name', 'name', 'text', { required: true, value: p.name, auto: 'name', max: 100 }));
    grid.appendChild(field('Email', 'email', 'email', { required: true, value: p.email, auto: 'email', max: 150 }));
    grid.appendChild(field('Phone', 'phone', 'tel', { value: p.phone, auto: 'tel', max: 20 }));
    var svc = field('Service', 'service', 'select', { required: true, options: services });
    svc.querySelector('select').value = guessService();
    grid.appendChild(svc);
    grid.appendChild(field('Preferred date', 'date', 'date', { min: C.today }));
    grid.appendChild(field('Preferred time', 'time', 'select', { options: times }));
    card.appendChild(grid);
    card.appendChild(field('Anything we should know? (optional)', 'notes', 'textarea', { max: 1000 }));
    var err = el('p', 'sol-chat__card-error'); err.hidden = true;
    card.appendChild(err);
    var actions = el('div', 'sol-chat__card-actions');
    var go = el('button', 'sol-chat__card-btn', 'Request consultation'); go.type = 'submit';
    var no = el('button', 'sol-chat__card-link', 'Not now'); no.type = 'button';
    actions.appendChild(go); actions.appendChild(no);
    card.appendChild(actions);
    no.addEventListener('click', function () { removeCards('book'); });
    card.addEventListener('submit', function (e) {
      e.preventDefault();
      var f = card.elements;
      var data = { name: f.name.value.trim(), email: f.email.value.trim(), phone: f.phone.value.trim(), service: f.service.value,
        date: f.date.value, time: f.time.value, notes: f.notes.value.trim(), page: location.href };
      var problem = data.name.length < 2 ? 'Please enter your full name.' : !/^\S+@\S+\.\S+$/.test(data.email) ? 'Please enter a valid email address.'
        : !data.service ? 'Please choose a service.' : '';
      if (problem) { err.textContent = problem; err.hidden = false; return; }
      err.hidden = true; go.disabled = true; go.textContent = 'Sending…';
      post(C.bookApi, data).then(function (res) {
        if (res.ok) {
          remember({ name: data.name, email: data.email, phone: data.phone });
          removeCards('book');
          add({ role: 'assistant', content: res.d.message, options: ['Ask another question'] });
        } else {
          err.textContent = res.d.message || 'Sorry, that didn’t go through. Please try again or call us.';
          err.hidden = false; go.disabled = false; go.textContent = 'Request consultation';
        }
      });
    });
    return card;
  }

  /* ---------- Handover card ---------- */
  function openHandoff() {
    if (live) { input.focus(); return; }
    if (!C.live) return;
    removeCards('handoff');
    add({ role: 'card', card: 'handoff' });
    if (!root.classList.contains('is-open')) open();
  }
  function handoffCard() {
    var p = me();
    var card = el('form', 'sol-chat__card sol-chat__card--team');
    card.noValidate = true;
    card.appendChild(el('p', 'sol-chat__card-title', 'Talk to the SOL team'));
    var status = el('div', 'sol-chat__team', 'Checking who’s online…');
    card.appendChild(status);
    var grid = el('div', 'sol-chat__grid');
    grid.appendChild(field('Your name', 'name', 'text', { required: true, value: p.name, auto: 'name', max: 80 }));
    grid.appendChild(field('Email', 'email', 'email', { required: true, value: p.email, auto: 'email', max: 150 }));
    card.appendChild(grid);
    card.appendChild(field('Phone (optional)', 'phone', 'tel', { value: p.phone, auto: 'tel', max: 20 }));
    card.appendChild(field('How can we help? (optional)', 'message', 'textarea', { max: 2000 }));
    var err = el('p', 'sol-chat__card-error'); err.hidden = true;
    card.appendChild(err);
    var actions = el('div', 'sol-chat__card-actions');
    var go = el('button', 'sol-chat__card-btn', 'Connect me'); go.type = 'submit';
    var no = el('button', 'sol-chat__card-link', 'Keep chatting with the assistant'); no.type = 'button';
    actions.appendChild(go); actions.appendChild(no);
    card.appendChild(actions);
    no.addEventListener('click', function () { removeCards('handoff'); });

    post(C.liveApi + 'status', {}).then(function (res) {
      status.textContent = '';
      if (res.ok && res.d.online) {
        var faces = el('span', 'sol-chat__faces');
        (res.d.agents || []).forEach(function (a) { var i = el('img'); i.src = a.avatar; i.alt = ''; faces.appendChild(i); });
        status.appendChild(faces);
        var names = (res.d.agents || []).map(function (a) { return a.name; }).join(', ');
        status.appendChild(el('span', 'sol-chat__team-text', '● Online now' + (names ? ': ' + names : '') + '. Usually replies in a few minutes.'));
        status.classList.add('is-online');
      } else {
        status.appendChild(el('span', 'sol-chat__team-text', "Our team is offline right now (" + (res.d.hours || C.hours) + "). Leave your details and we'll reply by email or phone."));
        go.textContent = 'Send to the team';
      }
    });

    card.addEventListener('submit', function (e) {
      e.preventDefault();
      var f = card.elements;
      var data = { name: f.name.value.trim(), email: f.email.value.trim(), phone: f.phone.value.trim(), message: f.message.value.trim() };
      var problem = data.name.length < 2 ? 'Please enter your name.' : !/^\S+@\S+\.\S+$/.test(data.email) ? 'Please enter a valid email address.' : '';
      if (problem) { err.textContent = problem; err.hidden = false; return; }
      err.hidden = true; go.disabled = true; go.textContent = 'Connecting…';
      data.page = location.href;
      data.transcript = messages.filter(function (m) { return (m.role === 'user' || m.role === 'assistant') && !m.isError; })
        .map(function (m) { return { role: m.role, content: m.content }; }).slice(-16);
      post(C.liveApi + 'start', data).then(function (res) {
        if (!res.ok || !res.d.id) {
          err.textContent = res.d.message || 'Sorry, we couldn’t connect you. Please try again or call us.';
          err.hidden = false; go.disabled = false; go.textContent = 'Connect me';
          return;
        }
        remember({ name: data.name, email: data.email, phone: data.phone });
        removeCards('handoff');
        var after = 0;
        (res.d.messages || []).forEach(function (m) { after = Math.max(after, m.id); });
        live = { id: res.d.id, token: res.d.token, after: after, status: 'waiting', agent: null, offline: !!res.d.offline, started: Date.now(), waited: false };
        saveLive();
        paintMode();
        if (data.message) add({ role: 'user', content: data.message });
        add({ role: 'note', content: live.offline
          ? 'Thanks ' + first(data.name) + '. Our team is offline, so we’ve emailed them your details. We’ll reply by email or phone within one business day. If someone comes online sooner, they’ll reply here.'
          : 'Thanks ' + first(data.name) + '. We’ve let the team know. Someone will join this chat shortly.' });
        poll();
      });
    });
    return card;
  }

  /* ---------- Live chat ---------- */
  function saveLive() { if (live) lstore.set(LIVE_KEY, live); else lstore.del(LIVE_KEY); }
  function schedule() {
    clearTimeout(pollTimer);
    if (!live) return;
    var active = root.classList.contains('is-open') && !document.hidden;
    var wait = live.offline && !live.agent ? 15000 : active ? 2500 : 9000;
    pollTimer = setTimeout(poll, wait);
  }
  function poll(full) {
    clearTimeout(pollTimer);
    if (!live) return;
    var me = live;
    post(C.liveApi + 'poll', { id: me.id, token: me.token, after: full ? 0 : me.after, typing: Date.now() - lastTyped < 4000 }).then(function (res) {
      if (live !== me) return;
      if (!res.ok) {
        if (res.status === 404) { endLive('This chat has ended.'); return; }
        schedule();
        return;
      }
      var d = res.d;
      if (d.agent && (!me.agent || me.agent.name !== d.agent.name)) { me.agent = d.agent; paintMode(); }
      me.offline = !!d.offline;
      typing(false);
      (d.messages || []).forEach(function (m) {
        if (!full && m.id <= me.after) return; // already shown by an overlapping poll
        me.after = Math.max(me.after, m.id);
        if (m.role === 'agent') {
          add({ role: 'agent', content: m.text, name: m.name });
          notifyClosed();
        } else if (m.role === 'note') {
          add({ role: 'note', content: m.text });
        } else if (full) {
          add({ role: m.role === 'visitor' ? 'user' : 'assistant', content: m.text });
        }
      });
      if (d.typing) typing(true, (me.agent ? me.agent.name : 'The team') + ' is typing');
      if (d.status === 'live') me.status = 'live';
      if (d.status === 'closed') {
        endLive(null);
        return;
      }
      if (!me.waited && !me.offline && me.status === 'waiting' && Date.now() - me.started > WAIT_NOTE_AFTER) {
        me.waited = true;
        add({ role: 'note', content: 'Thanks for waiting. The team is helping other customers right now. Stay here, or close the chat: we have your details and will email you if we miss you.' });
      }
      saveLive();
      schedule();
    });
  }
  function liveSend(text) {
    add({ role: 'user', content: text });
    input.value = '';
    grow();
    var me = live;
    post(C.liveApi + 'send', { id: me.id, token: me.token, text: text }).then(function (res) {
      if (live !== me) return;
      if (!res.ok) {
        if (res.status === 404 || res.status === 409) { endLive('This chat has ended. Your last message was not sent.'); return; }
        add({ role: 'assistant', content: res.d.message || 'Sorry, your message wasn’t sent. Please try again.', isError: true });
        return;
      }
      poll(); // our own message comes back here too; poll() skips visitor messages
    });
  }
  function endLive(note) {
    clearTimeout(pollTimer);
    typing(false);
    live = null;
    saveLive();
    paintMode();
    if (note) add({ role: 'note', content: note });
    add({ role: 'assistant', content: "You're back with the " + C.name + '. Is there anything else I can help with?' });
  }

  /* ---------- Open / close ---------- */
  function open() {
    panel.hidden = false;
    hello.hidden = true;
    badge.hidden = true;
    unread = 0;
    root.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    store.set('sol-chat-hello', 1);
    setTimeout(function () { root.classList.add('is-shown'); scroll(); }, 16);
    if (window.matchMedia('(min-width: 640px)').matches) input.focus();
    if (live) poll();
  }
  function close() {
    root.classList.remove('is-shown', 'is-open');
    toggle.setAttribute('aria-expanded', 'false');
    setTimeout(function () { if (!root.classList.contains('is-open')) panel.hidden = true; }, 220);
    toggle.focus();
  }

  toggle.addEventListener('click', function () { root.classList.contains('is-open') ? close() : open(); });
  hello.addEventListener('click', open);
  $('[data-chat-close]').addEventListener('click', close);
  if (humanBtn) humanBtn.addEventListener('click', openHandoff);
  // "Book Consultation" opens the booking form inside the chat (the link still works without JavaScript).
  var bookLink = $('[data-chat-book]');
  if (bookLink) bookLink.addEventListener('click', function (e) {
    if (live || e.ctrlKey || e.metaKey || e.shiftKey) return;
    e.preventDefault();
    removeCards('book');
    add({ role: 'card', card: 'book' });
  });
  $('[data-chat-reset]').addEventListener('click', function () {
    if (busy) return;
    if (live) {
      if (!window.confirm('End the chat with the SOL team?')) return;
      post(C.liveApi + 'end', { id: live.id, token: live.token });
      endLive('You ended the chat with the team.');
      return;
    }
    messages = [];
    store.del(KEY);
    render();
    input.focus();
  });
  form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
  input.addEventListener('input', function () { grow(); if (live) lastTyped = Date.now(); });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); ask(input.value); }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && root.classList.contains('is-open')) close();
  });
  document.addEventListener('visibilitychange', function () { if (live && !document.hidden) poll(); });

  // Restore this visit's conversation, and any live chat (also from another tab).
  var saved = store.get(KEY);
  if (saved && Array.isArray(saved.messages)) messages = saved.messages;
  live = C.live ? lstore.get(LIVE_KEY) : null;
  render();
  paintMode();
  if (live) poll(!messages.length);

  // A friendly "Hi, I'm online" nudge after a few seconds, once per visit.
  if (!store.get('sol-chat-hello')) {
    setTimeout(function () {
      if (!root.classList.contains('is-open')) { hello.hidden = false; }
    }, 4000);
  }
})();
