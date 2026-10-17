<?php
/**
 * Plugin Name:       ZEN Core – Apartman motor
 * Plugin URI:        https://zencentralapartments.com/
 * Description:       ZEN Central Apartments: Zen Studio admin (minden szöveg, kép, videó, design), többnyelvűség, foglalómotor, Zen AI concierge, űrlapok, elégedettségi portál, SEO + AI SEO (Rank Math és Site Kit kompatibilis), Cloudflare.
 * Version:           1.0.0
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

define( 'ZEN_CORE_VER', '1.0.0' );
define( 'ZEN_CORE_FILE', __FILE__ );
define( 'ZEN_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZEN_CORE_URL', plugin_dir_url( __FILE__ ) );

$zen_includes = array(
	'class-zen-settings.php',
	'class-zen-i18n.php',
	'class-zen-media.php',
	'class-zen-content.php',
	'class-zen-importer.php',
	'class-zen-seo.php',
	'class-zen-performance.php',
	'class-zen-security.php',
	'class-zen-booking.php',
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
