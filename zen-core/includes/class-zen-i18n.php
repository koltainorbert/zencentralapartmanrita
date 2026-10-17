<?php
/**
 * Zen I18n – nyelvek, aktuális nyelv, UI szövegek (fordítható, adminban felülírható).
 *
 * URL-ek: alapnyelv a gyökérben (/apartmanok/), a többi nyelv /xx/ szülőoldal alatt
 * (/en/apartments/, /de/apartments-2/) – az eredeti oldal URL-jei változatlanok maradnak.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_I18n {

	/** @var string|null */
	protected static $current = null;

	/** @var array */
	protected static $strings = array();

	public static function init() {
		add_filter( 'language_attributes', array( __CLASS__, 'language_attributes' ), 20 );
		add_filter( 'locale', array( __CLASS__, 'front_locale' ), 20 );
		add_action( 'template_redirect', array( __CLASS__, 'lang_root_redirect' ), 1 );
		add_action( 'zen_settings_updated', array( __CLASS__, 'flush_strings' ) );
	}

	/** Minden támogatott nyelv. */
	public static function all_languages() {
		return array(
			'hu' => array( 'name' => 'Magyar', 'native' => 'Magyar', 'locale' => 'hu_HU', 'hreflang' => 'hu' ),
			'en' => array( 'name' => 'Angol', 'native' => 'English', 'locale' => 'en_GB', 'hreflang' => 'en' ),
			'de' => array( 'name' => 'Német', 'native' => 'Deutsch', 'locale' => 'de_DE', 'hreflang' => 'de' ),
			'fr' => array( 'name' => 'Francia', 'native' => 'Français', 'locale' => 'fr_FR', 'hreflang' => 'fr' ),
			'it' => array( 'name' => 'Olasz', 'native' => 'Italiano', 'locale' => 'it_IT', 'hreflang' => 'it' ),
			'es' => array( 'name' => 'Spanyol', 'native' => 'Español', 'locale' => 'es_ES', 'hreflang' => 'es' ),
			'pl' => array( 'name' => 'Lengyel', 'native' => 'Polski', 'locale' => 'pl_PL', 'hreflang' => 'pl' ),
			'cs' => array( 'name' => 'Cseh', 'native' => 'Čeština', 'locale' => 'cs_CZ', 'hreflang' => 'cs' ),
			'sk' => array( 'name' => 'Szlovák', 'native' => 'Slovenčina', 'locale' => 'sk_SK', 'hreflang' => 'sk' ),
			'ro' => array( 'name' => 'Román', 'native' => 'Română', 'locale' => 'ro_RO', 'hreflang' => 'ro' ),
			'hr' => array( 'name' => 'Horvát', 'native' => 'Hrvatski', 'locale' => 'hr', 'hreflang' => 'hr' ),
			'sl' => array( 'name' => 'Szlovén', 'native' => 'Slovenščina', 'locale' => 'sl_SI', 'hreflang' => 'sl' ),
			'nl' => array( 'name' => 'Holland', 'native' => 'Nederlands', 'locale' => 'nl_NL', 'hreflang' => 'nl' ),
		);
	}

	public static function default_lang() {
		$d   = (string) Zen_Settings::get( 'languages.default', 'hu' );
		$all = self::all_languages();
		return isset( $all[ $d ] ) ? $d : 'hu';
	}

	/** Engedélyezett nyelvek (az alapnyelv mindig benne van). */
	public static function enabled() {
		$all     = self::all_languages();
		$enabled = (array) Zen_Settings::get( 'languages.enabled', array( 'hu', 'en', 'de' ) );
		$def     = self::default_lang();
		if ( ! in_array( $def, $enabled, true ) ) {
			array_unshift( $enabled, $def );
		}
		$out = array();
		foreach ( $enabled as $code ) {
			if ( isset( $all[ $code ] ) ) {
				$out[ $code ] = $all[ $code ];
			}
		}
		return $out;
	}

	/** Nyelvek, amelyekhez létezik főoldal (a nyelvváltóban csak ezek jelennek meg). */
	public static function live() {
		$out = array();
		foreach ( self::enabled() as $code => $meta ) {
			if ( $code === self::default_lang() || ( class_exists( 'Zen_Content' ) && Zen_Content::page_id( 'home', $code ) ) ) {
				$out[ $code ] = $meta;
			}
		}
		return $out;
	}

	public static function is_valid( $lang ) {
		return is_string( $lang ) && isset( self::enabled()[ $lang ] );
	}

	/** Aktuális nyelv megállapítása. */
	public static function current() {
		if ( null !== self::$current ) {
			return self::$current;
		}
		$lang = null;

		// REST / AJAX: ?lang= vagy zen_lang paraméter.
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() ) {
			$p = isset( $_REQUEST['lang'] ) ? sanitize_key( wp_unslash( $_REQUEST['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( self::is_valid( $p ) ) {
				return $p; // nem cache-eljük, kérésenként változhat
			}
		}

		if ( did_action( 'wp' ) ) {
			$id = get_queried_object_id();
			if ( $id ) {
				$meta = get_post_meta( $id, '_zen_lang', true );
				if ( self::is_valid( $meta ) ) {
					$lang = $meta;
				}
			}
			if ( ! $lang ) {
				$lang = self::from_path();
			}
			self::$current = $lang ?: self::default_lang();
			return self::$current;
		}

		$lang = self::from_path();
		return $lang ?: self::default_lang();
	}

	/** Nyelv az URL első szegmenséből (/en/...). */
	public static function from_path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		$home = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$uri  = (string) $uri;
		if ( $home && 0 === strpos( $uri, $home ) ) {
			$uri = substr( $uri, strlen( $home ) );
		}
		$seg = strtolower( strtok( trim( $uri, '/' ), '/' ) ?: '' );
		if ( $seg && $seg !== self::default_lang() && self::is_valid( $seg ) ) {
			return $seg;
		}
		return null;
	}

	public static function set_current( $lang ) {
		if ( self::is_valid( $lang ) ) {
			self::$current = $lang;
		}
	}

	/** Érték kiválasztása nyelvi tömbből: [hu=>..., en=>...]. */
	public static function pick( $by_lang, $lang = null ) {
		if ( ! is_array( $by_lang ) ) {
			return (string) $by_lang;
		}
		$lang = $lang ?: self::current();
		foreach ( array( $lang, 'en', self::default_lang(), 'hu' ) as $l ) {
			if ( isset( $by_lang[ $l ] ) && '' !== $by_lang[ $l ] ) {
				return $by_lang[ $l ];
			}
		}
		$first = reset( $by_lang );
		return is_string( $first ) ? $first : '';
	}

	/** Egy nyelv összes UI szövege. Rétegek: hu → en → adott nyelv (modul-alapértékek + JSON), végül admin felülírás. */
	public static function strings( $lang = null, $with_overrides = true ) {
		$lang = $lang ?: self::current();
		$ck   = $lang . ( $with_overrides ? '' : '|base' );
		if ( isset( self::$strings[ $ck ] ) ) {
			return self::$strings[ $ck ];
		}
		$chain = array( 'hu' );
		if ( 'hu' !== $lang ) {
			$chain[] = 'en';
		}
		if ( ! in_array( $lang, $chain, true ) ) {
			$chain[] = $lang;
		}
		$merged = array();
		foreach ( $chain as $l ) {
			$merged = array_merge( $merged, (array) apply_filters( 'zen_strings', array(), $l ), self::read_file( $l ) );
		}
		$over = $with_overrides ? Zen_Settings::get( 'strings.' . $lang, array() ) : array();
		if ( is_array( $over ) ) {
			foreach ( $over as $k => $v ) {
				if ( '' !== (string) $v ) {
					$merged[ $k ] = $v;
				}
			}
		}
		self::$strings[ $ck ] = $merged;
		return $merged;
	}

	protected static function read_file( $lang ) {
		$file = ZEN_CORE_DIR . 'data/strings/' . $lang . '.json';
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$json = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return is_array( $json ) ? $json : array();
	}

	public static function flush_strings() {
		self::$strings = array();
	}

	/** Fordítás + {változó} csere. */
	public static function t( $key, $lang = null, $vars = array() ) {
		$s   = self::strings( $lang );
		$val = isset( $s[ $key ] ) ? (string) $s[ $key ] : $key;
		$vars = array_merge(
			array(
				'year'  => gmdate( 'Y' ),
				'phone' => Zen_Settings::get( 'general.phone', '' ),
				'email' => Zen_Settings::get( 'general.email', '' ),
				'ntak'  => Zen_Settings::get( 'general.ntak', '' ),
				'site'  => Zen_Settings::get( 'general.site_name', '' ),
			),
			(array) $vars
		);
		foreach ( $vars as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$val = str_replace( '{' . $k . '}', (string) $v, $val );
			}
		}
		return $val;
	}

	/** <html lang="..."> */
	public static function language_attributes( $output ) {
		if ( is_admin() ) {
			return $output;
		}
		$lang = self::current();
		$all  = self::all_languages();
		$code = isset( $all[ $lang ] ) ? $all[ $lang ]['hreflang'] : $lang;
		$output = preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $code ) . '"', $output );
		if ( false === strpos( $output, 'lang=' ) ) {
			$output .= ' lang="' . esc_attr( $code ) . '"';
		}
		return $output;
	}

	/** WP locale a front-enden (dátumformátumok). */
	public static function front_locale( $locale ) {
		if ( is_admin() || ! did_action( 'wp' ) ) {
			return $locale;
		}
		$all  = self::all_languages();
		$lang = self::current();
		return isset( $all[ $lang ] ) ? $all[ $lang ]['locale'] : $locale;
	}

	/** /en/ gyökér → az adott nyelv főoldala. */
	public static function lang_root_redirect() {
		if ( ! is_page() ) {
			return;
		}
		$id = get_queried_object_id();
		if ( 'lang_root' === get_post_meta( $id, '_zen_key', true ) ) {
			$lang = get_post_meta( $id, '_zen_lang', true );
			$home = class_exists( 'Zen_Content' ) ? Zen_Content::page_id( 'home', $lang ) : 0;
			wp_safe_redirect( $home ? get_permalink( $home ) : home_url( '/' ), 301 );
			exit;
		}
	}

	/** Kompatibilitás: régi hívás. */
	public static function register_rewrites() {
		return true;
	}
}
