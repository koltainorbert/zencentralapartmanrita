/* Zen AI indító – a lótusz élete: kinyílik, idővel szomjas lesz és vizet kér; rámutatásra / érintésre megöntözve újra virágzik. */
(function () {
	var L = document.querySelector('.zca-launcher');
	if (!L || !L.querySelector('.zl') || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) { return; }
	var timer = null, busy = false;
	function arm(ms) { clearTimeout(timer); timer = setTimeout(function () { if (!L.classList.contains('is-hidden')) { L.classList.add('is-thirsty'); } else { arm(15000); } }, ms); }
	function water() {
		if (busy || !L.classList.contains('is-thirsty')) { return; }
		busy = true;
		L.classList.remove('is-thirsty');
		void L.offsetWidth; // animáció újraindítása
		L.classList.add('is-watering');
		setTimeout(function () { L.classList.remove('is-watering'); busy = false; }, 3200);
		arm(60000);
	}
	['pointerenter', 'focus', 'touchstart'].forEach(function (ev) { L.addEventListener(ev, water, { passive: true }); });
	L.addEventListener('click', function () { if (L.classList.contains('is-thirsty')) { water(); } else { arm(60000); } });
	document.addEventListener('visibilitychange', function () { if (document.hidden) { clearTimeout(timer); } else { arm(30000); } });
	arm(28000);
})();
