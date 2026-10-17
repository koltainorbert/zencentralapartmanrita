/* Zen Forms – AJAX űrlapok, hírlevél modal, csillagos értékelés (vanilla) */
(function () {
	'use strict';
	var C = window.ZenFormsCfg || { str: {} }, d = document;
	function t(k) { return (C.str && C.str[k]) || k; }
	var start = Date.now();
	Array.prototype.forEach.call(d.querySelectorAll('[data-zcf] [data-ts]'), function (i) { i.value = String(start); });

	function data(form) {
		var o = { lang: C.lang, page: location.href };
		Array.prototype.forEach.call(form.elements, function (e) {
			if (!e.name) { return; }
			if (e.type === 'checkbox') { o[e.name] = e.checked ? 1 : 0; }
			else if (e.type === 'radio') { if (e.checked) { o[e.name] = e.value; } }
			else { o[e.name] = e.value; }
		});
		return o;
	}
	function msg(form, text, ok) {
		var m = form.querySelector('.zcf-msg');
		if (!m) { return; }
		m.textContent = text || '';
		m.className = 'zcf-msg' + (text ? (ok ? ' is-ok' : ' is-err') : '');
	}
	d.addEventListener('submit', function (e) {
		var form = e.target.closest('[data-zcf]');
		if (!form) { return; }
		e.preventDefault();
		var o = data(form), type = o.form;
		var req = Array.prototype.filter.call(form.querySelectorAll('[required]'), function (x) {
			if (x.type === 'radio') { return !form.querySelector('input[name="' + x.name + '"]:checked'); }
			if (x.type === 'checkbox') { return !x.checked; }
			return !String(x.value).trim();
		});
		Array.prototype.forEach.call(form.querySelectorAll('.is-invalid'), function (x) { x.classList.remove('is-invalid'); });
		if (req.length) {
			req.forEach(function (x) { (x.closest('.zcf-field,.zcf-check,.zcf-rate') || x).classList.add('is-invalid'); });
			var gd = req.filter(function (x) { return x.name === 'gdpr'; }).length === req.length;
			msg(form, t(gd ? 'fm_err_gdpr' : 'fm_err_required'), false);
			req[0].focus();
			return;
		}
		if (o.email && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(o.email).trim())) { msg(form, t('fm_err_email'), false); return; }
		var btn = form.querySelector('.zcf-submit'), label = btn ? btn.querySelector('span') : null, old = label ? label.textContent : '';
		if (btn) { btn.disabled = true; if (label) { label.textContent = t('fm_sending'); } }
		msg(form, '', true);
		fetch(C.rest + type, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(o) })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (btn) { btn.disabled = false; if (label) { label.textContent = old; } }
				if (!j || !j.ok) { msg(form, (j && j.message) || t('fm_err_generic'), false); return; }
				form.classList.add('is-sent');
				msg(form, j.message, true);
				if (type === 'feedback') {
					var box = d.createElement('div');
					box.className = 'zcf-next';
					var p = d.createElement('p'); p.textContent = j.next || ''; box.appendChild(p);
					if (j.high) {
						[['google', 'fm_fb_google'], ['booking', 'fm_fb_booking']].forEach(function (x) {
							if (!C[x[0]]) { return; }
							var a = d.createElement('a'); a.className = 'zc-btn zc-btn--sm'; a.href = C[x[0]]; a.target = '_blank'; a.rel = 'noopener';
							var s = d.createElement('span'); s.textContent = t(x[1]); a.appendChild(s); box.appendChild(a);
						});
					}
					form.appendChild(box);
				}
				Array.prototype.forEach.call(form.querySelectorAll('input:not([type=hidden]):not([type=radio]):not([type=checkbox]), textarea, select'), function (x) { x.value = ''; });
			})
			.catch(function () { if (btn) { btn.disabled = false; if (label) { label.textContent = old; } } msg(form, t('fm_err_generic'), false); });
	});

	/* Hírlevél modal */
	var modal = d.querySelector('[data-zcf-modal]'), last = null;
	function open() { if (!modal) { return; } last = d.activeElement; modal.hidden = false; d.body.style.overflow = 'hidden'; var f = modal.querySelector('input:not([type=hidden]):not(.zcf-hp)'); if (f) { setTimeout(function () { f.focus(); }, 60); } }
	function close() { if (!modal || modal.hidden) { return; } modal.hidden = true; d.body.style.overflow = ''; if (last) { last.focus(); } }
	d.addEventListener('click', function (e) {
		if (e.target.closest('[data-zen-newsletter]')) { e.preventDefault(); open(); }
		else if (e.target.closest('[data-zcf-close]') || e.target === modal) { close(); }
	});
	d.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
	if (location.hash === '#hirlevel' || location.hash === '#newsletter') { open(); }
})();
