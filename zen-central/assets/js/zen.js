/* ZEN Central – front-end (vanilla, defer, függőség nélkül) */
(function () {
	'use strict';
	var d = document, de = d.documentElement, w = window;
	var cfg = w.ZC || { i18n: {} };
	var reduce = w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var $ = function (s, c) { return (c || d).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || d).querySelectorAll(s)); };

	/* ---------- Világos / sötét mód ---------- */
	function setTheme(t, save) {
		de.setAttribute('data-theme', t);
		if (save) { try { localStorage.setItem('zc-theme', t); } catch (e) {} }
		$$('[data-mode-toggle]').forEach(function (b) {
			b.setAttribute('aria-checked', t === 'dark' ? 'true' : 'false');
			b.setAttribute('aria-label', t === 'dark' ? (cfg.i18n.light || 'Light') : (cfg.i18n.dark || 'Dark'));
		});
		var meta = $$('meta[name="theme-color"]');
		meta.forEach(function (m) { m.setAttribute('content', t === 'dark' ? '#110F0C' : '#F7F3EC'); });
	}
	setTheme(de.getAttribute('data-theme') || 'light', false);
	d.addEventListener('click', function (e) {
		var b = e.target.closest('[data-mode-toggle]');
		if (!b) { return; }
		setTheme(de.getAttribute('data-theme') === 'dark' ? 'light' : 'dark', true);
	});
	if (cfg.mode === 'auto' && w.matchMedia) {
		w.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (ev) {
			var saved = null; try { saved = localStorage.getItem('zc-theme'); } catch (e) {}
			if (!saved) { setTheme(ev.matches ? 'dark' : 'light', false); }
		});
	}

	/* ---------- Fejléc görgetés ---------- */
	var ticking = false;
	function onScroll() {
		if (ticking) { return; }
		ticking = true;
		w.requestAnimationFrame(function () {
			d.body.classList.toggle('is-scrolled', w.scrollY > 40);
			ticking = false;
		});
	}
	w.addEventListener('scroll', onScroll, { passive: true });
	onScroll();
	// Ha az oldal nem hero-val kezdődik, a fejléc mindig "szilárd".
	var first = $('.zc-main > :first-child');
	if (!first || !first.classList.contains('zc-hero')) { d.body.classList.add('zc-no-hero'); var h = $('.zc-header'); if (h) { h.classList.add('zc-header--solid'); } }

	/* ---------- Mobil fiók (fókuszcsapda) ---------- */
	var drawer = $('[data-drawer]'), lastFocus = null;
	function openDrawer() {
		if (!drawer) { return; }
		lastFocus = d.activeElement;
		drawer.hidden = false;
		d.body.style.overflow = 'hidden';
		$$('[data-drawer-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
		var f = $('a,button', drawer); if (f) { f.focus(); }
	}
	function closeDrawer() {
		if (!drawer || drawer.hidden) { return; }
		drawer.hidden = true;
		d.body.style.overflow = '';
		$$('[data-drawer-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
		if (lastFocus) { lastFocus.focus(); }
	}
	d.addEventListener('click', function (e) {
		if (e.target.closest('[data-drawer-open]')) { openDrawer(); }
		else if (e.target.closest('[data-drawer-close]') || e.target === drawer) { closeDrawer(); }
		else if (drawer && !drawer.hidden && e.target.closest('.zc-drawer a[href]')) { closeDrawer(); }
	});
	d.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { closeDrawer(); closeLangs(); closeLb(); }
		if (e.key === 'Tab' && drawer && !drawer.hidden) {
			var f = $$('a[href],button:not([disabled])', drawer).filter(function (x) { return x.offsetParent !== null; });
			if (!f.length) { return; }
			if (e.shiftKey && d.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
			else if (!e.shiftKey && d.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
		}
	});

	/* ---------- Nyelvváltó ---------- */
	function closeLangs() { $$('[data-lang].is-open').forEach(function (l) { l.classList.remove('is-open'); var b = $('.zc-lang__btn', l); if (b) { b.setAttribute('aria-expanded', 'false'); } }); }
	d.addEventListener('click', function (e) {
		var b = e.target.closest('.zc-lang__btn');
		if (b) {
			var l = b.closest('[data-lang]'), open = !l.classList.contains('is-open');
			closeLangs();
			if (open) { l.classList.add('is-open'); b.setAttribute('aria-expanded', 'true'); }
			return;
		}
		if (!e.target.closest('[data-lang]')) { closeLangs(); }
	});

	/* ---------- Felugró nyelvválasztó (középre zárt üveg ablak) ---------- */
	var lm = $('[data-langmodal]'), lmLast = null;
	function openLm(trigger) {
		if (!lm) { return; }
		lmLast = trigger || d.activeElement;
		closeDrawer();
		lm.hidden = false;
		d.documentElement.classList.add('zc-lock');
		w.requestAnimationFrame(function () { lm.classList.add('is-open'); var c = $('[aria-current="true"]', lm) || $('a', lm); if (c) { c.focus({ preventScroll: true }); } });
	}
	function closeLm() {
		if (!lm || lm.hidden) { return; }
		lm.classList.remove('is-open');
		d.documentElement.classList.remove('zc-lock');
		setTimeout(function () { lm.hidden = true; }, reduce ? 0 : 220);
		if (lmLast && lmLast.focus) { lmLast.focus({ preventScroll: true }); }
	}
	d.addEventListener('click', function (e) {
		var o = e.target.closest('[data-lang-open]');
		if (o) { e.preventDefault(); openLm(o); return; }
		if (lm && !lm.hidden && (e.target.closest('[data-lang-close]') || e.target === lm)) { closeLm(); }
	});
	d.addEventListener('keydown', function (e) {
		if (!lm || lm.hidden) { return; }
		if (e.key === 'Escape') { closeLm(); return; }
		if (e.key === 'Tab') {
			var f = $$('a[href],button', lm);
			if (e.shiftKey && d.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
			else if (!e.shiftKey && d.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
		}
	});

	/* ---------- Megjelenési animációk ---------- */
	if (cfg.anim && !reduce && 'IntersectionObserver' in w) {
		de.classList.add('zc-anim');
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		$$('[data-reveal]').forEach(function (el) { io.observe(el); });
	}

	/* ---------- Videók: csak láthatóan játsszanak, takarékos módban ne ---------- */
	var saveData = navigator.connection && navigator.connection.saveData;
	var vids = $$('video[data-zc-video]');
	if ('IntersectionObserver' in w && vids.length) {
		var vo = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				var v = en.target;
				if (!v.autoplay) { return; }
				if (en.isIntersecting && !reduce && !saveData) { var p = v.play(); if (p && p.catch) { p.catch(function () {}); } }
				else { v.pause(); }
			});
		}, { threshold: 0.15 });
		vids.forEach(function (v) { if (reduce || saveData) { v.removeAttribute('autoplay'); v.pause(); } vo.observe(v); });
	}

	/* ---------- YouTube/Vimeo háttér: betöltés üresjáratban ---------- */
	function loadBgEmbeds() {
		$$('.zc-embed--bg iframe[data-src]').forEach(function (f) {
			if (reduce || saveData) { return; }
			f.src = f.getAttribute('data-src');
			f.removeAttribute('data-src');
			f.addEventListener('load', function () { setTimeout(function () { f.parentNode.classList.add('is-playing'); }, 900); });
		});
	}
	if ('requestIdleCallback' in w) { w.requestIdleCallback(loadBgEmbeds, { timeout: 2500 }); } else { setTimeout(loadBgEmbeds, 1500); }
	d.addEventListener('click', function (e) {
		var l = e.target.closest('.zc-embed--lite');
		if (!l) { return; }
		var f = d.createElement('iframe');
		f.src = l.getAttribute('data-embed');
		f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
		f.setAttribute('allowfullscreen', '');
		f.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;border:0';
		l.appendChild(f);
	});

	/* ---------- Térkép: OSM görgetéskor magától, Google kattintásra ---------- */
	function loadMap(m) {
		if (!m || m.classList.contains('is-loaded')) { return; }
		var f = d.createElement('iframe');
		f.src = m.getAttribute('data-map');
		f.loading = 'lazy';
		f.title = m.classList.contains('zc-map--osm') ? 'OpenStreetMap' : 'Google Maps';
		f.referrerPolicy = 'no-referrer-when-downgrade';
		f.setAttribute('allowfullscreen', '');
		m.insertBefore(f, m.firstChild);
		m.classList.add('is-loaded');
	}
	d.addEventListener('click', function (e) {
		var b = e.target.closest('[data-map-load]');
		if (b) { loadMap(b.closest('[data-map]')); }
	});
	var autoMaps = $$('[data-map-auto]');
	if (autoMaps.length) {
		if ('IntersectionObserver' in w) {
			var mo = new IntersectionObserver(function (en) { en.forEach(function (x) { if (x.isIntersecting) { loadMap(x.target); mo.unobserve(x.target); } }); }, { rootMargin: '300px 0px' });
			autoMaps.forEach(function (m) { mo.observe(m); });
		} else { autoMaps.forEach(loadMap); }
	}

	/* ---------- Fülek (GYIK) ---------- */
	d.addEventListener('click', function (e) {
		var t = e.target.closest('[data-tab]');
		if (!t) { return; }
		var root = t.closest('[data-tabs]'), i = t.getAttribute('data-tab');
		$$('[data-tab]', root).forEach(function (x) { x.setAttribute('aria-selected', x === t ? 'true' : 'false'); });
		$$('[data-panel]', root).forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== i; });
	});

	/* ---------- Galéria szűrő ---------- */
	d.addEventListener('click', function (e) {
		var f = e.target.closest('[data-filter] [data-f]');
		if (!f) { return; }
		var root = f.closest('[data-gallery]'), v = f.getAttribute('data-f');
		$$('[data-f]', root).forEach(function (x) { x.setAttribute('aria-pressed', x === f ? 'true' : 'false'); });
		$$('.zc-masonry__item', root).forEach(function (it) { it.classList.toggle('is-hidden', v !== '*' && it.getAttribute('data-g') !== v); });
	});

	/* ---------- Lightbox (kép, mp4, YouTube/Vimeo), billentyűzet + húzás ---------- */
	var lb = $('[data-lightbox]'), stage = lb ? $('[data-lb-stage]', lb) : null, count = lb ? $('[data-lb-count]', lb) : null;
	var items = [], idx = 0, lbLast = null;
	function ytEmbed(u) {
		var m = u.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/);
		if (m) { return 'https://www.youtube-nocookie.com/embed/' + m[1] + '?autoplay=1&rel=0'; }
		var v = u.match(/vimeo\.com\/(?:video\/)?(\d+)/);
		return v ? 'https://player.vimeo.com/video/' + v[1] + '?autoplay=1&dnt=1' : '';
	}
	function show(i) {
		if (!items.length) { return; }
		idx = (i + items.length) % items.length;
		var it = items[idx], el;
		stage.innerHTML = '';
		if (it.type === 'video') {
			var emb = ytEmbed(it.src);
			if (emb) { el = d.createElement('iframe'); el.src = emb; el.allow = 'autoplay; encrypted-media; fullscreen'; el.setAttribute('allowfullscreen', ''); }
			else { el = d.createElement('video'); el.src = it.src; el.controls = true; el.autoplay = true; el.playsInline = true; }
		} else {
			el = d.createElement('img'); el.src = it.src; el.alt = it.alt || ''; el.decoding = 'async';
		}
		stage.appendChild(el);
		if (count) { count.textContent = (idx + 1) + ' / ' + items.length; }
		var n = items[(idx + 1) % items.length];
		if (n && n.type !== 'video') { var pre = new Image(); pre.src = n.src; }
	}
	function openLb(trigger) {
		if (!lb) { return; }
		var root = trigger.closest('[data-gallery]') || trigger.closest('.zc-sec') || d;
		var group = $$('[data-lb]', root).filter(function (x) { return !x.classList.contains('is-hidden'); });
		var hero = trigger.getAttribute('data-lb-group');
		if (hero) {
			var g = trigger.closest('.zc-sec');
			group = [trigger].concat($$('.zc-apt__gallery [data-lb]', g));
		}
		items = group.map(function (x) { var im = $('img', x); return { src: x.getAttribute('data-lb'), type: x.getAttribute('data-type') || 'image', alt: im ? im.alt : '' }; });
		lbLast = trigger;
		lb.hidden = false;
		d.body.style.overflow = 'hidden';
		show(Math.max(0, group.indexOf(trigger)));
		var c = $('[data-lb-close]', lb); if (c) { c.focus(); }
	}
	function closeLb() {
		if (!lb || lb.hidden) { return; }
		lb.hidden = true;
		stage.innerHTML = '';
		d.body.style.overflow = '';
		if (lbLast) { lbLast.focus(); }
	}
	d.addEventListener('click', function (e) {
		var t = e.target.closest('[data-lb]');
		if (t && !e.target.closest('[data-lightbox]')) { e.preventDefault(); openLb(t); return; }
		if (!lb || lb.hidden) { return; }
		if (e.target.closest('[data-lb-close]') || e.target === lb) { closeLb(); }
		else if (e.target.closest('[data-lb-prev]')) { show(idx - 1); }
		else if (e.target.closest('[data-lb-next]')) { show(idx + 1); }
	});
	d.addEventListener('keydown', function (e) {
		if (!lb || lb.hidden) { return; }
		if (e.key === 'ArrowLeft') { show(idx - 1); }
		if (e.key === 'ArrowRight') { show(idx + 1); }
	});
	if (lb) {
		var sx = 0;
		lb.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
		lb.addEventListener('touchend', function (e) { var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) { show(idx + (dx < 0 ? 1 : -1)); } }, { passive: true });
	}

	/* ---------- Számláló animáció ---------- */
	if ('IntersectionObserver' in w && !reduce) {
		var co = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (!en.isIntersecting) { return; }
				var el = en.target, txt = el.textContent, m = txt.match(/^(\D*)([\d\s.,]+)(.*)$/);
				co.unobserve(el);
				if (!m) { return; }
				var target = parseFloat(m[2].replace(/[\s,]/g, '')), start = null;
				if (!isFinite(target)) { return; }
				(function step(ts) {
					if (!start) { start = ts; }
					var p = Math.min(1, (ts - start) / 1200), v = Math.round(target * (1 - Math.pow(1 - p, 3)));
					el.textContent = m[1] + v.toLocaleString(de.lang || 'hu') + m[3];
					if (p < 1) { w.requestAnimationFrame(step); }
				})(performance.now());
			});
		}, { threshold: 0.6 });
		$$('[data-count]').forEach(function (el) { co.observe(el); });
	}

	/* ---------- Finom parallaxis a bevezető képeken ---------- */
	if (!reduce && w.matchMedia('(min-width: 900px)').matches) {
		var px = $$('[data-parallax] .zc-media');
		if (px.length) {
			w.addEventListener('scroll', function () {
				w.requestAnimationFrame(function () {
					px.forEach(function (el) {
						var r = el.parentNode.getBoundingClientRect();
						if (r.bottom < 0 || r.top > w.innerHeight) { return; }
						var p = (r.top + r.height / 2 - w.innerHeight / 2) / w.innerHeight;
						el.style.transform = 'scale(1.08) translateY(' + (p * -24).toFixed(1) + 'px)';
					});
				});
			}, { passive: true });
		}
	}
})();
