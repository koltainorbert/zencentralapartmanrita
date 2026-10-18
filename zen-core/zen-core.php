<?php
/**
 * Plugin Name:       ZEN Core – Apartman motor
 * Plugin URI:        https://zencentralapartments.com/
 * Description:       ZEN Central Apartments: Zen Studio admin (minden szöveg, kép, videó, design), többnyelvűség, foglalómotor, Zen AI concierge, űrlapok, elégedettségi portál, SEO + AI SEO (Rank Math és Site Kit kompatibilis), Cloudflare.
 * Version:           1.2.3
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            InfoStudio Hungary
 * Author URI:        https://infostudio.hu
 * Text Domain:       zen-core
 * License:           GPL-2.0-or-later
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

define( 'ZEN_CORE_VER', '1.2.3' );
define( 'ZEN_CORE_FILE', __FILE__ );
define( 'ZEN_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZEN_CORE_URL', plugin_dir_url( __FILE__ ) );

$zen_includes = array(
	'class-zen-settings.php',
	'class-zen-i18n.php',
	'class-zen-media.php',
	'class-zen-amenities.php',
	'class-zen-content.php',
	'class-zen-importer.php',
	'class-zen-seo.php',
	'class-zen-performance.php',
	'class-zen-security.php',
	'class-zen-booking.php',
	'class-zen-extras.php',
	'class-zen-ai.php',
	'class-zen-forms.php',
	'class-zen-admin.php',
	'class-zen-sections-editor.php',
);
foreach ( $zen_includes as $zen_file ) {
	$zen_path = ZEN_CORE_DIR . 'includes/' . $zen_file;
	if ( file_exists( $zen_path ) ) {
		require_once $zen_path;
	}
}
unset( $zen_includes, $zen_file, $zen_path );

/* ------------------------------------------------------------------
 * Globális segédfüggvények (a téma és a modulok ezeket használják)
 * ------------------------------------------------------------------ */

/** Beállítás pont-útvonallal: zen_opt('general.phone'). */
function zen_opt( $path, $default = null ) {
	return Zen_Settings::get( $path, $default );
}

/** Aktuális nyelv. */
function zen_lang() {
	return Zen_I18n::current();
}

/** Alapértelmezett nyelv. */
function zen_default_lang() {
	return Zen_I18n::default_lang();
}

/** Engedélyezett nyelvek: code => [name, native, locale, flag]. */
function zen_langs() {
	return Zen_I18n::enabled();
}

/** UI szöveg fordítása. */
function zen_t( $key, $lang = null, $vars = array() ) {
	return Zen_I18n::t( $key, $lang, $vars );
}

/** Oldal ID kulcs + nyelv alapján. */
function zen_page_id( $key, $lang = null ) {
	return Zen_Content::page_id( $key, $lang ?: zen_lang() );
}

/** Oldal URL kulcs + nyelv alapján (fallback: alapnyelv). */
function zen_page_url( $key, $lang = null ) {
	return Zen_Content::page_url( $key, $lang ?: zen_lang() );
}

/** Egy oldal fordításai: lang => url. */
function zen_translations( $post_id = 0 ) {
	return Zen_Content::translations( $post_id ?: get_queried_object_id() );
}

/** Szekciók egy oldalhoz. */
function zen_sections( $post_id = 0 ) {
	return Zen_Content::get_sections( $post_id ?: get_the_ID() );
}

/** Szekciók mentése. */
function zen_save_sections( $post_id, $sections ) {
	return Zen_Content::save_sections( $post_id, $sections );
}

/** Media HTML (kép vagy videó). */
function zen_media_html( $media, $args = array() ) {
	return Zen_Media::html( $media, $args );
}

/** Media URL (videónál poster, ha kérjük). */
function zen_media_url( $media, $size = 'full' ) {
	return Zen_Media::url( $media, $size );
}

/** Apartmanok. */
function zen_apartments() {
	return Zen_Settings::apartments();
}

/** Egy apartman kulcs alapján. */
function zen_apartment( $key ) {
	foreach ( zen_apartments() as $a ) {
		if ( $a['key'] === $key ) {
			return $a;
		}
	}
	return null;
}

/** Apartman neve az adott nyelven. */
function zen_apt_name( $key, $lang = null ) {
	$a = zen_apartment( $key );
	if ( ! $a ) {
		return '';
	}
	return Zen_I18n::pick( $a['name'] ?? array(), $lang );
}

/** Skin lista. */
function zen_skins() {
	return Zen_Settings::skins();
}

/** SZÉP-kártya jelvény (üres, ha ki van kapcsolva vagy az ügyfél nem fogad SZÉP-kártyát). $place: footer|booking */
function zen_szep_badge( $place = '' ) {
	return class_exists( 'Zen_Extras' ) ? Zen_Extras::szep_html( $place ) : '';
}

/** Akciós / last minute doboz (üres, ha ki van kapcsolva vagy lejárt). */
function zen_promo_box() {
	return class_exists( 'Zen_Extras' ) ? Zen_Extras::promo_html() : '';
}

/** Foglaló widget HTML. */
function zen_booking_widget( $args = array() ) {
	return class_exists( 'Zen_Booking' ) ? Zen_Booking::widget( $args ) : '';
}

/** Űrlap HTML. */
function zen_form( $type, $args = array() ) {
	return class_exists( 'Zen_Forms' ) ? Zen_Forms::render( $type, $args ) : '';
}

/** Inline SVG ikon. */
function zen_icon( $name, $size = 20 ) {
	return Zen_Media::icon( $name, $size );
}

/** Felszereltség sor: [ 'icon', 'svg', 'text' ] – automatikus vagy „[[ikon]]” kézi ikonnal. */
function zen_amenity( $text, $size = 22 ) {
	return Zen_Amenities::item( $text, $size );
}

/* ------------------------------------------------------------------
 * Aktiválás / deaktiválás
 * ------------------------------------------------------------------ */

register_activation_hook(
	__FILE__,
	function () {
		if ( class_exists( 'Zen_Importer' ) ) {
			update_option( 'zen_needs_import', 1 );
		}
		if ( class_exists( 'Zen_I18n' ) ) {
			Zen_I18n::register_rewrites();
		}
		if ( class_exists( 'Zen_Booking' ) ) {
			Zen_Booking::schedule();
		}
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'zen_ical_sync' );
		flush_rewrite_rules();
	}
);

/* ------------------------------------------------------------------
 * Init
 * ------------------------------------------------------------------ */

add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain( 'zen-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		foreach ( array( 'Zen_I18n', 'Zen_Media', 'Zen_Content', 'Zen_Importer', 'Zen_SEO', 'Zen_Performance', 'Zen_Security', 'Zen_Booking', 'Zen_AI', 'Zen_Forms', 'Zen_Admin', 'Zen_Sections_Editor' ) as $cls ) {
			if ( class_exists( $cls ) && method_exists( $cls, 'init' ) ) {
				$cls::init();
			}
		}
	},
	5
);

/* ------------------------------------------------------------------
 * Frissítés 1.1.0: francia és holland apartmannevek, rövid leírások, extrák
 * (a mentett adatban hiányzó nyelvi kulcsokat pótolja, a meglévőt nem írja felül)
 * ------------------------------------------------------------------ */
function zen_lang_fill_data() {
	return array(
		'apts'   => array(
			'relax'   => array(
				'name'  => array( 'fr' => 'Appartement RELAX', 'nl' => 'RELAX-appartement' ),
				'short' => array( 'fr' => 'Studio pour 2 personnes, 20 m², terrasse, parking dans la cour fermée.', 'nl' => 'Studio voor 2 personen, 20 m², terras, parkeerplaats op de afgesloten binnenplaats.' ),
			),
			'jinjang' => array(
				'name'  => array( 'fr' => 'Appartement JIN-JANG', 'nl' => 'JIN-JANG-appartement' ),
				'short' => array( 'fr' => 'Appartement de 2 chambres pour 4 personnes, 35 m², terrasse.', 'nl' => 'Appartement met 2 slaapkamers voor 4 personen, 35 m², terras.' ),
			),
			'lotus'   => array(
				'name'  => array( 'fr' => 'Appartement LOTUS', 'nl' => 'LOTUS-appartement' ),
				'short' => array( 'fr' => 'Studio pour 3 personnes, 25 m², terrasse, parking dans la cour fermée.', 'nl' => 'Studio voor 3 personen, 25 m², terras, parkeerplaats op de afgesloten binnenplaats.' ),
			),
		),
		'extras' => array(
			'parking' => array( 'fr' => 'Place de parking privée dans la cour', 'nl' => 'Eigen parkeerplaats op de binnenplaats' ),
			'early'   => array( 'fr' => 'Arrivée anticipée (sur demande, avec supplément)', 'nl' => 'Vroege aankomst (op aanvraag, tegen meerprijs)' ),
			'late'    => array( 'fr' => 'Départ tardif (sur demande, avec supplément)', 'nl' => 'Laat vertrek (op aanvraag, tegen meerprijs)' ),
		),
	);
}

add_action(
	'init',
	function () {
		if ( version_compare( (string) get_option( 'zen_core_db', '1.0.0' ), '1.1.0', '>=' ) || ! class_exists( 'Zen_Settings' ) ) {
			return;
		}
		$fill = zen_lang_fill_data();
		$saved = get_option( Zen_Settings::OPT_APTS, null );
		if ( is_array( $saved ) && $saved ) {
			foreach ( $saved as &$a ) {
				foreach ( array( 'name', 'short' ) as $f ) {
					if ( isset( $fill['apts'][ $a['key'] ?? '' ][ $f ] ) && is_array( $a[ $f ] ?? null ) ) {
						$a[ $f ] += $fill['apts'][ $a['key'] ][ $f ];
					}
				}
			}
			unset( $a );
			update_option( Zen_Settings::OPT_APTS, $saved, true );
		}
		$opt = get_option( Zen_Settings::OPT, array() );
		if ( is_array( $opt ) && ! empty( $opt['booking']['extras'] ) && is_array( $opt['booking']['extras'] ) ) {
			foreach ( $opt['booking']['extras'] as &$e ) {
				if ( isset( $fill['extras'][ $e['key'] ?? '' ] ) && is_array( $e['label'] ?? null ) ) {
					$e['label'] += $fill['extras'][ $e['key'] ];
				}
			}
			unset( $e );
			update_option( Zen_Settings::OPT, $opt, true );
			Zen_Settings::flush();
		}
		update_option( 'zen_core_db', '1.1.0', true );
	},
	1
);

/* Frissítés 1.1.0: a lektorált francia és holland oldalszövegek egyszeri betöltése (URL-ek változatlanok).
 * Csak adminban fut, egyszer; utána a szövegek a megszokott módon szerkeszthetők. */
add_action(
	'admin_init',
	function () {
		if ( '1.1.0' === get_option( 'zen_fr_nl_v' ) || ! class_exists( 'Zen_Importer' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'zen_fr_nl_v', '1.1.0', false );
		foreach ( array( 'fr', 'nl' ) as $l ) {
			if ( in_array( $l, (array) Zen_Settings::get( 'languages.enabled', array() ), true ) ) {
				Zen_Importer::run( array( 'force' => true, 'lang' => $l ) );
			}
		}
		if ( class_exists( 'Zen_Performance' ) ) {
			Zen_Performance::purge_everything();
		}
	}
);

/* Frissítés 1.1.0: telefonszám egységesítése mindenhol (+36 30 116 9431) és az apartman-leírások
 * magyar eredetihez igazított változatának betöltése a többi nyelven. Egyszer fut, adminban. */
add_action(
	'admin_init',
	function () {
		if ( '1.1.0' === get_option( 'zen_fix_v' ) || ! class_exists( 'Zen_Content' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'zen_fix_v', '1.1.0', false );
		// 1) Telefonszám minden oldal szekcióiban (GYIK válasz).
		foreach ( Zen_Content::pages() as $p ) {
			$raw = (string) get_post_meta( $p['id'], '_zen_sections', true );
			if ( '' === $raw || ( false === strpos( $raw, '971 7556' ) && false === strpos( $raw, '9717556' ) ) ) {
				continue;
			}
			$secs = Zen_Content::get_sections( $p['id'] );
			array_walk_recursive(
				$secs,
				function ( &$v ) {
					if ( is_string( $v ) ) {
						$v = str_replace( array( '+36 20 971 7556', '06/20 971 7556', '+36 20 9717556', '06 20 971 7556' ), '+36 30 116 9431', $v );
					}
				}
			);
			Zen_Content::save_sections( $p['id'], $secs );
		}
		// 1/b) Foglalási telefonszám a beállításokban (foglalási súgó, Zen AI, e-mailek).
		$pb = preg_replace( '/[^0-9]/', '', (string) Zen_Settings::get( 'general.phone_booking', '' ) );
		if ( in_array( $pb, array( '06209717556', '36209717556' ), true ) ) {
			Zen_Settings::update( array( 'general' => array( 'phone_booking' => '+36 30 116 9431' ) ) );
		}
		// 2) Apartman oldalak újratöltése a javított szövegekkel (URL-ek változatlanok).
		if ( class_exists( 'Zen_Importer' ) ) {
			$enabled = (array) Zen_Settings::get( 'languages.enabled', array() );
			foreach ( array( 'en', 'de', 'it', 'es', 'pl', 'cs', 'sk', 'ro', 'hr', 'sl' ) as $l ) {
				if ( in_array( $l, $enabled, true ) ) {
					Zen_Importer::run( array( 'force' => true, 'lang' => $l, 'keys' => array( 'apt_relax', 'apt_jinjang', 'apt_lotus' ) ) );
				}
			}
		}
		if ( class_exists( 'Zen_AI' ) ) {
			Zen_AI::flush_ctx();
		}
		if ( class_exists( 'Zen_Performance' ) ) {
			Zen_Performance::purge_everything();
		}
	}
);

/* Frissítés 1.1.1: az apartmankártya-szekciók (pl. „További apartmanok”) háttere semleges – a mintás háttérkép kikerül. */
add_action(
	'admin_init',
	function () {
		if ( '1.1.1' === get_option( 'zen_fix2_v' ) || ! class_exists( 'Zen_Content' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'zen_fix2_v', '1.1.1', false );
		foreach ( Zen_Content::pages() as $p ) {
			$secs    = Zen_Content::get_sections( $p['id'] );
			$changed = false;
			foreach ( $secs as &$s ) {
				if ( 'cards' === ( $s['type'] ?? '' ) && ! empty( $s['style']['bg'] ) ) {
					$s['style']['bg'] = null;
					$changed          = true;
				}
			}
			unset( $s );
			if ( $changed ) {
				Zen_Content::save_sections( $p['id'], $secs );
			}
		}
		if ( class_exists( 'Zen_Performance' ) ) {
			Zen_Performance::purge_everything();
		}
	}
);

/* Frissítés 1.1.3: Google utcatérkép alapértelmezés; magyar szövegekben „asszisztens” a „concierge” helyett. */
add_action(
	'admin_init',
	function () {
		if ( '1.1.3' === get_option( 'zen_fix3_v' ) || ! class_exists( 'Zen_Content' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'zen_fix3_v', '1.1.3', false );
		if ( 'osm' === Zen_Settings::get( 'general.map_provider', 'google' ) ) {
			Zen_Settings::update( array( 'general' => array( 'map_provider' => 'google' ) ) );
		}
		$map = array( 'digitális concierge-ünket' => 'digitális asszisztensünket', 'digitális concierge-ünk' => 'digitális asszisztensünk', 'intelligenciás concierge-ünk' => 'intelligenciás asszisztensünk', 'Zen AI concierge' => 'Zen AI asszisztens', 'digitális concierge-e' => 'digitális asszisztense', 'digitális concierge' => 'digitális asszisztens' );
		foreach ( Zen_Content::pages( 'hu' ) as $p ) {
			$secs = Zen_Content::get_sections( $p['id'] );
			$raw  = wp_json_encode( $secs );
			array_walk_recursive(
				$secs,
				function ( &$v ) use ( $map ) {
					if ( is_string( $v ) ) {
						$v = strtr( $v, $map );
					}
				}
			);
			if ( wp_json_encode( $secs ) !== $raw ) {
				Zen_Content::save_sections( $p['id'], $secs );
			}
			foreach ( array( '_zen_seo_title', '_zen_seo_desc', 'rank_math_title', 'rank_math_description' ) as $mk ) {
				$v = (string) get_post_meta( $p['id'], $mk, true );
				if ( '' !== $v && false !== strpos( $v, 'concierge' ) ) {
					update_post_meta( $p['id'], $mk, strtr( $v, $map ) );
				}
			}
		}
		if ( class_exists( 'Zen_AI' ) ) {
			Zen_AI::flush_ctx();
		}
		if ( class_exists( 'Zen_Performance' ) ) {
			Zen_Performance::purge_everything();
		}
	}
);

/* Frissítés 1.1.4: a térképek mindig maguktól töltődnek be. */
add_action(
	'admin_init',
	function () {
		if ( '1.1.4' === get_option( 'zen_fix4_v' ) || ! class_exists( 'Zen_Settings' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'zen_fix4_v', '1.1.4', false );
		Zen_Settings::update( array( 'general' => array( 'map_autoload' => 'always', 'map_provider' => 'google' ) ) );
	}
);

/* Frissítés 1.2.0: az ügyfél kérdőív-válaszai (telefon, előleg, értesítési e-mail, fizetési módok, apartman-árak,
 * kézi jóváhagyás) + az új funkciók (vendégkalauz, SZÉP-jelzés, akciós doboz) alapbeállításai. Egyszer fut, adminban. */
add_action(
	'admin_init',
	function () {
		if ( '1.2.0' === get_option( 'zen_fix6_v' ) || ! class_exists( 'Zen_Settings' ) || ! class_exists( 'Zen_Content' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'zen_fix6_v', '1.2.0', false );
		$new_phone = '+36 20 971 7556';
		$old       = array( '+36 30 116 9431', '+36 30 1169431', '+36301169431', '06 30 116 9431', '06/30 116 9431', '06301169431' );
		// Előleg-szövegek (GYIK) 50% -> 100%: a magyar és idegen nyelvű magtartalom cseréje.
		$dep_map = array();
		$dep_file = ZEN_CORE_DIR . 'data/deposit-update.json';
		if ( is_readable( $dep_file ) ) {
			$sw = json_decode( (string) file_get_contents( $dep_file ), true );
			foreach ( (array) $sw as $x ) {
				if ( ! empty( $x['old'] ) && isset( $x['new'] ) ) {
					$dep_map[ $x['old'] ] = $x['new'];
				}
			}
		}
		// 1) Telefonszám az oldal-szekciókban és a beállításokban.
		foreach ( Zen_Content::pages() as $p ) {
			$raw = (string) get_post_meta( $p['id'], '_zen_sections', true );
			if ( '' === $raw ) {
				continue;
			}
			$secs = Zen_Content::get_sections( $p['id'] );
			array_walk_recursive(
				$secs,
				function ( &$v ) use ( $old, $new_phone, $dep_map ) {
					if ( is_string( $v ) ) {
						$v = str_replace( $old, $new_phone, $v );
						$v = strtr( $v, $dep_map );
					}
				}
			);
			Zen_Content::save_sections( $p['id'], $secs );
		}
		$upd = array();
		foreach ( array( 'phone', 'phone_booking' ) as $k ) {
			$digits = preg_replace( '/[^0-9]/', '', (string) Zen_Settings::get( 'general.' . $k, '' ) );
			if ( '' === $digits || in_array( $digits, array( '06301169431', '36301169431' ), true ) ) {
				$upd['general'][ $k ] = $new_phone;
			}
		}
		// 2) Előleg 100%, értesítések a Gmail-címre, fizetés: online kártya + átutalás, kézi jóváhagyás.
		if ( 50 === (int) Zen_Settings::get( 'general.deposit_pct', 50 ) ) {
			$upd['general']['deposit_pct'] = 100;
		}
		if ( 'info@zencentralapartments.com' === Zen_Settings::get( 'booking.notify_email', '' ) ) {
			$upd['booking']['notify_email'] = 'zencentralapartments@gmail.com';
		}
		if ( array( 'transfer', 'cash', 'card' ) === array_values( (array) Zen_Settings::get( 'booking.payments', array() ) ) ) {
			$upd['booking']['payments'] = array( 'card', 'transfer' );
		}
		$upd['booking']['auto_confirm'] = false; // minden foglalást a tulajdonos hagy jóvá (e-mailből egy kattintással)
		Zen_Settings::update( $upd );
		// 3) Apartman éjszakai árak (csak ha még nincs ár megadva).
		$prices = array( 'relax' => 30000, 'jinjang' => 60000, 'lotus' => 40000 );
		$apts   = Zen_Settings::apartments();
		foreach ( $apts as &$a ) {
			if ( isset( $prices[ $a['key'] ] ) && (float) ( $a['price'] ?? 0 ) <= 0 ) {
				$a['price'] = $prices[ $a['key'] ];
			}
		}
		unset( $a );
		Zen_Settings::save_apartments( $apts );
		if ( class_exists( 'Zen_AI' ) ) {
			Zen_AI::flush_ctx();
		}
		if ( class_exists( 'Zen_Performance' ) ) {
			Zen_Performance::purge_everything();
		}
	}
);
