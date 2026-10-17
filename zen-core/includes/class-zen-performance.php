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
		add_action( 'zen_booking_created', array( __CLASS__, 'maybe_purge_all' ) );
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
		if ( ! Zen_Settings::get( 'cloudflare.purge_on_save', false ) || wp_is_post_revision( $post_id ) || 'publish' !== $post->post_status ) {
			return;
		}
		if ( ! in_array( $post->post_type, array( 'page', 'post' ), true ) ) {
			return;
		}
		$urls = array( get_permalink( $post_id ), home_url( '/' ) );
		foreach ( zen_translations( $post_id ) as $u ) {
			$urls[] = $u;
		}
		self::purge( $urls );
	}

	public static function maybe_purge_all() {
		if ( Zen_Settings::get( 'cloudflare.purge_on_save', false ) ) {
			self::purge();
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
		$r = self::purge();
		wp_safe_redirect( admin_url( 'admin.php?page=zen-studio&cf=' . ( true === $r ? 'ok' : 'err' ) . '#cloudflare' ) );
		exit;
	}
}
