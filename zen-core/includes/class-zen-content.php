<?php
/**
 * Zen Content – Zen oldalak (kulcs + nyelv), szekciók tárolása, fordítások, URL-tokenek.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Content {

	const MAP = 'zen_page_map';

	/** @var array|null */
	protected static $map = null;

	public static function init() {
		add_action( 'save_post_page', array( __CLASS__, 'on_save' ), 20 );
		add_action( 'deleted_post', array( __CLASS__, 'rebuild_map' ) );
		add_action( 'trashed_post', array( __CLASS__, 'rebuild_map' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'rebuild_map' ) );
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_shortcode( 'zen_page_url', array( __CLASS__, 'sc_page_url' ) );
		add_shortcode( 'zen_t', array( __CLASS__, 'sc_t' ) );
		add_shortcode( 'zen_booking', array( __CLASS__, 'sc_booking' ) );
		add_shortcode( 'zen_form', array( __CLASS__, 'sc_form' ) );
	}

	/** Oldaltípusok (kulcsok) emberi névvel. */
	public static function keys() {
		return array(
			'home'        => 'Főoldal',
			'apartments'  => 'Apartmanok',
			'apt_relax'   => 'RELAX apartman',
			'apt_jinjang' => 'JIN-JANG apartman',
			'apt_lotus'   => 'LOTUS apartman',
			'gallery'     => 'Galéria',
			'blog'        => 'Blog',
			'contact'     => 'Kapcsolat',
			'booking'     => 'Foglalás',
			'feedback'    => 'Elégedettségi portál',
			'help'        => 'Súgó',
			'privacy'     => 'Adatkezelési tájékoztató',
		);
	}

	public static function register_meta() {
		foreach ( array( '_zen_key', '_zen_lang', '_zen_sections', '_zen_seo_title', '_zen_seo_desc' ) as $k ) {
			register_post_meta(
				'page',
				$k,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => function () {
						return current_user_can( 'edit_pages' );
					},
				)
			);
		}
	}

	public static function on_save( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( get_post_meta( $post_id, '_zen_key', true ) ) {
			self::rebuild_map();
		}
	}

	/** Kulcs+nyelv → oldal ID térkép. */
	public static function map() {
		if ( null !== self::$map ) {
			return self::$map;
		}
		$map = get_option( self::MAP, null );
		if ( ! is_array( $map ) ) {
			$map = self::rebuild_map();
		}
		self::$map = $map;
		return $map;
	}

	public static function rebuild_map() {
		global $wpdb;
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT p.ID, k.meta_value AS zkey, l.meta_value AS zlang
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} k ON k.post_id = p.ID AND k.meta_key = '_zen_key'
			 LEFT JOIN {$wpdb->postmeta} l ON l.post_id = p.ID AND l.meta_key = '_zen_lang'
			 WHERE p.post_type = 'page' AND p.post_status IN ('publish','private','draft')
			 ORDER BY p.post_status = 'publish' DESC, p.ID ASC"
		);
		$map = array();
		foreach ( (array) $rows as $r ) {
			$lang = $r->zlang ? $r->zlang : Zen_I18n::default_lang();
			if ( ! isset( $map[ $r->zkey ][ $lang ] ) ) {
				$map[ $r->zkey ][ $lang ] = (int) $r->ID;
			}
		}
		update_option( self::MAP, $map, true );
		self::$map = $map;
		return $map;
	}

	public static function page_id( $key, $lang = null ) {
		$lang = $lang ?: Zen_I18n::current();
		$map  = self::map();
		return isset( $map[ $key ][ $lang ] ) ? (int) $map[ $key ][ $lang ] : 0;
	}

	/** URL, fallback az alapnyelvre, majd a kezdőlapra. */
	public static function page_url( $key, $lang = null ) {
		$lang = $lang ?: Zen_I18n::current();
		$id   = self::page_id( $key, $lang );
		if ( ! $id ) {
			$id = self::page_id( $key, Zen_I18n::default_lang() );
		}
		if ( ! $id && 'home' === $key ) {
			return home_url( '/' );
		}
		return $id ? get_permalink( $id ) : home_url( '/' );
	}

	/** Egy oldal fordításai: lang => url (csak létező, publikált oldalak). */
	public static function translations( $post_id ) {
		$out = array();
		if ( ! $post_id ) {
			return $out;
		}
		$key = get_post_meta( $post_id, '_zen_key', true );
		if ( $key && 'lang_root' !== $key ) {
			foreach ( Zen_I18n::enabled() as $lang => $meta ) {
				$id = self::page_id( $key, $lang );
				if ( $id && 'publish' === get_post_status( $id ) ) {
					$out[ $lang ] = get_permalink( $id );
				}
			}
			return $out;
		}
		// Bejegyzések: saját nyelv, illetve kézi párok (_zen_translations: [lang => post_id]).
		$lang = get_post_meta( $post_id, '_zen_lang', true ) ?: Zen_I18n::default_lang();
		$out[ $lang ] = get_permalink( $post_id );
		$pairs        = get_post_meta( $post_id, '_zen_translations', true );
		if ( is_array( $pairs ) ) {
			foreach ( $pairs as $l => $pid ) {
				if ( Zen_I18n::is_valid( $l ) && 'publish' === get_post_status( (int) $pid ) ) {
					$out[ $l ] = get_permalink( (int) $pid );
				}
			}
		}
		return $out;
	}

	/** Szekciók egy oldalhoz (normalizálva). */
	public static function get_sections( $post_id ) {
		$raw = get_post_meta( $post_id, '_zen_sections', true );
		$arr = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );
		if ( ! is_array( $arr ) ) {
			return array();
		}
		$out = array();
		foreach ( $arr as $i => $s ) {
			if ( ! is_array( $s ) || empty( $s['type'] ) ) {
				continue;
			}
			$out[] = array(
				'id'      => ! empty( $s['id'] ) ? sanitize_key( $s['id'] ) : 's' . $i,
				'type'    => sanitize_key( $s['type'] ),
				'enabled' => ! isset( $s['enabled'] ) || (bool) $s['enabled'],
				'style'   => isset( $s['style'] ) && is_array( $s['style'] ) ? $s['style'] : array(),
				'data'    => isset( $s['data'] ) && is_array( $s['data'] ) ? $s['data'] : array(),
			);
		}
		return $out;
	}

	public static function has_sections( $post_id ) {
		return '' !== (string) get_post_meta( $post_id, '_zen_sections', true );
	}

	/** Szekciók mentése (wp_slash a JSON-on). */
	public static function save_sections( $post_id, $sections ) {
		if ( ! is_array( $sections ) ) {
			return false;
		}
		$clean = array();
		foreach ( array_values( $sections ) as $i => $s ) {
			if ( ! is_array( $s ) || empty( $s['type'] ) ) {
				continue;
			}
			$clean[] = array(
				'id'      => ! empty( $s['id'] ) ? sanitize_key( $s['id'] ) : 's' . wp_generate_password( 6, false, false ),
				'type'    => sanitize_key( $s['type'] ),
				'enabled' => ! isset( $s['enabled'] ) || (bool) $s['enabled'],
				'style'   => self::clean_tree( $s['style'] ?? array() ),
				'data'    => self::clean_tree( $s['data'] ?? array() ),
			);
		}
		$json = wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		update_post_meta( $post_id, '_zen_sections', wp_slash( $json ) );
		do_action( 'zen_sections_saved', $post_id, $clean );
		return true;
	}

	/** Rekurzív tisztítás: html kulcsok wp_kses_post, url kulcsok esc_url_raw, többi sanitize_textarea_field. */
	public static function clean_tree( $v, $key = '' ) {
		if ( is_array( $v ) ) {
			$o = array();
			foreach ( $v as $k => $vv ) {
				$o[ is_int( $k ) ? $k : sanitize_key( $k ) ] = self::clean_tree( $vv, is_int( $k ) ? $key : (string) $k );
			}
			return $o;
		}
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) || null === $v ) {
			return $v;
		}
		$v = (string) $v;
		if ( preg_match( '/(^|_)(html|a|text_html|access_html|checkin_html)$/', $key ) || 'html' === $key ) {
			return wp_kses_post( $v );
		}
		if ( in_array( $key, array( 'url', 'poster' ), true ) ) {
			// Tokenek ({page:x}) és relatív/anchor linkek megengedettek.
			if ( preg_match( '~^(\{page:[a-z_]+\}|#|/|mailto:|tel:)~', $v ) ) {
				return sanitize_text_field( $v );
			}
			return esc_url_raw( $v );
		}
		return sanitize_textarea_field( $v );
	}

	/** {page:key} tokenek feloldása (query suffix megmarad). */
	public static function resolve_url( $url, $lang = null ) {
		$url = (string) $url;
		return preg_replace_callback(
			'~\{page:([a-z_]+)\}~',
			function ( $m ) use ( $lang ) {
				return untrailingslashit( self::page_url( $m[1], $lang ) ) . '/';
			},
			$url
		);
	}

	/** Szöveg tokenek feloldása HTML-ben (href="{page:x}"). */
	public static function resolve_html( $html, $lang = null ) {
		return self::resolve_url( (string) $html, $lang );
	}

	/** Zen oldalak listája. */
	public static function pages( $lang = null ) {
		$q   = new WP_Query(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => 300,
				'meta_key'       => '_zen_key', // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);
		$out = array();
		foreach ( $q->posts as $p ) {
			$key = get_post_meta( $p->ID, '_zen_key', true );
			$l   = get_post_meta( $p->ID, '_zen_lang', true ) ?: Zen_I18n::default_lang();
			if ( 'lang_root' === $key || ( $lang && $lang !== $l ) ) {
				continue;
			}
			$out[] = array(
				'id'     => $p->ID,
				'key'    => $key,
				'lang'   => $l,
				'title'  => get_the_title( $p ),
				'url'    => get_permalink( $p ),
				'status' => $p->post_status,
				'edit'   => get_edit_post_link( $p->ID, 'raw' ),
			);
		}
		return $out;
	}

	/** Egy oldal teljes szövege (AI, llms.txt, keresés). */
	public static function plain_text( $post_id, $max = 12000 ) {
		$parts = array();
		foreach ( self::get_sections( $post_id ) as $s ) {
			if ( ! $s['enabled'] ) {
				continue;
			}
			$parts[] = self::flatten( $s['data'] );
		}
		$txt = trim( preg_replace( '/\s+/u', ' ', implode( "\n", $parts ) ) );
		return mb_substr( $txt, 0, $max );
	}

	protected static function flatten( $v, $key = '' ) {
		if ( is_array( $v ) ) {
			$o = array();
			foreach ( $v as $k => $vv ) {
				if ( in_array( (string) $k, array( 'url', 'media', 'bg', 'poster', 'gallery', 'id', 'style', 'apt', 'source', 'layout', 'height', 'engine', 'form', 'icon', 'map_query' ), true ) ) {
					continue;
				}
				if ( 'items' === $k && isset( $v['items'][0] ) && is_string( $v['items'][0] ) && 0 === strpos( $v['items'][0], 'U/' ) ) {
					continue;
				}
				$o[] = self::flatten( $vv, (string) $k );
			}
			return implode( "\n", array_filter( $o ) );
		}
		if ( ! is_string( $v ) || 0 === strpos( $v, 'U/' ) || 0 === strpos( $v, 'http' ) ) {
			return '';
		}
		if ( 'q' === $key ) {
			return 'K: ' . wp_strip_all_tags( $v );
		}
		return wp_strip_all_tags( str_replace( array( '<br>', '</p>', '</li>' ), array( "\n", "\n", "\n" ), $v ) );
	}

	/* Rövidkódok */
	public static function sc_page_url( $atts ) {
		$a = shortcode_atts( array( 'key' => 'home', 'lang' => '' ), $atts );
		return esc_url( self::page_url( sanitize_key( $a['key'] ), $a['lang'] ?: null ) );
	}

	public static function sc_t( $atts ) {
		$a = shortcode_atts( array( 'key' => '' ), $atts );
		return esc_html( Zen_I18n::t( sanitize_key( $a['key'] ) ) );
	}

	public static function sc_booking( $atts ) {
		$a = shortcode_atts( array( 'engine' => '' ), $atts );
		return zen_booking_widget( array( 'engine' => $a['engine'] ) );
	}

	public static function sc_form( $atts ) {
		$a = shortcode_atts( array( 'type' => 'contact' ), $atts );
		return zen_form( sanitize_key( $a['type'] ) );
	}
}
