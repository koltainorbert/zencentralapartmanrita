/* Zen Booking – 5 lépéses foglaló (vanilla JS, függőség nélkül) */
(function () {
	'use strict';
	var C = window.ZenBookingCfg;
	if (!C) { return; }
	var d = document, S = C.str || {};
	function t(k, v) { var s = S[k] || k; if (v) { Object.keys(v).forEach(function (x) { s = s.split('{' + x + '}').join(v[x]); }); } return s; }
	function el(tag, attrs, kids) {
		var e = d.createElement(tag);
		if (attrs) { Object.keys(attrs).forEach(function (k) {
			var v = attrs[k];
			if (v === null || v === undefined || v === false) { return; }
			if (k === 'class') { e.className = v; } else if (k === 'text') { e.textContent = v; } else if (k === 'html') { e.innerHTML = v; } else if (k.indexOf('on') === 0) { e.addEventListener(k.slice(2), v); } else { e.setAttribute(k, v === true ? '' : v); }
		}); }
		(kids || []).forEach(function (c) { if (c === null || c === undefined || c === false) { return; } e.appendChild(typeof c === 'string' ? d.createTextNode(c) : c); });
		return e;
	}
	function pad(n) { return (n < 10 ? '0' : '') + n; }
	function ymd(dt) { return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate()); }
	function parse(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
	function addDays(s, n) { var x = parse(s); x.setDate(x.getDate() + n); return ymd(x); }
	function nights(a, b) { return Math.round((parse(b) - parse(a)) / 864e5); }
	function money(n) { try { return new Intl.NumberFormat(C.locale, { style: 'currency', currency: C.currency, maximumFractionDigits: 0 }).format(n); } catch (e) { return Math.round(n) + ' ' + C.currency; } }
	function fmt(s, opt) { try { return parse(s).toLocaleDateString(C.locale, opt || { month: 'short', day: 'numeric', weekday: 'short' }); } catch (e) { return s; } }
	var ICON = {
		arrow: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
		left: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"/></svg>',
		right: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>',
		check: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>',
		users: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/></svg>',
		area: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>',
		phone: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>',
		sync: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 11a8 8 0 0 0-14.9-3M4 4v4h4M4 13a8 8 0 0 0 14.9 3M20 20v-4h-4"/></svg>'
	};
	function icon(n) { var s = el('span', { class: 'zcb-i', 'aria-hidden': 'true' }); s.innerHTML = ICON[n] || ''; return s; }
	function api(path, body) {
		var opt = body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body), credentials: 'same-origin' } : { credentials: 'same-origin' };
		return fetch(C.rest + path, opt).then(function (r) { return r.json().then(function (j) { j.__status = r.status; return j; }); });
	}
	var APT = {}; C.apartments.forEach(function (a) { APT[a.key] = a; });
	var TODAY = ymd(new Date());

	/* ======================================================================
	 * Elérhetőség – közös tár (sáv, felugró naptár, varázsló)
	 * ==================================================================== */
	var AV = { days: {}, events: [], loaded: {}, synced: 0 };
	function monthStart(off) { var n = new Date(); return new Date(n.getFullYear(), n.getMonth() + off, 1); }
	function monthsFromNow(s) { var x = parse(s), n = new Date(); return Math.max(0, (x.getFullYear() - n.getFullYear()) * 12 + x.getMonth() - n.getMonth()); }
	AV.load = function (off, count) {
		var a = ymd(monthStart(off)), last = monthStart(off + count); last.setDate(0);
		var b = ymd(last), key = a + '|' + b;
		if (AV.loaded[key]) { return AV.loaded[key] === 1 ? Promise.resolve(false) : AV.loaded[key]; }
		var pr = api('availability?from=' + a + '&to=' + b + '&lang=' + C.lang).then(function (j) {
			if (j && j.days) { Object.keys(j.days).forEach(function (k) { AV.days[k] = j.days[k]; }); AV.events = j.events || AV.events; AV.synced = j.synced; }
			AV.loaded[key] = 1;
			return true;
		}).catch(function () { delete AV.loaded[key]; return false; });
		AV.loaded[key] = pr;
		return pr;
	};
	AV.reset = function () { AV.days = {}; AV.loaded = {}; };
	function inEvent(date) { for (var i = 0; i < AV.events.length; i++) { var e = AV.events[i]; if (date >= e.from && date <= e.to) { return e; } } return null; }
	function aptFree(key, from, to) {
		for (var x = from; x < to; x = addDays(x, 1)) { var s = AV.days[x]; if (!s || s[key] !== 'free') { return false; } }
		return true;
	}
	function freeApts(from, to) { return C.apartments.filter(function (a) { return aptFree(a.key, from, to); }).map(function (a) { return a.key; }); }
	function anyFree(from, to) { return freeApts(from, to).length > 0; }
	function dayState(s, apt) {
		if (s < TODAY) { return 'closed'; }
		var info = AV.days[s];
		if (!info) { return 'loading'; }
		return apt ? (info[apt] || 'closed') : info.any;
	}
	function rangeFree(apt, from, to) { return apt ? aptFree(apt, from, to) : anyFree(from, to); }

	/* ======================================================================
	 * Felugró, széles üveg naptár (érkezés → távozás)
	 * ==================================================================== */
	var DP = (function () {
		var ov = null, o = {}, sel = { from: null, to: null }, off = 0, err = '', last = null, hover = null;
		var mq = window.matchMedia('(min-width: 760px)');
		function close() {
			if (!ov) { return; }
			ov.classList.remove('is-open');
			var x = ov; ov = null;
			setTimeout(function () { if (x.parentNode) { x.parentNode.removeChild(x); } }, 260);
			d.documentElement.classList.remove('zcb-dp-lock');
			d.removeEventListener('keydown', onKey, true);
			if (last && last.focus) { last.focus({ preventScroll: true }); }
		}
		function onKey(e) {
			if (!ov) { return; }
			if (e.key === 'Escape') { e.preventDefault(); close(); return; }
			if (e.key === 'Tab') {
				var f = Array.prototype.filter.call(ov.querySelectorAll('button:not([disabled]),a[href]'), function (x) { return x.offsetParent !== null; });
				if (!f.length) { return; }
				if (e.shiftKey && d.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
				else if (!e.shiftKey && d.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
			}
			if (/^Arrow/.test(e.key) && d.activeElement && d.activeElement.getAttribute('data-date')) {
				var step = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[e.key], nd = addDays(d.activeElement.getAttribute('data-date'), step);
				var nb = ov.querySelector('[data-date="' + nd + '"]');
				if (nb) { e.preventDefault(); nb.focus(); }
			}
		}
		function selectable(s) {
			var stt = dayState(s, o.apt);
			if (stt === 'closed' || stt === 'loading') { return false; }
			if (sel.from && !sel.to && s > sel.from) { return rangeFree(o.apt, sel.from, s); }
			return stt === 'free';
		}
		function pick(s) {
			err = '';
			if (!sel.from || sel.to || s <= sel.from || !rangeFree(o.apt, sel.from, s)) { sel = { from: s, to: null }; }
			else {
				var n = nights(sel.from, s), min = Math.max(1, C.minNights || 1);
				if (n < min) { err = t('bk_err_min', { n: min }); }
				else { sel.to = s; }
			}
			hover = null;
			render(s);
		}
		function dayBtn(s, day) {
			var stt = dayState(s, o.apt), ev = inEvent(s), can = selectable(s);
			var inRange = sel.from && sel.to && s > sel.from && s < sel.to;
			var prev = sel.from && !sel.to && hover && s > sel.from && s <= hover;
			var cls = 'zcb-d zcb-d--' + stt + (s === sel.from ? ' is-start' + (sel.to ? ' is-rs' : '') : '') + (s === sel.to ? ' is-end' : '') + (inRange ? ' is-in' : '') + (prev ? ' is-preview' : '') + (s === hover && prev ? ' is-hover-end' : '') + (s === TODAY ? ' is-today' : '') + (ev ? ' has-event' : '') + (!can ? ' is-off' : '');
			var label = fmt(s, { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' }) + ' – ' + t(stt === 'free' ? 'bk_legend_free' : stt === 'checkout' ? 'bk_legend_checkout' : stt === 'booked' ? 'bk_legend_booked' : 'bk_legend_closed') + (ev ? ' – ' + ev.name : '');
			return el('button', { type: 'button', class: cls, 'data-date': s, 'aria-label': label, 'aria-pressed': (s === sel.from || s === sel.to) ? 'true' : 'false', title: ev ? ev.name : null, disabled: can ? null : true, onclick: function () { pick(s); } }, [el('span', { class: 'zcb-d__n', text: String(day) })]);
		}
		function month(k) {
			var first = monthStart(k), y = first.getFullYear(), mo = first.getMonth();
			var box = el('div', { class: 'zcb-m' }, [el('p', { class: 'zcb-m__t', text: first.toLocaleDateString(C.locale, { month: 'long', year: 'numeric' }) })]);
			var grid = el('div', { class: 'zcb-m__g' });
			for (var i = 0; i < 7; i++) { grid.appendChild(el('span', { class: 'zcb-m__wd', 'aria-hidden': 'true', text: new Date(2024, 0, 1 + i).toLocaleDateString(C.locale, { weekday: 'short' }).replace('.', '').slice(0, 2) })); }
			var lead = (first.getDay() + 6) % 7;
			for (i = 0; i < lead; i++) { grid.appendChild(el('span', { class: 'zcb-d zcb-d--pad', 'aria-hidden': 'true' })); }
			var dim = new Date(y, mo + 1, 0).getDate();
			for (var day = 1; day <= dim; day++) { grid.appendChild(dayBtn(ymd(new Date(y, mo, day)), day)); }
			box.appendChild(grid);
			return box;
		}
		function tile(key, val, on) {
			return el('div', { class: 'zcb-dp__tile' + (on ? ' is-on' : '') }, [el('small', { text: t(key) }), el('b', { text: val ? fmt(val, { month: 'short', day: 'numeric', weekday: 'short' }) : '—' })]);
		}
		function render(focus) {
			if (!ov) { return; }
			var n = sel.from && sel.to ? nights(sel.from, sel.to) : 0;
			var free = n ? freeApts(sel.from, sel.to).length : 0;
			var count = 2, maxOff = Math.ceil((C.maxAdvance || 540) / 30);
			var head = el('header', { class: 'zcb-dp__head' }, [
				el('div', { class: 'zcb-dp__ttl' }, [el('p', { class: 'zcb-dp__k', text: o.title || t('bk_pick_dates') }), el('p', { class: 'zcb-dp__hint', text: err || (!sel.from || sel.to ? t('bk_choose_arrival') : t('bk_choose_departure')) + (o.apt && APT[o.apt] ? ' · ' + APT[o.apt].name : '') })]),
				el('button', { type: 'button', class: 'zcb-dp__x', 'aria-label': t('close'), onclick: close }, [el('span', { 'aria-hidden': 'true', html: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>' })])
			]);
			var sum = el('div', { class: 'zcb-dp__sum' }, [
				tile('bk_arrival', sel.from, !sel.from || !sel.to), el('span', { class: 'zcb-dp__arrow', 'aria-hidden': 'true', html: ICON.arrow }), tile('bk_departure', sel.to, sel.from && !sel.to),
				el('div', { class: 'zcb-dp__n' + (n ? ' is-on' : '') }, [el('b', { text: n ? String(n) : '0' }), el('small', { text: t('bk_nights', { n: '#' }).replace('#', '').replace(/[:\s]+$/, '').trim() })])
			]);
			var nav = el('div', { class: 'zcb-dp__nav' }, [
				el('button', { type: 'button', class: 'zcb-round', 'aria-label': '‹', disabled: off <= 0 ? true : null, onclick: function () { off = Math.max(0, off - 1); render(); } }, [icon('left')]),
				el('button', { type: 'button', class: 'zcb-round', 'aria-label': '›', disabled: off >= maxOff ? true : null, onclick: function () { off++; render(); } }, [icon('right')])
			]);
			var months = el('div', { class: 'zcb-dp__months' }, [month(off), month(off + 1)]);
			months.addEventListener('mouseover', function (e) {
				var b = e.target.closest('[data-date]');
				if (!b || !sel.from || sel.to || b.disabled) { return; }
				var s = b.getAttribute('data-date');
				if (s === hover) { return; }
				hover = s > sel.from ? s : null;
				Array.prototype.forEach.call(months.querySelectorAll('[data-date]'), function (x) {
					var v = x.getAttribute('data-date'), p = hover && v > sel.from && v <= hover;
					x.classList.toggle('is-preview', !!p); x.classList.toggle('is-hover-end', !!p && v === hover);
					if (v === sel.from) { x.classList.toggle('is-rs', !!hover); }
				});
			});
			var legend = el('ul', { class: 'zcb-dp__legend' }, [['free', 'bk_legend_free'], ['checkout', 'bk_legend_checkout'], ['booked', 'bk_legend_booked'], ['sel', 'bk_legend_selected']].concat(AV.events.length ? [['event', 'bk_legend_event']] : []).map(function (x) {
				return el('li', {}, [el('i', { class: 'zcb-sw zcb-sw--' + x[0], 'aria-hidden': 'true' }), t(x[1])]);
			}));
			var foot = el('footer', { class: 'zcb-dp__foot' }, [
				el('p', { class: 'zcb-dp__free', text: n ? t('bk_free_count', { n: free }) : t('bk_range_hint') }),
				el('div', { class: 'zcb-dp__btns' }, [
					el('button', { type: 'button', class: 'zc-btn zc-btn--ghost zc-btn--sm', disabled: sel.from ? null : true, onclick: function () { sel = { from: null, to: null }; err = ''; render(); } }, [el('span', { text: t('bk_clear') })]),
					el('button', { type: 'button', class: 'zc-btn zc-btn--sm zcb-dp__ok' + (n ? ' is-ready' : ''), disabled: n ? null : true, onclick: function () { var f = o.onApply; var r = { from: sel.from, to: sel.to }; close(); if (f) { f(r.from, r.to); } } }, [el('span', { text: t('bk_apply') }), icon('check')])
				])
			]);
			var box = ov.querySelector('.zcb-dp__box');
			box.innerHTML = '';
			[head, sum, el('div', { class: 'zcb-dp__body' }, [nav, months]), legend, foot].forEach(function (x) { box.appendChild(x); });
			AV.load(off, count + 1).then(function (fetched) { if (fetched && ov) { render(); } });
			var fb = focus ? box.querySelector('[data-date="' + focus + '"]') : null;
			if (fb) { fb.focus({ preventScroll: true }); }
		}
		function open(opts) {
			if (ov) { close(); }
			o = opts || {};
			last = d.activeElement;
			sel = { from: o.from || null, to: (o.from && o.to && o.to > o.from) ? o.to : null };
			off = sel.from ? monthsFromNow(sel.from) : 0;
			err = ''; hover = null;
			ov = el('div', { class: 'zcb-dp', role: 'dialog', 'aria-modal': 'true', 'aria-label': t('bk_pick_dates'), onclick: function (e) { if (e.target === ov) { close(); } } }, [el('div', { class: 'zcb-dp__box' })]);
			d.body.appendChild(ov);
			d.documentElement.classList.add('zcb-dp-lock');
			d.addEventListener('keydown', onKey, true);
			render();
			requestAnimationFrame(function () { if (ov) { ov.classList.add('is-open'); var f = ov.querySelector('.zcb-d.is-start') || ov.querySelector('.zcb-d:not([disabled]):not(.zcb-d--pad)'); if (f) { f.focus({ preventScroll: true }); } } });
		}
		mq.addEventListener && mq.addEventListener('change', function () { render(); });
		return { open: open, close: close };
	})();

	/* ======================================================================
	 * Foglalósáv / foglalódoboz (hero, apartman oldal) – felugró naptárral
	 * ==================================================================== */
	Array.prototype.forEach.call(d.querySelectorAll('[data-zcb-bar]'), function (f) {
		var a = f.querySelector('input[name="from"]'), b = f.querySelector('input[name="to"]'), hint = f.querySelector('[data-zcb-hint]');
		if (!a || !b) { return; }
		var apt = f.getAttribute('data-apt') || '';
		var qa = new URLSearchParams(location.search);
		a.type = 'hidden'; b.type = 'hidden'; a.required = false; b.required = false;
		if (/^\d{4}-\d{2}-\d{2}$/.test(qa.get('from') || '') && qa.get('from') >= TODAY) { a.value = qa.get('from'); }
		if (/^\d{4}-\d{2}-\d{2}$/.test(qa.get('to') || '') && a.value && qa.get('to') > a.value) { b.value = qa.get('to'); }
		function val(inp) {
			var fld = inp.closest('.zcb-bar__field');
			var v = el('span', { class: 'zcb-bar__val' + (inp.value ? '' : ' is-empty') });
			fld.appendChild(v); fld.classList.add('zcb-bar__field--pick'); fld.setAttribute('role', 'button'); fld.setAttribute('tabindex', '0'); fld.removeAttribute('for');
			fld.addEventListener('click', function (e) { e.preventDefault(); openDp(); });
			fld.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDp(); } });
			return v;
		}
		var va = val(a), vb = val(b);
		function update() {
			va.textContent = a.value ? fmt(a.value) : t('bk_pick_dates'); va.classList.toggle('is-empty', !a.value);
			vb.textContent = b.value ? fmt(b.value) : '—'; vb.classList.toggle('is-empty', !b.value);
			if (!hint) { return; }
			if (a.value && b.value) {
				var n = nights(a.value, b.value);
				hint.textContent = t('bk_nights', { n: n });
				AV.load(monthsFromNow(a.value), Math.max(1, monthsFromNow(b.value) - monthsFromNow(a.value) + 1)).then(function () {
					var fr = apt ? (aptFree(apt, a.value, b.value) ? 1 : 0) : freeApts(a.value, b.value).length;
					hint.textContent = t('bk_nights', { n: n }) + ' · ' + (apt ? (fr ? t('bk_legend_free') : t('bk_unavailable')) : t('bk_free_count', { n: fr }));
					hint.classList.toggle('is-bad', !fr);
				});
			} else { hint.textContent = ''; }
		}
		function openDp() { DP.open({ apt: apt, from: a.value, to: b.value, onApply: function (x, y) { a.value = x; b.value = y; update(); var g = f.querySelector('.zcb-bar__go'); if (g) { g.focus({ preventScroll: true }); } } }); }
		update();
		// Előtöltés, hogy a naptár azonnal nyíljon.
		var warm = function () { AV.load(0, 3); };
		if ('requestIdleCallback' in window) { window.requestIdleCallback(warm, { timeout: 3000 }); } else { setTimeout(warm, 1500); }
		f.addEventListener('submit', function (e) {
			if (!a.value || !b.value || b.value <= a.value) { e.preventDefault(); openDp(); }
		});
	});

	/* ======================================================================
	 * Varázsló
	 * ==================================================================== */
	var root = d.querySelector('[data-zcb]');
	if (!root) { return; }
	var q = new URLSearchParams(location.search);
	var st = {
		step: 1, from: null, to: null, adults: 2, children: 0, rooms: [], multi: false,
		month: 0,
		guest: { name: '', email: '', phone: '', country: '', arrival_time: '', message: '' },
		extras: [], payment: (C.payments || ['transfer'])[0], gdpr: false, terms: false, newsletter: false,
		quote: null, sending: false, error: '', result: null, ts: Date.now()
	};
	var qf = q.get('from'), qt = q.get('to');
	if (qf && /^\d{4}-\d{2}-\d{2}$/.test(qf) && qf >= TODAY) { st.from = qf; }
	if (qt && st.from && /^\d{4}-\d{2}-\d{2}$/.test(qt) && qt > st.from) { st.to = qt; }
	if (q.get('adults')) { st.adults = Math.min(9, Math.max(1, parseInt(q.get('adults'), 10) || 2)); }
	if (q.get('children')) { st.children = Math.min(8, Math.max(0, parseInt(q.get('children'), 10) || 0)); }
	if (q.get('apt') && APT[q.get('apt')]) { st.rooms = [q.get('apt')]; }
	if (st.from) { st.month = monthsFromNow(st.from); }

	/* ---------- Lépésjelző ---------- */
	var STEPS = ['bk_step_dates', 'bk_step_apartment', 'bk_step_details', 'bk_step_summary', 'bk_step_done'];
	function stepper() {
		return el('ol', { class: 'zcb-steps', 'aria-label': t('bk_title') }, STEPS.map(function (k, i) {
			var n = i + 1, cls = 'zcb-steps__item' + (n === st.step ? ' is-active' : '') + (n < st.step ? ' is-done' : '');
			return el('li', { class: cls, 'aria-current': n === st.step ? 'step' : null }, [
				el('span', { class: 'zcb-steps__n' }, [n < st.step ? icon('check') : String(n)]),
				el('span', { class: 'zcb-steps__l', text: t(k) })
			]);
		}));
	}
	function summaryBar() {
		if (!st.from) { return null; }
		var txt = fmt(st.from) + (st.to ? ' → ' + fmt(st.to) + ' · ' + t('bk_nights', { n: nights(st.from, st.to) }) : '');
		return el('div', { class: 'zcb-sum' }, [
			el('span', { class: 'zcb-sum__dates', text: txt }),
			el('span', { class: 'zcb-sum__guests' }, [icon('users'), String(st.adults + st.children)])
		]);
	}
	function help() {
		return el('p', { class: 'zcb-help' }, [icon('phone'), el('span', { text: t('bk_help') + ': ' }), el('a', { href: 'tel:' + String(C.phone).replace(/[^+0-9]/g, ''), text: C.phone })]);
	}
	function errBox() { return st.error ? el('p', { class: 'zcb-err', role: 'alert', text: st.error }) : null; }

	/* ---------- 1. Időpont – kompakt kártya, a naptár felugró ablakban ---------- */
	function openDates() {
		DP.open({ apt: st.rooms.length === 1 && q.get('apt') ? st.rooms[0] : '', from: st.from, to: st.to, onApply: function (a, b) { st.from = a; st.to = b; st.error = ''; render(); } });
	}
	function whenCard() {
		var n = st.from && st.to ? nights(st.from, st.to) : 0;
		function tl(key, v) { return el('span', { class: 'zcb-when__tile' + (v ? '' : ' is-empty') }, [el('small', { text: t(key) }), el('b', { text: v ? fmt(v, { month: 'long', day: 'numeric', weekday: 'short' }) : t('bk_pick_dates') })]); }
		var card = el('button', { type: 'button', class: 'zcb-when' + (n ? ' is-set' : ''), onclick: openDates, 'aria-label': t('bk_pick_dates') }, [
			el('span', { class: 'zcb-when__ico', 'aria-hidden': 'true', html: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><rect x="3.5" y="5" width="17" height="15.5" rx="4"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg>' }),
			tl('bk_arrival', st.from),
			el('span', { class: 'zcb-when__arrow', 'aria-hidden': 'true', html: ICON.arrow }),
			tl('bk_departure', st.to),
			el('span', { class: 'zcb-when__n' }, [n ? el('b', { text: String(n) }) : null, el('small', { text: n ? t('bk_nights', { n: '#' }).replace('#', '').replace(/[:\s]+$/, '').trim() : t('bk_change') })])
		]);
		var info = null;
		if (n && Object.keys(AV.days).length) {
			var fr = freeApts(st.from, st.to).length;
			info = el('p', { class: 'zcb-when__free' + (fr ? '' : ' is-bad') }, [el('i', { class: 'zcb-sw zcb-sw--' + (fr ? 'free' : 'booked'), 'aria-hidden': 'true' }), fr ? t('bk_free_count', { n: fr }) : t('bk_unavailable')]);
		}
		return el('div', { class: 'zcb-whenwrap' }, [card, info]);
	}
	function stepper2(label, val, min, max, note, onch) {
		return el('div', { class: 'zcb-num' }, [
			el('span', { class: 'zcb-num__l' }, [label, note ? el('small', { text: note }) : null]),
			el('div', { class: 'zcb-num__c' }, [
				el('button', { type: 'button', class: 'zcb-round', 'aria-label': '−', disabled: val <= min ? true : null, onclick: function () { onch(val - 1); } }, ['−']),
				el('output', { text: String(val), 'aria-live': 'polite' }),
				el('button', { type: 'button', class: 'zcb-round', 'aria-label': '+', disabled: val >= max ? true : null, onclick: function () { onch(val + 1); } }, ['+'])
			])
		]);
	}
	function step1() {
		var maxG = C.apartments.reduce(function (s, a) { return s + a.capacity; }, 0) + 1;
		return el('div', { class: 'zcb-pane' }, [
			whenCard(),
			el('div', { class: 'zcb-guests' }, [
				stepper2(t('bk_adults'), st.adults, 1, Math.max(1, maxG - st.children), '', function (v) { st.adults = v; render(); }),
				stepper2(t('bk_children'), st.children, 0, Math.max(0, maxG - st.adults), t('bk_children_note'), function (v) { st.children = v; render(); })
			]),
			errBox(),
			el('div', { class: 'zcb-actions' }, [
				el('button', { type: 'button', class: 'zc-btn', onclick: function () {
					if (!st.from || !st.to) { openDates(); return; }
					AV.load(monthsFromNow(st.from), monthsFromNow(st.to) - monthsFromNow(st.from) + 1).then(function () {
						st.rooms = st.rooms.filter(function (k) { return aptFree(k, st.from, st.to); });
						go(2);
					});
				} }, [el('span', { text: t('bk_next') }), icon('arrow')])
			])
		]);
	}

	/* ---------- 2. Apartman ---------- */
	function capSel() { return st.rooms.reduce(function (s, k) { return s + APT[k].capacity; }, 0); }
	function step2() {
		var guests = st.adults + st.children;
		var list = el('div', { class: 'zcb-apts' });
		C.apartments.forEach(function (a) {
			var free = aptFree(a.key, st.from, st.to), on = st.rooms.indexOf(a.key) >= 0;
			var small = !st.multi && a.capacity < guests;
			var q = st.quote && st.quote.items ? st.quote.items.filter(function (x) { return x.apt === a.key; })[0] : null;
			var price;
			if (!q || st.quote.quoteMode || !q.total) { price = el('p', { class: 'zcb-apt__price zcb-apt__price--q', text: t('bk_quote') }); }
			else { price = el('p', { class: 'zcb-apt__price' }, [el('b', { text: money(q.total) }), el('small', { text: ' · ' + t('bk_nights', { n: q.nights }) })]); }
			var card = el('article', { class: 'zcb-apt' + (on ? ' is-on' : '') + (!free || small ? ' is-off' : '') }, [
				el('div', { class: 'zcb-apt__img' }, [a.cover ? el('img', { src: a.cover, alt: a.name, loading: 'lazy', decoding: 'async' }) : null]),
				el('div', { class: 'zcb-apt__b' }, [
					el('h4', { text: a.name }),
					el('p', { class: 'zcb-apt__meta' }, [icon('users'), t('capacity', { n: a.capacity }), ' · ', icon('area'), t('size', { n: a.size }), ' · ', a.bedrooms ? t('bedrooms', { n: a.bedrooms }) : t('studio')]),
					a.short ? el('p', { class: 'zcb-apt__short', text: a.short }) : null,
					!free ? el('p', { class: 'zcb-apt__warn', text: t('bk_unavailable') }) : (small ? el('p', { class: 'zcb-apt__warn', text: t('bk_too_small', { n: a.capacity }) }) : price),
					el('div', { class: 'zcb-apt__act' }, [
						el('button', { type: 'button', class: 'zc-btn zc-btn--sm' + (on ? '' : ' zc-btn--ghost'), disabled: (!free || small) ? true : null, 'aria-pressed': on ? 'true' : 'false', onclick: function () {
							if (on) { st.rooms = st.rooms.filter(function (k) { return k !== a.key; }); }
							else if (st.multi) { if (st.rooms.length < (C.maxRooms || 3)) { st.rooms.push(a.key); } }
							else { st.rooms = [a.key]; }
							st.error = ''; render();
						} }, [on ? icon('check') : null, el('span', { text: on ? t('bk_selected') : t('bk_select') })]),
						a.url ? el('a', { class: 'zcb-link', href: a.url, target: '_blank', rel: 'noopener', text: t('details') }) : null
					])
				])
			]);
			list.appendChild(card);
		});
		if (!st.quote || st.quote.__k !== st.from + st.to) {
			var items = C.apartments.map(function (a) { return { apt: a.key, from: st.from, to: st.to, adults: 1, children: 0 }; });
			api('quote', { items: items }).then(function (j) { j.__k = st.from + st.to; st.quote = j; if (st.step === 2) { render(); } });
		}
		return el('div', { class: 'zcb-pane' }, [
			summaryBar(),
			list,
			el('button', { type: 'button', class: 'zcb-add' + (st.multi ? ' is-on' : ''), onclick: function () { st.multi = !st.multi; if (!st.multi && st.rooms.length > 1) { st.rooms = st.rooms.slice(0, 1); } render(); } }, [t('bk_add_room')]),
			errBox(),
			el('div', { class: 'zcb-actions' }, [
				el('button', { type: 'button', class: 'zc-btn zc-btn--ghost', onclick: function () { go(1); } }, [el('span', { text: t('bk_back') })]),
				el('button', { type: 'button', class: 'zc-btn', onclick: function () {
					if (!st.rooms.length) { st.error = t('bk_err_room'); render(); return; }
					if (capSel() < st.adults + st.children) { st.error = t('bk_too_small', { n: capSel() }); render(); return; }
					go(3);
				} }, [el('span', { text: t('bk_next') }), icon('arrow')])
			])
		]);
	}

	/* ---------- 3. Adatok ---------- */
	function field(name, label, type, req, attrs) {
		var id = 'zcb-' + name, input;
		if (type === 'textarea') { input = el('textarea', Object.assign({ id: id, rows: 3 }, attrs || {})); input.value = st.guest[name] || ''; }
		else { input = el('input', Object.assign({ id: id, type: type, value: st.guest[name] || '', required: req ? true : null, autocomplete: (attrs && attrs.autocomplete) || 'on' }, attrs || {})); }
		input.addEventListener('input', function () { st.guest[name] = input.value; });
		return el('label', { class: 'zcb-f' + (type === 'textarea' ? ' zcb-f--full' : ''), for: id }, [el('span', {}, [label, req ? el('b', { text: ' *' }) : null]), input]);
	}
	function check(key, label, link) {
		var id = 'zcb-c-' + key;
		var cb = el('input', { type: 'checkbox', id: id, name: key, required: key === 'gdpr' ? true : null, checked: st[key] ? true : null, onchange: function (e) { st[key] = e.target.checked; } });
		var lab = el('label', { class: 'zcb-check', for: id }, [cb, el('span', { class: 'zcb-check__box', 'aria-hidden': 'true' }, [icon('check')]), el('span', {}, [label])]);
		if (link) { lab.lastChild.appendChild(d.createTextNode(' ')); lab.lastChild.appendChild(el('a', { href: link, target: '_blank', rel: 'noopener', text: '↗' })); }
		return lab;
	}
	function step3() {
		var hp = el('input', { type: 'text', name: 'zc_hp', class: 'zcb-hp', tabindex: '-1', autocomplete: 'off', 'aria-hidden': 'true' });
		var extras = C.extras && C.extras.length ? el('fieldset', { class: 'zcb-fs' }, [el('legend', { text: t('bk_extras') })].concat(C.extras.map(function (x) {
			var id = 'zcb-x-' + x.key, on = st.extras.indexOf(x.key) >= 0;
			return el('label', { class: 'zcb-check', for: id }, [
				el('input', { type: 'checkbox', id: id, checked: on ? true : null, onchange: function (e) { if (e.target.checked) { st.extras.push(x.key); } else { st.extras = st.extras.filter(function (k) { return k !== x.key; }); } } }),
				el('span', { class: 'zcb-check__box', 'aria-hidden': 'true' }, [icon('check')]),
				el('span', { text: x.label + (x.price > 0 ? ' (+' + money(x.price) + ')' : '') })
			]);
		}))) : null;
		var pays = el('fieldset', { class: 'zcb-fs' }, [el('legend', { text: t('bk_payment') })].concat((C.payments || []).map(function (p) {
			var id = 'zcb-p-' + p;
			return el('label', { class: 'zcb-radio', for: id }, [
				el('input', { type: 'radio', name: 'zcb-pay', id: id, value: p, checked: st.payment === p ? true : null, onchange: function () { st.payment = p; } }),
				el('span', { class: 'zcb-radio__dot', 'aria-hidden': 'true' }),
				el('span', { text: t('bk_pay_' + p) })
			]);
		})));
		return el('form', { class: 'zcb-pane', novalidate: true, onsubmit: function (e) { e.preventDefault(); next3(); } }, [
			summaryBar(),
			el('div', { class: 'zcb-form' }, [
				field('name', t('bk_name'), 'text', true, { autocomplete: 'name' }),
				field('email', t('bk_email'), 'email', true, { autocomplete: 'email' }),
				field('phone', t('bk_phone'), 'tel', true, { autocomplete: 'tel' }),
				field('country', t('bk_country'), 'text', false, { autocomplete: 'country-name' }),
				field('arrival_time', t('bk_arrival_time'), 'time', false, { min: '00:00', step: 900 }),
				field('message', t('bk_message'), 'textarea', false)
			]),
			extras, pays,
			el('div', { class: 'zcb-checks' }, [
				check('gdpr', t('bk_gdpr'), C.privacy),
				check('newsletter', t('bk_newsletter'))
			]),
			hp, errBox(),
			el('div', { class: 'zcb-actions' }, [
				el('button', { type: 'button', class: 'zc-btn zc-btn--ghost', onclick: function () { go(2); } }, [el('span', { text: t('bk_back') })]),
				el('button', { type: 'submit', class: 'zc-btn' }, [el('span', { text: t('bk_next') }), icon('arrow')])
			])
		]);
	}
	function next3() {
		var g = st.guest;
		if (!g.name.trim() || !g.email.trim() || !g.phone.trim()) { st.error = t('bk_err_required'); render(); return; }
		if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(g.email.trim())) { st.error = t('bk_err_email'); render(); return; }
		if (!st.gdpr) { st.error = t('bk_err_gdpr'); render(); return; }
		st.quote4 = null; go(4);
	}

	/* ---------- 4. Összegzés ---------- */
	function splitGuests() {
		// Vendégek elosztása a kiválasztott apartmanok között (kapacitás szerint).
		var a = st.adults, c = st.children;
		return st.rooms.map(function (k, i) {
			var cap = APT[k].capacity, last = i === st.rooms.length - 1;
			var ad = last ? a : Math.min(a, Math.max(1, cap)); a -= ad;
			var ch = last ? c : Math.min(c, Math.max(0, cap - ad)); c -= ch;
			return { apt: k, from: st.from, to: st.to, adults: Math.max(1, ad), children: Math.max(0, ch) };
		});
	}
	function step4() {
		var items = splitGuests(), box = el('div', { class: 'zcb-pane' }, [summaryBar()]);
		if (!st.quote4) {
			box.appendChild(el('div', { class: 'zcb-skel' }, [el('span'), el('span'), el('span')]));
			api('quote', { items: items }).then(function (j) { st.quote4 = j; render(); });
			return box;
		}
		var qq = st.quote4;
		var rows = el('div', { class: 'zcb-bill' });
		(qq.items || []).forEach(function (it) {
			rows.appendChild(el('div', { class: 'zcb-bill__row' }, [
				el('div', {}, [el('b', { text: APT[it.apt].name }), el('small', { text: fmt(it.from) + ' → ' + fmt(it.to) + ' · ' + t('bk_nights', { n: it.nights }) + ' · ' + (it.adults + it.children) + ' ' + t('bk_guests').toLowerCase() })]),
				el('span', { text: qq.quoteMode ? '—' : money(it.total) })
			]));
		});
		if (qq.quoteMode) { rows.appendChild(el('p', { class: 'zcb-note', text: t('bk_quote_note') })); }
		else {
			rows.appendChild(el('div', { class: 'zcb-bill__row zcb-bill__row--total' }, [el('b', { text: t('bk_total') }), el('b', { text: money(qq.total) })]));
			rows.appendChild(el('div', { class: 'zcb-bill__row' }, [el('span', { text: t('bk_deposit', { pct: qq.depositPct }) }), el('span', { text: money(qq.deposit) })]));
		}
		box.appendChild(rows);
		box.appendChild(el('ul', { class: 'zcb-info' }, [
			el('li', { text: t('bk_tax_note', { tax: C.tax }) }),
			el('li', { text: t('bk_cancel_policy', { days: C.cancelDays }) }),
			el('li', { class: 'zcb-sync' }, [icon('sync'), t('bk_sync_note')])
		]));
		box.appendChild(el('div', { class: 'zcb-guest' }, [
			el('p', {}, [el('b', { text: st.guest.name }), ' · ' + st.guest.email + ' · ' + st.guest.phone]),
			st.guest.message ? el('p', { text: st.guest.message }) : null
		]));
		if (st.error) { box.appendChild(errBox()); }
		box.appendChild(el('div', { class: 'zcb-actions' }, [
			el('button', { type: 'button', class: 'zc-btn zc-btn--ghost', disabled: st.sending ? true : null, onclick: function () { go(3); } }, [el('span', { text: t('bk_back') })]),
			el('button', { type: 'button', class: 'zc-btn zcb-submit', disabled: st.sending ? true : null, onclick: submit }, [el('span', { text: st.sending ? t('bk_sending') : (C.instant ? t('bk_submit_instant') : t('bk_submit')) }), st.sending ? null : icon('arrow')])
		]));
		return box;
	}
	function submit() {
		if (st.sending) { return; }
		st.sending = true; st.error = ''; render();
		var hp = root.querySelector('.zcb-hp');
		api('booking', {
			items: splitGuests(), guest: st.guest, extras: st.extras, payment: st.payment,
			gdpr: st.gdpr, newsletter: st.newsletter, lang: C.lang, zc_hp: hp ? hp.value : '', zc_ts: st.ts
		}).then(function (j) {
			st.sending = false;
			if (j && j.ok) { st.result = j; go(5); return; }
			st.error = (j && j.message) || t('bk_err_generic', { phone: C.phone });
			if (j && j.error === 'taken') { AV.reset(); st.quote = null; }
			render();
		}).catch(function () { st.sending = false; st.error = t('bk_err_generic', { phone: C.phone }); render(); });
	}

	/* ---------- 5. Kész ---------- */
	function ics() {
		var dt = function (s) { return s.replace(/-/g, ''); };
		var body = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//ZEN Central//Booking//EN', 'BEGIN:VEVENT',
			'UID:' + st.result.ref + '@zencentral', 'DTSTAMP:' + new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z',
			'DTSTART;VALUE=DATE:' + dt(st.from), 'DTEND;VALUE=DATE:' + dt(st.to),
			'SUMMARY:' + C.site + ' (' + st.result.ref + ')', 'LOCATION:' + C.address, 'END:VEVENT', 'END:VCALENDAR'].join('\r\n');
		return 'data:text/calendar;charset=utf-8,' + encodeURIComponent(body);
	}
	function step5() {
		return el('div', { class: 'zcb-pane zcb-done' }, [
			el('div', { class: 'zcb-done__mark', 'aria-hidden': 'true' }, [icon('check')]),
			el('h3', { text: t('bk_done_title') }),
			el('p', { class: 'zcb-done__ref' }, [el('b', { text: st.result.ref })]),
			el('p', { text: st.result.message || '' }),
			summaryBar(),
			el('div', { class: 'zcb-actions zcb-actions--center' }, [
				el('a', { class: 'zc-btn zc-btn--ghost', href: ics(), download: 'zen-central-' + st.result.ref + '.ics' }, [el('span', { text: t('bk_add_calendar') })]),
				el('button', { type: 'button', class: 'zc-btn', onclick: function () { st.from = st.to = null; st.rooms = []; st.result = null; st.quote = st.quote4 = null; st.ts = Date.now(); go(1); } }, [el('span', { text: t('bk_new') })])
			])
		]);
	}

	/* ---------- Renderelés ---------- */
	function go(n) { st.step = n; st.error = ''; render(); var top = root.getBoundingClientRect().top + window.scrollY - 120; if (window.scrollY > top) { window.scrollTo({ top: top, behavior: 'smooth' }); } var h = root.querySelector('.zcb-pane'); if (h) { h.setAttribute('tabindex', '-1'); h.focus({ preventScroll: true }); } }
	function render() {
		var pane = st.step === 1 ? step1() : st.step === 2 ? step2() : st.step === 3 ? step3() : st.step === 4 ? step4() : step5();
		var card = el('div', { class: 'zcb-card' }, [
			el('header', { class: 'zcb-head' }, [
				el('div', {}, [el('p', { class: 'zcb-kicker', text: C.site }), el('h3', { class: 'zcb-title', text: t('bk_title') })]),
				el('p', { class: 'zcb-badge' }, [icon('check'), t('best_price')])
			]),
			stepper(),
			pane,
			help()
		]);
		root.innerHTML = '';
		root.appendChild(card);
		root.setAttribute('aria-busy', 'false');
	}

	// Előtöltés: ha az URL-ben dátum van, a 2. lépéssel indul.
	if (st.from && st.to) {
		AV.load(st.month, 2).then(function () { st.step = 2; render(); });
	} else { render(); }
	// Másik hónap előtöltése a gyors lapozáshoz.
	AV.load(st.month, 3).then(function () { if (st.step === 1) { render(); } });
	setTimeout(function () { AV.load(st.month + 3, 3); }, 1500);
})();
