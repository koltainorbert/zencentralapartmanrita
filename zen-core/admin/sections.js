/* Zen szekció-szerkesztő */
(function () {
	'use strict';
	var F = window.ZF, h = F.h, C = window.ZenSections || {};
	var app = document.getElementById('zen-sections-app'), store = document.getElementById('zen-sections-json');
	if (!app || !store) { return; }
	var data;
	try { data = JSON.parse(store.value || '[]'); } catch (e) { data = []; }
	if (!Array.isArray(data)) { data = []; }
	var open = {};
	function sync() { store.value = JSON.stringify(data); }

	var BTN = { k: 'buttons', l: 'Gombok', t: 'repeater', item: 'Gomb', add: 'Új gomb', fields: [{ k: 'label', l: 'Felirat' }, { k: 'url', l: 'Link (URL vagy {page:booking}, {page:contact}…)' }, { k: 'style', l: 'Stílus', t: 'select', opts: [['primary', 'Kitöltött'], ['ghost', 'Körvonalas'], ['light', 'Fehér']] }], blank: { label: '', url: '{page:booking}', style: 'primary' } };
	var APTS = [['', '—']].concat(C.apartments || []);
	var TYPES = {
		hero: { n: 'Hero (nyitókép)', i: '▣', f: [{ k: 'kicker', l: 'Felső kis cím' }, { k: 'title', l: 'Főcím' }, { k: 'text', l: 'Alcím / szöveg', t: 'textarea' }, BTN, { k: 'media', l: 'Háttér: kép VAGY videó (MP4 / YouTube / Vimeo)', t: 'media' }, { k: 'height', l: 'Magasság', t: 'select', opts: [['full', 'Teljes képernyő'], ['tall', 'Magas'], ['medium', 'Közepes'], ['small', 'Kicsi (aloldal)']] }, { k: 'booking_bar', l: 'Foglaló üvegdoboz a hero jobb oldalán (2 oszlop)', t: 'toggle' }, { k: 'panel_title', l: 'Foglalódoboz címe (üresen: „Foglaljon közvetlenül”)' }, { k: 'panel_text', l: 'Foglalódoboz alcíme (üresen: „Garantáltan legjobb ár…”)' }, { k: 'breadcrumbs', l: 'Morzsamenü', t: 'toggle' }] },
		intro: { n: 'Bevezető (szöveg + kép/videó)', i: '◧', f: [{ k: 'kicker', l: 'Felső kis cím' }, { k: 'title', l: 'Cím' }, { k: 'html', l: 'Szöveg', t: 'html' }, BTN, { k: 'media', l: 'Kép vagy videó', t: 'media' }, { k: 'layout', l: 'Elrendezés', t: 'select', opts: [['right', 'Kép jobbra'], ['left', 'Kép balra'], ['center', 'Középre, kép nélkül']] }] },
		cards: { n: 'Kártyák (apartmanok / egyéni)', i: '▦', f: [{ k: 'kicker', l: 'Felső kis cím' }, { k: 'title', l: 'Cím' }, { k: 'html', l: 'Bevezető szöveg', t: 'html' }, { k: 'source', l: 'Forrás', t: 'select', opts: [['manual', 'Kézi kártyák (lent)'], ['apartments', 'Automatikusan az apartmanokból']] }, { k: 'exclude', l: 'Automatikus módban kihagyandó apartman', t: 'select', opts: APTS }, { k: 'items', l: 'Kártyák', t: 'repeater', item: 'Kártya', add: 'Új kártya', fields: [{ k: 'title', l: 'Cím' }, { k: 'apt', l: 'Apartman (adatokhoz, ha van)', t: 'select', opts: APTS }, { k: 'url', l: 'Link' }, { k: 'meta', l: 'Rövid szöveg (ha nem apartman)' }, { k: 'media', l: 'Kép vagy videó', t: 'media' }], blank: { title: '', apt: '', url: '', meta: '', media: {} } }] },
		features: { n: 'Jellemzők lista', i: '☰', f: [{ k: 'title', l: 'Cím' }, { k: 'items', l: 'Elemek', t: 'list' }, { k: 'columns', l: 'Oszlopok', t: 'number', min: 2, max: 4 }] },
		map_list: { n: 'Térkép + közeli látnivalók', i: '⌖', f: [{ k: 'kicker', l: 'Felső kis cím' }, { k: 'title', l: 'Cím' }, { k: 'map_query', l: 'Térkép keresőkifejezés' }, { k: 'items', l: 'Látnivalók („Név: távolság”)', t: 'list', add: 'Új látnivaló' }, { k: 'html', l: 'Kiegészítő szöveg', t: 'html' }] },
		cta: { n: 'Felhívás (CTA)', i: '★', f: [{ k: 'title', l: 'Cím' }, { k: 'lines', l: 'Sorok', t: 'list' }, BTN] },
		faq: { n: 'GYIK (csoportok + kérdések)', i: '?', f: [{ k: 'kicker', l: 'Felső kis cím' }, { k: 'title', l: 'Cím' }, { k: 'text', l: 'Bevezető szöveg', t: 'textarea' }, { k: 'groups', l: 'Csoportok', t: 'repeater', item: 'Csoport', add: 'Új csoport', fields: [{ k: 'title', l: 'Csoport neve' }, { k: 'items', l: 'Kérdések', t: 'repeater', item: 'Kérdés', add: 'Új kérdés', fields: [{ k: 'q', l: 'Kérdés' }, { k: 'a', l: 'Válasz', t: 'html' }], blank: { q: '', a: '' } }], blank: { title: '', items: [] } }] },
		gallery: { n: 'Galéria (képek és videók)', i: '▤', f: [{ k: 'title', l: 'Cím' }, { k: 'groups', l: 'Csoportok (szűrő pillek)', t: 'repeater', item: 'Csoport', add: 'Új csoport', fields: [{ k: 'title', l: 'Csoport neve' }, { k: 'items', l: 'Képek / videók', t: 'gallery', wide: true }], blank: { title: '', items: [] } }] },
		apt_detail: { n: 'Apartman részletek', i: '⌂', f: [{ k: 'apt', l: 'Apartman', t: 'select', opts: APTS }, { k: 'title', l: 'Cím' }, { k: 'media', l: 'Fő kép vagy videó', t: 'media' }, { k: 'html', l: 'Leírás', t: 'html' }, { k: 'facts', l: 'Tények (adó, kapacitás, méret)', t: 'list' }, { k: 'amenities_title', l: 'Felszereltség címe' }, { k: 'amenities', l: 'Felszereltség', t: 'list', multiline: true, icons: true, add: 'Új felszereltség', help: 'Az ikon a szövegből automatikusan jön (13 nyelven). Kattints az ikonra, ha másikat szeretnél.' }, BTN, { k: 'aside_title', l: 'Oldalsó kártya címe' }, { k: 'aside_text', l: 'Oldalsó kártya szövege', t: 'textarea' }, { k: 'gallery', l: 'Galéria', t: 'gallery', wide: true }] },
		contact: { n: 'Kapcsolat', i: '✆', f: [{ k: 'kicker', l: 'Felső kis cím' }, { k: 'title', l: 'Cím' }, { k: 'notice', l: 'Kiemelt figyelmeztetés', t: 'textarea' }, { k: 'info_title', l: 'Elérhetőségek címe' }, { k: 'info', l: 'Elérhetőségek', t: 'repeater', item: 'Sor', add: 'Új sor', fields: [{ k: 'label', l: 'Szöveg' }, { k: 'url', l: 'Link (tel:, mailto:, https://)' }, { k: 'icon', l: 'Ikon', t: 'select', opts: [['phone', 'Telefon'], ['mail', 'E-mail'], ['pin', 'Cím'], ['facebook', 'Facebook'], ['instagram', 'Instagram'], ['info', 'Info']] }], blank: { label: '', url: '', icon: 'info' } }, { k: 'booking_title', l: 'Foglalás címe' }, BTN, { k: 'community_title', l: 'Közösség címe' }, { k: 'media', l: 'Kép vagy videó', t: 'media' }, { k: 'access_title', l: 'Megközelítés címe' }, { k: 'access_html', l: 'Megközelítés', t: 'html' }, { k: 'checkin_title', l: 'Bejelentkezés címe' }, { k: 'checkin_html', l: 'Bejelentkezés', t: 'html' }, { k: 'map_query', l: 'Térkép keresőkifejezés' }] },
		form: { n: 'Űrlap', i: '✉', f: [{ k: 'form', l: 'Típus', t: 'select', opts: [['contact', 'Kapcsolat'], ['newsletter', 'Hírlevél'], ['feedback', 'Ügyfél-elégedettség']] }, { k: 'title', l: 'Cím' }, { k: 'text', l: 'Bevezető', t: 'textarea' }] },
		richtext: { n: 'Szabad szöveg / HTML', i: '¶', f: [{ k: 'title', l: 'Cím' }, { k: 'html', l: 'Tartalom (HTML, rövidkód is: [zen_help], [zen_booking], [zen_form type="contact"])', t: 'html' }] },
		booking: { n: 'Foglaló', i: '▥', f: [{ k: 'title', l: 'Cím' }, { k: 'text', l: 'Bevezető', t: 'textarea' }, { k: 'engine', l: 'Motor', t: 'select', opts: [['inherit', 'Studio beállítás szerint'], ['native', 'Saját Zen motor'], ['nhb', 'NetHotelBooking']] }] },
		blog_list: { n: 'Bloglista', i: '✎', f: [{ k: 'title', l: 'Cím' }, { k: 'count', l: 'Darab / oldal', t: 'number', min: 1, max: 48 }] },
		reviews: { n: 'Vendégvélemények', i: '❝', f: [{ k: 'title', l: 'Cím' }, { k: 'source', l: 'Forrás', t: 'select', opts: [['feedback', 'Elégedettségi portál (jóváhagyott) + kézi'], ['manual', 'Csak kézi']] }, { k: 'items', l: 'Kézi vélemények', t: 'repeater', item: 'Vélemény', add: 'Új vélemény', fields: [{ k: 'name', l: 'Név' }, { k: 'text', l: 'Szöveg', t: 'textarea' }, { k: 'rating', l: 'Csillag', t: 'number', min: 1, max: 5 }, { k: 'origin', l: 'Forrás (pl. Booking.com)' }, { k: 'date', l: 'Dátum' }], blank: { name: '', text: '', rating: 5, origin: '', date: '' } }] },
		guide: { n: 'Veszprém kalauz (Zen tudásbázis)', i: '✦', f: [{ k: 'title', l: 'Cím' }, { k: 'text', l: 'Bevezető', t: 'textarea' }, { k: 'category', l: 'Kategória', t: 'select', opts: [['sight', 'Látnivalók'], ['daytrip', 'Kirándulások'], ['event', 'Események'], ['food', 'Gasztronómia'], ['all', 'Mind']] }, { k: 'limit', l: 'Darab', t: 'number', min: 1, max: 24 }] },
		stats: { n: 'Számok', i: '#', f: [{ k: 'title', l: 'Cím' }, { k: 'items', l: 'Számok', t: 'repeater', item: 'Szám', add: 'Új szám', fields: [{ k: 'value', l: 'Érték' }, { k: 'label', l: 'Felirat' }], blank: { value: '', label: '' } }] }
	};
	var STYLE = [{ k: 'bg', l: 'Szekció háttér (kép vagy videó)', t: 'media' }, { k: 'tone', l: 'Tónus', t: 'select', opts: [['auto', 'Alap'], ['alt', 'Alternatív (bézs)'], ['dark', 'Sötét'], ['accent', 'Kiemelt']] }, { k: 'pad', l: 'Térköz', t: 'select', opts: [['s', 'Kicsi'], ['m', 'Közepes'], ['l', 'Nagy']] }, { k: 'overlay', l: 'Háttér fedés (0–1)', t: 'range', min: 0, max: 1, step: 0.05 }, { k: 'anchor', l: 'Horgony (#azonosító)' }];

	function titleOf(s) { var t = TYPES[s.type] || { n: s.type }; var d = s.data || {}; return t.n + (d.title ? ' – ' + d.title : (d.kicker ? ' – ' + d.kicker : '')); }
	function render() {
		app.innerHTML = '';
		var bar = h('div', { class: 'zs-sec-bar' }, [
			h('b', { text: data.length + ' szekció' }),
			h('button', { type: 'button', class: 'zs-btn zs-btn--sm zs-btn--ghost', onclick: function () { var all = data.every(function (s, i) { return open[i]; }); data.forEach(function (s, i) { open[i] = !all; }); render(); } }, ['Mind ki/be']),
			(C.trans && C.trans.length) ? h('select', { class: 'zs-input zs-select zs-input--sm', onchange: function (e) { var id = e.target.value; e.target.value = ''; if (!id || !confirm('A jelenlegi szekciók helyére bemásolja a kiválasztott nyelvi változat szekcióit (fordításhoz). Folytatod?')) { return; } var fd = new FormData(); fd.append('action', 'zen_copy_sections'); fd.append('nonce', C.nonce); fd.append('from', id); fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) { if (j.success) { data = j.data; sync(); render(); } }); } }, [h('option', { value: '' }, ['Másolás másik nyelvből…'])].concat(C.trans.map(function (t) { return h('option', { value: t.id }, [t.name]); }))) : null
		]);
		app.appendChild(bar);
		data.forEach(function (s, i) {
			s.data = s.data || {}; s.style = s.style || {};
			var isOpen = !!open[i];
			var head = h('div', { class: 'zs-sec__head', onclick: function () { open[i] = !open[i]; render(); } }, [
				h('span', { class: 'zs-sec__i', text: (TYPES[s.type] || {}).i || '•' }),
				h('b', { class: 'zs-sec__t', text: titleOf(s) }),
				h('span', { class: 'zs-sec__tools', onclick: function (e) { e.stopPropagation(); } }, [
					F.toggle(s.enabled !== false, function (v) { s.enabled = v; sync(); head.parentNode.classList.toggle('is-off', !v); }, 'Megjelenik'),
					F.ib('up', 'Fel', function () { if (i > 0) { var t = data[i - 1]; data[i - 1] = s; data[i] = t; var o = open[i]; open[i] = open[i - 1]; open[i - 1] = o; sync(); render(); } }),
					F.ib('down', 'Le', function () { if (i < data.length - 1) { var t = data[i + 1]; data[i + 1] = s; data[i] = t; var o = open[i]; open[i] = open[i + 1]; open[i + 1] = o; sync(); render(); } }),
					F.ib('dup', 'Másolat', function () { var c = F.clone(s); c.id = 's' + Math.random().toString(36).slice(2, 8); data.splice(i + 1, 0, c); sync(); render(); }),
					F.ib('del', 'Törlés', function () { if (confirm('Törlöd ezt a szekciót?')) { data.splice(i, 1); sync(); render(); } }, 'is-danger')
				])
			]);
			var body = h('div', { class: 'zs-sec__body', hidden: isOpen ? null : true });
			if (isOpen) {
				var def = TYPES[s.type];
				var cont = h('div', { class: 'zs-grid2' });
				(def ? def.f : []).forEach(function (f) { cont.appendChild(F.field(f, s.data, sync)); });
				body.appendChild(cont);
				var st = h('details', { class: 'zs-sec__style' }, [h('summary', { text: 'Megjelenés (háttér, tónus, térköz)' })]);
				var sg = h('div', { class: 'zs-grid2' });
				STYLE.forEach(function (f) { sg.appendChild(F.field(f, s.style, sync)); });
				st.appendChild(sg);
				body.appendChild(st);
			}
			app.appendChild(h('div', { class: 'zs-sec' + (isOpen ? ' is-open' : '') + (s.enabled === false ? ' is-off' : '') }, [head, body]));
		});
		var picker = h('div', { class: 'zs-sec-add' }, [h('p', { class: 'zs-label', text: '+ Új szekció' }), h('div', { class: 'zs-sec-types' }, Object.keys(TYPES).map(function (k) {
			return h('button', { type: 'button', class: 'zs-sec-type', onclick: function () { data.push({ id: 's' + Math.random().toString(36).slice(2, 8), type: k, enabled: true, style: { tone: 'auto', pad: 'l', overlay: 0.35 }, data: {} }); open[data.length - 1] = true; sync(); render(); } }, [h('span', { text: TYPES[k].i }), TYPES[k].n]);
		}))]);
		app.appendChild(picker);
	}
	render();
	sync();
})();
