# ZEN Central Apartments – WordPress rendszer

A https://zencentralapartments.com/ teljes újraépítése: saját foglalómodul (NetHotelBooking nélkül), Booking.com és Airbnb naptárszinkron, Zen AI asszisztens, 13 nyelv, 10 téma (mindegyik világos és sötét változatban), SEO és AI SEO.

| Csomag | Mi van benne |
|---|---|
| `zen-core/` (bővítmény) | adatok, **Zen Studio** admin, foglalás, Zen AI, űrlapok, SEO/AI SEO, nyelvek, Cloudflare |
| `zen-central/` (téma) | a megjelenítés (sablonok, CSS, JS); a bővítmény nélkül is működik |

## Telepítés (kb. 10 perc)

1. **Mentés:** készíts teljes mentést a mostani oldalról (fájlok és adatbázis).
2. **Feltöltés:** Bővítmények → Új → Feltöltés → `zen-core.zip` → Bekapcsolás. Utána Megjelenés → Témák → Feltöltés → `zen-central.zip` → Bekapcsolás.
3. **Import:** az első admin-betöltéskor magától lefut. Kézzel: Zen Studio → Import / Export → *Eredeti tartalom visszaállítása* (felülírja a módosításokat!), vagy WP-CLI-vel: `wp zen import`.
   - A meglévő oldalakat URL alapján átveszi (az ID és a Rank Math adatok megmaradnak), a régi URL-ek nem változnak.
   - Létrehozza mind a 13 nyelvet: HU, EN, DE (az eredeti szöveg), valamint FR, IT, ES, PL, CS, SK, RO, HR, SL, NL.
4. **Képek helyi másolata:** Zen Studio → Import / Export → *Képek átemelése a saját médiatárba* (vagy `wp zen sideload`). Addig a képek az eredeti domainről töltődnek.
5. **Régi bővítmények:** az Elementor, a NetHotelBooking és a Polylang/WPML kikapcsolható, a Zen ezek nélkül működik.

## Foglalás – Booking.com és Airbnb szinkron (dupla foglalás ellen)

A weboldal a központ: minden csatorna foglalása egy naptárba fut be, és a többi csatorna is látja.

1. **Importálás a weboldalra:** Zen Studio → Apartmanok → az adott apartman → *iCal – Booking.com* és *iCal – Airbnb* mező: ide másold a Booking.com Extranetből és az Airbnb naptárából az **export** iCal linket.
2. **Exportálás a csatornákra:** Zen Studio → *Naptár & Booking/Airbnb* menüben apartmanonként két link van:
   - a „Booking.com-nak” linket a Booking.com Extranetben: Szobaárak és elérhetőség → Naptárszinkronizálás → *Naptár importálása*;
   - az „Airbnb-nek” linket az Airbnb-n: Naptár → Elérhetőség → *Naptár importálása*.
   Mindkét link csak a **többi** csatorna foglalásait küldi, így nem keletkezik visszacsatolás.
3. **Szinkron:** 15 percenként automatikusan fut, és minden webes foglalás előtt is. A *Szinkron most* gombbal azonnal elindítható.
   - **Cloudflare alatt ajánlott valódi cron:** `*/5 * * * * curl -s https://zencentralapartments.com/wp-cron.php >/dev/null`, és a `wp-config.php` fájlba: `define('DISABLE_WP_CRON', true);`
4. **Működési módok:**
   - Az alapértelmezés a *kérés mód*: a vendég foglalási kérést küld, te visszaigazolod. A naptárban a kérés már foglaltként jelenik meg.
   - Az *azonnali visszaigazolás* csak akkor kapcsolható be, ha mindkét csatorna iCal linkje be van állítva.
5. **Árak:** Zen Studio → Foglalás. Itt állíthatók az alapárak, a szezonok, a hétvégi felár, a minimum éjszakák száma, az előleg, az idegenforgalmi adó és az extrák. Ha egy ár 0, a rendszer ajánlatkérés módban működik (így volt eddig is).

## Zen AI

1. Zen Studio → Zen AI → add meg az API-kulcsot. Ajánlott az Anthropic (Claude); OpenAI és Gemini is választható.
2. Kulcs nélkül is válaszol: a GYIK és a Veszprém-tudásbázis alapján, offline módban.
3. A tudásbázis (látnivalók, kirándulások, események, programtervek) és a saját tudnivalók az adminban szerkeszthetők.
4. A beszélgetések a *Zen AI napló* menüben olvashatók. Ha a vendég e-mail-címet vagy telefonszámot ír, azt leadként jelöli.

## SEO, AI SEO, Rank Math, Site Kit, Cloudflare

- **Rank Math aktív:** a Zen kiegészíti a Rank Math JSON-LD adatait (LodgingBusiness, Apartment, FAQ, TouristAttraction, Breadcrumb), a meta adatokat a Rank Math kezeli. Rank Math nélkül a Zen saját meta, OG és JSON-LD adatait adja ki.
- **hreflang:** minden nyelvhez és x-default-hoz automatikusan elkészül.
- **AI keresők:**
  - a `/llms.txt` és a `/llms-full.txt` fájl automatikusan elkészül;
  - a robots.txt beengedi a GPTBot, ClaudeBot, PerplexityBot, Google-Extended és a többi AI keresőrobotot;
  - IndexNow értesítést küld minden mentéskor.
- **Google Site Kit:** ha aktív, a GA4/GTM kódot a Site Kit adja ki, a Zen nem duplikálja. A Consent Mode v2 süti-sáv mindig működik.
- **Cloudflare:** Zen Studio → Cloudflare. A Zone ID és az API token megadása után mentéskor automatikusan üríti a gyorsítótárat. A HTML cache TTL is ott állítható. A tokent titkosítva tárolja.

## Admin – Zen Studio

| Menüpont | Tartalom |
|---|---|
| Áttekintés | foglalások, ellenőrzőlista |
| Általános | elérhetőségek, NTAK, logók |
| Design | 10 skin, betűk, kerekítés, hero videó vagy kép |
| Apartmanok | adatok, galériák, iCal linkek |
| Oldalak & tartalom | minden nyelv minden oldala |
| Nyelvek & szövegek | nyelvek ki/be kapcsolása, a felület összes felirata |
| Foglalás | árak, szezonok, fizetési módok, e-mail sablonok |
| Zen AI | |
| Űrlapok | |
| SEO | |
| Cloudflare | |
| Import/Export | |

**Oldalak szerkesztése:** Oldalak → bármelyik Zen oldal. A *Zen szekciók* doboz az oldal teljes tartalmát kezeli: szöveg, kép vagy videó, gombok, sorrend, ki/be kapcsolás, új szekció hozzáadása és másolás másik nyelvből. Az összes képhely képet vagy videót is fogad (MP4, YouTube, Vimeo).

## Fejlesztés

- PHP 8.0+ és WordPress 6.4+ szükséges.
- Ellenőrizve WordPress + SQLite tesztkörnyezetben, PHP 8.3 alatt.
- Szerkezet és szerződés leírása: `docs/ARCHITECTURE.md`. Design-benchmark: `docs/design-benchmark.md`.
