<?php
/**
 * Zen Studio – egyetlen modern admin felület: minden beállítás, szöveg, kép, videó, design.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 80 );
		add_filter( 'plugin_action_links_' . plugin_basename( ZEN_CORE_FILE ), array( __CLASS__, 'links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	public static function menu() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9" stroke-dasharray="48 8"/><path d="M8.5 14c1 1.3 2.2 2 3.5 2s2.5-.7 3.5-2"/></svg>' );
		add_menu_page( 'Zen Studio', 'Zen Studio', 'manage_options', 'zen-studio', array( __CLASS__, 'page' ), $icon, 3 );
		add_submenu_page( 'zen-studio', 'Zen Studio', 'Studio (beállítások)', 'manage_options', 'zen-studio', array( __CLASS__, 'page' ) );
	}

	public static function links( $l ) {
		array_unshift( $l, '<a href="' . esc_url( admin_url( 'admin.php?page=zen-studio' ) ) . '">Zen Studio</a>' );
		return $l;
	}

	public static function admin_bar( $bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node( array( 'id' => 'zen-studio', 'title' => '◎ Zen Studio', 'href' => admin_url( 'admin.php?page=zen-studio' ) ) );
		if ( ! is_admin() && is_page() && get_post_meta( get_queried_object_id(), '_zen_key', true ) ) {
			$bar->add_node( array( 'id' => 'zen-edit-sections', 'parent' => 'zen-studio', 'title' => 'Oldal szekcióinak szerkesztése', 'href' => get_edit_post_link( get_queried_object_id() ) ) );
		}
		$bar->add_node( array( 'id' => 'zen-bookings', 'parent' => 'zen-studio', 'title' => 'Foglalások', 'href' => admin_url( 'edit.php?post_type=zen_booking' ) ) );
		$bar->add_node( array( 'id' => 'zen-cal', 'parent' => 'zen-studio', 'title' => 'Naptár & Booking/Airbnb', 'href' => admin_url( 'admin.php?page=zen-booking-calendar' ) ) );
	}

	public static function is_zen_screen() {
		$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $s ) {
			return false;
		}
		return false !== strpos( (string) $s->id, 'zen' ) || in_array( $s->post_type, array( 'zen_booking', 'zen_message', 'zen_feedback', 'zen_chat' ), true );
	}

	public static function assets( $hook ) {
		$v = ZEN_CORE_VER . '.' . ( file_exists( ZEN_CORE_DIR . 'admin/admin.css' ) ? filemtime( ZEN_CORE_DIR . 'admin/admin.css' ) : 0 );
		if ( self::is_zen_screen() || 'post.php' === $hook || 'post-new.php' === $hook ) {
			wp_enqueue_style( 'zen-admin', ZEN_CORE_URL . 'admin/admin.css', array(), $v );
		}
		if ( 'toplevel_page_zen-studio' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'zen-fields', ZEN_CORE_URL . 'admin/zen-fields.js', array(), $v, true );
		wp_enqueue_script( 'zen-studio', ZEN_CORE_URL . 'admin/studio.js', array( 'zen-fields' ), $v, true );
		wp_add_inline_script( 'zen-studio', 'window.ZenStudio=' . wp_json_encode( self::boot() ) . ';', 'before' );
	}

	/** A Studio indulóadatai. */
	public static function boot() {
		$settings = Zen_Settings::all();
		// API kulcsok: csak jelző, a valós érték nem megy ki a böngészőbe.
		$settings['ai']['api_key']           = '' !== (string) $settings['ai']['api_key'] ? '••••••••' : '';
		$settings['cloudflare']['api_token'] = '' !== (string) $settings['cloudflare']['api_token'] ? '••••••••' : '';
		unset( $settings['strings'] );
		$langs = array();
		foreach ( Zen_I18n::all_languages() as $c => $m ) {
			$langs[] = array( 'code' => $c, 'name' => $m['name'], 'native' => $m['native'], 'seed' => file_exists( ZEN_CORE_DIR . 'data/seed/' . $c . '.json' ) );
		}
		return array(
			'rest'       => esc_url_raw( rest_url( 'zen/v1/admin/' ) ),
			'restRoot'   => esc_url_raw( rest_url( 'zen/v1/' ) ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'ajax'       => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'  => wp_create_nonce( 'zen_admin' ),
			'settings'   => $settings,
			'apartments' => Zen_Settings::apartments(),
			'skins'      => Zen_Settings::skins(),
			'fonts'      => Zen_Settings::fonts(),
			'logoOpts'   => class_exists( 'Zen_Logos' ) ? Zen_Logos::options() : array(),
			'railStyles' => array_map( fn( $k, $v ) => array( $k, $v ), array_keys( self::rail_styles() ), array_values( self::rail_styles() ) ),
			'langs'      => $langs,
			'keys'       => Zen_Content::keys(),
			'home'       => home_url( '/' ),
			'calendar'   => admin_url( 'admin.php?page=zen-booking-calendar' ),
			'purgeUrl'   => wp_nonce_url( admin_url( 'admin-post.php?action=zen_cf_purge' ), 'zen_cf_purge' ),
			'guideTestUrl' => wp_nonce_url( admin_url( 'admin-post.php?action=zen_guide_test' ), 'zen_guide_test' ),
			'purgeLast'  => get_option( 'zen_purge_last', array() ),
			'icalUrls'   => self::ical_urls(),
			'defaultPrompt' => class_exists( 'Zen_AI' ) ? Zen_AI::default_prompt() : '',
			'kb'         => class_exists( 'Zen_AI' ) ? Zen_AI::kb() : array(),
			'eventsLast' => (int) get_option( 'zen_events_last', 0 ),
			'aiStatus'   => get_option( 'zen_ai_status', array() ),
			'eventsCount'=> count( (array) get_option( 'zen_live_events', array() ) ),
			'version'    => ZEN_CORE_VER,
		);
	}

	/** Oldalsó sáv stílusai – a téma adja, ha aktív; különben a beépített lista. */
	public static function rail_styles() {
		if ( function_exists( 'zc_rail_styles' ) ) {
			return zc_rail_styles();
		}
		return array( 'off' => 'Kikapcsolva', 'glass' => 'Üveg pill – bal oldal', 'glass-r' => 'Üveg pill – jobb oldal', 'dock' => 'Lebegő dokk – alul középen', 'minimal' => 'Minimál ikonsor vonallal', 'accent' => 'Kiemelő színű pill', 'dark' => 'Sötét pill', 'tab' => 'Fül a szélén (rámutatásra nyílik)', 'bubbles' => 'Különálló buborékok – jobb oldal', 'outline' => 'Körvonalas pill', 'gradient' => 'Színátmenetes pill' );
	}

	protected static function ical_urls() {
		$o = array();
		if ( class_exists( 'Zen_Booking' ) ) {
			foreach ( zen_apartments() as $a ) {
				$o[ $a['key'] ] = array( 'booking' => Zen_Booking::ical_url( $a['key'], 'booking' ), 'airbnb' => Zen_Booking::ical_url( $a['key'], 'airbnb' ) );
			}
		}
		return $o;
	}

	public static function page() {
		echo '<div class="wrap zs-app" id="zen-studio"><div class="zs-boot"><span></span><span></span><span></span></div></div>';
	}

	public static function notices() {
		if ( ! self::is_zen_screen() ) {
			return;
		}
		if ( defined( 'POLYLANG_VERSION' ) ) {
			echo '<div class="notice notice-warning"><p><b>Zen:</b> a Polylang aktív. A Zen saját, gyorsabb nyelvkezelője átvette a nyelveket (az eredeti URL-ek megmaradtak) – a Polylang kikapcsolása javasolt az ütközések elkerülésére.</p></div>';
		}
		if ( ! wp_get_theme()->get_template() || 'zen-central' !== get_template() ) {
			echo '<div class="notice notice-info"><p><b>Zen:</b> a teljes megjelenéshez aktiváld a <i>ZEN Central</i> témát (Megjelenés → Témák).</p></div>';
		}
	}

	/* ------------------------------------------------------------------
	 * REST
	 * ------------------------------------------------------------------ */
	public static function routes() {
		$perm = function () {
			return current_user_can( 'manage_options' );
		};
		register_rest_route( 'zen/v1', '/admin/settings', array(
			array( 'methods' => 'GET', 'permission_callback' => $perm, 'callback' => fn() => rest_ensure_response( self::boot() ) ),
			array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'save' ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/strings', array(
			array( 'methods' => 'GET', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'strings' ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/pages', array(
			array( 'methods' => 'GET', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'pages' ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/stats', array(
			array( 'methods' => 'GET', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'stats' ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/export', array(
			array( 'methods' => 'GET', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'export' ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/import', array(
			array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'import' ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/seed', array(
			array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'seed' ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/sideload', array(
			array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => fn() => rest_ensure_response( Zen_Importer::sideload_batch( 8 ) ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/ai-test', array(
			array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => function ( WP_REST_Request $r ) {
				return rest_ensure_response( class_exists( 'Zen_AI' ) ? Zen_AI::test( (string) $r->get_param( 'q' ) ) : array( 'ok' => false, 'error' => 'Zen AI nincs betöltve' ) );
			} ),
		) );
		register_rest_route( 'zen/v1', '/admin/events', array(
			array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => fn() => rest_ensure_response( array( 'status' => class_exists( 'Zen_AI' ) ? Zen_AI::sync_events() : array() ) ) ),
		) );
		register_rest_route( 'zen/v1', '/admin/sync', array(
			array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => function () {
				if ( class_exists( 'Zen_Booking' ) ) {
					delete_transient( 'zen_ical_lock' );
					Zen_Booking::sync_all();
				}
				return rest_ensure_response( array( 'status' => get_option( 'zen_ical_status', array() ), 'last' => (int) get_option( 'zen_ical_last', 0 ) ) );
			} ),
		) );
	}

	/** Mentés: settings (részleges), apartments, strings (nyelvenként). */
	public static function save( WP_REST_Request $req ) {
		$p = (array) $req->get_json_params();
		if ( isset( $p['settings'] ) && is_array( $p['settings'] ) ) {
			$s = self::sanitize_settings( $p['settings'] );
			// Titkok: '••••' = változatlan; új érték titkosítva.
			if ( isset( $s['ai']['api_key'] ) ) {
				if ( '••••••••' === $s['ai']['api_key'] ) {
					unset( $s['ai']['api_key'] );
				} elseif ( '' !== $s['ai']['api_key'] ) {
					$s['ai']['api_key'] = Zen_Security::encrypt( $s['ai']['api_key'] );
				}
			}
			if ( isset( $s['cloudflare']['api_token'] ) ) {
				if ( '••••••••' === $s['cloudflare']['api_token'] ) {
					unset( $s['cloudflare']['api_token'] );
				} elseif ( '' !== $s['cloudflare']['api_token'] ) {
					$s['cloudflare']['api_token'] = Zen_Security::encrypt( $s['cloudflare']['api_token'] );
				}
			}
			unset( $s['strings'] );
			Zen_Settings::update( $s );
		}
		if ( isset( $p['apartments'] ) && is_array( $p['apartments'] ) ) {
			$apts = array();
			foreach ( $p['apartments'] as $a ) {
				$a = self::sanitize_tree( $a );
				foreach ( array( 'cover', 'icon' ) as $mk ) {
					$a[ $mk ] = Zen_Settings::media( $a[ $mk ] ?? array() );
				}
				$a['gallery']    = array_values( array_map( array( 'Zen_Settings', 'media' ), (array) ( $a['gallery'] ?? array() ) ) );
				$a['ical_booking'] = esc_url_raw( $a['ical_booking'] ?? '' );
				$a['ical_airbnb']  = esc_url_raw( $a['ical_airbnb'] ?? '' );
				$a['ical_other']   = array_values( array_filter( array_map( 'esc_url_raw', (array) ( $a['ical_other'] ?? array() ) ) ) );
				$apts[] = $a;
			}
			Zen_Settings::save_apartments( $apts );
		}
		if ( isset( $p['strings'], $p['strings_lang'] ) && Zen_I18n::is_valid( $p['strings_lang'] ) ) {
			$clean = array();
			foreach ( (array) $p['strings'] as $k => $v ) {
				$clean[ sanitize_key( $k ) ] = sanitize_textarea_field( (string) $v );
			}
			$all = (array) Zen_Settings::get( 'strings', array() );
			$all[ $p['strings_lang'] ] = $clean;
			$opt = get_option( Zen_Settings::OPT, array() );
			$opt['strings'] = $all;
			update_option( Zen_Settings::OPT, $opt, true );
			Zen_Settings::flush();
			Zen_I18n::flush_strings();
			do_action( 'zen_settings_updated', $opt );
		}
		if ( isset( $p['kb'] ) ) {
			$kb = is_string( $p['kb'] ) ? json_decode( wp_unslash( $p['kb'] ), true ) : $p['kb'];
			if ( is_array( $kb ) ) {
				update_option( 'zen_kb', wp_json_encode( self::sanitize_tree( $kb ), JSON_UNESCAPED_UNICODE ), false );
			} elseif ( '' === $p['kb'] ) {
				delete_option( 'zen_kb' );
			}
		}
		return rest_ensure_response( array( 'ok' => true, 'data' => self::boot() ) );
	}

	protected static function sanitize_settings( $s ) {
		$s = self::sanitize_tree( $s );
		if ( isset( $s['design']['custom_css'] ) ) {
			$s['design']['custom_css'] = wp_strip_all_tags( (string) $s['design']['custom_css'] );
		}
		if ( isset( $s['ai']['system_prompt'] ) ) {
			$s['ai']['system_prompt'] = sanitize_textarea_field( (string) $s['ai']['system_prompt'] );
		}
		foreach ( array( 'logo', 'logo_dark', 'side_badge', 'payment_logo' ) as $mk ) {
			if ( isset( $s['general'][ $mk ] ) ) {
				$s['general'][ $mk ] = Zen_Settings::media( $s['general'][ $mk ] );
			}
		}
		if ( isset( $s['seo']['default_og'] ) ) {
			$s['seo']['default_og'] = Zen_Settings::media( $s['seo']['default_og'] );
		}
		if ( isset( $s['ai']['avatar'] ) ) {
			$s['ai']['avatar'] = Zen_Settings::media( $s['ai']['avatar'] );
		}
		if ( isset( $s['languages']['enabled'] ) ) {
			$s['languages']['enabled'] = array_values( array_intersect( array_map( 'sanitize_key', (array) $s['languages']['enabled'] ), array_keys( Zen_I18n::all_languages() ) ) );
		}
		return $s;
	}

	/** Rekurzív tisztítás típus szerint (bool/num megmarad, szöveg sanitize, url-ek esc). */
	public static function sanitize_tree( $v, $key = '' ) {
		if ( is_array( $v ) ) {
			$o = array();
			foreach ( $v as $k => $vv ) {
				$o[ is_int( $k ) ? $k : sanitize_key( $k ) ] = self::sanitize_tree( $vv, is_int( $k ) ? $key : (string) $k );
			}
			return $o;
		}
		if ( is_bool( $v ) || is_int( $v ) || is_float( $v ) || null === $v ) {
			return $v;
		}
		$v = (string) $v;
		if ( in_array( $key, array( 'url', 'poster', 'facebook', 'instagram', 'google_maps', 'payment_url', 'nhb_url', 'nhb_script', 'card_payment_url', 'google_review_url', 'booking_review_url' ), true ) ) {
			return esc_url_raw( $v );
		}
		if ( in_array( $key, array( 'kb_extra', 'bank_details', 'system_prompt', 'custom_css', 'short', 'note' ), true ) || false !== strpos( $v, "\n" ) ) {
			return sanitize_textarea_field( $v );
		}
		return sanitize_text_field( $v );
	}

	/** UI szövegek egy nyelvre: alap + felülírt. */
	public static function strings( WP_REST_Request $req ) {
		$lang = sanitize_key( (string) $req->get_param( 'lang' ) );
		$lang = Zen_I18n::is_valid( $lang ) || isset( Zen_I18n::all_languages()[ $lang ] ) ? $lang : 'hu';
		Zen_I18n::flush_strings();
		$over = (array) Zen_Settings::get( 'strings.' . $lang, array() );
		$base = Zen_I18n::strings( $lang, false );
		$hu   = Zen_I18n::strings( 'hu', false );
		ksort( $base );
		return rest_ensure_response( array( 'lang' => $lang, 'base' => $base, 'hu' => $hu, 'over' => $over ) );
	}

	public static function pages() {
		$rows = array();
		foreach ( Zen_Content::pages() as $p ) {
			$rows[] = $p;
		}
		return rest_ensure_response( array( 'pages' => $rows, 'live' => array_keys( Zen_I18n::live() ), 'report' => get_option( 'zen_import_report', array() ) ) );
	}

	public static function stats() {
		$pending  = get_posts( array( 'post_type' => 'zen_booking', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_status', 'meta_value' => 'pending' ) ); // phpcs:ignore
		$upcoming = get_posts(
			array(
				'post_type'   => 'zen_booking',
				'numberposts' => 8,
				'meta_key'    => '_from', // phpcs:ignore
				'orderby'     => 'meta_value',
				'order'       => 'ASC',
				'meta_query'  => array( // phpcs:ignore
					array( 'key' => '_from', 'value' => wp_date( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
					array( 'key' => '_status', 'value' => array( 'pending', 'confirmed' ), 'compare' => 'IN' ),
				),
			)
		);
		$up = array();
		foreach ( $upcoming as $b ) {
			$g    = (array) get_post_meta( $b->ID, '_guest', true );
			$up[] = array(
				'ref'    => get_post_meta( $b->ID, '_ref', true ),
				'from'   => get_post_meta( $b->ID, '_from', true ),
				'to'     => get_post_meta( $b->ID, '_to', true ),
				'name'   => $g['name'] ?? '',
				'status' => get_post_meta( $b->ID, '_status', true ),
				'apts'   => implode( ', ', array_map( fn( $i ) => zen_apt_name( $i['apt'] ?? '', 'hu' ), (array) get_post_meta( $b->ID, '_items', true ) ) ),
				'edit'   => get_edit_post_link( $b->ID, 'raw' ),
			);
		}
		$week = array( 'after' => '7 days ago' );
		$msgs = get_posts( array( 'post_type' => 'zen_message', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'date_query' => array( $week ) ) );
		$chat = get_posts( array( 'post_type' => 'zen_chat', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'date_query' => array( $week ) ) );
		$fb   = class_exists( 'Zen_Forms' ) ? Zen_Forms::stats() : array( 'count' => 0, 'avg' => 0 );
		$fbp  = get_posts( array( 'post_type' => 'zen_feedback', 'post_status' => 'pending', 'numberposts' => -1, 'fields' => 'ids' ) );
		$env  = array(
			'rank_math' => Zen_SEO::rank_math(),
			'site_kit'  => Zen_SEO::site_kit(),
			'polylang'  => defined( 'POLYLANG_VERSION' ),
			'elementor' => defined( 'ELEMENTOR_VERSION' ),
			'theme'     => get_template(),
			'php'       => PHP_VERSION,
			'wp'        => get_bloginfo( 'version' ),
			'permalink' => (bool) get_option( 'permalink_structure' ),
			'cron'      => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'ai_key'    => '' !== (string) Zen_Settings::get( 'ai.api_key', '' ),
			'cloudflare'=> isset( $_SERVER['HTTP_CF_RAY'] ) || isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ),
		);
		return rest_ensure_response(
			array(
				'pending'   => count( $pending ),
				'upcoming'  => $up,
				'messages7' => count( $msgs ),
				'chats7'    => count( $chat ),
				'feedback'  => $fb,
				'fbPending' => count( $fbp ),
				'icalLast'  => (int) get_option( 'zen_ical_last', 0 ),
				'icalStatus'=> get_option( 'zen_ical_status', array() ),
				'env'       => $env,
				'media'     => count( (array) get_option( 'zen_media_map', array() ) ),
			)
		);
	}

	public static function export() {
		$pages = array();
		foreach ( Zen_Content::pages() as $p ) {
			$pages[] = array( 'key' => $p['key'], 'lang' => $p['lang'], 'title' => $p['title'], 'sections' => Zen_Content::get_sections( $p['id'] ), 'seo_title' => get_post_meta( $p['id'], '_zen_seo_title', true ), 'seo_desc' => get_post_meta( $p['id'], '_zen_seo_desc', true ) );
		}
		$s = get_option( Zen_Settings::OPT, array() );
		unset( $s['ai']['api_key'], $s['cloudflare']['api_token'] );
		return rest_ensure_response(
			array(
				'zen_export' => 1,
				'version'    => ZEN_CORE_VER,
				'site'       => home_url( '/' ),
				'date'       => gmdate( 'c' ),
				'settings'   => $s,
				'apartments' => Zen_Settings::apartments(),
				'kb'         => get_option( 'zen_kb', '' ),
				'pages'      => $pages,
			)
		);
	}

	public static function import( WP_REST_Request $req ) {
		$p = (array) $req->get_json_params();
		if ( empty( $p['zen_export'] ) ) {
			return new WP_Error( 'zen_bad', 'Érvénytelen export fájl.', array( 'status' => 400 ) );
		}
		if ( ! empty( $p['settings'] ) ) {
			$cur = get_option( Zen_Settings::OPT, array() );
			$new = self::sanitize_settings( (array) $p['settings'] );
			if ( isset( $cur['ai']['api_key'] ) ) {
				$new['ai']['api_key'] = $cur['ai']['api_key'];
			}
			if ( isset( $cur['cloudflare']['api_token'] ) ) {
				$new['cloudflare']['api_token'] = $cur['cloudflare']['api_token'];
			}
			update_option( Zen_Settings::OPT, $new, true );
			Zen_Settings::flush();
		}
		if ( ! empty( $p['apartments'] ) ) {
			Zen_Settings::save_apartments( self::sanitize_tree( (array) $p['apartments'] ) );
		}
		if ( isset( $p['kb'] ) && $p['kb'] ) {
			update_option( 'zen_kb', is_string( $p['kb'] ) ? $p['kb'] : wp_json_encode( $p['kb'] ), false );
		}
		$n = 0;
		foreach ( (array) ( $p['pages'] ?? array() ) as $pg ) {
			$id = Zen_Content::page_id( sanitize_key( $pg['key'] ?? '' ), sanitize_key( $pg['lang'] ?? '' ) );
			if ( $id && isset( $pg['sections'] ) ) {
				Zen_Content::save_sections( $id, (array) $pg['sections'] );
				$n++;
			}
		}
		do_action( 'zen_settings_updated', get_option( Zen_Settings::OPT ) );
		return rest_ensure_response( array( 'ok' => true, 'pages' => $n ) );
	}

	public static function seed( WP_REST_Request $req ) {
		$p = (array) $req->get_json_params();
		return rest_ensure_response( Zen_Importer::run( array( 'force' => ! empty( $p['force'] ), 'lang' => sanitize_key( $p['lang'] ?? '' ) ) ) );
	}
}
