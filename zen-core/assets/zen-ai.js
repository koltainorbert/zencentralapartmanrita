/* Zen AI – concierge chat panel (vanilla, lusta betöltés) */
(function () {
	'use strict';
	var d = document, cfgEl = d.getElementById('zca-config');
	if (!cfgEl) { return; }
	var C = JSON.parse(cfgEl.textContent), S = C.str || {};
	function t(k) { return S[k] || k; }
	var KEY = 'zca-' + C.lang, state;
	try { state = JSON.parse(sessionStorage.getItem(KEY) || 'null'); } catch (e) { state = null; }
	if (!state) { state = { sid: Math.random().toString(36).slice(2) + Date.now().toString(36), msgs: [] }; }
	function save() { try { sessionStorage.setItem(KEY, JSON.stringify({ sid: state.sid, msgs: state.msgs.slice(-30) })); } catch (e) {} }
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	function el(tag, cls, text) { var e = d.createElement(tag); if (cls) { e.className = cls; } if (text !== undefined) { e.textContent = text; } return e; }

	/* --- Markdown-lite → biztonságos DOM (nincs innerHTML a modell szövegével) --- */
	function inline(parent, text) {
		var re = /(\*\*([^*]+)\*\*|\*([^*]+)\*|(https?:\/\/[^\s)<]+))/g, last = 0, m;
		while ((m = re.exec(text))) {
			if (m.index > last) { parent.appendChild(d.createTextNode(text.slice(last, m.index))); }
			if (m[2]) { parent.appendChild(el('strong', '', m[2])); }
			else if (m[3]) { parent.appendChild(el('em', '', m[3])); }
			else if (m[4]) { var a = el('a', '', m[4].replace(/^https?:\/\/(www\.)?/, '').slice(0, 42)); a.href = m[4]; a.target = '_blank'; a.rel = 'noopener'; parent.appendChild(a); }
			last = re.lastIndex;
		}
		if (last < text.length) { parent.appendChild(d.createTextNode(text.slice(last))); }
	}
	function render(text) {
		var box = el('div', 'zca-md'), list = null;
		String(text || '').split(/\n/).forEach(function (line) {
			var li = line.match(/^\s*(?:[-•*]|\d+[.)])\s+(.*)$/);
			if (li) { if (!list) { list = el(/^\s*\d/.test(line) ? 'ol' : 'ul'); box.appendChild(list); } var item = el('li'); inline(item, li[1]); list.appendChild(item); return; }
			list = null;
			if (!line.trim()) { return; }
			var h = line.match(/^#{1,4}\s+(.*)$/), p = el(h ? 'h5' : 'p');
			inline(p, h ? h[1] : line);
			box.appendChild(p);
		});
		return box;
	}

	/* --- Panel --- */
	var panel = el('div', 'zca'), head = el('header', 'zca__head'), body = el('div', 'zca__body'), foot = el('form', 'zca__foot');
	panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-modal', 'false'); panel.setAttribute('aria-label', C.name); panel.hidden = true;
	if (C.pos === 'left') { panel.classList.add('zca--left'); }
	var ava = el('span', 'zca__ava');
	if (C.avatar) { var im = el('img'); im.src = C.avatar; im.alt = ''; ava.appendChild(im); } else { ava.innerHTML = '<svg width="24" height="24" viewBox="0 0 32 32" fill="none" aria-hidden="true"><circle cx="16" cy="16" r="12.5" stroke="currentColor" stroke-width="2.2" stroke-dasharray="70 9" stroke-linecap="round"/><path d="M10.5 18.5c1.6 2 3.4 3 5.5 3s3.9-1 5.5-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="13.5" r="1.4" fill="currentColor"/><circle cx="20" cy="13.5" r="1.4" fill="currentColor"/></svg>'; }
	var who = el('div', 'zca__who'); who.appendChild(el('b', '', C.name)); var role = el('small', '', t('ai_role') + ' · '); role.appendChild(el('i', 'zca__on', t('ai_online'))); who.appendChild(role);
	var resetB = el('button', 'zca__ib'); resetB.type = 'button'; resetB.setAttribute('aria-label', t('ai_reset')); resetB.title = t('ai_reset'); resetB.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>';
	var closeB = el('button', 'zca__ib'); closeB.type = 'button'; closeB.setAttribute('aria-label', t('close')); closeB.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';
	head.appendChild(ava); head.appendChild(who); head.appendChild(resetB); head.appendChild(closeB);
	var log = el('div', 'zca__log'); log.setAttribute('aria-live', 'polite'); body.appendChild(log);
	var chipsBox = el('div', 'zca__chips'); body.appendChild(chipsBox);
	var ta = el('textarea', 'zca__in'); ta.rows = 1; ta.placeholder = t('ai_placeholder'); ta.setAttribute('aria-label', t('ai_placeholder')); ta.maxLength = 1500;
	var send = el('button', 'zca__send'); send.type = 'submit'; send.setAttribute('aria-label', t('ai_send')); send.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12 20 4l-6 16-3-7z"/></svg>';
	foot.appendChild(ta); foot.appendChild(send);
	var note = el('p', 'zca__note', t('ai_privacy'));
	panel.appendChild(head); panel.appendChild(body); panel.appendChild(foot); panel.appendChild(note);
	d.body.appendChild(panel);

	function scroll() { body.scrollTo({ top: body.scrollHeight, behavior: reduce ? 'auto' : 'smooth' }); }
	function bubble(role, text, actions) {
		var b = el('div', 'zca__m zca__m--' + (role === 'user' ? 'u' : 'a'));
		if (role === 'user') { b.appendChild(el('p', '', text)); } else { b.appendChild(render(text)); }
		if (actions && actions.length) {
			var row = el('div', 'zca__acts');
			actions.forEach(function (a) { var x = el('a', 'zca__act zca__act--' + a.type, a.label); x.href = a.url; if (/^https?:/.test(a.url) && a.url.indexOf(location.host) < 0) { x.target = '_blank'; x.rel = 'noopener'; } row.appendChild(x); });
			b.appendChild(row);
		}
		log.appendChild(b);
		scroll();
	}
	function chips(list) {
		chipsBox.innerHTML = '';
		(list || []).forEach(function (c) { var b = el('button', 'zca__chip', c); b.type = 'button'; b.addEventListener('click', function () { ask(c); }); chipsBox.appendChild(b); });
	}
	function typing(on) {
		var ex = log.querySelector('.zca__typing');
		if (on && !ex) { var tp = el('div', 'zca__m zca__m--a zca__typing'); tp.setAttribute('aria-label', t('ai_typing')); tp.appendChild(el('span')); tp.appendChild(el('span')); tp.appendChild(el('span')); log.appendChild(tp); scroll(); }
		if (!on && ex) { ex.remove(); }
	}
	function boot() {
		log.innerHTML = '';
		bubble('assistant', t('ai_greeting'));
		state.msgs.forEach(function (m) { bubble(m.role, m.content, m.actions); });
		chips(C.chips);
	}
	var busy = false;
	function ask(text, retry) {
		text = String(text || '').trim();
		if (!text || busy) { return; }
		busy = true; send.disabled = true;
		if (!retry) { state.msgs.push({ role: 'user', content: text }); bubble('user', text); save(); }
		chipsBox.innerHTML = ''; typing(true);
		var payload = { messages: state.msgs.map(function (m) { return { role: m.role, content: m.content }; }), lang: C.lang, page: location.href, sid: state.sid };
		fetch(C.rest, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload), credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				typing(false);
				var reply = (j && j.reply) || t('ai_error').replace('{phone}', C.phone).replace('{email}', C.email);
				state.msgs.push({ role: 'assistant', content: reply, actions: (j && j.actions) || [] }); save();
				bubble('assistant', reply, j && j.actions);
				chips(j && j.chips);
			})
			.catch(function () {
				typing(false);
				if (!retry) { setTimeout(function () { busy = false; ask(text, true); }, 1200); return; }
				bubble('assistant', t('ai_error').replace('{phone}', C.phone).replace('{email}', C.email));
			})
			.then(function () { busy = false; send.disabled = false; });
	}
	foot.addEventListener('submit', function (e) { e.preventDefault(); var v = ta.value; ta.value = ''; ta.style.height = ''; ask(v); });
	ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); foot.requestSubmit ? foot.requestSubmit() : foot.dispatchEvent(new Event('submit')); } });
	ta.addEventListener('input', function () { ta.style.height = 'auto'; ta.style.height = Math.min(140, ta.scrollHeight) + 'px'; });
	resetB.addEventListener('click', function () { state = { sid: Math.random().toString(36).slice(2) + Date.now().toString(36), msgs: [] }; save(); boot(); ta.focus(); });

	var launcher = d.querySelector('.zca-launcher'), lastFocus = null;
	function open(prompt) {
		if (panel.hidden) { lastFocus = d.activeElement; panel.hidden = false; requestAnimationFrame(function () { panel.classList.add('is-open'); }); if (launcher) { launcher.classList.add('is-hidden'); } if (!log.childNodes.length) { boot(); } }
		if (window.matchMedia('(max-width: 640px)').matches) { panel.setAttribute('aria-modal', 'true'); d.body.style.overflow = 'hidden'; }
		if (prompt) { ask(prompt); } else { setTimeout(function () { ta.focus(); }, 80); }
	}
	function close() {
		panel.classList.remove('is-open');
		setTimeout(function () { panel.hidden = true; }, reduce ? 0 : 260);
		if (launcher) { launcher.classList.remove('is-hidden'); }
		d.body.style.overflow = '';
		if (lastFocus) { lastFocus.focus(); }
	}
	closeB.addEventListener('click', close);
	d.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { close(); } });
	window.ZenAI = { open: open, close: close, ask: function (q) { open(); ask(q); } };
})();
