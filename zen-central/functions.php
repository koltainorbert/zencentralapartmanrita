<?php
/**
 * ZEN Central téma – belépési pont.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;

define( 'ZC_VER', '1.2.6' );
define( 'ZC_DIR', get_template_directory() . '/' );
define( 'ZC_URI', get_template_directory_uri() . '/' );

require_once ZC_DIR . 'inc/fallback.php';
require_once ZC_DIR . 'inc/template-functions.php';

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'zen-central', ZC_DIR . 'languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'custom-logo' );
		add_image_size( 'zc-card', 900, 1100, true );
		add_image_size( 'zc-wide', 1600, 1000, false );
		// Fő menü nyelvenként: ha a Megjelenés → Menük alatt hozzárendelsz egy menüt, az jelenik meg
		// (minden módosítás azonnal, cache ürítéssel); ha nincs hozzárendelve, automatikus menü.
		$zc_locs = array( 'primary' => __( 'Fő menü – alapnyelv (üresen automatikus)', 'zen-central' ) );
		if ( class_exists( 'Zen_I18n' ) ) {
			foreach ( Zen_I18n::all_languages() as $zc_code => $zc_meta ) {
				if ( Zen_I18n::default_lang() !== $zc_code ) {
					$zc_locs[ 'primary_' . $zc_code ] = sprintf( 'Fő menü – %s (üresen automatikus)', $zc_meta['native'] ?? strtoupper( $zc_code ) );
				}
			}
		}
		register_nav_menus( $zc_locs );
	}
);

/* ------------------------------------------------------------------
 * Eszközök: egy CSS + egy JS, semmi felesleg
 * ------------------------------------------------------------------ */
add_action(
	'wp_enqueue_scripts',
	function () {
		$css = ZC_DIR . 'assets/css/zen.css';
		$js  = ZC_DIR . 'assets/js/zen.js';
		wp_enqueue_style( 'zen-central', ZC_URI . 'assets/css/zen.css', array(), ZC_VER . '.' . ( file_exists( $css ) ? filemtime( $css ) : 0 ) );
		wp_enqueue_script( 'zen-central', ZC_URI . 'assets/js/zen.js', array(), ZC_VER . '.' . ( file_exists( $js ) ? filemtime( $js ) : 0 ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script(
			'zen-central',
			'ZC',
			array(
				'mode'  => zc_opt( 'design.mode_default', 'auto' ),
				'anim'  => (bool) zc_opt( 'design.animations', true ),
				'i18n'  => array(
					'close' => zc_t( 'close' ),
					'prev'  => zc_t( 'prev' ),
					'next'  => zc_t( 'next' ),
					'dark'  => zc_t( 'theme_dark' ),
					'light' => zc_t( 'theme_light' ),
				),
			)
		);
		// Felesleges WP front-end eszközök kikapcsolása (sebesség).
		if ( ! is_admin() ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'classic-theme-styles' );
			wp_dequeue_style( 'global-styles' );
			if ( is_singular( 'post' ) ) {
				wp_enqueue_style( 'wp-block-library' ); // bejegyzésekben blokkok lehetnek
			}
		}
	},
	20
);

/* Emoji szkriptek ki – gyorsabb első festés. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

/* Fej: skin CSS változók, betűk, téma-villanásgátló. */
add_action( 'wp_head', 'zc_head_assets', 2 );

/* Gutenberg felületek a Zen oldalakon nem kellenek. */
add_filter(
	'body_class',
	function ( $c ) {
		$c[] = 'zc';
		$c[] = 'skin-' . sanitize_html_class( zc_opt( 'design.skin', 'zen-sand' ) );
		$c[] = 'hdr-' . sanitize_html_class( zc_opt( 'design.header_style', 'glass' ) );
		if ( is_page() && function_exists( 'zen_sections' ) && zen_sections( get_queried_object_id() ) ) {
			$c[] = 'zc-has-sections';
		}
		return $c;
	}
);

/* Kivonat. */
add_filter( 'excerpt_length', fn() => 28 );
add_filter( 'excerpt_more', fn() => '…' );

/* Bejegyzések kiemelt képe fallback: importált távoli kép (_zen_featured_url). */
add_filter(
	'post_thumbnail_html',
	function ( $html, $post_id ) {
		if ( $html ) {
			return $html;
		}
		$u = get_post_meta( $post_id, '_zen_featured_url', true );
		return $u ? '<img src="' . esc_url( $u ) . '" alt="' . esc_attr( get_the_title( $post_id ) ) . '" loading="lazy" decoding="async">' : '';
	},
	10,
	2
);
add_filter(
	'has_post_thumbnail',
	function ( $has, $post ) {
		if ( $has ) {
			return true;
		}
		$id = $post ? ( is_object( $post ) ? $post->ID : (int) $post ) : get_the_ID();
		return (bool) get_post_meta( $id, '_zen_featured_url', true );
	},
	10,
	2
);
