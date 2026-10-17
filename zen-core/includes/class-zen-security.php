<?php
/**
 * Zen Security – nyilvános REST kérések védelme (cache-barát, nonce nélkül).
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Security {

	public static function init() {
		// Biztonsági fejlécek a front-enden.
		add_action( 'send_headers', array( __CLASS__, 'headers' ) );
	}

	public static function headers() {
		if ( is_admin() || headers_sent() ) {
			return;
		}
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
		header( 'X-Frame-Options: SAMEORIGIN' );
	}

	/** Látogató IP (Cloudflare fejléccel). */
	public static function ip() {
		$keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
		foreach ( $keys as $k ) {
			if ( ! empty( $_SERVER[ $k ] ) ) {
				$ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $k ] ) ) )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '0.0.0.0';
	}

	/**
	 * Rate limit: true ha mehet, különben false.
	 */
	public static function rate_ok( $bucket, $limit = 10, $window = 600 ) {
		$key   = 'zen_rl_' . md5( $bucket . '|' . self::ip() );
		$count = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return false;
		}
		set_transient( $key, $count + 1, $window );
		return true;
	}

	/**
	 * Nyilvános űrlap/REST kérés ellenőrzése: honeypot (zc_hp üres), időbélyeg (zc_ts legalább $min_secs mp-es), rate limit.
	 *
	 * @return true|WP_Error
	 */
	public static function check( $params, $bucket, $limit = 10, $window = 600, $min_secs = 3 ) {
		$params = is_array( $params ) ? $params : array();
		if ( ! empty( $params['zc_hp'] ) ) {
			return new WP_Error( 'zen_spam', 'Spam', array( 'status' => 400 ) );
		}
		if ( isset( $params['zc_ts'] ) && $min_secs > 0 ) {
			$ts = (int) $params['zc_ts'];
			// ms vagy s is elfogadott.
			if ( $ts > 9999999999 ) {
				$ts = (int) floor( $ts / 1000 );
			}
			if ( $ts > 0 && ( time() - $ts ) < $min_secs ) {
				return new WP_Error( 'zen_too_fast', 'Too fast', array( 'status' => 400 ) );
			}
		}
		if ( ! self::rate_ok( $bucket, $limit, $window ) ) {
			return new WP_Error( 'zen_rate', 'Too many requests', array( 'status' => 429 ) );
		}
		return true;
	}

	/** Titkosítás (API kulcsok tárolásához) – AUTH_KEY alapú. */
	public static function encrypt( $plain ) {
		if ( '' === (string) $plain || ! function_exists( 'openssl_encrypt' ) ) {
			return (string) $plain;
		}
		$key = hash( 'sha256', defined( 'AUTH_KEY' ) ? AUTH_KEY : 'zen', true );
		$iv  = random_bytes( 16 );
		$enc = openssl_encrypt( $plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		return 'zenc:' . base64_encode( $iv . $enc );
	}

	public static function decrypt( $value ) {
		$value = (string) $value;
		if ( 0 !== strpos( $value, 'zenc:' ) || ! function_exists( 'openssl_decrypt' ) ) {
			return $value;
		}
		$raw = base64_decode( substr( $value, 5 ) );
		if ( strlen( $raw ) < 17 ) {
			return '';
		}
		$key = hash( 'sha256', defined( 'AUTH_KEY' ) ? AUTH_KEY : 'zen', true );
		$out = openssl_decrypt( substr( $raw, 16 ), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, substr( $raw, 0, 16 ) );
		return false === $out ? '' : $out;
	}
}
