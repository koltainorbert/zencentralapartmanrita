/* Zen Fields – közös admin mezőkönyvtár (Studio + Szekció-szerkesztő). Vanilla JS. */
(function (w) {
	'use strict';
	var d = document;

	function h(tag, attrs, kids) {
		var e = d.createElement(tag);
		attrs = attrs || {};
		Object.keys(attrs).forEach(function (k) {
			var v = attrs[k];
			if (v === null || v === undefined || v === false) { return; }
			if (k === 'class') { e.className = v; }
			else if (k === 'text') { e.textContent = v; }
			else if (k === 'html') { e.innerHTML = v; }
			else if (k.indexOf('on') === 0 && typeof v === 'function') { e.addEventListener(k.slice(2), v); }
			else if (k === 'value') { e.value = v; }
			else if (k === 'checked') { e.checked = !!v; }
			else { e.setAttribute(k, v === true ? '' : v); }
		});
		(kids || []).forEach(function (c) { if (c === null || c === undefined || c === false) { return; } e.appendChild(typeof c === 'string' || typeof c === 'number' ? d.createTextNode(String(c)) : c); });
		return e;
	}
	function get(o, p) { if (!p) { return o; } return String(p).split('.').reduce(function (a, k) { return (a && typeof a === 'object') ? a[k] : undefined; }, o); }
	function set(o, p, v) { var ks = String(p).split('.'), last = ks.pop(), cur = o; ks.forEach(function (k) { if (!cur[k] || typeof cur[k] !== 'object') { cur[k] = {}; } cur = cur[k]; }); cur[last] = v; }
	function clone(x) { return JSON.parse(JSON.stringify(x === undefined ? null : x)); }
	var uid = 0; function id() { uid++; return 'zf' + uid; }

	var ICON = {
		up: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 15 6-6 6 6"/></svg>',
		down: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>',
		del: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/></svg>',
		dup: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/></svg>',
		img: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/></svg>',
		vid: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="5" width="13" height="14" rx="3"/><path d="m16 10 5-3v10l-5-3"/></svg>',
		chev: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>'
	};
	function ib(icon, title, fn, cls) { var b = h('button', { type: 'button', class: 'zs-ib ' + (cls || ''), title: title, 'aria-label': title, onclick: function (e) { e.preventDefault(); e.stopPropagation(); fn(); } }); b.innerHTML = ICON[icon]; return b; }

	/* ---------------- Kapcsoló (felirat nélkül) ---------------- */
	function toggle(val, onch, label) {
		var b = h('button', { type: 'button', class: 'zs-switch', role: 'switch', 'aria-checked': val ? 'true' : 'false', 'aria-label': label || '' }, [h('span', { class: 'zs-switch__t' }, [h('span', { class: 'zs-switch__k' })])]);
		b.addEventListener('click', function () { var v = b.getAttribute('aria-checked') !== 'true'; b.setAttribute('aria-checked', v ? 'true' : 'false'); onch(v); });
		return b;
	}

	/* ---------------- Média választó (kép VAGY videó) ---------------- */
	function normMedia(m) { m = m || {}; if (typeof m === 'string') { m = { url: m }; } return { type: m.type === 'video' ? 'video' : (/\.(mp4|webm|mov)(\?|$)|youtube|youtu\.be|vimeo/i.test(m.url || '') ? 'video' : 'image'), url: m.url || '', id: m.id || 0, poster: m.poster || '', alt: m.alt || '' }; }
	function previewUrl(m) { if (m.type === 'video') { var y = (m.url || '').match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/); if (m.poster) { return m.poster; } if (y) { return 'https://i.ytimg.com/vi/' + y[1] + '/hqdefault.jpg'; } return ''; } return m.url; }
	function mediaPicker(value, onch, opts) {
		opts = opts || {};
		var m = normMedia(value), wrap = h('div', { class: 'zs-media' });
		function render() {
			wrap.innerHTML = '';
			var pv = previewUrl(m), thumb = h('div', { class: 'zs-media__thumb' + (m.url ? '' : ' is-empty') });
			if (m.type === 'video' && !pv && m.url) { var v = h('video', { src: m.url, muted: true, playsinline: true, preload: 'metadata' }); thumb.appendChild(v); }
			else if (pv) { thumb.appendChild(h('img', { src: pv, alt: '' })); }
			else { thumb.innerHTML = ICON.img; }
			if (m.type === 'video') { var badge = h('span', { class: 'zs-media__badge' }); badge.innerHTML = ICON.vid; thumb.appendChild(badge); }
			var seg = h('div', { class: 'zs-seg', role: 'group', 'aria-label': 'Típus' }, [
				h('button', { type: 'button', class: m.type === 'image' ? 'is-on' : '', onclick: function () { m.type = 'image'; emit(); render(); } }, ['Kép']),
				h('button', { type: 'button', class: m.type === 'video' ? 'is-on' : '', onclick: function () { m.type = 'video'; emit(); render(); } }, ['Videó'])
			]);
			var url = h('input', { type: 'url', class: 'zs-input', placeholder: m.type === 'video' ? 'MP4/WebM fájl vagy YouTube/Vimeo link' : 'Kép URL', value: m.url, oninput: function (e) { m.url = e.target.value; m.id = 0; emit(); } });
			url.addEventListener('change', render);
			var btns = h('div', { class: 'zs-row' }, [
				h('button', { type: 'button', class: 'zs-btn zs-btn--sm', onclick: function () { open(false); } }, ['Médiatár']),
				m.type === 'video' ? h('button', { type: 'button', class: 'zs-btn zs-btn--sm zs-btn--ghost', onclick: function () { open(true); } }, ['Borítókép']) : null,
				m.url ? h('button', { type: 'button', class: 'zs-btn zs-btn--sm zs-btn--ghost', onclick: function () { m = normMedia({}); emit(); render(); } }, ['Törlés']) : null
			]);
			var alt = opts.noAlt ? null : h('input', { type: 'text', class: 'zs-input zs-input--sm', placeholder: 'Alternatív szöveg (SEO, akadálymentesség)', value: m.alt, oninput: function (e) { m.alt = e.target.value; emit(); } });
			wrap.appendChild(thumb);
			wrap.appendChild(h('div', { class: 'zs-media__side' }, [seg, url, btns, alt]));
		}
		function emit() { onch(normMedia(m)); }
		function open(poster) {
			if (!w.wp || !w.wp.media) { alert('A WordPress médiatár nem érhető el.'); return; }
			var frame = w.wp.media({ title: poster ? 'Borítókép kiválasztása' : 'Kép vagy videó kiválasztása', button: { text: 'Kiválasztás' }, multiple: false, library: { type: poster ? 'image' : (m.type === 'video' ? 'video' : ['image', 'video']) } });
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				if (poster) { m.poster = a.url; }
				else { m.url = a.url; m.id = a.id; m.type = a.type === 'video' ? 'video' : 'image'; if (!m.alt && a.alt) { m.alt = a.alt; } }
				emit(); render();
			});
			frame.open();
		}
		render();
		return wrap;
	}

	/* ---------------- Galéria (több média, sorrend) ---------------- */
	function gallery(list, onch) {
		list = (list || []).map(function (x) { return normMedia(x && x.media ? x.media : x); });
		var wrap = h('div', { class: 'zs-gallery' });
		function emit() { onch(list.map(normMedia)); }
		function render() {
			wrap.innerHTML = '';
			var grid = h('div', { class: 'zs-gallery__grid' });
			list.forEach(function (m, i) {
				var pv = previewUrl(m), cell = h('div', { class: 'zs-gallery__it', draggable: 'true' });
				cell.appendChild(pv ? h('img', { src: pv, alt: '', loading: 'lazy' }) : h('span', { class: 'zs-gallery__vid', html: ICON.vid }));
				if (m.type === 'video') { cell.appendChild(h('span', { class: 'zs-media__badge', html: ICON.vid })); }
				cell.appendChild(h('div', { class: 'zs-gallery__tools' }, [
					ib('up', 'Előre', function () { if (i > 0) { var t = list[i - 1]; list[i - 1] = list[i]; list[i] = t; emit(); render(); } }),
					ib('del', 'Törlés', function () { list.splice(i, 1); emit(); render(); }, 'is-danger')
				]));
				cell.addEventListener('dragstart', function (e) { e.dataTransfer.setData('text/plain', String(i)); cell.classList.add('is-drag'); });
				cell.addEventListener('dragend', function () { cell.classList.remove('is-drag'); });
				cell.addEventListener('dragover', function (e) { e.preventDefault(); });
				cell.addEventListener('drop', function (e) { e.preventDefault(); var from = +e.dataTransfer.getData('text/plain'); if (from === i) { return; } var it = list.splice(from, 1)[0]; list.splice(i, 0, it); emit(); render(); });
				grid.appendChild(cell);
			});
			var add = h('button', { type: 'button', class: 'zs-gallery__add', onclick: function () {
				if (!w.wp || !w.wp.media) { var u = prompt('Kép vagy videó URL'); if (u) { list.push(normMedia({ url: u })); emit(); render(); } return; }
				var frame = w.wp.media({ title: 'Képek / videók hozzáadása', button: { text: 'Hozzáadás' }, multiple: 'add', library: { type: ['image', 'video'] } });
				frame.on('select', function () { frame.state().get('selection').each(function (s) { var a = s.toJSON(); list.push(normMedia({ url: a.url, id: a.id, type: a.type === 'video' ? 'video' : 'image', alt: a.alt || '' })); }); emit(); render(); });
				frame.open();
			} }, ['+ Kép / videó']);
			grid.appendChild(add);
			wrap.appendChild(grid);
			wrap.appendChild(h('p', { class: 'zs-help', text: list.length + ' elem · húzással rendezhető · URL beillesztéshez: médiatár → „Beszúrás URL-ből”' }));
		}
		render();
		return wrap;
	}

	/* ---------------- Felszereltség ikonok (automatikus + kézi „[[ikon]]” előtag) ---------------- */
	var AM_PRE = /^\s*\[\[([a-z0-9-]+)\]\]\s*/;
	function amData() { var a = w.ZenAmenityIcons; return a && a.icons ? a : null; }
	function amMatch(text) {
		var A = amData(), t = ' ' + String(text || '').toLowerCase() + ' ', best = 'check', pos = 1e9, len = 0;
		if (!A || !A.rules) { return best; }
		Object.keys(A.rules).forEach(function (ic) {
			(A.rules[ic] || []).forEach(function (wd) {
				var p = wd ? t.indexOf(wd) : -1;
				if (p > -1 && (p < pos || (p === pos && wd.length > len))) { pos = p; len = wd.length; best = ic; }
			});
		});
		return best;
	}
	function amSplit(v) {
		var A = amData(), s = String(v || ''), m = s.match(AM_PRE);
		if (m && A && A.icons[m[1]]) { return { icon: m[1], text: s.replace(AM_PRE, ''), manual: true }; }
		return { icon: null, text: s.replace(AM_PRE, ''), manual: false };
	}
	function amPicker(anchor, current, manual, onpick) {
		var A = amData(), old = d.querySelector('.zs-amen-pop');
		if (old) { old.remove(); }
		var pop = h('div', { class: 'zs-amen-pop', role: 'dialog', 'aria-label': 'Ikon választása' });
		pop.appendChild(h('button', { type: 'button', class: 'zs-amen-opt zs-amen-opt--auto' + (!manual ? ' is-on' : ''), title: 'Automatikus (a szövegből)', onclick: function () { onpick(null); pop.remove(); } }, [h('span', { class: 'zs-amen-ico', html: A.icons[current] ? A.icons[current].svg : '' }), h('span', { text: 'Automatikus' })]));
		var grid = h('div', { class: 'zs-amen-grid' });
		Object.keys(A.icons).forEach(function (k) {
			grid.appendChild(h('button', { type: 'button', class: 'zs-amen-opt' + (manual && k === current ? ' is-on' : ''), title: A.icons[k].l, 'aria-label': A.icons[k].l, onclick: function () { onpick(k); pop.remove(); } }, [h('span', { class: 'zs-amen-ico', html: A.icons[k].svg }), h('span', { class: 'zs-amen-l', text: A.icons[k].l })]));
		});
		pop.appendChild(grid);
		d.body.appendChild(pop);
		var r = anchor.getBoundingClientRect(), pw = Math.min(420, w.innerWidth - 24);
		pop.style.width = pw + 'px';
		pop.style.left = Math.max(12, Math.min(r.left + w.scrollX, w.scrollX + w.innerWidth - pw - 12)) + 'px';
		pop.style.top = (r.bottom + w.scrollY + 8) + 'px';
		setTimeout(function () {
			function off(e) { if (!pop.contains(e.target) && e.target !== anchor) { pop.remove(); d.removeEventListener('mousedown', off); d.removeEventListener('keydown', esc); } }
			function esc(e) { if (e.key === 'Escape') { pop.remove(); d.removeEventListener('mousedown', off); d.removeEventListener('keydown', esc); anchor.focus(); } }
			d.addEventListener('mousedown', off);
			d.addEventListener('keydown', esc);
		}, 0);
	}

	/* ---------------- Szöveglista ---------------- */
	function list(arr, onch, opts) {
		arr = (arr || []).slice();
		opts = opts || {};
		var wrap = h('div', { class: 'zs-list' + (opts.icons && amData() ? ' zs-list--icons' : '') });
		function emit() { onch(arr.slice()); }
		function render() {
			wrap.innerHTML = '';
			arr.forEach(function (v, i) {
				var useIco = opts.icons && amData(), part = useIco ? amSplit(v) : { text: v }, btn = null;
				function store(txt) { part.text = txt; arr[i] = (part.manual ? '[[' + part.icon + ']] ' : '') + txt; }
				function paint() {
					if (!btn) { return; }
					var A = amData(), ic = part.manual ? part.icon : amMatch(part.text);
					btn.innerHTML = A.icons[ic] ? A.icons[ic].svg : '';
					btn.className = 'zs-amen-btn' + (part.manual ? ' is-manual' : '');
					btn.title = (part.manual ? 'Kézi ikon: ' : 'Automatikus ikon: ') + (A.icons[ic] ? A.icons[ic].l : '') + ' – kattints a cseréhez';
				}
				var onIn = function (e) { if (useIco) { store(e.target.value); paint(); } else { arr[i] = e.target.value; } emit(); };
				var inp = opts.multiline ? h('textarea', { class: 'zs-input', rows: 2, value: part.text, oninput: onIn }) : h('input', { type: 'text', class: 'zs-input', value: part.text, placeholder: opts.ph || '', oninput: onIn });
				if (useIco) {
					btn = h('button', { type: 'button', class: 'zs-amen-btn', onclick: function () {
						amPicker(btn, part.manual ? part.icon : amMatch(part.text), part.manual, function (k) {
							part.manual = !!k; part.icon = k; store(part.text); emit(); paint();
						});
					} });
					paint();
				}
				wrap.appendChild(h('div', { class: 'zs-list__row' }, [btn, inp, ib('up', 'Fel', function () { if (i > 0) { var t = arr[i - 1]; arr[i - 1] = arr[i]; arr[i] = t; emit(); render(); } }), ib('del', 'Törlés', function () { arr.splice(i, 1); emit(); render(); }, 'is-danger')]));
			});
			wrap.appendChild(h('button', { type: 'button', class: 'zs-btn zs-btn--sm zs-btn--ghost', onclick: function () { arr.push(''); emit(); render(); var l = wrap.querySelectorAll('.zs-input'); if (l.length) { l[l.length - 1].focus(); } } }, ['+ ' + (opts.add || 'Új sor')]));
		}
		render();
		return wrap;
	}

	/* ---------------- Ismétlő (objektumlista) ---------------- */
	function repeater(arr, fields, onch, opts) {
		arr = clone(arr || []) || [];
		opts = opts || {};
		var wrap = h('div', { class: 'zs-rep' }), open = {};
		function emit() { onch(clone(arr)); }
		function titleOf(it, i) { var t = opts.title ? opts.title(it, i) : null; if (t) { return t; } for (var k = 0; k < fields.length; k++) { var v = get(it, fields[k].k); if (typeof v === 'string' && v.trim()) { return v.slice(0, 70); } if (v && typeof v === 'object' && !Array.isArray(v)) { var any = Object.keys(v).map(function (x) { return v[x]; }).filter(function (x) { return typeof x === 'string' && x; })[0]; if (any) { return any.slice(0, 70); } } } return (opts.item || 'Elem') + ' ' + (i + 1); }
		function render() {
			wrap.innerHTML = '';
			arr.forEach(function (it, i) {
				var isOpen = open[i] || arr.length === 1 && opts.openSingle;
				var head = h('div', { class: 'zs-rep__head', onclick: function () { open[i] = !open[i]; render(); } }, [
					h('span', { class: 'zs-rep__chev' + (isOpen ? ' is-open' : ''), html: ICON.chev }),
					h('b', { text: titleOf(it, i) }),
					h('span', { class: 'zs-rep__tools' }, [
						ib('up', 'Fel', function () { if (i > 0) { var t = arr[i - 1]; arr[i - 1] = arr[i]; arr[i] = t; emit(); render(); } }),
						ib('down', 'Le', function () { if (i < arr.length - 1) { var t = arr[i + 1]; arr[i + 1] = arr[i]; arr[i] = t; emit(); render(); } }),
						ib('dup', 'Másolat', function () { arr.splice(i + 1, 0, clone(it)); emit(); render(); }),
						ib('del', 'Törlés', function () { if (confirm('Biztosan törlöd?')) { arr.splice(i, 1); emit(); render(); } }, 'is-danger')
					])
				]);
				var body = h('div', { class: 'zs-rep__body', hidden: isOpen ? null : true });
				if (isOpen) { fields.forEach(function (f) { body.appendChild(field(f, it, function () { emit(); })); }); }
				wrap.appendChild(h('div', { class: 'zs-rep__it' + (isOpen ? ' is-open' : '') }, [head, body]));
			});
			wrap.appendChild(h('button', { type: 'button', class: 'zs-btn zs-btn--sm', onclick: function () { arr.push(clone(opts.blank || {})); open[arr.length - 1] = true; emit(); render(); } }, ['+ ' + (opts.add || 'Új elem')]));
		}
		render();
		return wrap;
	}

	/* ---------------- Nyelvi mező {hu:'',en:''} ---------------- */
	function langtext(obj, langs, onch, multiline) {
		obj = Object.assign({}, obj || {});
		var cur = langs[0], wrap = h('div', { class: 'zs-lt' });
		function render() {
			wrap.innerHTML = '';
			var tabs = h('div', { class: 'zs-lt__tabs' }, langs.map(function (l) { return h('button', { type: 'button', class: (l === cur ? 'is-on' : '') + (obj[l] ? ' has' : ''), onclick: function () { cur = l; render(); } }, [l.toUpperCase()]); }));
			var inp = multiline ? h('textarea', { class: 'zs-input', rows: 3, value: obj[cur] || '', oninput: function (e) { obj[cur] = e.target.value; onch(Object.assign({}, obj)); } }) : h('input', { type: 'text', class: 'zs-input', value: obj[cur] || '', oninput: function (e) { obj[cur] = e.target.value; onch(Object.assign({}, obj)); } });
			wrap.appendChild(tabs); wrap.appendChild(inp);
		}
		render();
		return wrap;
	}

	/* ---------------- Mini HTML szerkesztő ---------------- */
	function htmlEditor(val, onch) {
		var ta = h('textarea', { class: 'zs-input zs-input--html', rows: 7, value: val || '', oninput: function () { onch(ta.value); } });
		function wrapSel(a, b) { var s = ta.selectionStart, e = ta.selectionEnd, v = ta.value; ta.value = v.slice(0, s) + a + v.slice(s, e) + b + v.slice(e); ta.focus(); ta.selectionStart = s + a.length; ta.selectionEnd = e + a.length; onch(ta.value); }
		var bar = h('div', { class: 'zs-tb' }, [
			h('button', { type: 'button', title: 'Félkövér', onclick: function () { wrapSel('<b>', '</b>'); } }, ['B']),
			h('button', { type: 'button', title: 'Dőlt', onclick: function () { wrapSel('<i>', '</i>'); } }, ['I']),
			h('button', { type: 'button', title: 'Bekezdés', onclick: function () { wrapSel('<p>', '</p>'); } }, ['¶']),
			h('button', { type: 'button', title: 'Lista', onclick: function () { wrapSel('<ul>\n<li>', '</li>\n</ul>'); } }, ['• Lista']),
			h('button', { type: 'button', title: 'Link', onclick: function () { var u = prompt('Link címe (URL vagy {page:booking})', 'https://'); if (u) { wrapSel('<a href="' + u + '">', '</a>'); } } }, ['Link']),
			h('button', { type: 'button', title: 'Sortörés', onclick: function () { wrapSel('<br>', ''); } }, ['↵'])
		]);
		return h('div', { class: 'zs-html' }, [bar, ta]);
	}

	/* ---------------- Mező gyártó ---------------- */
	// def: { k, t, l, help, opts:[[v,l]], min, max, step, fields, item, add, langs, ph, show:function(model) }
	function field(def, model, onChange) {
		var fid = id(), val = get(model, def.k), ctrl;
		var change = function (v) { set(model, def.k, v); onChange && onChange(def.k, v); };
		switch (def.t) {
			case 'textarea': ctrl = h('textarea', { id: fid, class: 'zs-input', rows: def.rows || 3, value: val || '', placeholder: def.ph || '', oninput: function (e) { change(e.target.value); } }); break;
			case 'code': ctrl = h('textarea', { id: fid, class: 'zs-input zs-input--code', rows: def.rows || 8, value: val || '', spellcheck: 'false', oninput: function (e) { change(e.target.value); } }); break;
			case 'html': ctrl = htmlEditor(val, change); break;
			case 'number': ctrl = h('input', { id: fid, type: 'number', class: 'zs-input zs-input--num', value: val === undefined ? '' : val, min: def.min, max: def.max, step: def.step || 1, oninput: function (e) { change(e.target.value === '' ? 0 : Number(e.target.value)); } }); break;
			case 'range': var out = h('output', { text: String(val) }); ctrl = h('div', { class: 'zs-range' }, [h('input', { id: fid, type: 'range', min: def.min, max: def.max, step: def.step || 1, value: val, oninput: function (e) { out.textContent = e.target.value; change(Number(e.target.value)); } }), out]); break;
			case 'toggle': ctrl = toggle(!!val, change, def.l); break;
			case 'select': ctrl = h('select', { id: fid, class: 'zs-input zs-select', onchange: function (e) { change(e.target.value); } }, (def.opts || []).map(function (o) { var v = Array.isArray(o) ? o[0] : o, l = Array.isArray(o) ? o[1] : o; return h('option', { value: v, selected: String(v) === String(val) ? 'selected' : null }, [l]); })); break;
			case 'color':
				var txtIn = h('input', { type: 'text', class: 'zs-input zs-input--sm', value: val || '', placeholder: 'üres = a skin alapszíne', oninput: function (e) { change(e.target.value); if (/^#[0-9a-f]{6}$/i.test(e.target.value)) { picker.value = e.target.value; } } });
				var picker = h('input', { type: 'color', value: /^#[0-9a-f]{6}$/i.test(val || '') ? val : '#a9844c', oninput: function (e) { change(e.target.value); txtIn.value = e.target.value; } });
				ctrl = h('div', { class: 'zs-color' }, [picker, txtIn]);
				break;
			case 'media': ctrl = mediaPicker(val, change, def); break;
			case 'gallery': ctrl = gallery(val, change); break;
			case 'list': ctrl = list(val, change, def); break;
			case 'repeater': ctrl = repeater(val, def.fields, change, def); break;
			case 'langtext': ctrl = langtext(val, def.langs || ['hu', 'en', 'de'], change, def.multiline); break;
			case 'password': ctrl = h('input', { id: fid, type: 'password', class: 'zs-input', value: val || '', autocomplete: 'new-password', placeholder: def.ph || '', oninput: function (e) { change(e.target.value); } }); break;
			case 'info': ctrl = h('div', { class: 'zs-info', html: def.html || '' }); break;
			case 'date': ctrl = h('input', { id: fid, type: 'date', class: 'zs-input zs-input--date', value: val || '', oninput: function (e) { change(e.target.value); } }); break;
			default: ctrl = h('input', { id: fid, type: def.t === 'url' || def.t === 'email' ? def.t : 'text', class: 'zs-input', value: val === undefined || val === null ? '' : val, placeholder: def.ph || '', oninput: function (e) { change(e.target.value); } });
		}
		var row = h('div', { class: 'zs-field zs-field--' + (def.t || 'text') + (def.wide ? ' zs-field--wide' : '') + (def.half ? ' zs-field--half' : '') });
		if (def.t === 'toggle') {
			row.appendChild(h('div', { class: 'zs-field__inline' }, [ctrl, h('label', { class: 'zs-label zs-label--inline', for: fid, text: def.l || '', onclick: function () { ctrl.click(); } })]));
		} else {
			if (def.l) { row.appendChild(h('label', { class: 'zs-label', for: fid, text: def.l })); }
			row.appendChild(ctrl);
		}
		if (def.help) { row.appendChild(h('p', { class: 'zs-help', html: def.help })); }
		return row;
	}

	w.ZF = { h: h, get: get, set: set, clone: clone, field: field, toggle: toggle, media: mediaPicker, gallery: gallery, list: list, repeater: repeater, langtext: langtext, html: htmlEditor, normMedia: normMedia, ib: ib, ICON: ICON };
})(window);
