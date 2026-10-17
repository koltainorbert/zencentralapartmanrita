# ZEN Central Apartments – Design benchmark és design spec

Készült: 2026-10-17 · Forrás: a felsorolt oldalak élő tartalma (WebFetch) és az Awwwards hotel-találati lista (2025–2026).
Megjegyzés: a WebFetch a renderelt CSS-t nem látja, ezért a betűtípus és a szín csak ott szerepel, ahol a markup megadja (theme-color, Awwwards-paletta). A többi vizuális állítás a szerkezetből levont következtetés.

---

## 1. rész – 10 benchmark oldal

### 1. Aman – https://www.aman.com/
- **Hero:** egy kiemelt, teljes szélességű kép, rövid címmel és egyetlen „Discover” linkkel. Mellette háttérben csendes, loopoló videó fut.
- **Foglalás:** a hero nem tartalmaz foglaló sávot. A fejlécben csak egy „Reserve” link van, ami külön foglalási folyamatba visz (úti cél és dátum paraméterekkel). Prémium, nyugodt hatású, mert nem tolakodik.
- **Szín:** meleg, törtfehér alap (theme-color `#f3eee7`). A színt a fotók adják, a felület semleges marad.
- **Tipográfia:** kis, nagybetűs „eyebrow” címke áll a cím fölött (kategória), alatta nagy cím és nagyon rövid szöveg.
- **Kártyák:** egységes séma: kép → eyebrow → cím → 1 mondat → CTA, státuszjelvénnyel („Now open”).
- **A11y:** „Skip to main content” link, és több szintre lebontott (drill-down) menü.

### 2. Six Senses – https://www.sixsenses.com/
- **Hero:** teljes képernyős videó „teljes videó lejátszása” vezérlővel és egy rövid, érzelmi taglinnal.
- **Foglaló sáv:** a hero-ban és a fejlécben („Book now”) is ott van. Fokozatosan nyílik ki: dátumválasztó elérhetőségi jelmagyarázattal és minimum-éjszaka figyelmeztetéssel, felnőtt/gyerek léptető gyerek-életkorral, promókód összecsukva.
- **Szobaösszehasonlító tálca:** legfeljebb 3 szoba hasonlítható össze egymás mellett. Nálunk 3 apartman van, ez pontosan illik ránk.
- **Bizalmi elemek:** fenntarthatósági tanúsítványok (LEED), külön szekcióban.
- **Ajánlatkártyák:** „Early booking” és „Stay longer” megtakarítás-keretezéssel.

### 3. Numa – https://www.numastays.com/
- **Hero:** loopoló városi videó, kétsoros szlogen és egyetlen elsődleges CTA („Check availability”).
- **Foglaló sáv a hero-ban,** ez az első interaktív elem: Hol / Mikor / Vendégek / gomb. Kevés mező, gyors kitöltés.
- **Digitális élmény:** külön blokk szól a kulcs nélküli, sorban állás nélküli check-inről, app-store jelvényekkel.
- **Bizalmi elemek:** „legjobb ár garancia”, tagsági előnyök ikonlistában (pl. kedvezmény hosszabb tartózkodásra, rugalmas lemondás), 24/7 WhatsApp-támogatás és fizetési logók a lábléc közelében.
- **Mobil:** a menü 3 fő pontra és egy „More” menüre szűkül.

### 4. Zoku – https://livezoku.com/amsterdam/stay
- **Szobakártya:** kép, név, rövid leírás, valamint egy specifikációs lista (férőhely, m², ágy, konyha, fürdő). Két gomb tartozik hozzá: „Részletek” és „Foglalás”. **Nálunk ez a modell a három apartmanra.**
- **Ajánlatkártyák:** „-tól” ár / éj, cím és 1 mondat.
- **Bizalmi elemek:** értékelési pontszámok 4 platformról (Booking, Airbnb, Google, Tripadvisor) és sajtó-idézetek logóval. Tanulság: náluk a pontszám két helyen eltér, ezért nálunk egyetlen adatforrásból jöjjön.
- **GYIK:** kérdés-válasz blokk (FAQPage schema-jelölt).
- **Navigáció:** mega-menü bélyegképekkel, nyelvváltóval (EN/NL).

### 5. The Hoxton – https://thehoxton.com/
- **Foglalás panelben:** a fejléc „Find a Room” gombja nyitja meg. Tabok: szoba / tárgyaló / étterem. Mezők: dátum, helyszín, vendég (1–20) és „Check availability”.
- **Validáció:** egyértelmű hibaüzenet és dátumfüggő figyelmeztetés (pl. „csak ettől a naptól nyit”). Így a felhasználó nem fut zsákutcába.
- **Direkt foglalás előnye:** az ajánlatkártyákon ismétlődik, hogy a saját oldalon a legjobb az ár.
- **Szín:** meleg törtfehér logó a láblécben (`#eee6dd`).
- **Lábléc:** csoportosított linkek (Links / Legal / Social / Language), hírlevél inline validációval, „Manage my booking”.

### 6. Ace Hotel – https://www.acehotel.com/
- **Hero:** némított, loopoló háttérvideó statikus fallback képpel, alatta rövid márkabevezető.
- **Foglaló sáv a hero tetején:** helyszín, érkezés és távozás, vendég-léptető (alapérték: 1 felnőtt, 0 gyerek), opcionális kód (típus + mező + reset), „Book Now”, ami overlay-t nyit.
- **A11y-minta:** a dátumválasztóhoz billentyűzetes navigációs instrukció tartozik, és van skip-link. **Ezt vegyük át.**
- **Címsor-hierarchia:** H1 → H2 szekciók → H3 kártyacímek. Tiszta, SEO-barát.
- **Szín:** fehér alap (`#ffffff`), a karaktert a fotó adja.

### 7. Boutique Hotel Altstadt Vienna – https://www.altstadt.at/
- **Egyedi szobák egyedi néven:** minden szoba egy tervezőhöz vagy művészhez kötött témát kap, külön képpel. **Közvetlen párhuzam a RELAX / JIN-JANG / LOTUS koncepcióval.**
- **Hero:** váltakozó képsorok, rövid, tördelt mondatokkal. Lassú, „szerkesztői” ritmus.
- **Fejléc:** állandóan látszik a telefonszám és a „Bestpreis buchen” (legjobb ár) gomb.
- **Bizalmi elemek:** Michelin-kalauz, Gault & Millau, SLH, Mr & Mrs Smith, Umweltzeichen, valamint külön értékelés- és fenntarthatósági oldal.
- **Lábléc:** teljes cím, telefon, e-mail és Impresszum/Adatvédelem, plusz WhatsApp és Messenger ikon.

### 8. Lingers | Suites & Residence (Dél-Tirol) – https://www.lingers.it/en
Awwwards Honorable Mention (2026-08-31). Ez a **legközelebbi üzleti analóg**: 16 suite és apartman.
- **Hero:** teljes szélességű tájkép, kétszintű szlogennel és egy „legjobb ár direkt foglalásnál” sávval.
- **Szobakártya:** fotó, név, m² és férőhely, „-tól” ár, plusz 3 akció: Ajánlatkérés / Foglalás / Részletek.
- **Galéria:** bélyegkép-rács, ahol minden kép nagy nézetre nyílik (lightbox).
- **Kicsi, nagybetűs címkék** („RECOMMENDED BY”), sajtóidézet-kártyák logóval.
- **Lábléc:** telefonos ügyfélszolgálati nyitvatartás, adószám, tanúsítvány-logók, Tripadvisor link.

### 9. Relais Rossar (Garda-tó) – https://www.relaisrossar.it
Awwwards Honorable Mention (2026-06-09).
- **Hero:** loopoló videó, közvetlenül alatta foglaló sáv (érkezés, távozás, felnőtt, gyerek, „elérhetőség”). Elöl a feladat, utána a hangulat.
- **Tipográfia:** nagy címek, a kulcsszó *dőlt* kiemeléssel (serif italic accent). Ez finom, prémium gesztus.
- **Szobakártyák rövid loopoló videóval** statikus kép helyett, címmel és „Fedezd fel” linkkel.
- **Szín:** fehér alap, a logó tompa barnás-vörös (`#854d47`). Egyetlen földszínű akcentus.
- **Cookie-panel:** kiegyensúlyozott „Elutasít / Beállít / Elfogad” választás (GDPR-barát).

### 10. The Drake Hotel (Toronto) – https://thedrake.ca
Awwwards Site of the Day + Developer Award (2025-09-02). Awwwards-paletta: `#F0EEEA`, `#FFFFFF`, akcentus `#E25856`.
- **Hero:** számozott (01–03) slideshow előző/következő vezérlővel, mozgóképpel.
- **Foglalás:** a fejlécben „Menu / Book / nyelv”. A „Book” panelt nyit, benne módváltó (szálloda / étterem / esemény) és dátumtartomány-választó inline validációval.
- **Címek vegyes súllyal,** egy-egy szó dőlt kiemeléssel.
- **Technika:** Tailwind, mikrointerakciók, Headless CMS (Contentful).
- **Mobil:** teljes képernyős menü, jól látható bezárás-gombbal.

### Kiegészítő megfigyelések
- **Sonder** (sonder.com): 2026 júliusában a márkanevet és a domaint a TravelAI vette meg. Az eredeti üzemeltetés megszűnt, így ma már csak közvetítő oldal. **Ezért nem benchmark.** Ami mégis hasznos tőle: a mobilon alul rögzített tab-sáv és a számozott GYIK (01/08).
- **Tandjung Sari** (Bali, Awwwards HM 2026): sötét zöldesszürke theme-color (`#2b3530`), „Book room” mellett WhatsApp-os asztalfoglalás, és „görgess tovább” jellegű, történetmesélő lapozás.
- **Casa Cook** (casacook.com): lágy bézs, szürke és fekete minimalizmus. A vendégvéleményeknél név, tartózkodás dátuma és Tripadvisor-link szerepel.
- **Mama Shelter:** a foglalópanelen a „nem elérhető dátum” hibaüzenet közvetlenül a mezőnél jelenik meg.

### Közös minták (amit mind a 10 csinál)
1. Hero = egy kép vagy videó, egyetlen mondat és egyetlen CTA. Soha nincs benne 3 gomb.
2. A foglalás mindig 1 kattintásra van: vagy a hero alatti sávban, vagy egy fejléc-gomb nyit panelt.
3. A felület semleges (fehér vagy meleg törtfehér), a színt a fotó adja, és csak 1 akcentusszín van.
4. Kártya = kép, eyebrow, cím, 1 mondat, specifikáció, 1–2 CTA.
5. Bizalom: direkt-foglalás előny, értékelési pontszámok, díj-logók, teljes elérhetőség a láblécben.
6. A11y: skip-link, billentyűzettel kezelhető dátumválasztó, tiszta H1–H3 hierarchia.

---

## 2. rész – Design spec (ZEN Central Apartments)

### 2.1 Tipográfia (Google Fonts)
- **Alap párosítás:** címekhez `Cormorant Garamond` 500/600 (a kiemelt szó *italic*, Relais Rossar és Drake mintájára), szöveghez `Manrope` 400/500/600.
- **Alternatívák:** `Fraunces` + `Inter`, `Instrument Serif` + `DM Sans`, `Marcellus` + `Jost`.
- **Kötelező:** `latin-ext` subset (ő, ű). `font-display: swap`, a címfontból csak 1 preload. Self-host WOFF2 (GDPR, gyorsabb, nincs Google-kérés).
- **Skála (fluid, `clamp`):** 12 / 14 / 16 / 18 / 22 / 28 / 36 / 48 / 64 px. Hero H1: `clamp(40px, 6vw, 72px)`.
- **Sorköz:** szöveg 1.6, címek 1.1. Betűköz: eyebrow `0.14em` nagybetűs 12 px, a cím `-0.01em`.
- **Sorhossz:** max 68ch.

### 2.2 Térköz-skála (4 px alap)
- `--s-1:4px`, `--s-2:8px`, `--s-3:12px`, `--s-4:16px`, `--s-5:24px`, `--s-6:32px`, `--s-7:48px`, `--s-8:64px`, `--s-9:96px`, `--s-10:128px`.
- **Szekciók:** függőleges padding mobilon 64 px, asztalin 128 px. Konténer max 1240 px, oldalsó margó 16/24/40 px.
- **Kapcsoló (switch):** körülötte legalább 6 px üres tér, mellette nincs Aktív/Inaktív felirat.

### 2.3 Lekerekítés
- **Minden gomb, chip, input, select, foglaló sáv:** `border-radius: 999px` (pill).
- **Kártya:** 24 px, kép a kártyán belül 18 px, modal/lightbox 28 px, tooltip 12 px.
- **Ikon-gomb:** kör (44×44 px, ez az érintési minimum).

### 2.4 Glassmorphism / blur
Csak 3 helyen használjuk:
1. **Sticky fejléc görgetés után:** `backdrop-filter: blur(16px) saturate(140%)`, `bg: color-mix(in oklab, var(--surface) 72%, transparent)`.
2. **Foglaló sáv a hero fölött:** ugyanez, 1 px-es `rgba(255,255,255,.35)` kerettel (sötét módban `.08`).
3. **Lightbox vezérlők.**
- **Fallback:** `@supports not (backdrop-filter: blur())` esetén 94%-os opak surface.
- Szövegtartalom alá **soha** nem kerül blur, mert rontja az olvashatóságot.

### 2.5 Mozgás
- **Tempó:** 180 ms (hover), 320 ms (panel), 600 ms (reveal). Easing: `cubic-bezier(.22,1,.36,1)`.
- **Reveal:** 12 px felúszás és opacity, egyszer fut le (IntersectionObserver). Nincs parallax a szövegen.
- **Hero:** lassú Ken Burns effekt (1.00 → 1.06, 20 s), vagy max 15 s-os, némított loop videó poster képpel. A videó csak `>=768px` és nem `saveData` esetén töltődik be.
- **`@media (prefers-reduced-motion: reduce)`:** minden animáció, transition és videó-autoplay kikapcsol, a hero statikus poster lesz.
- **Zen motívum:** a jin-jang vagy lotus SVG legfeljebb 1 lassú forgást vagy kirajzolódást kap az oldalon.

### 2.6 Foglaló sáv
- **Asztali nézet:** a hero alsó harmadában lebegő pill-sáv (glass) a következő mezőkkel: `Apartman (Bármelyik / RELAX / JIN-JANG / LOTUS)` · `Érkezés – Távozás` · `Vendégek` · `[Elérhetőség]` gomb.
- **Sticky viselkedés:** amikor a sáv kigörgetődik, a fejlécbe zsugorodik kompakt pill formában („Dátumok · 2 vendég · Keresés”). Kattintásra panelként nyílik le.
- **Mobil:** alul rögzített pill-gomb „Foglalás” felirattal (safe-area inset). Kattintásra bottom-sheet nyílik: naptár, vendég-léptető, gomb.
- **Dátumválasztó:** egyetlen dátumtartomány 2 hónapos nézetben (mobilon 1), a foglalt napok áthúzva, minimum-éjszaka jelzéssel, éjszakaszámláló („3 éj”), ár/éj megjelenítéssel. Billentyűzettel kezelhető (nyilak, Enter, Esc), `aria-live` jelzi a kiválasztott tartományt.
- **Vendégek:** felnőtt/gyerek léptető pill alakú −/+ gombokkal. A maximumot az apartman férőhelye korlátozza.
- **Hiba:** inline jelenik meg a mező alatt, mindig javaslattal („Legközelebbi szabad: …”).
- **Admin:** mezők, címkék, min. éjszaka, max. vendég, árak és zárolt napok mind szerkeszthetők.

### 2.7 Apartmankártya
- **Rács:** asztalin 3 oszlop, tableten 2, mobilon 1 (vízszintes snap-scroll is választható).
- **Felépítés:** kép (4:5, `aspect-ratio`, lazy, `srcset` AVIF/WebP), sarokban motívum-ikon (lotus / jin-jang / hullám) → eyebrow („ZEN · 2. emelet”) → cím (serif) → 1 mondat → spec-chipek (férőhely, m², ágy, konyha, klíma) → „-tól X Ft / éj” → két pill gomb: **Foglalás** (accent) és **Részletek** (ghost).
- **Hover:** a kép 1.03-ra nagyul, a kártya 4 px-t emelkedik, finom árnyékkal.
- **Összehasonlítás:** egy „3 apartman egymás mellett” táblázat-nézet (Six Senses-minta).

### 2.8 Galéria
- **Apartmanoldal:** bento-rács (1 nagy és 4 kicsi kép), mellette egy „Összes kép (n)” pill gomb.
- **Lightbox:** teljes képernyő, swipe, nyilak/Esc, számláló (3/24), alsó bélyegkép-sáv, glass vezérlők, fókuszcsapda. A háttér görgetése tiltott.
- **Szűrő-chipek:** Nappali / Háló / Fürdő / Konyha / Környék.
- **Teljesítmény:** blur-up placeholder (LQIP), `loading="lazy"`, `decoding="async"`. Az első kép `fetchpriority="high"`.

### 2.9 Lábléc
- **4 oszlop:** márka és rövid szöveg | Apartmanok | Információ (házirend, GYIK, megközelítés, ÁSZF, Adatvédelem, Impresszum) | Kapcsolat (cím, telefon, e-mail, WhatsApp, térkép link, NTAK-szám).
- **Felső sáv:** értékelési pontszámok egyetlen adatforrásból és egy „Direkt foglalás = legjobb ár” blokk.
- **Alsó sor:** nyelvváltó (HU/EN/DE) pill formában, social ikonok, cookie-beállítás link, ©.
- **Strukturált adat:** `LodgingBusiness` / `Apartment`, `AggregateRating`, `FAQPage`, `BreadcrumbList` JSON-LD.

### 2.10 Sötét mód
- Alapból a rendszert követi (`prefers-color-scheme`). Van kézi kapcsoló is (nap/hold kör gomb), a választás `localStorage`-ba kerül, a `<html data-theme>` attribútumon keresztül.
- **Tokenek:** `--bg --surface --text --muted --accent --accent-2 --line`. Mindkét módhoz külön hex tartozik (lásd 2.11).
- **Sötétben:** nem tiszta fekete, hanem meleg sötét alap. Az akcentus világosabb árnyalatú, az árnyék helyett 1 px-es `--line` keret. A fotók `filter: brightness(.92)` értéket kapnak.

### 2.11 Tíz skin (light / dark)
Mérve: minden `text`, `muted`, `accent`, `accent-2` legalább 4.5:1 kontrasztot ad a saját `bg`-n (WCAG AA), light és dark módban is.

| # | Skin | Hangulat | Mód | bg | surface | text | muted | accent | accent-2 | Címfont | Szövegfont |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | **Sand Zen** | meleg homok, a jelenlegi bézs továbbvitele | L | `#F6F1EA` | `#FFFDF9` | `#2A2621` | `#6B6258` | `#8A6438` | `#55705A` | Cormorant Garamond | Manrope |
| | | | D | `#161412` | `#211E1A` | `#EFE8DE` | `#A89E91` | `#D2A874` | `#8FB096` | | |
| 2 | **Lotus Blush** | lótuszrózsa, lágy, nőies | L | `#F8F2F1` | `#FFFFFF` | `#2E2426` | `#6F5F65` | `#A3536A` | `#5F7356` | Fraunces | Inter |
| | | | D | `#171214` | `#231B1E` | `#F2E7E9` | `#B09FA4` | `#E59AAE` | `#A9BC9C` | | |
| 3 | **Jin-Jang Ink** | tus és papír, erős kontraszt | L | `#F4F3F0` | `#FFFFFF` | `#111111` | `#5C5C59` | `#8C2F2B` | `#3E3E3B` | Instrument Serif | DM Sans |
| | | | D | `#0E0E0E` | `#1A1A19` | `#F2F1EE` | `#9C9B97` | `#E0675F` | `#CFCDC7` | | |
| 4 | **Matcha** | friss zöld tea, természet | L | `#F2F4EE` | `#FBFCF8` | `#1F2620` | `#5A675B` | `#4E6B43` | `#8A6A3A` | Lora | Figtree |
| | | | D | `#101411` | `#1A201B` | `#E6ECE4` | `#9AA89B` | `#9CC28A` | `#D4B47E` | | |
| 5 | **Hinoki** | cédrusfa, onsen-melegség | L | `#F7F0E6` | `#FFFAF3` | `#2B2219` | `#705E4B` | `#8A4F25` | `#3F5B5A` | Marcellus | Jost |
| | | | D | `#15110D` | `#211A14` | `#F1E6D8` | `#B19E89` | `#E09A62` | `#8DB3B1` | | |
| 6 | **Karesansui** | kőkert, kavicsszürke csend | L | `#EFEEEB` | `#F9F8F6` | `#22221F` | `#62615B` | `#405361` | `#7D6848` | Noto Serif Display | Work Sans |
| | | | D | `#121313` | `#1C1D1D` | `#E9E8E4` | `#A2A19B` | `#9FB6C6` | `#CDB48F` | | |
| 7 | **Balaton Mist** | tópára, helyi kötődés | L | `#EEF3F4` | `#FAFCFC` | `#1A2629` | `#55676C` | `#28606D` | `#93603A` | Fraunces | Manrope |
| | | | D | `#0D1517` | `#162124` | `#E3EEF0` | `#93A9AE` | `#7CC3D1` | `#E2AE7F` | | |
| 8 | **Indigo Night** | aizome indigó, esti nyugalom | L | `#F1F1F6` | `#FFFFFF` | `#1B1C2E` | `#5B5D72` | `#3D4A8C` | `#7F6234` | Playfair Display | Inter |
| | | | D | `#0D0E18` | `#171929` | `#E8E8F2` | `#9A9CB5` | `#9AA8F0` | `#DDBA82` | | |
| 9 | **Washi Bamboo** | rizspapír és bambusz, légies | L | `#FAF8F3` | `#FFFFFF` | `#262522` | `#67645C` | `#56652A` | `#7A643E` | Cormorant Garamond | Outfit |
| | | | D | `#131311` | `#1D1D1A` | `#F0EEE7` | `#A5A298` | `#B7C978` | `#D9C59B` | | |
| 10 | **Hojicha Ember** | pörkölt tea, gyertyafény | L | `#F6EEE8` | `#FFF9F5` | `#2D1F1A` | `#735C52` | `#9C4528` | `#4F6150` | Bodoni Moda | Plus Jakarta Sans |
| | | | D | `#160F0C` | `#231915` | `#F3E5DD` | `#B49C91` | `#EE8A63` | `#9DB295` | | |

**Gombszöveg:** light módban az accent gombon `#FFFFFF` (≥5.2:1, mérve), dark módban az accent gombon `#111111` (≥5.6:1, mérve).
**Admin:** a skin legördülő menüből választható, és minden token felülírható színválasztóval. Élő előnézet light és dark módban.

### 2.12 Teljesítmény és SEO (Numa, Ace és Drake mintájára)
- LCP-kép `preload`, AVIF/WebP, kritikus CSS inline, JS `defer`. Harmadik féltől származó widget (chat, térkép) csak interakcióra töltődik be.
- Célérték: LCP < 2.5 s, CLS < 0.1, INP < 200 ms. Ez cél, nem mérés.
- Minden oldalon egyetlen H1. Az apartmanoldalakon `Apartment` + `Offer` JSON-LD, `hreflang` HU/EN/DE.
- **AI/chat:** lebegő kör-gomb jobb alul, a mobilos foglaló gomb fölött. Késleltetve töltődik be, GYIK-alapú válaszokkal és WhatsApp-átadással.
