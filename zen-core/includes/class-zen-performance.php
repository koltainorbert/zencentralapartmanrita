<?php
/**
 * Zen Performance – villámgyors front-end + Cloudflare integráció.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Performance {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'cleanup' ) );
		add_action( 'send_headers', array( __CLASS__, 'cache_headers' ), 20 );
		add_filter( 'heartbeat_settings', array( __CLASS__, 'heartbeat' ) );
		add_action( 'save_post', array( __CLASS__, 'maybe_purge_post' ), 99, 2 );
		add_action( 'zen_settings_updated', array( __CLASS__, 'maybe_purge_all' ) );
		add_action( 'zen_apartments_updated', array( __CLASS__, 'maybe_purge_all' ) );
		// Minden admin-módosítás azonnal látszódjon a front-enden (menü, szerző, téma, testreszabás, szekciók).
		foreach ( array( 'wp_update_nav_menu', 'wp_delete_nav_menu', 'customize_save_after', 'switch_theme', 'profile_update', 'zen_sections_saved', 'update_option_sidebars_widgets', 'update_option_blogname', 'update_option_blogdescription', 'update_option_theme_mods_zen-central', 'delete_post', 'set_object_terms' ) as $h ) {
			add_action( $h, array( __CLASS__, 'purge_everything' ), 99 );
		}
		add_action( 'post_updated', array( __CLASS__, 'author_changed' ), 99, 3 );
		add_action( 'shutdown', array( __CLASS__, 'run_deferred_purge' ), 0 );
		add_action( 'send_headers', array( __CLASS__, 'logged_in_nocache' ), 30 );
		add_action( 'zen_ical_synced', array( __CLASS__, 'maybe_purge_booking' ) );
		add_action( 'admin_post_zen_cf_purge', array( __CLASS__, 'admin_purge' ) );
		add_filter( 'wp_resource_hints', array( __CLASS__, 'hints' ), 10, 2 );
	}

	public static function cleanup() {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		add_action(
			'wp',
			function () {
				// Klasszikus Zen téma: a blokk-téma globális stílusai (~10 KB inline) csak bejegyzéseken kellenek.
				if ( ! is_singular( 'post' ) ) {
					remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
					remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
					remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
				}
			}
		);
		add_action(
			'wp_enqueue_scripts',
			function () {
				if ( ! is_user_logged_in() ) {
					wp_dequeue_style( 'dashicons' );
				}
				wp_deregister_script( 'wp-embed' );
			},
			100
		);
	}

	public static function heartbeat( $s ) {
		$s['interval'] = 60;
		return $s;
	}

	public static function hints( $urls, $type ) {
		// A betű-preconnectet a téma írja ki (zc_head_assets) – itt csak a duplikátumot szűrjük.
		if ( 'preconnect' === $type ) {
			$urls = array_values(
				array_filter(
					$urls,
					function ( $u ) {
						$h = is_array( $u ) ? ( $u['href'] ?? '' ) : $u;
						return false === strpos( (string) $h, 'fonts.bunny.net' );
					}
				)
			);
		}
		return $urls;
	}

	/** Cloudflare edge cache fejléc a bejelentkezés nélküli HTML oldalakra (ha be van állítva). */
	public static function cache_headers() {
		$ttl = (int) Zen_Settings::get( 'cloudflare.html_ttl', 0 );
		if ( $ttl <= 0 || is_admin() || is_user_logged_in() || headers_sent() ) {
			return;
		}
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { // phpcs:ignore
			return;
		}
		if ( ! empty( $_GET ) ) { // phpcs:ignore -- foglalási paraméterek stb. ne kerüljenek cache-be
			header( 'Cache-Control: no-cache' );
			return;
		}
		header( 'Cache-Control: public, max-age=0, s-maxage=' . $ttl . ', stale-while-revalidate=60' );
		header( 'CDN-Cache-Control: max-age=' . $ttl );
	}

	/* ------------------------------------------------------------------
	 * Cloudflare purge
	 * ------------------------------------------------------------------ */
	protected static function cf() {
		$zone  = trim( (string) Zen_Settings::get( 'cloudflare.zone_id', '' ) );
		$token = Zen_Security::decrypt( trim( (string) Zen_Settings::get( 'cloudflare.api_token', '' ) ) );
		return ( $zone && $token ) ? array( $zone, $token ) : null;
	}

	public static function purge( $urls = array() ) {
		$cf = self::cf();
		if ( ! $cf ) {
			return new WP_Error( 'zen_cf', 'Cloudflare nincs beállítva.' );
		}
		$body = $urls ? array( 'files' => array_values( array_slice( array_unique( $urls ), 0, 30 ) ) ) : array( 'purge_everything' => true );
		$res  = wp_remote_post(
			'https://api.cloudflare.com/client/v4/zones/' . rawurlencode( $cf[0] ) . '/purge_cache',
			array(
				'timeout' => 10,
				'headers' => array( 'Authorization' => 'Bearer ' . $cf[1], 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$j = json_decode( wp_remote_retrieve_body( $res ), true );
		update_option( 'zen_cf_last', array( 'time' => time(), 'ok' => ! empty( $j['success'] ), 'msg' => wp_json_encode( $j['errors'] ?? array() ) ), false );
		return ! empty( $j['success'] );
	}

	public static function maybe_purge_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! in_array( $post->post_type, array( 'page', 'post', 'nav_menu_item' ), true ) ) {
			return;
		}
		// Az oldal/bejegyzés a fejlécben, láblécben, menüben, blog listában és fordításokon is megjelenhet → teljes ürítés.
		self::purge_everything();
	}

	public static function maybe_purge_all() {
		self::purge_everything();
	}

	public static function author_changed( $post_id, $after, $before ) {
		if ( (int) $after->post_author !== (int) $before->post_author ) {
			self::purge_everything();
		}
	}

	/** @var bool ürítés a kérés végén (egy kérésben többször is hívódhat, de csak egyszer fut) */
	protected static $pending = false;

	/** Teljes cache ürítés: kérés végén, egyszer. */
	public static function purge_everything() {
		self::$pending = true;
	}

	public static function run_deferred_purge() {
		if ( ! self::$pending ) {
			return;
		}
		self::$pending = false;
		self::purge_local();
		if ( Zen_Settings::get( 'cloudflare.purge_on_save', true ) && self::cf() ) {
			self::purge();
		}
	}

	/**
	 * Helyi oldal-cache-ek ürítése (ami telepítve van): LiteSpeed, WP Rocket, W3TC, WP Super Cache,
	 * WP Fastest Cache, SiteGround, Breeze, Hummingbird, Autoptimize, Cache Enabler, Comet, Swift,
	 * Nginx Helper, Varnish HTTP Purge, Kinsta, WP Engine, Pantheon, GoDaddy, Cloudflare bővítmény, objektum-cache.
	 */
	public static function purge_local() {
		$done = array();
		$try  = function ( $label, $cb ) use ( &$done ) {
			try {
				$cb();
				$done[] = $label;
			} catch ( \Throwable $e ) { // phpcs:ignore
				unset( $e );
			}
		};
		if ( has_action( 'litespeed_purge_all' ) || defined( 'LSCWP_V' ) ) {
			$try( 'LiteSpeed', fn() => do_action( 'litespeed_purge_all' ) );
		}
		if ( function_exists( 'rocket_clean_domain' ) ) {
			$try( 'WP Rocket', function () { rocket_clean_domain(); if ( function_exists( 'rocket_clean_minify' ) ) { rocket_clean_minify(); } } );
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			$try( 'W3 Total Cache', fn() => w3tc_flush_all() );
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			$try( 'WP Super Cache', fn() => wp_cache_clear_cache() );
		}
		if ( isset( $GLOBALS['wp_fastest_cache'] ) && method_exists( $GLOBALS['wp_fastest_cache'], 'deleteCache' ) ) {
			$try( 'WP Fastest Cache', fn() => $GLOBALS['wp_fastest_cache']->deleteCache( true ) );
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			$try( 'SiteGround', fn() => sg_cachepress_purge_cache() );
		}
		if ( has_action( 'breeze_clear_all_cache' ) ) {
			$try( 'Breeze', fn() => do_action( 'breeze_clear_all_cache' ) );
		}
		if ( has_action( 'wphb_clear_page_cache' ) ) {
			$try( 'Hummingbird', fn() => do_action( 'wphb_clear_page_cache' ) );
		}
		if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
			$try( 'Autoptimize', fn() => autoptimizeCache::clearall() );
		}
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			$try( 'Cache Enabler', fn() => do_action( 'cache_enabler_clear_complete_cache' ) );
		}
		if ( class_exists( 'Swift_Performance_Cache' ) && method_exists( 'Swift_Performance_Cache', 'clear_all_cache' ) ) {
			$try( 'Swift', fn() => Swift_Performance_Cache::clear_all_cache() );
		}
		if ( has_action( 'rt_nginx_helper_purge_all' ) ) {
			$try( 'Nginx Helper', fn() => do_action( 'rt_nginx_helper_purge_all' ) );
		}
		if ( has_action( 'vhp_purge_all' ) || class_exists( 'VarnishPurger' ) ) {
			$try( 'Varnish', fn() => do_action( 'vhp_purge_all' ) );
		}
		if ( isset( $GLOBALS['kinsta_cache'] ) && ! empty( $GLOBALS['kinsta_cache']->kinsta_cache_purge ) && method_exists( $GLOBALS['kinsta_cache']->kinsta_cache_purge, 'purge_complete_caches' ) ) {
			$try( 'Kinsta', fn() => $GLOBALS['kinsta_cache']->kinsta_cache_purge->purge_complete_caches() );
		}
		if ( class_exists( 'WpeCommon' ) ) {
			$try( 'WP Engine', function () { if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) { WpeCommon::purge_memcached(); } if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) { WpeCommon::purge_varnish_cache(); } } );
		}
		if ( function_exists( 'pantheon_wp_clear_edge_all' ) ) {
			$try( 'Pantheon', fn() => pantheon_wp_clear_edge_all() );
		}
		if ( class_exists( '\WPaaS\Plugin' ) ) {
			$try( 'GoDaddy', fn() => do_action( 'wpaas_flush_cache' ) );
		}
		if ( has_action( 'cloudflare_purge_everything' ) ) {
			$try( 'Cloudflare plugin', fn() => do_action( 'cloudflare_purge_everything' ) );
		}
		if ( class_exists( 'Zen_AI' ) ) {
			Zen_AI::flush_ctx();
		}
		wp_cache_flush();
		update_option( 'zen_purge_last', array( 'time' => time(), 'done' => $done ), false );
		do_action( 'zen_cache_purged', $done );
		return $done;
	}

	/** Bejelentkezett szerkesztő soha ne kapjon böngészőből régi oldalt. */
	public static function logged_in_nocache() {
		if ( ! is_admin() && is_user_logged_in() && ! headers_sent() ) {
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
		}
	}

	public static function maybe_purge_booking() {
		// Az elérhetőség REST-en jön (no-store), így HTML purge nem szükséges.
	}

	public static function admin_purge() {
		check_admin_referer( 'zen_cf_purge' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nincs jogosultság.' );
		}
		self::purge_local();
		$r = self::cf() ? self::purge() : true;
		wp_safe_redirect( admin_url( 'admin.php?page=zen-studio&cf=' . ( true === $r ? 'ok' : 'err' ) . '#cloudflare' ) );
		exit;
	}
}
