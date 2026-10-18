/* Zen Studio – admin alkalmazás */
(function () {
	'use strict';
	var Z = window.ZenStudio, F = window.ZF, h = F.h;
	var root = document.getElementById('zen-studio');
	if (!Z || !root) { return; }
	var S = F.clone(Z.settings), A = F.clone(Z.apartments), dirty = false, saving = false;
	var LANGS = function () { var en = (S.languages && S.languages.enabled) || ['hu', 'en', 'de']; return en.length ? en : ['hu']; };
	function api(path, method, body) {
		return fetch(Z.rest + path, { method: method || 'GET', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': Z.nonce }, credentials: 'same-origin', body: body ? JSON.stringify(body) : undefined }).then(function (r) { return r.json(); });
	}
	function toast(msg, ok) {
		var t = h('div', { class: 'zs-toast' + (ok === false ? ' is-err' : '') , role: 'status', text: msg });
		document.body.appendChild(t);
		requestAnimationFrame(function () { t.classList.add('is-in'); });
		setTimeout(function () { t.classList.remove('is-in'); setTimeout(function () { t.remove(); }, 400); }, 2600);
	}
	function mark() { dirty = true; saveBtn.classList.add('is-dirty'); saveBtn.disabled = false; }
	function card(title, kids, desc) { return h('section', { class: 'zs-card' }, [title ? h('h2', { class: 'zs-card__t', text: title }) : null, desc ? h('p', { class: 'zs-card__d', html: desc }) : null, h('div', { class: 'zs-card__b' }, kids)]); }
	function fields(defs, model) { return defs.map(function (d) { return F.field(d, model || S, mark); }); }
	function grid(kids) { return h('div', { class: 'zs-grid2' }, kids); }

	/* ================= Fejléc + navigáció ================= */
	var TABS = [
		['dashboard', 'Áttekintés', 'M3 13h8V3H3zm10 8h8V11h-8zM3 21h8v-6H3zm10-18v6h8V3z'],
		['general', 'Általános', 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2zm0-8h-2V7h2z'],
		['design', 'Design & skinek', 'M12 3a9 9 0 0 0 0 18c1 0 1.5-.8 1.5-1.5 0-.4-.2-.7-.4-1-.3-.3-.4-.6-.4-1 0-.8.7-1.5 1.5-1.5H16a5 5 0 0 0 5-5c0-4.4-4-8-9-8zM6.5 12a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm3-4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm3 4a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z'],
		['apartments', 'Apartmanok', 'M3 21V9l9-6 9 6v12h-6v-7H9v7z'],
		['pages', 'Oldalak & tartalom', 'M6 2h9l5 5v15H6zm8 1.5V8h4.5z'],
		['languages', 'Nyelvek & szövegek', 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm6.9 6h-3a15.7 15.7 0 0 0-1.4-3.6A8 8 0 0 1 18.9 8zM12 4a14 14 0 0 1 1.9 4h-3.8A14 14 0 0 1 12 4z'],
		['booking', 'Foglalás & Booking/Airbnb', 'M7 2v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2zm-2 8h14v10H5z'],
		['ai', 'Zen AI', 'M12 2l2.4 5.6L20 10l-5.6 2.4L12 18l-2.4-5.6L4 10l5.6-2.4z'],
		['forms', 'Űrlapok & vélemények', 'M4 4h16v12H8l-4 4z'],
		['seo', 'SEO & AI SEO', 'M10 2a8 8 0 1 0 4.9 14.3l5.4 5.4 1.4-1.4-5.4-5.4A8 8 0 0 0 10 2zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12z'],
		['cloudflare', 'Cloudflare & sebesség', 'M13 2 3 14h7l-1 8 10-12h-7z'],
		['tools', 'Import / Export', 'M12 3v12m0 0-4-4m4 4 4-4M4 17v3h16v-3']
	];
	var current = (location.hash || '#dashboard').slice(1);
	if (!TABS.some(function (t) { return t[0] === current; })) { current = 'dashboard'; }
	var nav = h('nav', { class: 'zs-nav', 'aria-label': 'Zen Studio' });
	var main = h('main', { class: 'zs-main' });
	var saveBtn = h('button', { type: 'button', class: 'zs-btn zs-save', disabled: true, onclick: save }, ['Mentés']);
	var top = h('header', { class: 'zs-top' }, [
		h('div', { class: 'zs-brand' }, [h('span', { class: 'zs-brand__mark', html: '<svg width="26" height="26" viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="12.5" stroke="currentColor" stroke-width="2.4" stroke-dasharray="70 9" stroke-linecap="round"/><path d="M10.5 18.5c1.6 2 3.4 3 5.5 3s3.9-1 5.5-3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>' }), h('div', {}, [h('b', { text: 'Zen Studio' }), h('small', { text: 'ZEN Central Apartments · v' + Z.version })])]),
		h('div', { class: 'zs-top__act' }, [h('a', { class: 'zs-btn zs-btn--ghost', href: Z.home, target: '_blank' }, ['Weboldal megnyitása ↗']), saveBtn])
	]);
	function renderNav() {
		nav.innerHTML = '';
		TABS.forEach(function (t) {
			var a = h('a', { href: '#' + t[0], class: 'zs-nav__a' + (t[0] === current ? ' is-on' : ''), onclick: function (e) { e.preventDefault(); go(t[0]); } }, [h('span', { class: 'zs-nav__i', html: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="' + t[2] + '"/></svg>' }), t[1]]);
			nav.appendChild(a);
		});
	}
	function go(tab) { current = tab; history.replaceState(null, '', '#' + tab); renderNav(); render(); window.scrollTo(0, 0); }
	root.innerHTML = '';
	root.appendChild(top);
	root.appendChild(h('div', { class: 'zs-layout' }, [nav, main]));
	window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

	function save() {
		if (saving) { return; }
		saving = true; saveBtn.textContent = 'Mentés…';
		api('settings', 'POST', { settings: S, apartments: A }).then(function (r) {
			saving = false; saveBtn.textContent = 'Mentés';
			if (r && r.ok) { dirty = false; saveBtn.classList.remove('is-dirty'); saveBtn.disabled = true; S = F.clone(r.data.settings); A = F.clone(r.data.apartments); Z.icalUrls = r.data.icalUrls; toast('Elmentve ✓'); render(); }
			else { toast((r && r.message) || 'Hiba a mentésnél', false); }
		}).catch(function () { saving = false; saveBtn.textContent = 'Mentés'; toast('Hálózati hiba', false); });
	}
	document.addEventListener('keydown', function (e) { if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); if (dirty) { save(); } } });

	/* ================= Fülek ================= */
	var VIEWS = {};

	VIEWS.dashboard = function () {
		var box = h('div', { class: 'zs-stack' }, [h('div', { class: 'zs-skel' })]);
		api('stats').then(function (st) {
			box.innerHTML = '';
			var tiles = h('div', { class: 'zs-tiles' }, [
				tile('Jóváhagyásra váró foglalás', st.pending, 'edit.php?post_type=zen_booking&zb_status=pending', st.pending ? 'warn' : ''),
				tile('Üzenet (7 nap)', st.messages7, 'edit.php?post_type=zen_message'),
				tile('Zen AI beszélgetés (7 nap)', st.chats7, 'edit.php?post_type=zen_chat'),
				tile('Vendégértékelés átlag', (st.feedback.avg || '–') + ' ★', 'edit.php?post_type=zen_feedback', '', st.feedback.count + ' publikált · ' + st.fbPending + ' moderálásra vár')
			]);
			box.appendChild(tiles);
			var up = h('table', { class: 'zs-table' }, [h('thead', {}, [h('tr', {}, ['Azonosító', 'Érkezés', 'Távozás', 'Vendég', 'Apartman', 'Állapot'].map(function (x) { return h('th', { text: x }); }))]), h('tbody', {}, (st.upcoming.length ? st.upcoming : [null]).map(function (b) {
				if (!b) { return h('tr', {}, [h('td', { colspan: 6, class: 'zs-muted', text: 'Nincs közelgő foglalás.' })]); }
				return h('tr', {}, [h('td', {}, [h('a', { href: b.edit, text: b.ref })]), h('td', { text: b.from }), h('td', { text: b.to }), h('td', { text: b.name }), h('td', { text: b.apts }), h('td', {}, [h('span', { class: 'zs-badge zs-badge--' + b.status, text: b.status === 'pending' ? 'Jóváhagyásra vár' : 'Visszaigazolva' })])]);
			}))]);
			box.appendChild(card('Közelgő érkezések', [up, h('p', {}, [h('a', { class: 'zs-btn zs-btn--sm', href: Z.calendar }, ['Foglaltsági naptár & Booking/Airbnb szinkron'])])]));
			var e = st.env, chk = function (ok, label, hint) { return h('li', { class: ok ? 'is-ok' : 'is-warn' }, [h('b', { text: ok ? '✓' : '!' }), label, hint ? h('small', { text: ' – ' + hint }) : null]); };
			box.appendChild(card('Rendszer állapot', [h('ul', { class: 'zs-checks' }, [
				chk(e.theme === 'zen-central', 'ZEN Central téma aktív', e.theme !== 'zen-central' ? 'Megjelenés → Témák' : ''),
				chk(e.permalink, 'Szép URL-ek (permalink)'),
				chk(e.rank_math, 'Rank Math SEO', e.rank_math ? 'a Zen kiegészíti a schemát' : 'nélküle a Zen teljes SEO-t ad'),
				chk(e.site_kit, 'Google Site Kit', e.site_kit ? 'analitika a Site Kitben' : 'GA4/GTM a SEO fülön is megadható'),
				chk(!e.polylang, 'Polylang kikapcsolva', e.polylang ? 'a Zen nyelvkezelő átvette – kapcsold ki' : ''),
				chk(e.ai_key, 'Zen AI kulcs beállítva', e.ai_key ? '' : 'kulcs nélkül GYIK-motorral válaszol'),
				chk(!!st.icalLast, 'Booking/Airbnb szinkron', st.icalLast ? 'utolsó: ' + new Date(st.icalLast * 1000).toLocaleString('hu-HU') : 'add meg az iCal linkeket az Apartmanok fülön'),
				chk(e.cron, 'WP-Cron', e.cron ? 'éles oldalon szerver-cron javasolt (15 percenként)' : 'DISABLE_WP_CRON – állíts be szerver cront'),
				chk(e.cloudflare, 'Cloudflare mögött', e.cloudflare ? '' : 'nem érzékelhető (nem hiba)'),
				chk(true, 'PHP ' + e.php + ' · WordPress ' + e.wp)
			])]));
		}).catch(function () { box.innerHTML = '<p>A statisztika nem tölthető be.</p>'; });
		return box;
		function tile(l, v, href, cls, sub) { return h('a', { class: 'zs-tile ' + (cls ? 'is-' + cls : ''), href: href }, [h('b', { text: String(v) }), h('span', { text: l }), sub ? h('small', { text: sub }) : null]); }
	};

	VIEWS.general = function () {
		return h('div', { class: 'zs-stack' }, [
			card('Szálláshely adatai', [grid(fields([
				{ k: 'general.site_name', l: 'Név' }, { k: 'general.tagline', l: 'Szlogen' },
				{ k: 'general.phone', l: 'Telefon (általános)' }, { k: 'general.phone_booking', l: 'Telefon (foglalás)' },
				{ k: 'general.email', l: 'E-mail', t: 'email' }, { k: 'general.email_cancel', l: 'Lemondási e-mail', t: 'email' },
				{ k: 'general.address_line', l: 'Teljes cím (megjelenő)' }, { k: 'general.street', l: 'Utca, házszám' },
				{ k: 'general.zip', l: 'Irányítószám' }, { k: 'general.city', l: 'Település' },
				{ k: 'general.lat', l: 'Szélesség (GPS)' }, { k: 'general.lng', l: 'Hosszúság (GPS)' },
				{ k: 'general.map_provider', l: 'Térkép', t: 'select', opts: [['google', 'Google utcatérkép + útvonaltervezés (ajánlott)'], ['osm', 'OpenStreetMap (sütimentes)']] }, { k: 'general.map_autoload', l: 'Google térkép betöltése', t: 'select', opts: [['always', 'Mindig magától (ajánlott)'], ['consent', 'Csak süti-elfogadás után, előtte egy kattintással']] }, { k: 'general.google_maps_key', l: 'Google Maps Embed API kulcs (opcionális)', ph: 'AIza…' }, { k: 'general.map_query', l: 'Google térkép keresőkifejezés' }, { k: 'general.google_maps', l: 'Google Maps link', t: 'url' },
				{ k: 'general.facebook', l: 'Facebook', t: 'url' }, { k: 'general.instagram', l: 'Instagram', t: 'url' },
				{ k: 'general.ntak', l: 'NTAK szám' }, { k: 'general.tax_number', l: 'Adószám' }, { k: 'general.owner', l: 'Üzemeltető' }
			]))]),
			card('Szabályok', [grid(fields([
				{ k: 'general.checkin', l: 'Bejelentkezés (óra:perc)' }, { k: 'general.checkout', l: 'Távozás (óra:perc)' },
				{ k: 'general.tourist_tax', l: 'Idegenforgalmi adó (Ft/fő/éj)', t: 'number', min: 0 }, { k: 'general.deposit_pct', l: 'Előleg (%)', t: 'number', min: 0, max: 100 },
				{ k: 'general.free_cancel_days', l: 'Díjmentes lemondás (nap)', t: 'number', min: 0 }
			]))]),
			card('Logók és jelvények', [grid(fields([
				{ k: 'general.logo', l: 'Logó (világos háttérre)', t: 'media', noAlt: false }, { k: 'general.logo_dark', l: 'Logó sötét módhoz (opcionális)', t: 'media' },
				{ k: 'general.payment_logo', l: 'Fizetési partner logó (lábléc)', t: 'media' }, { k: 'general.payment_url', l: 'Fizetési partner link', t: 'url' },
				{ k: 'general.copyright_year', l: 'Copyright év' }, { k: 'general.credit_text', l: 'Készítő szöveg (lábléc)' }
			]))])
		]);
	};

	VIEWS.design = function () {
		var skins = Z.skins, cur = S.design.skin, gridEl = h('div', { class: 'zs-skins' });
		Object.keys(skins).forEach(function (k) {
			var sk = skins[k], mode = document.documentElement.classList.contains('zs-dark') ? 'dark' : 'light';
			var sw = function (p) { return h('div', { class: 'zs-skin__pv', style: 'background:' + p.bg + ';color:' + p.text }, [
				h('div', { class: 'zs-skin__bar', style: 'background:' + p.surface + ';border-color:' + p.line }, [h('i', { style: 'background:' + p.accent }), h('i', { style: 'background:' + p.accent2 }), h('i', { style: 'background:' + p.line })]),
				h('b', { style: 'font-family:' + sk.h + ',serif', text: 'Aa' }),
				h('span', { class: 'zs-skin__btn', style: 'background:' + p.accent + ';color:' + p.ink, text: 'FOGLALÁS' })
			]); };
			var b = h('button', { type: 'button', class: 'zs-skin' + (k === cur ? ' is-on' : ''), onclick: function () { S.design.skin = k; if (!S.design.font_heading || confirm('Átvegyük a skin betűtípusait is? (' + sk.h + ' + ' + sk.b + ')')) { S.design.font_heading = sk.h; S.design.font_body = sk.b; } mark(); render(); } }, [
				h('div', { class: 'zs-skin__duo' }, [sw(sk.light), sw(sk.dark)]),
				h('div', { class: 'zs-skin__meta' }, [h('b', { text: sk.name }), h('small', { text: sk.mood })])
			]);
			gridEl.appendChild(b);
		});
		var fontOpts = Z.fonts.map(function (f) { return [f, f]; });
		return h('div', { class: 'zs-stack' }, [
			card('10 megjelenési forma (skin)', [gridEl], 'Mindegyik világos és sötét változattal. A kiválasztás után mentés – a weboldal azonnal az új hangulatot kapja.'),
			card('Világos / sötét mód', [grid(fields([
				{ k: 'design.mode_default', l: 'Alapértelmezett mód', t: 'select', opts: [['auto', 'Automatikus (a látogató rendszere szerint)'], ['light', 'Világos'], ['dark', 'Sötét']] },
				{ k: 'design.mode_toggle', l: 'Világos/sötét kapcsoló a fejlécben', t: 'toggle' }
			]))]),
			card('Tipográfia és arányok', [grid(fields([
				{ k: 'design.font_source', l: 'Betűforrás', t: 'select', opts: [['bunny', 'Bunny Fonts (GDPR-barát, gyors)'], ['google', 'Google Fonts'], ['system', 'Rendszerbetű (leggyorsabb)']] },
				{ k: 'design.accent_override', l: 'Saját kiemelő szín (felülírja a skint)', t: 'color' },
				{ k: 'design.font_heading', l: 'Címsor betű', t: 'select', opts: fontOpts }, { k: 'design.font_body', l: 'Szövegtörzs betű', t: 'select', opts: fontOpts },
				{ k: 'design.radius', l: 'Lekerekítés (px)', t: 'range', min: 8, max: 36 }, { k: 'design.container', l: 'Tartalom szélessége (px)', t: 'range', min: 1040, max: 1600, step: 20 },
				{ k: 'design.header_style', l: 'Fejléc stílus', t: 'select', opts: [['glass', 'Üveg (lebegő pill)'], ['solid', 'Szilárd'], ['transparent', 'Átlátszó']] }
			]))]),
			card('Effektek és elemek', [grid(fields([
				{ k: 'design.animations', l: 'Finom megjelenési animációk', t: 'toggle' },
				{ k: 'design.rail', l: 'Oldalsó kapcsolat-sáv (10 stílus)', t: 'select', opts: Z.railStyles || [['off', 'Kikapcsolva']] },
				{ k: 'design.sticky_book', l: 'Mobilon „Szabad időpontok” pill a fejlécben', t: 'toggle' }, { k: 'design.post_author', l: 'Blogbejegyzés szerzőjének kiírása', t: 'toggle' },
				{ k: 'design.grain', l: 'Finom szemcse textúra', t: 'toggle' }
			])), F.field({ k: 'design.custom_css', l: 'Egyedi CSS', t: 'code', rows: 8, help: 'Haladó: saját CSS szabályok. Változók: --zc-accent, --zc-bg, --zc-text, --zc-radius…' }, S, mark)])
		]);
	};

	var APT_FIELDS = function () {
		var L = LANGS();
		return [
			{ k: 'key', l: 'Azonosító (ne módosítsd, ha már van oldal)' },
			{ k: 'name', l: 'Név', t: 'langtext', langs: L },
			{ k: 'capacity', l: 'Max. fő', t: 'number', min: 1, max: 12 }, { k: 'size', l: 'Méret (m²)', t: 'number', step: 0.5 },
			{ k: 'bedrooms', l: 'Hálószobák (0 = stúdió)', t: 'number', min: 0 }, { k: 'min_nights', l: 'Min. éjszaka', t: 'number', min: 1 },
			{ k: 'price', l: 'Alapár / éj (Ft) – 0 = árajánlat mód', t: 'number', min: 0, step: 500 }, { k: 'price_weekend', l: 'Hétvégi ár / éj (péntek, szombat)', t: 'number', min: 0, step: 500 },
			{ k: 'short', l: 'Rövid leírás', t: 'langtext', langs: L, multiline: true },
			{ k: 'cover', l: 'Borító (kép vagy videó)', t: 'media' }, { k: 'icon', l: 'Ikon / jelvény', t: 'media' },
			{ k: 'gallery', l: 'Galéria (képek és videók)', t: 'gallery', wide: true },
			{ k: 'color', l: 'Szín (naptárban)', t: 'color' },
			{ k: 'ical_booking', l: 'Booking.com iCal export link (ide másold a Booking naptár linkjét)', t: 'url', ph: 'https://admin.booking.com/hotel/hoteladmin/ical.html?t=…' },
			{ k: 'ical_airbnb', l: 'Airbnb iCal export link', t: 'url', ph: 'https://www.airbnb.com/calendar/ical/….ics?s=…' },
			{ k: 'ical_other', l: 'További iCal naptárak (Szallas.hu, Google stb.)', t: 'list', add: 'Új iCal link', ph: 'https://….ics' }
		];
	};
	VIEWS.apartments = function () {
		var urls = h('div', { class: 'zs-ical' });
		A.forEach(function (a) {
			var u = (Z.icalUrls || {})[a.key];
			if (!u) { return; }
			urls.appendChild(h('div', { class: 'zs-ical__row' }, [h('b', { text: (a.name && (a.name.hu || a.name.en)) || a.key }),
				copy('→ Booking.com-ba', u.booking), copy('→ Airbnb-be', u.airbnb)]));
		});
		function copy(label, val) { var i = h('input', { type: 'text', class: 'zs-input zs-input--sm', readonly: true, value: val, onclick: function () { i.select(); try { navigator.clipboard.writeText(val); toast('Link másolva'); } catch (e) {} } }); return h('label', { class: 'zs-ical__f' }, [h('small', { text: label }), i]); }
		return h('div', { class: 'zs-stack' }, [
			card('Apartmanok', [F.repeater(A, APT_FIELDS(), function (v) { A = v; mark(); }, { item: 'Apartman', add: 'Új apartman', title: function (it) { return it.name && (it.name.hu || it.name.en) || it.key; }, blank: { key: '', name: {}, capacity: 2, size: 20, bedrooms: 0, price: 0, price_weekend: 0, min_nights: 1, short: {}, cover: {}, icon: {}, gallery: [], ical_booking: '', ical_airbnb: '', ical_other: [] } })],
				'Minden kép helyére videó is tehető. Az ár 0 = „Árajánlatot küldünk” mód (mint az eredeti oldalon).'),
			card('Export linkek a Booking.com és az Airbnb felé', [urls, h('p', { class: 'zs-help', html: 'Ezeket a linkeket egyszer illeszd be: <b>Booking.com Extranet</b> → Szobaárak és elérhetőség → Naptár szinkronizálása → Naptár importálása; <b>Airbnb</b> → Naptár → Elérhetőség → Naptárak összekapcsolása → Másik webhely naptárának importálása. Így a weboldalon érkező foglalások automatikusan lezárják a napokat ott is. <a href="' + Z.calendar + '">Részletes útmutató és szinkron állapot →</a>' })])
		]);
	};

	VIEWS.pages = function () {
		var box = h('div', { class: 'zs-stack' }, [h('div', { class: 'zs-skel' })]);
		api('pages').then(function (r) {
			box.innerHTML = '';
			var langs = LANGS(), byKey = {};
			r.pages.forEach(function (p) { byKey[p.key] = byKey[p.key] || {}; byKey[p.key][p.lang] = p; });
			var head = h('tr', {}, [h('th', { text: 'Oldal' })].concat(langs.map(function (l) { return h('th', { text: l.toUpperCase() }); })));
			var rows = Object.keys(Z.keys).map(function (k) {
				return h('tr', {}, [h('td', {}, [h('b', { text: Z.keys[k] })])].concat(langs.map(function (l) {
					var p = (byKey[k] || {})[l];
					return h('td', {}, p ? [h('a', { class: 'zs-chip' + (p.status !== 'publish' ? ' is-draft' : ''), href: p.edit, title: p.title }, ['Szerkesztés']), ' ', h('a', { class: 'zs-chip zs-chip--ghost', href: p.url, target: '_blank', title: 'Megtekintés' }, ['↗'])] : [h('span', { class: 'zs-muted', text: '—' })]);
				})));
			});
			box.appendChild(card('Oldalak nyelvenként', [h('div', { class: 'zs-scroll' }, [h('table', { class: 'zs-table zs-table--pages' }, [h('thead', {}, [head]), h('tbody', {}, rows)])]),
				h('p', { class: 'zs-help', html: 'Minden oldal szekciókból áll (hero, bevezető, kártyák, galéria, GYIK…). A <b>Szerkesztés</b> gombbal minden szöveg, kép és videó módosítható, szekciók hozzáadhatók, sorrendezhetők, kikapcsolhatók.' })]));
			var sel = h('select', { class: 'zs-input zs-select' }, Z.langs.filter(function (l) { return l.seed; }).map(function (l) { return h('option', { value: l.code }, [l.native + ' (' + l.code + ')']); }));
			box.appendChild(card('Hiányzó nyelvi oldalak létrehozása', [h('div', { class: 'zs-row' }, [sel,
				h('button', { type: 'button', class: 'zs-btn', onclick: function () { api('seed', 'POST', { lang: sel.value }).then(function (x) { toast('Kész: ' + (x.pages_created || 0) + ' új oldal'); render(); }); } }, ['Létrehozás']),
				h('button', { type: 'button', class: 'zs-btn zs-btn--ghost', onclick: function () { if (confirm('A kiválasztott nyelv oldalainak tartalmát VISSZAÁLLÍTJA az eredeti/seed állapotra. Biztos?')) { api('seed', 'POST', { lang: sel.value, force: true }).then(function () { toast('Visszaállítva'); render(); }); } } }, ['Eredeti tartalom visszaállítása'])
			])], 'A nyelvi oldalak az eredeti URL-eken (pl. /en/…, /de/…) jönnek létre. A meglévő módosításaidat a „Létrehozás” nem írja felül.'));
		});
		return box;
	};

	VIEWS.languages = function () {
		var en = S.languages.enabled || [];
		var tg = h('div', { class: 'zs-langs' }, Z.langs.map(function (l) {
			var on = en.indexOf(l.code) >= 0;
			return h('div', { class: 'zs-lang' + (on ? ' is-on' : '') }, [F.toggle(on, function (v) { var a = S.languages.enabled || []; if (v && a.indexOf(l.code) < 0) { a.push(l.code); } if (!v) { a = a.filter(function (x) { return x !== l.code || x === S.languages.default; }); } S.languages.enabled = a; mark(); }, l.native), h('span', {}, [h('b', { text: l.native }), h('small', { text: l.code.toUpperCase() + (l.seed ? ' · tartalom kész' : ' · csak felület') })])]);
		}));
		var strBox = h('div', {}), langSel = h('select', { class: 'zs-input zs-select', onchange: function () { loadStr(langSel.value); } }, Z.langs.map(function (l) { return h('option', { value: l.code }, [l.native]); }));
		var search = h('input', { type: 'search', class: 'zs-input', placeholder: 'Keresés a szövegekben…', oninput: function () { filter(); } });
		var STR = null;
		function loadStr(lang) {
			strBox.innerHTML = '<div class="zs-skel"></div>';
			api('strings?lang=' + lang).then(function (r) { STR = r; drawStr(); });
		}
		function drawStr() {
			strBox.innerHTML = '';
			var tbl = h('div', { class: 'zs-strings' });
			Object.keys(STR.base).forEach(function (k) {
				var ov = STR.over[k] || '';
				var inp = h('textarea', { class: 'zs-input zs-input--sm', rows: 1, placeholder: STR.base[k], value: ov, oninput: function (e) { STR.over[k] = e.target.value; strSave.disabled = false; } });
				tbl.appendChild(h('div', { class: 'zs-str', 'data-s': (k + ' ' + STR.base[k] + ' ' + (STR.hu[k] || '')).toLowerCase() }, [h('code', { text: k }), h('small', { text: STR.hu[k] || '' }), inp]));
			});
			strBox.appendChild(tbl);
			filter();
		}
		function filter() { var q = (search.value || '').toLowerCase(); Array.prototype.forEach.call(strBox.querySelectorAll('.zs-str'), function (r) { r.hidden = q && r.getAttribute('data-s').indexOf(q) < 0; }); }
		var strSave = h('button', { type: 'button', class: 'zs-btn', disabled: true, onclick: function () { api('settings', 'POST', { strings: STR.over, strings_lang: STR.lang }).then(function (r) { if (r.ok) { toast('Szövegek elmentve ✓'); strSave.disabled = true; } }); } }, ['Szövegek mentése']);
		setTimeout(function () { loadStr('hu'); }, 0);
		return h('div', { class: 'zs-stack' }, [
			card('Bekapcsolt nyelvek', [tg, grid(fields([
				{ k: 'languages.default', l: 'Alapnyelv (gyökér URL)', t: 'select', opts: Z.langs.map(function (l) { return [l.code, l.native]; }) },
				{ k: 'languages.ui', l: 'Nyelvváltó stílusa', t: 'select', opts: [['popup', 'Felugró üveg ablak középen (ajánlott)'], ['dropdown', 'Legördülő lista a fejlécben']] }
			]))], 'A nyelvváltóban csak azok a nyelvek jelennek meg, amelyekhez már van oldal (Oldalak fül → Hiányzó nyelvi oldalak).'),
			card('Felületi szövegek (menü, gombok, űrlapok, foglaló, Zen AI) – minden nyelven', [h('div', { class: 'zs-row' }, [langSel, search, strSave]), strBox], 'Az üres mező az alapértelmezett szöveget használja (szürkén látszik). Csak amit módosítani akarsz, azt írd be.')
		]);
	};

	VIEWS.booking = function () {
		var L = LANGS();
		var aptPrices = {};
		A.forEach(function (a) { aptPrices[a.key] = 0; });
		var seasonFields = [{ k: 'name', l: 'Szezon neve' }, { k: 'from', l: 'Kezdete', t: 'date' }, { k: 'to', l: 'Vége', t: 'date' }, { k: 'min_nights', l: 'Min. éj', t: 'number', min: 1 }].concat(A.map(function (a) { return { k: 'prices.' + a.key, l: ((a.name && a.name.hu) || a.key) + ' ár/éj (Ft)', t: 'number', min: 0, step: 500 }; }));
		return h('div', { class: 'zs-stack' }, [
			card('Foglalómotor', [grid(fields([
				{ k: 'booking.engine', l: 'Motor', t: 'select', opts: [['native', 'Saját Zen motor (ajánlott) – Booking.com + Airbnb iCal szinkron'], ['nhb', 'NetHotelBooking beágyazás (régi, csak tartalék)']] },
				{ k: 'booking.auto_confirm', l: 'Azonnali visszaigazolás (csak ha minden apartmannak van iCal szinkronja)', t: 'toggle' },
				{ k: 'booking.notify_email', l: 'Értesítési e-mail', t: 'email' }, { k: 'booking.currency', l: 'Pénznem', t: 'select', opts: [['HUF', 'HUF (Ft)'], ['EUR', 'EUR (€)']] },
				{ k: 'booking.min_nights', l: 'Min. éjszaka (általános)', t: 'number', min: 1 }, { k: 'booking.max_advance', l: 'Legfeljebb ennyi nappal előre', t: 'number', min: 30 },
				{ k: 'booking.max_rooms', l: 'Max. apartman egy foglalásban', t: 'number', min: 1, max: 3 }, { k: 'booking.cleaning_fee', l: 'Takarítási díj / foglalás (Ft)', t: 'number', min: 0, step: 500 },
				{ k: 'booking.sync_minutes', l: 'Booking/Airbnb szinkron (perc)', t: 'number', min: 5, max: 120 }, { k: 'booking.resync_before_booking', l: 'Friss szinkron foglalás előtt, ha régebbi mint (perc)', t: 'number', min: 1, max: 60 }
			]))], 'Alapból „foglalási kérelem” mód: a vendég kérelmet küld, te egy kattintással jóváhagyod – így a Booking.com/Airbnb szinkron késése sem okozhat dupla foglalást.'),
			card('Fizetés', [grid(fields([
				{ k: 'booking.payments', l: 'Fizetési módok', t: 'list', add: 'Új mód (transfer / cash / card)' },
				{ k: 'booking.card_payment_url', l: 'Online kártyás fizetési link (opcionális)', t: 'url' }
			])), F.field({ k: 'booking.bank_details', l: 'Bankadatok az előleg átutalásához (visszaigazoló e-mailbe kerül)', t: 'textarea', rows: 3 }, S, mark)]),
			card('Árak szezononként', [F.field({ k: 'booking.seasons', t: 'repeater', fields: seasonFields, item: 'Szezon', add: 'Új szezon', blank: { name: '', from: '', to: '', min_nights: 1, prices: aptPrices } }, S, mark)], 'Ha egy éjszakára nincs ár (0), a foglaló „Árajánlatot küldünk” módban működik.'),
			card('Események a naptárban (piros pont)', [F.field({ k: 'booking.events', t: 'repeater', fields: [{ k: 'name', l: 'Esemény neve' }, { k: 'from', l: 'Kezdete', t: 'date' }, { k: 'to', l: 'Vége', t: 'date' }], item: 'Esemény', add: 'Új esemény', blank: { name: '', from: '', to: '' } }, S, mark)]),
			card('Kézi zárások (karbantartás, saját használat)', [F.field({ k: 'booking.blocked', t: 'repeater', fields: [{ k: 'apt', l: 'Apartman', t: 'select', opts: [['all', 'Mindegyik']].concat(A.map(function (a) { return [a.key, (a.name && a.name.hu) || a.key]; })) }, { k: 'from', l: 'Első zárt éj', t: 'date' }, { k: 'to', l: 'Távozás napja (kizárva)', t: 'date' }, { k: 'note', l: 'Megjegyzés' }], item: 'Zárás', add: 'Új zárás', blank: { apt: 'all', from: '', to: '', note: '' } }, S, mark)]),
			card('Extrák', [F.field({ k: 'booking.extras', t: 'repeater', fields: [{ k: 'key', l: 'Azonosító' }, { k: 'label', l: 'Felirat', t: 'langtext', langs: L }, { k: 'price', l: 'Ár (Ft, 0 = egyeztetés)', t: 'number', min: 0 }, { k: 'per', l: 'Egység', t: 'select', opts: [['stay', 'foglalásonként'], ['night', 'éjszakánként']] }], item: 'Extra', add: 'Új extra', blank: { key: '', label: {}, price: 0, per: 'stay' } }, S, mark)]),
			card('NetHotelBooking (csak ha visszakapcsolnád)', [grid(fields([{ k: 'booking.nhb_url', l: 'NHB URL', t: 'url' }, { k: 'booking.nhb_script', l: 'NHB script', t: 'url' }]))])
		]);
	};

	VIEWS.ai = function () {
		var kbTa = h('textarea', { class: 'zs-input zs-input--code', rows: 14, spellcheck: 'false', value: JSON.stringify(Z.kb || {}, null, 2) });
		var testOut = h('div', { class: 'zs-ai-test' }), testIn = h('input', { type: 'text', class: 'zs-input', value: 'Mit érdemes megnézni Veszprémben egy esős napon?' });
		return h('div', { class: 'zs-stack' }, [
			card('Zen – mesterséges intelligenciás concierge és súgó', [grid(fields([
				{ k: 'ai.enabled', l: 'Zen AI bekapcsolva', t: 'toggle' }, { k: 'ai.name', l: 'Asszisztens neve' },
				{ k: 'ai.provider', l: 'Szolgáltató', t: 'select', opts: [['anthropic', 'Anthropic Claude (ajánlott)'], ['openai', 'OpenAI'], ['gemini', 'Google Gemini']] },
				{ k: 'ai.model', l: 'Modell', ph: 'pl. claude-sonnet-5-5 / gpt-4o-mini / gemini-2.5-flash' },
				{ k: 'ai.api_key', l: 'API kulcs (titkosítva tárolva)', t: 'password', ph: 'sk-…' },
				{ k: 'ai.temperature', l: 'Kreativitás', t: 'range', min: 0, max: 1, step: 0.1 }, { k: 'ai.max_tokens', l: 'Max. válaszhossz (token)', t: 'number', min: 150, max: 2000, step: 50 },
				{ k: 'ai.position', l: 'Gomb helye', t: 'select', opts: [['right', 'Jobbra lent'], ['left', 'Balra lent']] },
				{ k: 'ai.avatar', l: 'Avatar (opcionális)', t: 'media' },
				{ k: 'ai.log_chats', l: 'Beszélgetések naplózása (CRM, 180 nap)', t: 'toggle' }, { k: 'ai.collect_leads', l: 'E-mail/telefon kiemelése (lead)', t: 'toggle' },
				{ k: 'ai.web_search', l: 'Élő webes keresés (aktuális programok, nyitvatartás) – Anthropic/Gemini', t: 'toggle' }, { k: 'ai.web_search_max', l: 'Keresések száma válaszonként', t: 'number', min: 1, max: 8, step: 1 },
				{ k: 'ai.event_feeds', l: 'Programnaptár feedek (iCal / RSS / JSON URL-ek) – naponta kétszer frissül', t: 'list', add: 'Új feed URL' }
			]))], 'Kulcs nélkül is működik: ilyenkor a beépített GYIK-motor válaszol. Zen látja a weboldal teljes tartalmát (minden nyelven), a szabad időszakokat és a Veszprém tudásbázist.'),
			card('Viselkedés (rendszer-prompt)', [F.field({ k: 'ai.system_prompt', t: 'textarea', rows: 12, ph: Z.defaultPrompt }, S, mark), h('div', { class: 'zs-row' }, [h('button', { type: 'button', class: 'zs-btn zs-btn--ghost zs-btn--sm', onclick: function () { S.ai.system_prompt = Z.defaultPrompt; mark(); render(); } }, ['Alapértelmezett betöltése szerkesztéshez']), h('button', { type: 'button', class: 'zs-btn zs-btn--ghost zs-btn--sm', onclick: function () { S.ai.system_prompt = ''; mark(); render(); } }, ['Visszaállítás alapra'])]), F.field({ k: 'ai.kb_extra', l: 'Saját tudnivalók (akciók, kódok, házirend, helyi tippek)', t: 'textarea', rows: 5 }, S, mark)]),
			card('Élő programok (feedek)', [h('p', { class: 'zs-help', text: Z.eventsLast ? 'Utolsó frissítés: ' + new Date(Z.eventsLast * 1000).toLocaleString('hu-HU') + ' · ' + Z.eventsCount + ' program a listában.' : 'Még nem volt frissítés.' }), h('div', { class: 'zs-row' }, [h('button', { type: 'button', class: 'zs-btn zs-btn--sm', onclick: function (e) { var b = e.currentTarget; b.disabled = true; b.textContent = 'Frissítés…'; api('events', 'POST', {}).then(function (r) { b.disabled = false; b.textContent = 'Feedek frissítése most'; var ok = 0, n = 0; Object.keys(r.status || {}).forEach(function (k) { if (r.status[k].ok) { ok++; } n += r.status[k].count || 0; }); toast('Feedek: ' + ok + ' működik · ' + n + ' program'); }); } }, ['Feedek frissítése most'])])], 'A feedeket a fenti Zen AI kártyán adod meg (mentés után frissíts). Zen ezekből és élő webes kereséssel ismeri az aktuális veszprémi programokat.'),
			card('Veszprém tudásbázis (JSON)', [kbTa, h('div', { class: 'zs-row' }, [h('button', { type: 'button', class: 'zs-btn zs-btn--sm', onclick: function () { try { JSON.parse(kbTa.value); } catch (e) { toast('Hibás JSON: ' + e.message, false); return; } api('settings', 'POST', { kb: kbTa.value }).then(function () { toast('Tudásbázis mentve ✓'); }); } }, ['Tudásbázis mentése']), h('button', { type: 'button', class: 'zs-btn zs-btn--ghost zs-btn--sm', onclick: function () { if (confirm('Visszaállítod a beépített tudásbázist?')) { api('settings', 'POST', { kb: '' }).then(function () { toast('Visszaállítva – frissítsd az oldalt'); }); } } }, ['Beépített visszaállítása'])])], 'Látnivalók, kirándulások, események, éttermek, programtervek – ezekből ajánl Zen. Szerkeszthető.'),
			card('Próba', [h('div', { class: 'zs-row' }, [testIn, h('button', { type: 'button', class: 'zs-btn', onclick: function () { testOut.textContent = 'Zen gondolkodik…'; fetch(Z.restRoot + 'chat', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ messages: [{ role: 'user', content: testIn.value }], lang: 'hu', sid: 'admintest' + Date.now() }) }).then(function (r) { return r.json(); }).then(function (j) { testOut.textContent = (j.reply || '') + (j.actions && j.actions.length ? '\n\n[Gombok: ' + j.actions.map(function (a) { return a.label; }).join(', ') + ']' : ''); }); } }, ['Kérdezem'])]), testOut], 'Mentés után teszteld (az új kulcs a mentéssel lép életbe).')
		]);
	};

	VIEWS.forms = function () {
		return h('div', { class: 'zs-stack' }, [card('Űrlapok, hírlevél, ügyfél-elégedettségi portál', [grid(fields([
			{ k: 'forms.recipient', l: 'Címzett e-mail', t: 'email' }, { k: 'forms.store_messages', l: 'Üzenetek tárolása az adminban', t: 'toggle' },
			{ k: 'forms.feedback_auto_publish', l: 'Jó értékelések automatikus megjelenítése (hozzájárulással)', t: 'toggle' }, { k: 'forms.feedback_min_publish', l: 'Automatikus megjelenítés min. csillag', t: 'number', min: 1, max: 5 },
			{ k: 'forms.google_review_url', l: 'Google értékelés link (elégedett vendégeknek)', t: 'url' }, { k: 'forms.booking_review_url', l: 'Booking.com értékelés link', t: 'url' }
		])), h('p', {}, [h('a', { class: 'zs-btn zs-btn--sm zs-btn--ghost', href: 'edit.php?post_type=zen_message' }, ['Üzenetek']), ' ', h('a', { class: 'zs-btn zs-btn--sm zs-btn--ghost', href: 'edit.php?post_type=zen_feedback' }, ['Értékelések moderálása']), ' ', h('a', { class: 'zs-btn zs-btn--sm zs-btn--ghost', href: 'admin.php?page=zen-subscribers' }, ['Feliratkozók / CSV'])])])]);
	};

	VIEWS.seo = function () {
		return h('div', { class: 'zs-stack' }, [
			card('Keresőoptimalizálás', [grid(fields([
				{ k: 'seo.price_range', l: 'Árkategória (schema)', t: 'select', opts: [['€', '€'], ['€€', '€€'], ['€€€', '€€€']] },
				{ k: 'seo.default_og', l: 'Alap megosztási kép (OG)', t: 'media' },
				{ k: 'seo.google_verify', l: 'Google Search Console kód (ha nincs Rank Math/Site Kit)' }, { k: 'seo.bing_verify', l: 'Bing Webmaster kód' },
				{ k: 'seo.gtm_id', l: 'Google Tag Manager ID (ha nincs Site Kit)', ph: 'GTM-XXXXXXX' }, { k: 'seo.ga4_id', l: 'GA4 mérési ID (ha nincs Site Kit)', ph: 'G-XXXXXXXXXX' },
				{ k: 'seo.same_as', l: 'Profilok (sameAs): Facebook, Google Maps, Booking, Airbnb, TripAdvisor…', t: 'list', add: 'Új profil link' },
				{ k: 'seo.amenities', l: 'Szolgáltatások (schema)', t: 'list', add: 'Új szolgáltatás' },
				{ k: 'seo.schema_reviews', l: 'Saját értékelés-átlag a schemában (Google ezt helyi vállalkozásnál nem jeleníti meg – csak AI-nak)', t: 'toggle' }
			]))], 'Rank Math mellett a cím/leírás a Rank Mathben szerkeszthető (az import kitöltötte), a Zen a LodgingBusiness / Apartment / FAQ / ItemList schemát és a hreflang címkéket adja.'),
			card('AI SEO – hogy minden AI ismerje és ajánlja', [grid(fields([
				{ k: 'seo.ai_bots_allow', l: 'AI keresők és asszisztensek engedélyezése (GPTBot, ClaudeBot, PerplexityBot, Google-Extended…)', t: 'toggle' },
				{ k: 'seo.llms_enabled', l: '/llms.txt és /llms-full.txt (AI összefoglaló)', t: 'toggle' },
				{ k: 'seo.indexnow_key', l: 'IndexNow kulcs (Bing/Copilot/ChatGPT kereső gyors indexelés)', help: '<button type="button" class="zs-btn zs-btn--sm zs-btn--ghost" onclick="var i=this.closest(\'.zs-field\').querySelector(\'input\');i.value=([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g,function(c){return(c^crypto.getRandomValues(new Uint8Array(1))[0]&15>>c/4).toString(16)}).replace(/-/g,\'\');i.dispatchEvent(new Event(\'input\'));">Kulcs generálása</button>' }
			])), h('p', { class: 'zs-help', html: 'Előnézet: <a href="' + Z.home + 'llms.txt" target="_blank">/llms.txt</a> · <a href="' + Z.home + 'llms-full.txt" target="_blank">/llms-full.txt</a> · <a href="' + Z.home + 'robots.txt" target="_blank">/robots.txt</a>' })])
		]);
	};

	VIEWS.cloudflare = function () {
		return h('div', { class: 'zs-stack' }, [card('Cloudflare', [grid(fields([
			{ k: 'cloudflare.zone_id', l: 'Zone ID' }, { k: 'cloudflare.api_token', l: 'API token (Cache Purge jog)', t: 'password' },
			{ k: 'cloudflare.purge_on_save', l: 'Cache ürítése mentéskor', t: 'toggle' }, { k: 'cloudflare.html_ttl', l: 'HTML edge cache (mp, 0 = ki) – csak nem bejelentkezett látogatóknak', t: 'number', min: 0, step: 60 }
		])), h('p', {}, [h('a', { class: 'zs-btn zs-btn--sm', href: Z.purgeUrl }, ['Teljes cache ürítése most (szerver + Cloudflare)'])]), h('p', { class: 'zs-help', text: 'Minden mentés (Zen Studio, oldalak, menük, szerző, testreszabás) után a weboldal automatikusan üríti a telepített cache bővítményeket' + (Z.purgeLast && Z.purgeLast.time ? ' · utolsó ürítés: ' + new Date(Z.purgeLast.time * 1000).toLocaleString('hu-HU') + (Z.purgeLast.done && Z.purgeLast.done.length ? ' (' + Z.purgeLast.done.join(', ') + ')' : '') : '') + '.' }), h('p', { class: 'zs-help', html: 'Javasolt Cloudflare beállítás: „Cache Everything” szabály a HTML-re bypass-szal a <code>wordpress_logged_in</code> sütire; a foglalás és a Zen AI REST-en megy (no-store), így mindig friss.' })])]);
	};

	VIEWS.tools = function () {
		var file = h('input', { type: 'file', accept: 'application/json' });
		var prog = h('p', { class: 'zs-help' });
		function sideload() { prog.textContent = 'Képek letöltése a saját médiatárba…'; (function step() { api('sideload', 'POST', {}).then(function (r) { prog.textContent = 'Kész: ' + (r.total - r.remaining) + ' / ' + r.total + (r.errors && r.errors.length ? ' · hibás: ' + r.errors.length : ''); if (r.remaining > 0 && r.done > 0) { step(); } else { toast('Képek kész ✓'); } }); })(); }
		return h('div', { class: 'zs-stack' }, [
			card('Export / Import', [h('div', { class: 'zs-row' }, [
				h('button', { type: 'button', class: 'zs-btn', onclick: function () { api('export').then(function (j) { var a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([JSON.stringify(j, null, 2)], { type: 'application/json' })); a.download = 'zen-export-' + new Date().toISOString().slice(0, 10) + '.json'; a.click(); }); } }, ['Teljes export (JSON)']),
				file,
				h('button', { type: 'button', class: 'zs-btn zs-btn--ghost', onclick: function () { if (!file.files[0]) { toast('Válassz fájlt', false); return; } var r = new FileReader(); r.onload = function () { try { var j = JSON.parse(r.result); api('import', 'POST', j).then(function (x) { toast(x.ok ? 'Importálva (' + x.pages + ' oldal)' : 'Hiba', !!x.ok); location.reload(); }); } catch (e) { toast('Hibás fájl', false); } }; r.readAsText(file.files[0]); } }, ['Import'])
			])], 'Beállítások, apartmanok, tudásbázis és minden oldal szekciói (az API kulcsok nélkül).'),
			card('Eredeti oldal képei', [h('button', { type: 'button', class: 'zs-btn', onclick: sideload }, ['Képek átemelése a saját médiatárba']), prog], 'Az eredeti zencentralapartments.com képeit letölti (vagy ha ugyanazon a tárhelyen vagy, összekapcsolja), így minden kép a saját szerverről, srcset-tel töltődik.'),
			card('Tartalom visszaállítása', [h('button', { type: 'button', class: 'zs-btn zs-btn--ghost', onclick: function () { if (confirm('MINDEN nyelv minden Zen oldalát visszaállítja az eredeti tartalomra. Biztos?')) { api('seed', 'POST', { force: true }).then(function (r) { toast('Visszaállítva: ' + (r.langs || []).join(', ')); }); } } }, ['Minden oldal visszaállítása az eredetire'])])
		]);
	};

	function render() {
		main.innerHTML = '';
		var t = TABS.filter(function (x) { return x[0] === current; })[0];
		main.appendChild(h('h1', { class: 'zs-h1', text: t[1] }));
		main.appendChild((VIEWS[current] || VIEWS.dashboard)());
	}
	renderNav();
	render();
})();
