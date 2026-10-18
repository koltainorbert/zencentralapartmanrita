<?php
/**
 * Zen Booking – saját foglalómotor (NetHotelBooking nélkül).
 * Kétirányú iCal szinkron: Booking.com + Airbnb (+ bármely további iCal) → import 15 percenként és
 * minden foglalás előtt; export csatornánként (a saját foglalások + a többi csatorna foglalásai),
 * így a weboldal a „hub”: a Booking látja az Airbnb foglalásait és fordítva.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Booking {

	const CPT        = 'zen_booking';
	const CACHE      = 'zen_ical_cache';
	const STATUS_OPT = 'zen_ical_status';

	/** @var array kérésenkénti gyorsítótár */
	protected static $occ = array();

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ), 20 );
		add_action( 'zen_ical_sync', array( __CLASS__, 'sync_all' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'template_redirect', array( __CLASS__, 'ical_export' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'mail_action' ), 0 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 20 );
			add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
			add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_admin' ), 10, 2 );
			add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
			add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
			add_action( 'admin_post_zen_booking_status', array( __CLASS__, 'admin_status' ) );
			add_action( 'admin_post_zen_ical_sync_now', array( __CLASS__, 'admin_sync_now' ) );
			add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
			add_action( 'restrict_manage_posts', array( __CLASS__, 'status_filter' ) );
			add_action( 'pre_get_posts', array( __CLASS__, 'status_filter_query' ) );
		}
	}

	/* ==================================================================
	 * Regisztráció, ütemezés
	 * ================================================================== */

	public static function cron_schedules( $s ) {
		$min = max( 5, (int) Zen_Settings::get( 'booking.sync_minutes', 15 ) );
		$s['zen_sync'] = array(
			'interval' => $min * MINUTE_IN_SECONDS,
			'display'  => 'Zen iCal szinkron (' . $min . ' perc)',
		);
		return $s;
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( 'zen_ical_sync' ) ) {
			wp_schedule_event( time() + 60, 'zen_sync', 'zen_ical_sync' );
		}
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'          => 'Foglalások',
					'singular_name' => 'Foglalás',
					'add_new'       => 'Új foglalás',
					'add_new_item'  => 'Új foglalás rögzítése (telefonos / személyes)',
					'edit_item'     => 'Foglalás',
					'search_items'  => 'Foglalás keresése',
					'not_found'     => 'Nincs foglalás',
					'menu_name'     => 'Foglalások',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'zen-studio',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'menu_icon'       => 'dashicons-calendar-alt',
			)
		);
	}

	/* ==================================================================
	 * Elérhetőség
	 * ================================================================== */

	public static function today() {
		return wp_date( 'Y-m-d' );
	}

	public static function add_days( $date, $n ) {
		return gmdate( 'Y-m-d', strtotime( $date . ' ' . ( $n >= 0 ? '+' : '' ) . (int) $n . ' days UTC' ) );
	}

	public static function valid_date( $d ) {
		return is_string( $d ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) && strtotime( $d . ' UTC' );
	}

	public static function nights_between( $from, $to ) {
		return (int) round( ( strtotime( $to . ' UTC' ) - strtotime( $from . ' UTC' ) ) / DAY_IN_SECONDS );
	}

	/**
	 * Foglalt tartományok egy apartmanhoz: [[from, to(kizárt), source, label]].
	 * source: zen (saját), block (kézi zárás), booking, airbnb, ical (egyéb).
	 */
	public static function occupied( $apt, $exclude_post = 0 ) {
		$key = $apt . '|' . $exclude_post;
		if ( isset( self::$occ[ $key ] ) ) {
			return self::$occ[ $key ];
		}
		$out = array();
		// Saját foglalások.
		$q = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => array( 'publish', 'private', 'draft', 'pending' ),
				'posts_per_page' => 500,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array( 'key' => '_status', 'value' => array( 'pending', 'confirmed' ), 'compare' => 'IN' ),
					array( 'key' => '_to', 'value' => self::add_days( self::today(), -1 ), 'compare' => '>=', 'type' => 'DATE' ),
				),
			)
		);
		foreach ( $q->posts as $pid ) {
			if ( (int) $pid === (int) $exclude_post ) {
				continue;
			}
			foreach ( (array) get_post_meta( $pid, '_items', true ) as $it ) {
				if ( ( $it['apt'] ?? '' ) === $apt ) {
					$out[] = array( $it['from'], $it['to'], 'zen', get_post_meta( $pid, '_ref', true ) );
				}
			}
		}
		// Kézi zárások.
		foreach ( (array) Zen_Settings::get( 'booking.blocked', array() ) as $b ) {
			if ( ( empty( $b['apt'] ) || 'all' === $b['apt'] || $b['apt'] === $apt ) && self::valid_date( $b['from'] ?? '' ) && self::valid_date( $b['to'] ?? '' ) ) {
				$to    = $b['to'] > $b['from'] ? $b['to'] : self::add_days( $b['from'], 1 );
				$out[] = array( $b['from'], $to, 'block', $b['note'] ?? '' );
			}
		}
		// Importált csatornák.
		$cache = get_option( self::CACHE, array() );
		foreach ( (array) ( $cache[ $apt ] ?? array() ) as $e ) {
			$out[] = array( $e[0], $e[1], $e[3] ?? 'ical', $e[2] ?? '' );
		}
		self::$occ[ $key ] = $out;
		return $out;
	}

	public static function flush_cache() {
		self::$occ = array();
	}

	/** Foglalt-e az adott éjszaka (date → date+1). */
	public static function night_taken( $apt, $date, $exclude_post = 0 ) {
		foreach ( self::occupied( $apt, $exclude_post ) as $r ) {
			if ( $date >= $r[0] && $date < $r[1] ) {
				return true;
			}
		}
		return false;
	}

	/** Szabad-e a teljes tartomány. */
	public static function is_free( $apt, $from, $to, $exclude_post = 0 ) {
		for ( $d = $from; $d < $to; $d = self::add_days( $d, 1 ) ) {
			if ( self::night_taken( $apt, $d, $exclude_post ) ) {
				return false;
			}
		}
		return true;
	}

	/** Napi állapot egy apartmanra. */
	public static function day_state( $apt, $date ) {
		$today = self::today();
		$max   = self::add_days( $today, (int) Zen_Settings::get( 'booking.max_advance', 540 ) );
		if ( $date < $today || $date > $max ) {
			return 'closed';
		}
		$taken = self::night_taken( $apt, $date );
		if ( ! $taken ) {
			return 'free';
		}
		// Az előző éjszaka szabad → ezen a napon csak távozni lehet.
		return self::night_taken( $apt, self::add_days( $date, -1 ) ) ? 'booked' : 'checkout';
	}

	/** Elérhetőségi naptár. */
	public static function availability( $from, $to ) {
		$apts = zen_apartments();
		$days = array();
		for ( $d = $from; $d <= $to; $d = self::add_days( $d, 1 ) ) {
			$row = array();
			$any = 'booked';
			$closed = 0;
			foreach ( $apts as $a ) {
				$st             = self::day_state( $a['key'], $d );
				$row[ $a['key'] ] = $st;
				if ( 'free' === $st ) {
					$any = 'free';
				} elseif ( 'checkout' === $st && 'free' !== $any ) {
					$any = 'checkout';
				} elseif ( 'closed' === $st ) {
					$closed++;
				}
			}
			if ( $closed === count( $apts ) ) {
				$any = 'closed';
			}
			$row['any']  = $any;
			$days[ $d ] = $row;
		}
		$events = array();
		foreach ( (array) Zen_Settings::get( 'booking.events', array() ) as $e ) {
			if ( self::valid_date( $e['from'] ?? '' ) ) {
				$events[] = array( 'name' => (string) ( $e['name'] ?? '' ), 'from' => $e['from'], 'to' => self::valid_date( $e['to'] ?? '' ) ? $e['to'] : $e['from'] );
			}
		}
		return array(
			'days'      => $days,
			'events'    => $events,
			'minNights' => (int) Zen_Settings::get( 'booking.min_nights', 1 ),
			'synced'    => (int) get_option( 'zen_ical_last', 0 ),
		);
	}

	/* ==================================================================
	 * Árazás
	 * ================================================================== */

	public static function night_price( $apt, $date ) {
		foreach ( (array) Zen_Settings::get( 'booking.seasons', array() ) as $s ) {
			if ( self::valid_date( $s['from'] ?? '' ) && self::valid_date( $s['to'] ?? '' ) && $date >= $s['from'] && $date <= $s['to'] ) {
				$p = (float) ( $s['prices'][ $apt ] ?? 0 );
				if ( $p > 0 ) {
					return $p;
				}
			}
		}
		$a = zen_apartment( $apt );
		if ( ! $a ) {
			return 0;
		}
		$dow = (int) gmdate( 'N', strtotime( $date . ' UTC' ) ); // 5 = péntek, 6 = szombat éjszaka
		if ( in_array( $dow, array( 5, 6 ), true ) && (float) ( $a['price_weekend'] ?? 0 ) > 0 ) {
			return (float) $a['price_weekend'];
		}
		return (float) ( $a['price'] ?? 0 );
	}

	public static function min_nights( $apt, $from ) {
		$min = max( 1, (int) Zen_Settings::get( 'booking.min_nights', 1 ) );
		$a   = zen_apartment( $apt );
		if ( $a ) {
			$min = max( $min, (int) ( $a['min_nights'] ?? 1 ) );
		}
		foreach ( (array) Zen_Settings::get( 'booking.seasons', array() ) as $s ) {
			if ( self::valid_date( $s['from'] ?? '' ) && $from >= $s['from'] && $from <= ( $s['to'] ?? $s['from'] ) ) {
				$min = max( $min, (int) ( $s['min_nights'] ?? 1 ) );
			}
		}
		return $min;
	}

	/** Árajánlat számítás. */
	public static function quote( $items ) {
		$out   = array( 'items' => array(), 'total' => 0, 'quoteMode' => false, 'currency' => Zen_Settings::get( 'booking.currency', 'HUF' ) );
		$clean = (float) Zen_Settings::get( 'booking.cleaning_fee', 0 );
		foreach ( (array) $items as $it ) {
			$apt  = sanitize_key( $it['apt'] ?? '' );
			$from = $it['from'] ?? '';
			$to   = $it['to'] ?? '';
			if ( ! zen_apartment( $apt ) || ! self::valid_date( $from ) || ! self::valid_date( $to ) || $to <= $from ) {
				continue;
			}
			$per   = array();
			$total = 0;
			for ( $d = $from; $d < $to; $d = self::add_days( $d, 1 ) ) {
				$p     = self::night_price( $apt, $d );
				$per[] = $p;
				$total += $p;
				if ( $p <= 0 ) {
					$out['quoteMode'] = true;
				}
			}
			if ( $clean > 0 ) {
				$total += $clean;
			}
			$out['items'][] = array(
				'apt'      => $apt,
				'from'     => $from,
				'to'       => $to,
				'nights'   => count( $per ),
				'perNight' => $per,
				'cleaning' => $clean,
				'total'    => $total,
				'free'     => self::is_free( $apt, $from, $to ),
				'minNights'=> self::min_nights( $apt, $from ),
				'adults'   => max( 1, (int) ( $it['adults'] ?? 1 ) ),
				'children' => max( 0, (int) ( $it['children'] ?? 0 ) ),
			);
			$out['total'] += $total;
		}
		$pct            = (int) Zen_Settings::get( 'general.deposit_pct', 50 );
		$out['deposit'] = $out['quoteMode'] ? 0 : round( $out['total'] * $pct / 100 );
		$out['depositPct'] = $pct;
		$out['tax']     = (int) Zen_Settings::get( 'general.tourist_tax', 500 );
		if ( $out['quoteMode'] ) {
			$out['total'] = 0;
		}
		return $out;
	}

	/* ==================================================================
	 * REST
	 * ================================================================== */

	public static function routes() {
		register_rest_route(
			'zen/v1',
			'/availability',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_availability' ),
			)
		);
		register_rest_route(
			'zen/v1',
			'/quote',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_quote' ),
			)
		);
		register_rest_route(
			'zen/v1',
			'/booking',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_booking' ),
			)
		);
	}

	protected static function nocache( $data, $status = 200 ) {
		$r = new WP_REST_Response( $data, $status );
		$r->header( 'Cache-Control', 'no-store, max-age=0' );
		return $r;
	}

	public static function rest_availability( WP_REST_Request $req ) {
		$from = $req->get_param( 'from' );
		$to   = $req->get_param( 'to' );
		$from = self::valid_date( $from ) ? $from : self::today();
		$to   = self::valid_date( $to ) ? $to : self::add_days( $from, 120 );
		if ( self::nights_between( $from, $to ) > 400 ) {
			$to = self::add_days( $from, 400 );
		}
		self::maybe_resync( 30 );
		return self::nocache( self::availability( $from, $to ) );
	}

	public static function rest_quote( WP_REST_Request $req ) {
		$p = (array) $req->get_json_params();
		if ( ! Zen_Security::rate_ok( 'quote', 120, 600 ) ) {
			return self::nocache( array( 'error' => 'rate' ), 429 );
		}
		return self::nocache( self::quote( $p['items'] ?? array() ) );
	}

	public static function rest_booking( WP_REST_Request $req ) {
		$p   = (array) $req->get_json_params();
		$sec = Zen_Security::check( $p, 'booking', 6, 3600, 4 );
		if ( is_wp_error( $sec ) ) {
			return self::nocache( array( 'ok' => false, 'error' => $sec->get_error_code() ), 400 );
		}
		$lang = Zen_I18n::is_valid( $p['lang'] ?? '' ) ? $p['lang'] : Zen_I18n::default_lang();
		$g    = (array) ( $p['guest'] ?? array() );
		$name  = sanitize_text_field( $g['name'] ?? '' );
		$email = sanitize_email( $g['email'] ?? '' );
		$phone = sanitize_text_field( $g['phone'] ?? '' );
		if ( ! $name || ! is_email( $email ) || ! $phone ) {
			return self::nocache( array( 'ok' => false, 'error' => 'required', 'message' => zen_t( 'bk_err_required', $lang ) ), 422 );
		}
		if ( empty( $p['gdpr'] ) ) {
			return self::nocache( array( 'ok' => false, 'error' => 'gdpr', 'message' => zen_t( 'bk_err_gdpr', $lang ) ), 422 );
		}

		// Friss csatorna-szinkron foglalás előtt (dupla foglalás ellen).
		self::maybe_resync( max( 1, (int) Zen_Settings::get( 'booking.resync_before_booking', 5 ) ) * 60, true );
		self::flush_cache();

		$q = self::quote( $p['items'] ?? array() );
		if ( empty( $q['items'] ) ) {
			return self::nocache( array( 'ok' => false, 'error' => 'dates', 'message' => zen_t( 'bk_err_dates', $lang ) ), 422 );
		}
		$max_rooms = max( 1, (int) Zen_Settings::get( 'booking.max_rooms', 3 ) );
		if ( count( $q['items'] ) > $max_rooms ) {
			$q['items'] = array_slice( $q['items'], 0, $max_rooms );
		}
		foreach ( $q['items'] as $it ) {
			$a = zen_apartment( $it['apt'] );
			if ( ! $it['free'] ) {
				return self::nocache( array( 'ok' => false, 'error' => 'taken', 'message' => zen_t( 'bk_err_taken', $lang ) ), 409 );
			}
			if ( $it['nights'] < $it['minNights'] ) {
				return self::nocache( array( 'ok' => false, 'error' => 'min', 'message' => zen_t( 'bk_err_min', $lang, array( 'n' => $it['minNights'] ) ) ), 422 );
			}
			if ( ( $it['adults'] + $it['children'] ) > (int) $a['capacity'] ) {
				return self::nocache( array( 'ok' => false, 'error' => 'capacity', 'message' => zen_t( 'bk_too_small', $lang, array( 'n' => $a['capacity'] ) ) ), 422 );
			}
		}

		$ref = self::new_ref();
		$all_feeds = true;
		foreach ( $q['items'] as $it ) {
			$a = zen_apartment( $it['apt'] );
			if ( empty( $a['ical_booking'] ) && empty( $a['ical_airbnb'] ) && empty( $a['ical_other'] ) ) {
				$all_feeds = false;
			}
		}
		$status = ( Zen_Settings::get( 'booking.auto_confirm', false ) && $all_feeds && ! $q['quoteMode'] ) ? 'confirmed' : 'pending';

		$from = min( wp_list_pluck( $q['items'], 'from' ) );
		$to   = max( wp_list_pluck( $q['items'], 'to' ) );
		$pid  = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => $ref . ' – ' . $name . ' (' . $from . ' → ' . $to . ')',
			),
			true
		);
		if ( is_wp_error( $pid ) ) {
			return self::nocache( array( 'ok' => false, 'error' => 'db', 'message' => zen_t( 'bk_err_generic', $lang ) ), 500 );
		}
		$extras   = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $p['extras'] ?? array() ) ), wp_list_pluck( (array) Zen_Settings::get( 'booking.extras', array() ), 'key' ) ) );
		$payments = (array) Zen_Settings::get( 'booking.payments', array( 'transfer' ) );
		$payment  = in_array( $p['payment'] ?? '', $payments, true ) ? $p['payment'] : ( $payments[0] ?? 'transfer' );
		$guest    = array(
			'name'         => $name,
			'email'        => $email,
			'phone'        => $phone,
			'country'      => sanitize_text_field( $g['country'] ?? '' ),
			'arrival_time' => sanitize_text_field( $g['arrival_time'] ?? '' ),
			'message'      => sanitize_textarea_field( $g['message'] ?? '' ),
			'newsletter'   => ! empty( $p['newsletter'] ),
		);
		update_post_meta( $pid, '_ref', $ref );
		update_post_meta( $pid, '_status', $status );
		update_post_meta( $pid, '_items', $q['items'] );
		update_post_meta( $pid, '_from', $from );
		update_post_meta( $pid, '_to', $to );
		update_post_meta( $pid, '_guest', $guest );
		update_post_meta( $pid, '_extras', $extras );
		update_post_meta( $pid, '_payment', $payment );
		update_post_meta( $pid, '_lang', $lang );
		update_post_meta( $pid, '_total', $q['total'] );
		update_post_meta( $pid, '_deposit', $q['deposit'] );
		update_post_meta( $pid, '_quote_mode', $q['quoteMode'] ? 1 : 0 );
		update_post_meta( $pid, '_source', 'web' );
		update_post_meta( $pid, '_akey', wp_generate_password( 24, false ) );
		update_post_meta( $pid, '_created_ip', wp_hash( Zen_Security::ip() ) );
		self::flush_cache();

		if ( $guest['newsletter'] && class_exists( 'Zen_Forms' ) && method_exists( 'Zen_Forms', 'subscribe' ) ) {
			Zen_Forms::subscribe( $name, $email, $lang, 'booking' );
		}

		self::mail_admin( $pid );
		self::mail_guest( $pid, 'confirmed' === $status ? 'confirm' : 'received' );
		do_action( 'zen_booking_created', $pid, $status );

		return self::nocache(
			array(
				'ok'      => true,
				'ref'     => $ref,
				'status'  => $status,
				'message' => zen_t( 'confirmed' === $status ? 'bk_done_instant' : 'bk_done_text', $lang, array( 'ref' => $ref ) ),
			)
		);
	}

	protected static function new_ref() {
		$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		do {
			$code = 'ZC-' . wp_date( 'ym' ) . '-';
			for ( $i = 0; $i < 4; $i++ ) {
				$code .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
			}
			$exists = get_posts( array( 'post_type' => self::CPT, 'meta_key' => '_ref', 'meta_value' => $code, 'fields' => 'ids', 'numberposts' => 1, 'post_status' => 'any' ) ); // phpcs:ignore
		} while ( $exists );
		return $code;
	}

	/* ==================================================================
	 * iCal import (Booking.com, Airbnb, egyéb)
	 * ================================================================== */

	public static function feeds( $a ) {
		$f = array();
		if ( ! empty( $a['ical_booking'] ) ) {
			$f['booking'] = $a['ical_booking'];
		}
		if ( ! empty( $a['ical_airbnb'] ) ) {
			$f['airbnb'] = $a['ical_airbnb'];
		}
		foreach ( (array) ( $a['ical_other'] ?? array() ) as $i => $u ) {
			if ( $u ) {
				$f[ 'ical' . ( $i ? $i : '' ) ] = $u;
			}
		}
		foreach ( (array) ( $a['ical_in'] ?? array() ) as $i => $u ) { // régi mező kompatibilitás
			if ( $u ) {
				$f[ 'ical_in' . $i ] = $u;
			}
		}
		return $f;
	}

	/** Csak akkor szinkronizál, ha a gyorsítótár régebbi mint $max_age mp. */
	public static function maybe_resync( $max_age, $force_if_stale = false ) {
		$last = (int) get_option( 'zen_ical_last', 0 );
		if ( time() - $last > $max_age ) {
			$has = false;
			foreach ( zen_apartments() as $a ) {
				if ( self::feeds( $a ) ) {
					$has = true;
					break;
				}
			}
			if ( $has && ( $force_if_stale || ! get_transient( 'zen_ical_lock' ) ) ) {
				self::sync_all();
			}
		}
	}

	public static function sync_all() {
		if ( get_transient( 'zen_ical_lock' ) ) {
			return;
		}
		set_transient( 'zen_ical_lock', 1, 60 );
		$cache  = array();
		$status = get_option( self::STATUS_OPT, array() );
		$status = is_array( $status ) ? $status : array();
		$old    = get_option( self::CACHE, array() );
		foreach ( zen_apartments() as $a ) {
			$apt           = $a['key'];
			$cache[ $apt ] = array();
			foreach ( self::feeds( $a ) as $src => $url ) {
				$res = wp_remote_get(
					$url,
					array(
						'timeout'    => 15,
						'user-agent' => 'ZenCentral-iCal/1.0 (+' . home_url( '/' ) . ')',
						'headers'    => array( 'Accept' => 'text/calendar, */*' ),
					)
				);
				$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
				$body = is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_body( $res );
				if ( 200 !== $code || false === strpos( $body, 'BEGIN:VCALENDAR' ) ) {
					// Hiba esetén megtartjuk a korábbi adatot (biztonság: inkább foglalt maradjon).
					foreach ( (array) ( $old[ $apt ] ?? array() ) as $e ) {
						if ( ( $e[3] ?? '' ) === self::src_type( $src ) ) {
							$cache[ $apt ][] = $e;
						}
					}
					$status[ $apt ][ $src ] = array( 'time' => time(), 'ok' => false, 'count' => 0, 'error' => is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . $code );
					continue;
				}
				$events = self::parse_ical( $body );
				foreach ( $events as $e ) {
					$cache[ $apt ][] = array( $e['from'], $e['to'], $e['summary'], self::src_type( $src ) );
				}
				$status[ $apt ][ $src ] = array( 'time' => time(), 'ok' => true, 'count' => count( $events ), 'error' => '' );
			}
		}
		update_option( self::CACHE, $cache, false );
		update_option( self::STATUS_OPT, $status, false );
		update_option( 'zen_ical_last', time(), false );
		self::flush_cache();
		delete_transient( 'zen_ical_lock' );
		do_action( 'zen_ical_synced', $cache );
	}

	protected static function src_type( $src ) {
		if ( 0 === strpos( $src, 'booking' ) ) {
			return 'booking';
		}
		if ( 0 === strpos( $src, 'airbnb' ) ) {
			return 'airbnb';
		}
		return 'ical';
	}

	/** Egyszerű, robusztus iCal (RFC 5545) VEVENT parser – DATE és DATE-TIME. */
	public static function parse_ical( $text ) {
		$text  = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		$text  = preg_replace( "/\n[ \t]/", '', $text ); // sor-hajtogatás feloldása
		$lines = explode( "\n", $text );
		$out   = array();
		$ev    = null;
		$today = self::add_days( self::today(), -1 );
		foreach ( $lines as $ln ) {
			$ln = trim( $ln );
			if ( 'BEGIN:VEVENT' === $ln ) {
				$ev = array();
				continue;
			}
			if ( 'END:VEVENT' === $ln ) {
				if ( ! empty( $ev['from'] ) ) {
					$to = ! empty( $ev['to'] ) ? $ev['to'] : self::add_days( $ev['from'], 1 );
					if ( $to <= $ev['from'] ) {
						$to = self::add_days( $ev['from'], 1 );
					}
					if ( $to >= $today && empty( $ev['cancelled'] ) ) {
						$out[] = array( 'from' => $ev['from'], 'to' => $to, 'summary' => mb_substr( (string) ( $ev['summary'] ?? '' ), 0, 80 ) );
					}
				}
				$ev = null;
				continue;
			}
			if ( null === $ev || false === strpos( $ln, ':' ) ) {
				continue;
			}
			list( $k, $v ) = explode( ':', $ln, 2 );
			$prop          = strtoupper( strtok( $k, ';' ) );
			if ( 'DTSTART' === $prop || 'DTEND' === $prop ) {
				$date = self::ical_date( $v, $k );
				if ( $date ) {
					$ev[ 'DTSTART' === $prop ? 'from' : 'to' ] = $date;
				}
			} elseif ( 'SUMMARY' === $prop ) {
				$ev['summary'] = str_replace( array( '\\,', '\\;', '\\n' ), array( ',', ';', ' ' ), $v );
			} elseif ( 'STATUS' === $prop && 'CANCELLED' === strtoupper( trim( $v ) ) ) {
				$ev['cancelled'] = true;
			}
		}
		return $out;
	}

	protected static function ical_date( $v, $params ) {
		$v = trim( $v );
		if ( preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $v, $m ) ) {
			return "$m[1]-$m[2]-$m[3]";
		}
		if ( preg_match( '/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})(Z?)$/', $v, $m ) ) {
			$tz = 'UTC';
			if ( ! $m[7] && preg_match( '/TZID=([^;:]+)/', $params, $t ) ) {
				$tz = $t[1];
			} elseif ( ! $m[7] ) {
				$tz = wp_timezone_string();
			}
			try {
				$dt = new DateTime( "$m[1]-$m[2]-$m[3] $m[4]:$m[5]:$m[6]", new DateTimeZone( $tz ) );
				$dt->setTimezone( wp_timezone() );
				return $dt->format( 'Y-m-d' );
			} catch ( Exception $e ) {
				return "$m[1]-$m[2]-$m[3]";
			}
		}
		return null;
	}

	/* ==================================================================
	 * iCal export – csatornánként (?for=booking|airbnb|all)
	 * ================================================================== */

	public static function ical_url( $apt, $for = 'all' ) {
		$a = zen_apartment( $apt );
		if ( ! $a ) {
			return '';
		}
		if ( empty( $a['ical_token'] ) ) {
			$apts = zen_apartments();
			foreach ( $apts as &$x ) {
				if ( $x['key'] === $apt ) {
					$x['ical_token'] = wp_generate_password( 20, false );
					$a = $x;
				}
			}
			unset( $x );
			Zen_Settings::save_apartments( $apts );
		}
		return add_query_arg( array( 'zen_ical' => $apt, 't' => $a['ical_token'], 'for' => $for ), home_url( '/' ) );
	}

	public static function ical_export() {
		if ( empty( $_GET['zen_ical'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$apt = sanitize_key( wp_unslash( $_GET['zen_ical'] ) ); // phpcs:ignore
		$tok = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore
		$for = isset( $_GET['for'] ) ? sanitize_key( wp_unslash( $_GET['for'] ) ) : 'all'; // phpcs:ignore
		$a   = zen_apartment( $apt );
		if ( ! $a || empty( $a['ical_token'] ) || ! hash_equals( (string) $a['ical_token'], $tok ) ) {
			status_header( 404 );
			exit;
		}
		$host  = wp_parse_url( home_url(), PHP_URL_HOST );
		$name  = Zen_I18n::pick( $a['name'] ?? array(), 'en' );
		$lines = array( 'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//ZEN Central Apartments//Zen Booking 1.0//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:' . self::esc_ical( $name ) );
		$stamp = gmdate( 'Ymd\THis\Z' );
		foreach ( self::occupied( $apt ) as $i => $r ) {
			// Csatorna ne kapja vissza a saját foglalásait (nincs visszhang).
			if ( 'all' !== $for && $r[2] === $for ) {
				continue;
			}
			$lines[] = 'BEGIN:VEVENT';
			$lines[] = 'UID:' . md5( $apt . $r[0] . $r[1] . $r[2] . $i ) . '@' . $host;
			$lines[] = 'DTSTAMP:' . $stamp;
			$lines[] = 'DTSTART;VALUE=DATE:' . str_replace( '-', '', $r[0] );
			$lines[] = 'DTEND;VALUE=DATE:' . str_replace( '-', '', $r[1] );
			$lines[] = 'SUMMARY:' . ( 'zen' === $r[2] ? 'Reserved (website)' : ( 'block' === $r[2] ? 'Not available' : 'Reserved (' . $r[2] . ')' ) );
			$lines[] = 'END:VEVENT';
		}
		$lines[] = 'END:VCALENDAR';
		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename="' . $apt . '.ics"' );
		echo implode( "\r\n", $lines ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	protected static function esc_ical( $s ) {
		return str_replace( array( '\\', ',', ';', "\n" ), array( '\\\\', '\\,', '\\;', '\\n' ), (string) $s );
	}

	/* ==================================================================
	 * E-mailek
	 * ================================================================== */

	protected static function summary_rows( $pid, $lang ) {
		$rows  = array();
		foreach ( (array) get_post_meta( $pid, '_items', true ) as $it ) {
			$rows[] = array(
				zen_apt_name( $it['apt'], $lang ),
				$it['from'] . ' → ' . $it['to'] . ' (' . zen_t( 'bk_nights', $lang, array( 'n' => $it['nights'] ) ) . ')',
				( (int) $it['adults'] ) . ' + ' . ( (int) $it['children'] ),
				$it['total'] > 0 ? self::money( $it['total'] ) : zen_t( 'bk_quote', $lang ),
			);
		}
		return $rows;
	}

	public static function money( $n ) {
		$cur = Zen_Settings::get( 'booking.currency', 'HUF' );
		return number_format_i18n( (float) $n, 0 ) . ' ' . ( 'HUF' === $cur ? 'Ft' : $cur );
	}

	protected static function mail_wrap( $title, $body_html ) {
		$logo = Zen_Settings::get( 'general.logo.url', '' );
		$name = Zen_Settings::get( 'general.site_name', '' );
		return '<!doctype html><html><body style="margin:0;background:#f4efe7;font-family:Helvetica,Arial,sans-serif;color:#1f1b16">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4efe7;padding:28px 12px"><tr><td align="center">'
			. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:22px;overflow:hidden">'
			. '<tr><td style="padding:28px 32px;background:#0f0d0b" align="left">' . ( $logo ? '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $name ) . '" height="44" style="height:44px;filter:brightness(0) invert(1)">' : '<b style="color:#fff;font-size:20px">' . esc_html( $name ) . '</b>' ) . '</td></tr>'
			. '<tr><td style="padding:32px"><h1 style="font-family:Georgia,serif;font-weight:500;font-size:26px;margin:0 0 16px">' . esc_html( $title ) . '</h1>' . $body_html . '</td></tr>'
			. '<tr><td style="padding:20px 32px;background:#f8f4ee;font-size:12px;color:#6b6358">' . esc_html( $name ) . ' · ' . esc_html( Zen_Settings::get( 'general.address_line', '' ) ) . ' · NTAK: ' . esc_html( Zen_Settings::get( 'general.ntak', '' ) ) . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}

	/** Nyilvános e-mail keret (a Zen_Extras vendégkalauz használja). */
	public static function wrap_html( $title, $body_html ) {
		return self::mail_wrap( $title, $body_html );
	}

	protected static function table( $rows, $head = array() ) {
		$h = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:16px 0;font-size:14px">';
		if ( $head ) {
			$h .= '<tr>' . implode( '', array_map( fn( $c ) => '<th align="left" style="padding:10px;border-bottom:2px solid #e3dacb">' . esc_html( $c ) . '</th>', $head ) ) . '</tr>';
		}
		foreach ( $rows as $r ) {
			$h .= '<tr>' . implode( '', array_map( fn( $c ) => '<td style="padding:10px;border-bottom:1px solid #eee">' . esc_html( $c ) . '</td>', $r ) ) . '</tr>';
		}
		return $h . '</table>';
	}

	public static function mail_guest( $pid, $type = 'received', $note = '' ) {
		$g    = (array) get_post_meta( $pid, '_guest', true );
		$lang = get_post_meta( $pid, '_lang', true ) ?: Zen_I18n::default_lang();
		$ref  = get_post_meta( $pid, '_ref', true );
		if ( empty( $g['email'] ) ) {
			return false;
		}
		$map = array(
			'received' => array( 'bk_mail_subject_guest', 'bk_mail_received' ),
			'confirm'  => array( 'bk_mail_subject_confirm', 'bk_mail_confirmed' ),
			'cancel'   => array( 'bk_mail_subject_cancel', 'bk_mail_cancelled' ),
		);
		list( $subj_k, $intro_k ) = $map[ $type ] ?? $map['received'];
		$subject = zen_t( $subj_k, $lang, array( 'ref' => $ref ) );
		$body    = '<p>' . esc_html( zen_t( 'bk_mail_hello', $lang, array( 'name' => $g['name'] ?? '' ) ) ) . '</p>';
		$body   .= '<p>' . esc_html( zen_t( $intro_k, $lang ) ) . '</p>';
		if ( '' !== trim( (string) $note ) ) {
			$body .= '<p style="padding:14px 18px;border-radius:16px;background:#f8f4ee;border-left:3px solid #a9844c">' . nl2br( esc_html( $note ) ) . '</p>';
		}
		$body   .= '<p style="font-size:13px;color:#6b6358">#' . esc_html( $ref ) . '</p>';
		$body   .= self::table( self::summary_rows( $pid, $lang ), array( zen_t( 'bk_step_apartment', $lang ), zen_t( 'bk_step_dates', $lang ), zen_t( 'bk_guests', $lang ), zen_t( 'bk_total', $lang ) ) );
		$total   = (float) get_post_meta( $pid, '_total', true );
		$dep     = (float) get_post_meta( $pid, '_deposit', true );
		if ( $total > 0 ) {
			$body .= '<p><b>' . esc_html( zen_t( 'bk_total', $lang ) ) . ':</b> ' . esc_html( self::money( $total ) ) . '<br><b>' . esc_html( zen_t( 'bk_deposit', $lang, array( 'pct' => Zen_Settings::get( 'general.deposit_pct', 50 ) ) ) ) . ':</b> ' . esc_html( self::money( $dep ) ) . '</p>';
		} else {
			$body .= '<p>' . esc_html( zen_t( 'bk_quote_note', $lang ) ) . '</p>';
		}
		if ( 'cancel' !== $type ) {
			$bank = trim( (string) Zen_Settings::get( 'booking.bank_details', '' ) );
			if ( 'confirm' === $type && $bank && 'transfer' === get_post_meta( $pid, '_payment', true ) ) {
				$body .= '<p><b>' . esc_html( zen_t( 'bk_mail_bank', $lang ) ) . '</b><br>' . nl2br( esc_html( $bank ) ) . '<br>' . esc_html( $ref ) . '</p>';
			}
			$link = trim( (string) Zen_Settings::get( 'booking.card_payment_url', '' ) );
			if ( 'confirm' === $type && $link && 'card' === get_post_meta( $pid, '_payment', true ) ) {
				$body .= '<p><b>' . esc_html( zen_t( 'bk_mail_pay_link', $lang ) ) . '</b> <a href="' . esc_url( add_query_arg( 'ref', $ref, $link ) ) . '">' . esc_html( $link ) . '</a></p>';
			}
			$body .= '<p>' . esc_html( zen_t( 'bk_tax_note', $lang, array( 'tax' => Zen_Settings::get( 'general.tourist_tax', 500 ) ) ) ) . '</p>';
			$body .= '<p>' . esc_html( zen_t( 'bk_mail_checkin', $lang, array( 'in' => Zen_Settings::get( 'general.checkin' ), 'out' => Zen_Settings::get( 'general.checkout' ), 'address' => Zen_Settings::get( 'general.address_line' ) ) ) ) . '</p>';
			$body .= '<p style="font-size:13px;color:#6b6358">' . esc_html( zen_t( 'bk_cancel_policy', $lang, array( 'days' => Zen_Settings::get( 'general.free_cancel_days', 15 ) ) ) ) . '</p>';
		}
		$body .= '<p>' . esc_html( zen_t( 'bk_mail_footer', $lang ) ) . '</p>';
		$headers = array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . Zen_Settings::get( 'general.email' ) );
		return wp_mail( $g['email'], $subject, self::mail_wrap( $subject, $body ), $headers );
	}

	/** Pill gomb e-mailbe (Outlook-barát táblázatos gomb). */
	protected static function mail_btn( $url, $label, $bg, $fg = '#ffffff' ) {
		return '<a href="' . esc_url( $url ) . '" style="display:inline-block;margin:4px 6px 4px 0;padding:14px 26px;border-radius:999px;background:' . esc_attr( $bg ) . ';color:' . esc_attr( $fg ) . ';text-decoration:none;font-weight:bold;font-size:15px;line-height:1">' . esc_html( $label ) . '</a>';
	}

	/** Értesítés a tulajdonosnak – egy kattintással elfogadható / lemondható. */
	public static function mail_admin( $pid ) {
		$to   = Zen_Settings::get( 'booking.notify_email', get_option( 'admin_email' ) );
		$g    = (array) get_post_meta( $pid, '_guest', true );
		$ref  = get_post_meta( $pid, '_ref', true );
		$st   = get_post_meta( $pid, '_status', true );
		$tel  = preg_replace( '/[^+0-9]/', '', (string) ( $g['phone'] ?? '' ) );
		$subject = ( 'pending' === $st ? '🔔 Jóváhagyásra vár' : '✅ Visszaigazolva' ) . ' – új foglalás ' . $ref . ' – ' . ( $g['name'] ?? '' );
		$body  = '<p style="margin:0 0 18px;font-size:15px">' . ( 'pending' === $st ? 'Új foglalási kérés érkezett. Egy kattintással elfogadhatod vagy elutasíthatod – a vendég azonnal e-mailt kap.' : 'Új, automatikusan visszaigazolt foglalás érkezett.' ) . '</p>';
		$body .= '<div style="margin:0 0 22px">';
		if ( 'confirmed' !== $st ) {
			$body .= self::mail_btn( self::action_url( $pid, 'confirm' ), '✓ Elfogadom', '#2f8f5b' );
		}
		$body .= self::mail_btn( self::action_url( $pid, 'cancel' ), '✕ ' . ( 'pending' === $st ? 'Elutasítom' : 'Lemondom' ), '#c2413a' );
		$body .= self::mail_btn( self::action_url( $pid, 'view' ), 'Részletek és kezelés', '#efe8dc', '#1f1b16' );
		$body .= '</div>';
		$body .= '<p><b>Vendég:</b> ' . esc_html( $g['name'] ?? '' ) . ' · ' . esc_html( $g['country'] ?? '' ) . '<br><a href="mailto:' . esc_attr( $g['email'] ?? '' ) . '">' . esc_html( $g['email'] ?? '' ) . '</a> · <a href="tel:' . esc_attr( $tel ) . '">' . esc_html( $g['phone'] ?? '' ) . '</a></p>';
		$body .= self::table( self::summary_rows( $pid, 'hu' ), array( 'Apartman', 'Időpont', 'Fő (f+gy)', 'Összeg' ) );
		$body .= '<p><b>Érkezés:</b> ' . esc_html( $g['arrival_time'] ?? '' ) . '<br><b>Fizetés:</b> ' . esc_html( get_post_meta( $pid, '_payment', true ) ) . '<br><b>Extrák:</b> ' . esc_html( implode( ', ', (array) get_post_meta( $pid, '_extras', true ) ) ) . '<br><b>Megjegyzés:</b> ' . nl2br( esc_html( $g['message'] ?? '' ) ) . '</p>';
		$body .= '<p style="padding:14px;border-radius:14px;background:#fff7e6;font-size:13px">A függő és a visszaigazolt foglalás azonnal lezárja a napokat a weboldalon, és bekerül a Booking.com / Airbnb felé küldött iCal-ba. Elutasításkor a napok újra szabadok lesznek.</p>';
		$headers = array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . ( $g['email'] ?? '' ), 'X-Priority: 1', 'Importance: High' );
		return wp_mail( $to, $subject, self::mail_wrap( 'Új foglalás: ' . $ref, $body ), $headers );
	}

	/* ==================================================================
	 * E-mailes műveletek (elfogadás / lemondás bejelentkezés nélkül)
	 * A link csak egy megerősítő oldalt nyit (GET), a művelet gombnyomásra (POST) fut –
	 * így a levelezők linkellenőrzője sem tud véletlenül jóváhagyni.
	 * ================================================================== */

	protected static function action_sig( $pid, $act ) {
		$key = (string) get_post_meta( $pid, '_akey', true );
		if ( '' === $key ) {
			$key = wp_generate_password( 24, false );
			update_post_meta( $pid, '_akey', $key );
		}
		return hash_hmac( 'sha256', $pid . '|' . $act, $key . wp_salt( 'auth' ) );
	}

	public static function action_url( $pid, $act ) {
		return add_query_arg( array( 'zen_bk' => (int) $pid, 'a' => $act, 'k' => self::action_sig( $pid, $act ) ), home_url( '/' ) );
	}

	public static function mail_action() {
		if ( empty( $_GET['zen_bk'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$pid = (int) $_GET['zen_bk']; // phpcs:ignore
		$act = isset( $_GET['a'] ) ? sanitize_key( wp_unslash( $_GET['a'] ) ) : 'view'; // phpcs:ignore
		$sig = isset( $_GET['k'] ) ? sanitize_text_field( wp_unslash( $_GET['k'] ) ) : ''; // phpcs:ignore
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );
		if ( ! in_array( $act, array( 'view', 'confirm', 'cancel' ), true ) || self::CPT !== get_post_type( $pid ) || ! hash_equals( self::action_sig( $pid, $act ), $sig ) ) {
			self::action_page( 0, 'Érvénytelen vagy lejárt link', '<p>A link nem érvényes. Nyisd meg a foglalást a WordPress adminban.</p>' );
		}
		$msg = '';
		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && in_array( $act, array( 'confirm', 'cancel' ), true ) ) { // phpcs:ignore
			$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : ''; // phpcs:ignore
			$res  = self::set_status( $pid, 'confirm' === $act ? 'confirmed' : 'cancelled', $note, 'email' );
			$msg  = is_wp_error( $res ) ? '<p class="err">' . esc_html( $res->get_error_message() ) . '</p>' : '<p class="ok">' . ( 'confirm' === $act ? '✓ Elfogadva – a vendég visszaigazoló e-mailt kapott.' : '✓ ' . ( 'cancelled' === $res ? 'Lemondva' : 'Kész' ) . ' – a vendég értesítést kapott, a napok felszabadultak.' ) . '</p>';
		}
		self::action_page( $pid, '', $msg, $act );
	}

	/** Állapotváltás + vendég e-mail (admin, e-mail link és REST közös útja). */
	public static function set_status( $pid, $status, $note = '', $via = 'admin' ) {
		$old = get_post_meta( $pid, '_status', true );
		if ( $old === $status ) {
			return new WP_Error( 'same', 'cancelled' === $status ? 'Ez a foglalás már le van mondva.' : 'Ez a foglalás már vissza van igazolva.' );
		}
		if ( 'confirmed' === $status ) {
			self::flush_cache();
			foreach ( (array) get_post_meta( $pid, '_items', true ) as $it ) {
				if ( ! self::is_free( $it['apt'], $it['from'], $it['to'], $pid ) ) {
					return new WP_Error( 'taken', 'Ütközés: ' . zen_apt_name( $it['apt'], 'hu' ) . ' ' . $it['from'] . ' → ' . $it['to'] . ' időközben foglalt lett (pl. Booking.com / Airbnb). Egyeztess a vendéggel.' );
				}
			}
		}
		update_post_meta( $pid, '_status', $status );
		if ( '' !== $note ) {
			update_post_meta( $pid, '_guest_note', $note );
		}
		$log   = (array) get_post_meta( $pid, '_log', true );
		$log[] = array( 'ts' => time(), 's' => $status, 'via' => $via );
		update_post_meta( $pid, '_log', array_slice( $log, -30 ) );
		self::flush_cache();
		self::mail_guest( $pid, 'confirmed' === $status ? 'confirm' : 'cancel', $note );
		do_action( 'zen_booking_status', $pid, $status, $old );
		return $status;
	}

	/** Önálló, mobilbarát kezelőoldal (üveg kártya, pill gombok). */
	protected static function action_page( $pid, $title = '', $msg = '', $act = 'view' ) {
		$name = Zen_Settings::get( 'general.site_name', '' );
		$h    = '';
		if ( $pid ) {
			$g     = (array) get_post_meta( $pid, '_guest', true );
			$st    = get_post_meta( $pid, '_status', true );
			$ref   = get_post_meta( $pid, '_ref', true );
			$tel   = preg_replace( '/[^+0-9]/', '', (string) ( $g['phone'] ?? '' ) );
			$title = 'Foglalás ' . $ref;
			$h    .= '<p class="badge b-' . esc_attr( $st ) . '">' . esc_html( self::status_label( $st ) ) . '</p>' . $msg;
			$h    .= '<div class="guest"><b>' . esc_html( $g['name'] ?? '' ) . '</b><span>' . esc_html( $g['country'] ?? '' ) . '</span><div class="row"><a class="pill ghost" href="tel:' . esc_attr( $tel ) . '">☎ ' . esc_html( $g['phone'] ?? '' ) . '</a><a class="pill ghost" href="mailto:' . esc_attr( $g['email'] ?? '' ) . '?subject=' . rawurlencode( $ref ) . '">✉ ' . esc_html( $g['email'] ?? '' ) . '</a></div></div>';
			$h    .= '<ul class="items">';
			foreach ( self::summary_rows( $pid, 'hu' ) as $r ) {
				$h .= '<li><b>' . esc_html( $r[0] ) . '</b><span>' . esc_html( $r[1] ) . ' · ' . esc_html( $r[2] ) . ' fő</span><em>' . esc_html( $r[3] ) . '</em></li>';
			}
			$h .= '</ul>';
			if ( ! empty( $g['message'] ) ) {
				$h .= '<p class="note">„' . esc_html( $g['message'] ) . '”</p>';
			}
			$h .= '<form method="post" class="acts"><label for="zn">Üzenet a vendégnek (opcionális, bekerül az e-mailbe)</label><textarea id="zn" name="note" rows="3" placeholder="Pl. érkezéskor hívjon, szívesen segítünk a parkolásban."></textarea><div class="row">';
			if ( 'confirmed' !== $st ) {
				$h .= '<button class="pill ok" formaction="' . esc_url( self::action_url( $pid, 'confirm' ) ) . '">✓ Elfogadom és értesítem a vendéget</button>';
			}
			if ( 'cancelled' !== $st ) {
				$h .= '<button class="pill bad" formaction="' . esc_url( self::action_url( $pid, 'cancel' ) ) . '" onclick="return confirm(\'Biztosan ' . ( 'pending' === $st ? 'elutasítod' : 'lemondod' ) . '? A vendég e-mailt kap.\')">✕ ' . ( 'pending' === $st ? 'Elutasítom' : 'Lemondom' ) . '</button>';
			}
			$h .= '</div></form><p class="foot"><a href="' . esc_url( admin_url( 'post.php?post=' . (int) $pid . '&action=edit' ) ) . '">Szerkesztés az adminban (dátum, összeg, jegyzet) ↗</a></p>';
		} else {
			$h = $msg;
		}
		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );
		echo '<!doctype html><html lang="hu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>' . esc_html( $title . ' · ' . $name ) . '</title><style>'
			. ':root{color-scheme:light dark;--bg:#f4efe7;--card:rgba(255,255,255,.78);--tx:#1f1b16;--mu:#6b6358;--ln:rgba(31,27,22,.12);--ok:#2f8f5b;--bad:#c2413a;--ac:#a9844c}'
			. '@media(prefers-color-scheme:dark){:root{--bg:#110f0c;--card:rgba(30,27,22,.72);--tx:#f3ede3;--mu:#a79e90;--ln:rgba(255,255,255,.12)}}'
			. '*{box-sizing:border-box}body{margin:0;min-height:100svh;display:grid;place-items:center;padding:18px;font:15px/1.5 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:var(--tx);background:radial-gradient(60% 50% at 10% 0%,rgba(169,132,76,.35),transparent),radial-gradient(50% 50% at 100% 100%,rgba(110,139,116,.3),transparent),var(--bg)}'
			. '.card{width:min(620px,100%);padding:clamp(20px,5vw,36px);border-radius:28px;background:var(--card);border:1px solid var(--ln);backdrop-filter:blur(24px) saturate(1.3);-webkit-backdrop-filter:blur(24px) saturate(1.3);box-shadow:0 30px 80px -30px rgba(0,0,0,.45)}'
			. 'h1{margin:0 0 10px;font:500 clamp(24px,5vw,32px)/1.15 Georgia,serif}.k{margin:0 0 6px;font-size:11px;letter-spacing:.24em;text-transform:uppercase;color:var(--ac);font-weight:700}'
			. '.badge{display:inline-block;margin:0 0 14px;padding:7px 14px;border-radius:999px;font-weight:700;font-size:13px;background:rgba(183,121,31,.15);color:#b7791f}.b-confirmed{background:rgba(47,143,91,.15);color:var(--ok)}.b-cancelled{background:rgba(194,65,58,.14);color:var(--bad)}'
			. '.ok{color:var(--ok);font-weight:700}.err{color:var(--bad);font-weight:700}.guest{display:grid;gap:4px;padding:14px 16px;border-radius:20px;border:1px solid var(--ln);margin:0 0 12px}.guest span{color:var(--mu)}'
			. '.items{list-style:none;margin:0 0 12px;padding:0;display:grid;gap:8px}.items li{display:grid;grid-template-columns:1fr auto;gap:2px 12px;padding:12px 16px;border-radius:18px;border:1px solid var(--ln)}.items span{color:var(--mu);font-size:13.5px}.items em{grid-row:1/3;grid-column:2;align-self:center;font-style:normal;font-weight:700}'
			. '.note{padding:12px 16px;border-radius:18px;background:rgba(169,132,76,.1);font-style:italic}label{display:block;font-weight:700;font-size:13px;margin:14px 0 6px}'
			. 'textarea{width:100%;padding:12px 16px;border-radius:18px;border:1px solid var(--ln);background:transparent;color:var(--tx);font:inherit;resize:vertical}'
			. '.row{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}.pill{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:0 22px;border-radius:999px;border:0;font-family:inherit;font-weight:700;font-size:14.5px;line-height:1;cursor:pointer;text-decoration:none;color:#fff;flex:1 1 auto}'
			. '.pill.ok{background:var(--ok);color:#fff}.pill.bad{background:var(--bad)}.pill.ghost{background:transparent;color:var(--tx);border:1px solid var(--ln);min-height:40px;font-weight:600;flex:0 1 auto}.foot{margin:18px 0 0;font-size:13px}.foot a{color:var(--mu)}'
			. '</style></head><body><main class="card"><p class="k">' . esc_html( $name ) . '</p><h1>' . esc_html( $title ) . '</h1>' . $h . '</main></body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	/* ==================================================================
	 * Front-end: widget + foglalósáv
	 * ================================================================== */

	protected static function enqueue() {
		$v = ZEN_CORE_VER . '.' . ( file_exists( ZEN_CORE_DIR . 'assets/booking.js' ) ? filemtime( ZEN_CORE_DIR . 'assets/booking.js' ) : 0 );
		wp_enqueue_style( 'zen-booking', ZEN_CORE_URL . 'assets/booking.css', array(), $v );
		wp_enqueue_script( 'zen-booking', ZEN_CORE_URL . 'assets/booking.js', array(), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		$lang = zen_lang();
		$apts = array();
		foreach ( zen_apartments() as $a ) {
			$apts[] = array(
				'key'      => $a['key'],
				'name'     => Zen_I18n::pick( $a['name'] ?? array(), $lang ),
				'capacity' => (int) $a['capacity'],
				'size'     => (float) $a['size'],
				'bedrooms' => (int) ( $a['bedrooms'] ?? 0 ),
				'cover'    => Zen_Media::url( $a['cover'] ?? array(), 'medium_large', true ),
				'short'    => Zen_I18n::pick( $a['short'] ?? array(), $lang ),
				'color'    => $a['color'] ?? '',
				'url'      => zen_page_url( 'apt_' . $a['key'], $lang ),
			);
		}
		$extras = array();
		foreach ( (array) Zen_Settings::get( 'booking.extras', array() ) as $e ) {
			$extras[] = array( 'key' => $e['key'], 'label' => Zen_I18n::pick( $e['label'] ?? array(), $lang ), 'price' => (float) ( $e['price'] ?? 0 ), 'per' => $e['per'] ?? 'stay' );
		}
		$keys = array( 'bk_title', 'bk_step_dates', 'bk_step_apartment', 'bk_step_details', 'bk_step_summary', 'bk_step_done', 'bk_choose_arrival', 'bk_choose_departure', 'bk_arrival', 'bk_departure', 'bk_guests', 'bk_adults', 'bk_children', 'bk_children_note', 'bk_nights', 'bk_legend_free', 'bk_legend_checkout', 'bk_legend_closed', 'bk_legend_event', 'bk_legend_selected', 'bk_add_room', 'bk_remove_room', 'bk_room', 'bk_help', 'bk_search', 'bk_next', 'bk_back', 'bk_select', 'bk_selected', 'bk_unavailable', 'bk_too_small', 'bk_per_night', 'bk_total', 'bk_quote', 'bk_quote_note', 'bk_deposit', 'bk_tax_note', 'bk_cleaning', 'bk_extras', 'bk_name', 'bk_email', 'bk_phone', 'bk_country', 'bk_arrival_time', 'bk_message', 'bk_payment', 'bk_pay_transfer', 'bk_pay_cash', 'bk_pay_card', 'bk_gdpr', 'bk_terms', 'bk_newsletter', 'bk_cancel_policy', 'bk_submit', 'bk_submit_instant', 'bk_sending', 'bk_done_title', 'bk_done_text', 'bk_done_instant', 'bk_add_calendar', 'bk_new', 'bk_err_dates', 'bk_err_min', 'bk_err_room', 'bk_err_required', 'bk_err_email', 'bk_err_gdpr', 'bk_err_taken', 'bk_err_generic', 'bk_sync_note', 'bk_dates', 'bk_pick_dates', 'bk_apply', 'bk_clear', 'bk_legend_booked', 'bk_change', 'bk_free_count', 'bk_range_hint', 'privacy', 'capacity', 'size', 'bedrooms', 'studio', 'details', 'close', 'best_price' );
		$str  = array();
		foreach ( $keys as $k ) {
			$str[ $k ] = zen_t( $k, $lang );
		}
		$cfg = array(
			'rest'        => esc_url_raw( rest_url( 'zen/v1/' ) ),
			'lang'        => $lang,
			'locale'      => str_replace( '_', '-', Zen_I18n::all_languages()[ $lang ]['locale'] ?? 'hu-HU' ),
			'apartments'  => $apts,
			'extras'      => $extras,
			'payments'    => array_values( (array) Zen_Settings::get( 'booking.payments', array( 'transfer' ) ) ),
			'currency'    => Zen_Settings::get( 'booking.currency', 'HUF' ),
			'depositPct'  => (int) Zen_Settings::get( 'general.deposit_pct', 50 ),
			'tax'         => (int) Zen_Settings::get( 'general.tourist_tax', 500 ),
			'cancelDays'  => (int) Zen_Settings::get( 'general.free_cancel_days', 15 ),
			'checkin'     => Zen_Settings::get( 'general.checkin', '14:00' ),
			'checkout'    => Zen_Settings::get( 'general.checkout', '10:00' ),
			'minNights'   => (int) Zen_Settings::get( 'booking.min_nights', 1 ),
			'maxRooms'    => (int) Zen_Settings::get( 'booking.max_rooms', 3 ),
			'maxAdvance'  => (int) Zen_Settings::get( 'booking.max_advance', 540 ),
			'instant'     => (bool) Zen_Settings::get( 'booking.auto_confirm', false ),
			'privacy'     => zen_page_url( 'privacy', $lang ),
			'bookingUrl'  => zen_page_url( 'booking', $lang ),
			'phone'       => Zen_Settings::get( 'general.phone_booking', Zen_Settings::get( 'general.phone' ) ),
			'email'       => Zen_Settings::get( 'general.email' ),
			'address'     => Zen_Settings::get( 'general.address_line' ),
			'site'        => Zen_Settings::get( 'general.site_name' ),
			'str'         => $str,
		);
		wp_add_inline_script( 'zen-booking', 'window.ZenBookingCfg=' . wp_json_encode( $cfg ) . ';', 'before' );
	}

	/** Foglaló widget (natív motor alapértelmezett; NetHotelBooking csak tartalék). */
	public static function widget( $args = array() ) {
		$engine = ! empty( $args['engine'] ) ? $args['engine'] : Zen_Settings::get( 'booking.engine', 'native' );
		if ( 'nhb' === $engine ) {
			return self::nhb_frame();
		}
		self::enqueue();
		$phone = Zen_Settings::get( 'general.phone_booking', Zen_Settings::get( 'general.phone' ) );
		ob_start();
		?>
		<?php echo class_exists( 'Zen_Extras' ) ? Zen_Extras::szep_html( 'booking' ) : ''; // phpcs:ignore ?>
		<div class="zcb" data-zcb>
			<div class="zcb__loading" aria-hidden="true"><span></span><span></span><span></span></div>
			<noscript><p class="zcb__noscript"><?php echo esc_html( zen_t( 'bk_help' ) ); ?>: <a href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a> · <a href="mailto:<?php echo esc_attr( Zen_Settings::get( 'general.email' ) ); ?>"><?php echo esc_html( Zen_Settings::get( 'general.email' ) ); ?></a></p></noscript>
		</div>
		<?php
		return ob_get_clean();
	}

	protected static function nhb_frame() {
		$lang = zen_lang();
		$l    = in_array( $lang, array( 'hu', 'en', 'de' ), true ) ? $lang : 'en';
		$url  = trailingslashit( Zen_Settings::get( 'booking.nhb_url', '' ) ) . 'iframe=1;lang=' . $l . ';ca_currency=' . Zen_Settings::get( 'booking.currency', 'HUF' );
		$scr  = Zen_Settings::get( 'booking.nhb_script', '' );
		return '<div class="zcb zcb--nhb">' . ( $scr ? '<script src="' . esc_url( $scr ) . '"></script>' : '' ) . '<div id="resnweb-iframe-wrapper"><iframe title="' . esc_attr( zen_t( 'bk_title' ) ) . '" width="100%" id="resnweb-iframe" name="resnweb-iframe" src="' . esc_url( $url ) . '" scrolling="no" frameborder="0" style="width:100%;min-height:1100px;border:0;display:block"></iframe></div></div>';
	}

	/** Kompakt foglalósáv (hero, apartman oldal). JS nélkül is működik (natív dátummezők). */
	public static function bar( $args = array() ) {
		self::enqueue();
		$apt     = sanitize_key( $args['apt'] ?? '' );
		$compact = ! empty( $args['compact'] );
		$variant = in_array( $args['variant'] ?? '', array( 'panel' ), true ) ? $args['variant'] : '';
		$today   = self::today();
		$id      = 'zcb' . wp_rand( 100, 999 );
		$cls     = 'zcb-bar' . ( $compact ? ' zcb-bar--compact' : '' ) . ( $variant ? ' zcb-bar--' . $variant : '' );
		ob_start();
		?>
		<form class="<?php echo esc_attr( $cls ); ?>" action="<?php echo esc_url( zen_page_url( 'booking' ) ); ?>" method="get" data-zcb-bar data-apt="<?php echo esc_attr( $apt ); ?>">
			<?php if ( $apt ) : ?><input type="hidden" name="apt" value="<?php echo esc_attr( $apt ); ?>"><?php endif; ?>
			<label class="zcb-bar__field" for="<?php echo esc_attr( $id ); ?>f">
				<span><?php echo esc_html( zen_t( 'bk_arrival' ) ); ?></span>
				<input id="<?php echo esc_attr( $id ); ?>f" type="date" name="from" min="<?php echo esc_attr( $today ); ?>" required>
			</label>
			<label class="zcb-bar__field" for="<?php echo esc_attr( $id ); ?>t">
				<span><?php echo esc_html( zen_t( 'bk_departure' ) ); ?></span>
				<input id="<?php echo esc_attr( $id ); ?>t" type="date" name="to" min="<?php echo esc_attr( self::add_days( $today, 1 ) ); ?>" required>
			</label>
			<label class="zcb-bar__field zcb-bar__field--guests" for="<?php echo esc_attr( $id ); ?>g">
				<span><?php echo esc_html( zen_t( 'bk_guests' ) ); ?></span>
				<select id="<?php echo esc_attr( $id ); ?>g" name="adults">
					<?php for ( $i = 1; $i <= 9; $i++ ) : ?>
						<option value="<?php echo (int) $i; ?>"<?php selected( 2, $i ); ?>><?php echo esc_html( zen_t( 'capacity', null, array( 'n' => $i ) ) ); ?></option>
					<?php endfor; ?>
				</select>
			</label>
			<p class="zcb-bar__hint" data-zcb-hint aria-live="polite"></p>
			<button type="submit" class="zc-btn zcb-bar__go"><span><?php echo esc_html( zen_t( 'bk_search' ) ); ?></span><?php echo Zen_Media::icon( 'arrow', 18 ); // phpcs:ignore ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	/* ==================================================================
	 * Admin
	 * ================================================================== */

	public static function admin_menu() {
		add_submenu_page( 'zen-studio', 'Foglaltsági naptár & csatornák', 'Naptár & Booking/Airbnb', 'edit_posts', 'zen-booking-calendar', array( __CLASS__, 'calendar_page' ) );
	}

	public static function status_label( $s ) {
		$m = array( 'pending' => 'Jóváhagyásra vár', 'confirmed' => 'Visszaigazolva', 'cancelled' => 'Lemondva' );
		return $m[ $s ] ?? $s;
	}

	public static function columns( $c ) {
		return array(
			'cb'       => $c['cb'] ?? '',
			'title'    => 'Foglalás',
			'zb_status'=> 'Állapot',
			'zb_apts'  => 'Apartman',
			'zb_dates' => 'Időpont',
			'zb_guest' => 'Vendég',
			'zb_total' => 'Összeg',
			'date'     => 'Rögzítve',
		);
	}

	public static function column( $col, $pid ) {
		$items = (array) get_post_meta( $pid, '_items', true );
		$g     = (array) get_post_meta( $pid, '_guest', true );
		switch ( $col ) {
			case 'zb_status':
				$s = get_post_meta( $pid, '_status', true );
				echo '<span class="zs-badge zs-badge--' . esc_attr( $s ) . '">' . esc_html( self::status_label( $s ) ) . '</span>';
				break;
			case 'zb_apts':
				echo esc_html( implode( ', ', array_map( fn( $i ) => zen_apt_name( $i['apt'] ?? '', 'hu' ), $items ) ) );
				break;
			case 'zb_dates':
				echo esc_html( get_post_meta( $pid, '_from', true ) . ' → ' . get_post_meta( $pid, '_to', true ) );
				break;
			case 'zb_guest':
				echo esc_html( ( $g['name'] ?? '' ) . ' · ' . ( $g['phone'] ?? '' ) );
				break;
			case 'zb_total':
				$t = (float) get_post_meta( $pid, '_total', true );
				echo esc_html( $t > 0 ? self::money( $t ) : 'Árajánlat' );
				break;
		}
	}

	public static function status_filter( $type ) {
		if ( self::CPT !== $type ) {
			return;
		}
		$cur = isset( $_GET['zb_status'] ) ? sanitize_key( wp_unslash( $_GET['zb_status'] ) ) : ''; // phpcs:ignore
		echo '<select name="zb_status"><option value="">Minden állapot</option>';
		foreach ( array( 'pending', 'confirmed', 'cancelled' ) as $s ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( $cur, $s, false ) . '>' . esc_html( self::status_label( $s ) ) . '</option>';
		}
		echo '</select>';
	}

	public static function status_filter_query( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || self::CPT !== $q->get( 'post_type' ) || empty( $_GET['zb_status'] ) ) { // phpcs:ignore
			return;
		}
		$q->set( 'meta_key', '_status' ); // phpcs:ignore
		$q->set( 'meta_value', sanitize_key( wp_unslash( $_GET['zb_status'] ) ) ); // phpcs:ignore
	}

	public static function meta_boxes() {
		add_meta_box( 'zen_booking_box', 'Foglalás részletei', array( __CLASS__, 'box' ), self::CPT, 'normal', 'high' );
		add_meta_box( 'zen_booking_actions', 'Műveletek', array( __CLASS__, 'box_actions' ), self::CPT, 'side', 'high' );
	}

	public static function box( $post ) {
		wp_nonce_field( 'zen_booking_save', 'zen_booking_nonce' );
		$items = (array) get_post_meta( $post->ID, '_items', true );
		$g     = (array) get_post_meta( $post->ID, '_guest', true );
		$it    = $items[0] ?? array( 'apt' => '', 'from' => '', 'to' => '', 'adults' => 2, 'children' => 0 );
		$ref   = get_post_meta( $post->ID, '_ref', true );
		echo '<div class="zs-box">';
		if ( $ref ) {
			echo '<p class="zs-ref"><b>' . esc_html( $ref ) . '</b> · ' . esc_html( get_post_meta( $post->ID, '_source', true ) ?: 'web' ) . ' · nyelv: ' . esc_html( get_post_meta( $post->ID, '_lang', true ) ) . '</p>';
		}
		if ( count( $items ) > 1 ) {
			echo '<p><b>Több apartmanos foglalás:</b></p><ul>';
			foreach ( $items as $x ) {
				echo '<li>' . esc_html( zen_apt_name( $x['apt'], 'hu' ) . ': ' . $x['from'] . ' → ' . $x['to'] . ' (' . $x['adults'] . '+' . $x['children'] . ' fő)' ) . '</li>';
			}
			echo '</ul><p class="description">Az első tétel alább szerkeszthető.</p>';
		}
		echo '<div class="zs-grid">';
		echo '<label>Apartman<select name="zb[apt]">';
		foreach ( zen_apartments() as $a ) {
			echo '<option value="' . esc_attr( $a['key'] ) . '"' . selected( $it['apt'] ?? '', $a['key'], false ) . '>' . esc_html( zen_apt_name( $a['key'], 'hu' ) ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>Érkezés<input type="date" name="zb[from]" value="' . esc_attr( $it['from'] ?? '' ) . '" required></label>';
		echo '<label>Távozás<input type="date" name="zb[to]" value="' . esc_attr( $it['to'] ?? '' ) . '" required></label>';
		echo '<label>Felnőtt<input type="number" min="1" max="9" name="zb[adults]" value="' . esc_attr( $it['adults'] ?? 2 ) . '"></label>';
		echo '<label>Gyermek<input type="number" min="0" max="9" name="zb[children]" value="' . esc_attr( $it['children'] ?? 0 ) . '"></label>';
		echo '<label>Összeg (Ft)<input type="number" min="0" step="100" name="zb[total]" value="' . esc_attr( get_post_meta( $post->ID, '_total', true ) ) . '"></label>';
		echo '<label>Név<input type="text" name="zb[name]" value="' . esc_attr( $g['name'] ?? '' ) . '"></label>';
		echo '<label>E-mail<input type="email" name="zb[email]" value="' . esc_attr( $g['email'] ?? '' ) . '"></label>';
		echo '<label>Telefon<input type="text" name="zb[phone]" value="' . esc_attr( $g['phone'] ?? '' ) . '"></label>';
		echo '<label>Ország<input type="text" name="zb[country]" value="' . esc_attr( $g['country'] ?? '' ) . '"></label>';
		echo '<label>Érkezés ideje<input type="text" name="zb[arrival_time]" value="' . esc_attr( $g['arrival_time'] ?? '' ) . '"></label>';
		echo '<label>Fizetés<select name="zb[payment]">';
		foreach ( array( 'transfer' => 'Átutalás', 'cash' => 'Készpénz', 'card' => 'Kártya' ) as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( get_post_meta( $post->ID, '_payment', true ), $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label>';
		echo '</div>';
		echo '<label class="zs-full">Vendég megjegyzése<textarea name="zb[message]" rows="3">' . esc_textarea( $g['message'] ?? '' ) . '</textarea></label>';
		echo '<label class="zs-full">Belső jegyzet (csak admin)<textarea name="zb[note]" rows="3">' . esc_textarea( get_post_meta( $post->ID, '_note', true ) ) . '</textarea></label>';
		echo '</div>';
	}

	public static function box_actions( $post ) {
		$s = get_post_meta( $post->ID, '_status', true ) ?: 'pending';
		echo '<p>Állapot: <span class="zs-badge zs-badge--' . esc_attr( $s ) . '">' . esc_html( self::status_label( $s ) ) . '</span></p>';
		echo '<p><label>Állapot mentéskor<select name="zb[status]" style="width:100%">';
		foreach ( array( 'pending', 'confirmed', 'cancelled' ) as $x ) {
			echo '<option value="' . esc_attr( $x ) . '"' . selected( $s, $x, false ) . '>' . esc_html( self::status_label( $x ) ) . '</option>';
		}
		echo '</select></label></p>';
		if ( get_post_meta( $post->ID, '_ref', true ) ) {
			$base = admin_url( 'admin-post.php?action=zen_booking_status&id=' . $post->ID );
			echo '<p><a class="button button-primary zs-pill" href="' . esc_url( wp_nonce_url( $base . '&s=confirmed', 'zen_bs_' . $post->ID ) ) . '">Visszaigazolás + e-mail a vendégnek</a></p>';
			echo '<p><a class="button zs-pill" href="' . esc_url( wp_nonce_url( $base . '&s=cancelled', 'zen_bs_' . $post->ID ) ) . '" onclick="return confirm(\'Biztosan lemondod? A vendég e-mailt kap.\')">Lemondás + e-mail</a></p>';
		}
		if ( class_exists( 'Zen_Extras' ) ) {
			Zen_Extras::booking_box_button( $post->ID );
		}
		echo '<p class="description">A visszaigazolt és a függő foglalások azonnal lezárják a napokat a weboldalon, és megjelennek a Booking.com / Airbnb felé küldött iCal-ban.</p>';
	}

	public static function save_admin( $pid, $post ) {
		if ( ! isset( $_POST['zen_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zen_booking_nonce'] ) ), 'zen_booking_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $pid ) || wp_is_post_autosave( $pid ) ) {
			return;
		}
		$z = isset( $_POST['zb'] ) ? (array) wp_unslash( $_POST['zb'] ) : array(); // phpcs:ignore
		$apt  = sanitize_key( $z['apt'] ?? '' );
		$from = sanitize_text_field( $z['from'] ?? '' );
		$to   = sanitize_text_field( $z['to'] ?? '' );
		if ( ! self::valid_date( $from ) || ! self::valid_date( $to ) || $to <= $from || ! zen_apartment( $apt ) ) {
			return;
		}
		$items = (array) get_post_meta( $pid, '_items', true );
		$items[0] = array_merge(
			$items[0] ?? array(),
			array(
				'apt'      => $apt,
				'from'     => $from,
				'to'       => $to,
				'nights'   => self::nights_between( $from, $to ),
				'adults'   => max( 1, (int) ( $z['adults'] ?? 1 ) ),
				'children' => max( 0, (int) ( $z['children'] ?? 0 ) ),
				'total'    => (float) ( $z['total'] ?? 0 ),
			)
		);
		update_post_meta( $pid, '_items', array_values( $items ) );
		update_post_meta( $pid, '_from', min( wp_list_pluck( $items, 'from' ) ) );
		update_post_meta( $pid, '_to', max( wp_list_pluck( $items, 'to' ) ) );
		update_post_meta( $pid, '_total', (float) ( $z['total'] ?? 0 ) );
		$g = (array) get_post_meta( $pid, '_guest', true );
		foreach ( array( 'name', 'phone', 'country', 'arrival_time' ) as $k ) {
			$g[ $k ] = sanitize_text_field( $z[ $k ] ?? '' );
		}
		$g['email']   = sanitize_email( $z['email'] ?? '' );
		$g['message'] = sanitize_textarea_field( $z['message'] ?? '' );
		update_post_meta( $pid, '_guest', $g );
		update_post_meta( $pid, '_payment', sanitize_key( $z['payment'] ?? 'transfer' ) );
		update_post_meta( $pid, '_note', sanitize_textarea_field( $z['note'] ?? '' ) );
		$st = in_array( $z['status'] ?? '', array( 'pending', 'confirmed', 'cancelled' ), true ) ? $z['status'] : 'pending';
		update_post_meta( $pid, '_status', $st );
		if ( ! get_post_meta( $pid, '_ref', true ) ) {
			update_post_meta( $pid, '_ref', self::new_ref() );
			update_post_meta( $pid, '_source', 'admin' );
			update_post_meta( $pid, '_lang', Zen_I18n::default_lang() );
		}
		remove_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_admin' ), 10 );
		wp_update_post( array( 'ID' => $pid, 'post_title' => get_post_meta( $pid, '_ref', true ) . ' – ' . $g['name'] . ' (' . $from . ' → ' . $to . ')' ) );
		self::flush_cache();
	}

	public static function admin_status() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$s  = isset( $_GET['s'] ) ? sanitize_key( wp_unslash( $_GET['s'] ) ) : '';
		check_admin_referer( 'zen_bs_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) || ! in_array( $s, array( 'confirmed', 'cancelled' ), true ) ) {
			wp_die( 'Nincs jogosultság.' );
		}
		$r = self::set_status( $id, $s, '', 'admin' );
		wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit&zen_msg=' . ( is_wp_error( $r ) ? $r->get_error_code() : $s ) ) );
		exit;
	}

	public static function admin_sync_now() {
		check_admin_referer( 'zen_sync_now' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Nincs jogosultság.' );
		}
		delete_transient( 'zen_ical_lock' );
		self::sync_all();
		wp_safe_redirect( admin_url( 'admin.php?page=zen-booking-calendar&synced=1' ) );
		exit;
	}

	public static function admin_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ( false === strpos( (string) $screen->id, 'zen' ) && self::CPT !== $screen->post_type ) ) {
			return;
		}
		if ( 'native' !== Zen_Settings::get( 'booking.engine', 'native' ) ) {
			return;
		}
		$missing = array();
		foreach ( zen_apartments() as $a ) {
			if ( empty( $a['ical_booking'] ) && empty( $a['ical_airbnb'] ) && empty( $a['ical_other'] ) ) {
				$missing[] = zen_apt_name( $a['key'], 'hu' );
			}
		}
		if ( $missing ) {
			echo '<div class="notice notice-warning"><p><b>Figyelem:</b> Booking.com/Airbnb szinkron nélkül dupla foglalás lehetséges. Hiányzó iCal: ' . esc_html( implode( ', ', $missing ) ) . '. <a href="' . esc_url( admin_url( 'admin.php?page=zen-studio#apartments' ) ) . '">Beállítás</a> · <a href="' . esc_url( admin_url( 'admin.php?page=zen-booking-calendar' ) ) . '">Útmutató</a></p></div>';
		}
	}

	/** Naptár + csatorna-útmutató oldal. */
	public static function calendar_page() {
		$offset = isset( $_GET['o'] ) ? (int) $_GET['o'] : 0; // phpcs:ignore
		$start  = self::add_days( self::today(), $offset * 30 );
		$days   = 45;
		$status = get_option( self::STATUS_OPT, array() );
		$last   = (int) get_option( 'zen_ical_last', 0 );
		echo '<div class="wrap zs-wrap"><h1 class="zs-h1">Foglaltsági naptár & Booking.com / Airbnb szinkron</h1>';
		if ( isset( $_GET['synced'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-success"><p>Szinkron kész.</p></div>';
		}
		echo '<p class="zs-toolbar"><a class="button zs-pill" href="' . esc_url( add_query_arg( 'o', $offset - 1 ) ) . '">‹ Korábban</a> <a class="button zs-pill" href="' . esc_url( remove_query_arg( 'o' ) ) . '">Ma</a> <a class="button zs-pill" href="' . esc_url( add_query_arg( 'o', $offset + 1 ) ) . '">Később ›</a> ';
		echo '<a class="button button-primary zs-pill" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=zen_ical_sync_now' ), 'zen_sync_now' ) ) . '">Szinkron most</a> ';
		echo '<span class="zs-muted">Utolsó szinkron: ' . ( $last ? esc_html( wp_date( 'Y-m-d H:i', $last ) ) : 'még nem volt' ) . ' · automatikusan ' . (int) Zen_Settings::get( 'booking.sync_minutes', 15 ) . ' percenként</span></p>';

		echo '<div class="zs-cal"><table><thead><tr><th>Apartman</th>';
		for ( $i = 0; $i < $days; $i++ ) {
			$d   = self::add_days( $start, $i );
			$dow = (int) gmdate( 'N', strtotime( $d . ' UTC' ) );
			echo '<th class="' . ( $dow >= 6 ? 'we' : '' ) . '">' . esc_html( gmdate( 'm.d', strtotime( $d . ' UTC' ) ) ) . '<br><small>' . esc_html( array( '', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo', 'V' )[ $dow ] ) . '</small></th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( zen_apartments() as $a ) {
			echo '<tr><th>' . esc_html( zen_apt_name( $a['key'], 'hu' ) ) . '</th>';
			$occ = self::occupied( $a['key'] );
			for ( $i = 0; $i < $days; $i++ ) {
				$d   = self::add_days( $start, $i );
				$src = '';
				$lbl = '';
				foreach ( $occ as $r ) {
					if ( $d >= $r[0] && $d < $r[1] ) {
						$src = $r[2];
						$lbl = $r[3];
						break;
					}
				}
				echo '<td class="' . esc_attr( $src ? 'o o-' . $src : '' ) . '" title="' . esc_attr( $src ? $src . ' ' . $lbl : 'szabad' ) . '"></td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table></div>';
		echo '<p class="zs-legend"><span class="o-zen"></span> Weboldal &nbsp; <span class="o-booking"></span> Booking.com &nbsp; <span class="o-airbnb"></span> Airbnb &nbsp; <span class="o-ical"></span> Egyéb iCal &nbsp; <span class="o-block"></span> Kézi zárás</p>';

		echo '<h2 class="zs-h2">Csatornák összekötése (egyszeri beállítás)</h2>';
		echo '<ol class="zs-steps"><li><b>Import (Booking/Airbnb → weboldal):</b> a Booking.com Extranetben <i>Szobaárak és elérhetőség → Naptár szinkronizálása → Naptár exportálása</i>, az Airbnb-n <i>Naptár → Elérhetőség → Naptárak összekapcsolása → Naptár exportálása</i>. A kapott .ics linket másold be a <a href="' . esc_url( admin_url( 'admin.php?page=zen-studio#apartments' ) ) . '">Zen Studio → Apartmanok</a> megfelelő mezőjébe.</li>';
		echo '<li><b>Export (weboldal → Booking/Airbnb):</b> az alábbi linkeket illeszd be a Booking.com-on és az Airbnb-n az <i>Importálás / Másik naptár hozzáadása</i> mezőbe. Mindkét csatorna saját linket kap, így egymás foglalásait is látják, de a sajátjukat nem kapják vissza.</li></ol>';
		echo '<table class="widefat zs-table"><thead><tr><th>Apartman</th><th>Link a Booking.com-ba</th><th>Link az Airbnb-be</th><th>Import állapot</th></tr></thead><tbody>';
		foreach ( zen_apartments() as $a ) {
			$st = '';
			foreach ( (array) ( $status[ $a['key'] ] ?? array() ) as $src => $x ) {
				$st .= '<div class="' . ( $x['ok'] ? 'zs-ok' : 'zs-bad' ) . '">' . esc_html( $src . ': ' . ( $x['ok'] ? $x['count'] . ' esemény' : $x['error'] ) . ' · ' . wp_date( 'm.d H:i', $x['time'] ) ) . '</div>';
			}
			echo '<tr><td><b>' . esc_html( zen_apt_name( $a['key'], 'hu' ) ) . '</b></td>';
			foreach ( array( 'booking', 'airbnb' ) as $for ) {
				$u = self::ical_url( $a['key'], $for );
				echo '<td><input type="text" readonly class="zs-copy" value="' . esc_attr( $u ) . '" onclick="this.select();document.execCommand&&document.execCommand(\'copy\')"></td>';
			}
			echo '<td>' . ( $st ? $st : '<span class="zs-muted">nincs import link</span>' ) . '</td></tr>'; // phpcs:ignore
		}
		echo '</tbody></table><p class="description">Megjegyzés: a Booking.com és az Airbnb a beillesztett naptárakat a saját ütemezése szerint olvassa be – ezt nem a weboldal szabja meg. Ezért alapból „foglalási kérelem” mód van bekapcsolva: a vendég kérelmét te hagyod jóvá.</p></div>';
	}
}
