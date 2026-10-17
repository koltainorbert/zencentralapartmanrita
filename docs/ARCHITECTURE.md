# ZEN Central Apartments – architektúra (szerződés minden modulhoz)

Két csomag:
- `zen-core/` – plugin (minden adat, admin, foglalás, Zen AI, űrlapok, SEO, nyelvek). Témaváltáskor sem vész el semmi.
- `zen-central/` – téma (csak megjelenítés). zen-core nélkül minimális fallbackkel működik.

Követelmények (mindenhol): PHP 8.0+, WP 6.4+. Front-enden **nincs jQuery**, nincs build-lépés, vanilla JS (ES2019), defer. Minden gomb **pill** (border-radius: 999px), minden input/select/textarea lekerekített (min. 14px). Kapcsoló (toggle) mellett **nincs** Aktív/Inaktív felirat, körülötte min. 6 px hely. Minden szöveg fordítható (zen_t). Minden kép helyén lehet videó is (media objektum). `prefers-reduced-motion` tisztelete. Escape mindenhol (esc_html, esc_attr, esc_url, wp_kses_post).

## Konstansok
`ZEN_CORE_VER`, `ZEN_CORE_FILE`, `ZEN_CORE_DIR` (trailing slash), `ZEN_CORE_URL` (trailing slash).

## Opciók
- `zen_settings` (array) – `Zen_Settings::defaults()` adja a teljes szerkezetet. Csoportok: `general`, `design`, `languages`, `booking`, `ai`, `seo`, `forms`, `cloudflare`, `strings`.
  Olvasás: `zen_opt('general.phone', $default)` (pont-útvonal). Írás: `Zen_Settings::update(array $partial)` (mély merge + sanitize).
- `zen_apartments` (array) – lásd lent.

## Media objektum (mindenhol azonos)
```json
{"type":"image|video","url":"https://...","id":0,"poster":"","alt":""}
```
Videó url lehet mp4/webm vagy YouTube/Vimeo link. Renderelés: `zen_media_html($media, $args)` (`$args`: class, sizes, eager(bool), cover(bool), autoplay(bool, default true videónál), controls(bool)).

## Apartman (zen_apartments elem)
```json
{"key":"relax","name":{"hu":"RELAX Apartman","en":"RELAX Apartment","de":"RELAX Apartment"},
 "capacity":2,"size":20,"bedrooms":1,"price":0,"price_weekend":0,"min_nights":1,
 "short":{"hu":"..."},"cover":{media},"icon":{media},"gallery":[{media}],
 "color":"#B08D57","ical_in":["https://...ics"],"ical_token":"random"}
```
`price` 0 = árajánlat-kérés mód (nincs ár kiírva, „Kérjen ajánlatot”).

## Oldalak és szekciók
Valódi WP oldalak (Rank Math, sitemap, Site Kit kompatibilis). Meta:
- `_zen_key`: `home, apartments, apt_relax, apt_jinjang, apt_lotus, gallery, blog, contact, booking, privacy, feedback, help, lang_root`
- `_zen_lang`: nyelvkód (`hu` alapértelmezett, gyökérben; többi nyelv `/xx/` szülőoldal alatt, az eredeti URL-ek megmaradnak: `/en/zen-central-english/`, `/de/relax-apartment-2/` …)
- `_zen_sections`: JSON tömb:
```json
[{"id":"s1a2","type":"hero","enabled":true,
  "style":{"bg":{media|null},"tone":"auto|alt|dark|accent","pad":"s|m|l","overlay":0.35,"anchor":""},
  "data":{...típusfüggő...}}]
```
Olvasás: `zen_sections($post_id)` (tömb), írás: `zen_save_sections($post_id, $arr)`.

### Szekció típusok és `data` mezők
| type | data |
|---|---|
| `hero` | kicker, title, text, buttons[{label,url,style:"primary|ghost"}], media{media}, height:"full|tall|medium|small", booking_bar(bool), breadcrumbs(bool), side_logo(bool) |
| `intro` | kicker, title, html, buttons[], media{media}, layout:"left|right|center" |
| `cards` | kicker, title, html, source:"apartments|manual", exclude(key), items[{title,meta,url,media}] |
| `features` | title, items[string], columns |
| `map_list` | title, map_query, items[string], html |
| `cta` | title, lines[string], buttons[] |
| `faq` | title, groups[{title, items[{q, a(html)}]}] |
| `gallery` | title, groups[{title, items[{media}]}] |
| `apt_detail` | apt(key), title, media{media}, html, facts[string], amenities_title, amenities[string], buttons[], gallery[{media}] |
| `contact` | kicker, title, notice, info_title, info[{label,url,icon}], booking_title, buttons[], community_title, media{media}, access_title, access_html, checkin_title, checkin_html, map_query |
| `form` | form:"contact|newsletter|feedback", title, text |
| `richtext` | title, html |
| `booking` | title, text, engine:"inherit|native|nhb|both" |
| `blog_list` | title, count, show_excerpt |
| `reviews` | title, text, source:"feedback|manual", items[{name,text,rating,date,origin}] |
| `guide` | title, text, category:"all|sight|daytrip|event|food", limit |
| `stats` | items[{value,label}] |

Téma renderelő: `zen-central/template-parts/sections/{type}.php` – változók: `$s` (szekció), `$d` (= $s['data']), `$ctx` (['lang','post_id','index']).

## Globális függvények (zen-core)
`zen_opt()`, `zen_lang()`, `zen_langs()` (engedélyezett nyelvek: code => [name, native, locale, flag]), `zen_default_lang()`, `zen_t($key, $lang=null, $vars=[])`, `zen_page_id($key,$lang=null)`, `zen_page_url($key,$lang=null)`, `zen_translations($post_id)` (lang => url), `zen_sections()`, `zen_save_sections()`, `zen_media_html()`, `zen_media_url($media)`, `zen_apartments()`, `zen_apartment($key)`, `zen_apt_name($key,$lang=null)`, `zen_booking_widget($args=[])`, `zen_form($type,$args=[])`, `zen_skins()`, `zen_skin_css()`, `zen_icon($name)` (inline SVG).

UI szövegek: `Zen_I18n::strings($lang)` = `data/strings/{lang}.json` + modulok `zen_strings` filter (`apply_filters('zen_strings', $arr, $lang)`) + admin felülírás (`zen_settings.strings[lang][key]`). Hiányzó kulcs → en → hu fallback.

## REST (`zen/v1`)
Nyilvános végpontok nonce nélkül is működnek (Cloudflare HTML cache miatt), védelem: honeypot mező `zc_hp`, időbélyeg `zc_ts` (min. 3 s), IP alapú rate limit transientben.
- `GET  /availability?from=Y-m-d&to=Y-m-d` → `{days:{"2026-10-20":{"relax":"free|booked|checkout|closed"}}, events:[{name,from,to}], minNights:1}`
- `POST /quote` `{items:[{apt,from,to,adults,children}]}` → `{items:[{apt,nights,total,perNight[]}], total, deposit, tax, currency, quoteMode(bool)}`
- `POST /booking` → `{ok:true, ref:"ZC-2610-AB12"}`
- `POST /chat` `{messages:[{role,content}], lang, page, sid}` → `{reply, chips[], actions[]}`
- `POST /form/(contact|newsletter|feedback)` → `{ok, message}`
- iCal export: `/?zen_ical=relax&t=TOKEN`

## CPT-k
`zen_booking` (foglalás/kérelem), `zen_message` (kapcsolat + hírlevél, meta `_type`), `zen_feedback` (elégedettség, publikálható), `zen_chat` (Zen AI beszélgetések – CRM). Mind admin-only (`show_ui` true, `public` false).

## Front-end design tokenek (téma adja, modulok használják)
CSS változók a `:root`-on: `--zc-bg --zc-surface --zc-surface-2 --zc-text --zc-muted --zc-line --zc-accent --zc-accent-2 --zc-accent-ink --zc-glass --zc-shadow --zc-radius (22px) --zc-radius-s (14px) --zc-font-h --zc-font-b --zc-ok --zc-warn --zc-bad`.
Sötét mód: `<html data-theme="dark">`, skin: `<html data-skin="zen-sand">`. Osztály-prefix: `zc-` (téma), modulok: `zcb-` (booking), `zca-` (Zen AI), `zcf-` (forms), admin: `zs-`.
Közös gombok: `.zc-btn` (+ `.zc-btn--ghost`, `.zc-btn--sm`), inputok: `.zc-input`, kapcsoló: `.zc-switch`.

## Nyelvek
Teljes tartalom: hu, en, de (eredeti, szó szerint). Továbbiak (fordítás): fr, it, es, pl, cs, sk, ro, hr, sl, nl. Admin bármelyiket ki-/bekapcsolja.

## Teljesítmény
Egy CSS (`zen.css`) + egy JS (`zen.js`, defer). Booking és Zen AI JS csak ott/akkor töltődik, ahol kell (booking oldal / chat megnyitás – dinamikus import). Hero kép `fetchpriority="high"`, preload; többi lazy. Betűk: Bunny Fonts (GDPR-barát) vagy rendszerbetű opció.
