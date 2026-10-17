<?php
/**
 * Burkoló függvények: a ZEN Core pluginra támaszkodnak, nélküle biztonságos alapértékek.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;

function zc_core() {
	return function_exists( 'zen_opt' );
}

function zc_opt( $path, $default = null ) {
	return zc_core() ? zen_opt( $path, $default ) : $default;
}

function zc_t( $key, $vars = array() ) {
	if ( zc_core() ) {
		return zen_t( $key, null, $vars );
	}
	$fallback = array(
		'skip' => 'Skip to content', 'menu' => 'Menu', 'close' => 'Close', 'read_more' => 'Read more',
		'back_home' => 'Home', 'not_found_title' => 'Page not found', 'not_found_text' => '', 'search' => 'Search',
		'theme_dark' => 'Dark', 'theme_light' => 'Light', 'prev' => 'Previous', 'next' => 'Next',
	);
	$v = $fallback[ $key ] ?? $key;
	foreach ( (array) $vars as $k => $val ) {
		$v = str_replace( '{' . $k . '}', (string) $val, $v );
	}
	return $v;
}

function zc_lang() {
	return zc_core() ? zen_lang() : substr( get_locale(), 0, 2 );
}

function zc_page_url( $key ) {
	return zc_core() ? zen_page_url( $key ) : home_url( '/' );
}

function zc_icon( $name, $size = 20 ) {
	return zc_core() ? zen_icon( $name, $size ) : '';
}

function zc_media( $m, $args = array() ) {
	if ( zc_core() ) {
		return zen_media_html( $m, $args );
	}
	$url = is_array( $m ) ? ( $m['url'] ?? '' ) : (string) $m;
	return $url ? '<img src="' . esc_url( $url ) . '" alt="" loading="lazy">' : '';
}

function zc_url( $url ) {
	if ( zc_core() ) {
		$url = Zen_Content::resolve_url( (string) $url );
	}
	return esc_url( $url );
}

function zc_html( $html ) {
	if ( zc_core() ) {
		$html = Zen_Content::resolve_html( (string) $html );
	}
	// Előbb tisztítás, utána rövidkódok (a rövidkód-kimenet – űrlap, foglaló – ne sérüljön).
	return do_shortcode( wp_kses_post( (string) $html ) );
}
