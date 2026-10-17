<?php
/**
 * Zen Forms – kapcsolat, hírlevél (felugró ablak), ügyfél-elégedettségi portál.
 * Minden beküldés tárolva (CRM), e-mail értesítés, CSV export, véleménymoderálás.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Forms {

	const MSG = 'zen_message';
	const FB  = 'zen_feedback';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'wp_footer', array( __CLASS__, 'newsletter_modal' ), 5 );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 21 );
			add_filter( 'manage_' . self::MSG . '_posts_columns', array( __CLASS__, 'msg_columns' ) );
			add_action( 'manage_' . self::MSG . '_posts_custom_column', array( __CLASS__, 'msg_column' ), 10, 2 );
			add_filter( 'manage_' . self::FB . '_posts_columns', array( __CLASS__, 'fb_columns' ) );
			add_action( 'manage_' . self::FB . '_posts_custom_column', array( __CLASS__, 'fb_column' ), 10, 2 );
			add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
			add_action( 'admin_post_zen_export_subscribers', array( __CLASS__, 'export_csv' ) );
			add_action( 'admin_post_zen_fb_publish', array( __CLASS__, 'admin_fb_publish' ) );
		}
	}

	public static function register() {
		register_post_type(
			self::MSG,
			array(
				'labels'       => array( 'name' => 'Üzenetek & feliratkozók', 'singular_name' => 'Üzenet', 'menu_name' => 'Üzenetek' ),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'zen-studio',
				'supports'     => array( 'title' ),
				'capabilities' => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap' => true,
			)
		);
		register_post_type(
			self::FB,
			array(
				'labels'       => array( 'name' => 'Vendégértékelések', 'singular_name' => 'Értékelés', 'menu_name' => 'Értékelések' ),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'zen-studio',
				'supports'     => array( 'title', 'editor' ),
				'map_meta_cap' => true,
			)
		);
	}

	/* ------------------------------------------------------------------
	 * Renderelés
	 * ------------------------------------------------------------------ */

	protected static function enqueue() {
		$v = ZEN_CORE_VER . '.' . ( file_exists( ZEN_CORE_DIR . 'assets/forms.js' ) ? filemtime( ZEN_CORE_DIR . 'assets/forms.js' ) : 0 );
		wp_enqueue_style( 'zen-forms', ZEN_CORE_URL . 'assets/forms.css', array(), $v );
		wp_enqueue_script( 'zen-forms', ZEN_CORE_URL . 'assets/forms.js', array(), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		static $once = false;
		if ( ! $once ) {
			$once = true;
			$lang = zen_lang();
			$keys = array( 'fm_sending', 'fm_err_required', 'fm_err_email', 'fm_err_gdpr', 'fm_err_generic', 'fm_fb_high', 'fm_fb_low', 'fm_fb_google', 'fm_fb_booking', 'close' );
			$s    = array();
			foreach ( $keys as $k ) {
				$s[ $k ] = zen_t( $k, $lang );
			}
			wp_add_inline_script(
				'zen-forms',
				'window.ZenFormsCfg=' . wp_json_encode(
					array(
						'rest'    => esc_url_raw( rest_url( 'zen/v1/form/' ) ),
						'lang'    => $lang,
						'str'     => $s,
						'google'  => (string) Zen_Settings::get( 'forms.google_review_url', '' ),
						'booking' => (string) Zen_Settings::get( 'forms.booking_review_url', '' ),
					)
				) . ';',
				'before'
			);
		}
	}

	protected static function hidden( $type ) {
		return '<input type="hidden" name="form" value="' . esc_attr( $type ) . '"><input type="text" name="zc_hp" class="zcf-hp" tabindex="-1" autocomplete="off" aria-hidden="true"><input type="hidden" name="zc_ts" value="" data-ts>';
	}

	protected static function f( $name, $label, $type = 'text', $req = false, $extra = '' ) {
		$id = 'zcf-' . $name . '-' . wp_rand( 100, 9999 );
		$in = 'textarea' === $type
			? '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="5"' . ( $req ? ' required' : '' ) . ' ' . $extra . '></textarea>'
			: '<input id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '"' . ( $req ? ' required' : '' ) . ' ' . $extra . '>';
		return '<label class="zcf-field' . ( 'textarea' === $type ? ' zcf-field--full' : '' ) . '" for="' . esc_attr( $id ) . '"><span>' . esc_html( $label ) . ( $req ? '<b aria-hidden="true">*</b>' : '' ) . '</span>' . $in . '</label>';
	}

	protected static function check( $name, $label_html, $req = false ) {
		$id = 'zcf-' . $name . '-' . wp_rand( 100, 9999 );
		return '<label class="zcf-check" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . ( $req ? ' required' : '' ) . '><span class="zcf-check__box" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg></span><span>' . $label_html . '</span></label>';
	}

	protected static function gdpr_label( $key = 'fm_gdpr' ) {
		return esc_html( zen_t( $key ) ) . ' <a href="' . esc_url( zen_page_url( 'privacy' ) ) . '" target="_blank" rel="noopener">↗</a>';
	}

	public static function render( $type, $args = array() ) {
		self::enqueue();
		switch ( $type ) {
			case 'newsletter':
				return self::render_newsletter();
			case 'feedback':
				return self::render_feedback();
			default:
				return self::render_contact();
		}
	}

	protected static function render_contact() {
		$o  = '<form class="zcf zcf--contact" data-zcf novalidate>' . self::hidden( 'contact' );
		$o .= '<div class="zcf-grid">';
		$o .= self::f( 'name', zen_t( 'fm_name' ), 'text', true, 'autocomplete="name"' );
		$o .= self::f( 'booking_no', zen_t( 'fm_booking_no' ) );
		$o .= self::f( 'email', zen_t( 'fm_email' ), 'email', true, 'autocomplete="email"' );
		$o .= self::f( 'phone', zen_t( 'fm_phone' ), 'tel', false, 'autocomplete="tel"' );
		$o .= self::f( 'message', zen_t( 'fm_message' ), 'textarea', true );
		$o .= '</div><div class="zcf-checks">';
		$o .= self::check( 'gdpr', self::gdpr_label(), true );
		$o .= self::check( 'offers', esc_html( zen_t( 'fm_offers' ) ) );
		$o .= '</div><div class="zcf-msg" role="status" aria-live="polite"></div>';
		$o .= '<button type="submit" class="zc-btn zcf-submit"><span>' . esc_html( zen_t( 'fm_send' ) ) . '</span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>';
		return $o . '</form>';
	}

	protected static function render_newsletter() {
		$o  = '<form class="zcf zcf--newsletter" data-zcf novalidate>' . self::hidden( 'newsletter' );
		$o .= '<div class="zcf-grid">';
		$o .= self::f( 'name', zen_t( 'fm_name' ), 'text', true, 'autocomplete="name"' );
		$o .= self::f( 'email', zen_t( 'fm_email' ), 'email', true, 'autocomplete="email"' );
		$o .= '</div><div class="zcf-checks">' . self::check( 'gdpr', self::gdpr_label(), true ) . '</div>';
		$o .= '<div class="zcf-msg" role="status" aria-live="polite"></div>';
		$o .= '<button type="submit" class="zc-btn zcf-submit"><span>' . esc_html( zen_t( 'fm_subscribe' ) ) . '</span></button>';
		return $o . '</form>';
	}

	protected static function render_feedback() {
		$cats = array( 'overall' => 'fm_fb_overall', 'clean' => 'fm_fb_clean', 'location' => 'fm_fb_location', 'comfort' => 'fm_fb_comfort', 'communication' => 'fm_fb_communication', 'value' => 'fm_fb_value', 'equipment' => 'fm_fb_equipment' );
		$o    = '<form class="zcf zcf--feedback" data-zcf novalidate>' . self::hidden( 'feedback' );
		$o   .= '<div class="zcf-ratings">';
		foreach ( $cats as $k => $label ) {
			$o .= '<fieldset class="zcf-rate' . ( 'overall' === $k ? ' zcf-rate--main' : '' ) . '"><legend>' . esc_html( zen_t( $label ) ) . ( 'overall' === $k ? '<b aria-hidden="true">*</b>' : '' ) . '</legend><div class="zcf-stars">';
			for ( $i = 5; $i >= 1; $i-- ) {
				$id = 'zcf-r-' . $k . '-' . $i;
				$o .= '<input type="radio" id="' . esc_attr( $id ) . '" name="r_' . esc_attr( $k ) . '" value="' . (int) $i . '"' . ( 'overall' === $k ? ' required' : '' ) . '><label for="' . esc_attr( $id ) . '" title="' . esc_attr( zen_t( 'fm_fb_rate', null, array( 'n' => $i ) ) ) . '"><svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/></svg><span class="screen-reader-text">' . esc_html( zen_t( 'fm_fb_rate', null, array( 'n' => $i ) ) ) . '</span></label>';
			}
			$o .= '</div></fieldset>';
		}
		$o .= '</div><div class="zcf-grid">';
		$sel = '<option value="">' . esc_html( zen_t( 'fm_fb_choose' ) ) . '</option>';
		foreach ( zen_apartments() as $a ) {
			$sel .= '<option value="' . esc_attr( $a['key'] ) . '">' . esc_html( zen_apt_name( $a['key'] ) ) . '</option>';
		}
		$id  = 'zcf-apt-' . wp_rand( 100, 9999 );
		$o  .= '<label class="zcf-field" for="' . esc_attr( $id ) . '"><span>' . esc_html( zen_t( 'fm_fb_apartment' ) ) . '</span><select id="' . esc_attr( $id ) . '" name="apt">' . $sel . '</select></label>';
		$o  .= self::f( 'month', zen_t( 'fm_fb_month' ), 'month', false, 'max="' . esc_attr( wp_date( 'Y-m' ) ) . '"' );
		$o  .= self::f( 'name', zen_t( 'fm_name' ), 'text', true, 'autocomplete="given-name"' );
		$o  .= self::f( 'email', zen_t( 'fm_email' ), 'email', false, 'autocomplete="email"' );
		$o  .= self::f( 'comment', zen_t( 'fm_fb_comment' ), 'textarea', false );
		$o  .= '</div><div class="zcf-checks">';
		$o  .= self::check( 'publish', esc_html( zen_t( 'fm_fb_publish' ) ) );
		$o  .= self::check( 'gdpr', self::gdpr_label(), true );
		$o  .= '</div><div class="zcf-msg" role="status" aria-live="polite"></div>';
		$o  .= '<button type="submit" class="zc-btn zcf-submit"><span>' . esc_html( zen_t( 'fm_fb_send' ) ) . '</span></button>';
		return $o . '</form>';
	}

	/** Hírlevél felugró ablak (a láblécben, [data-zen-newsletter] nyitja). */
	public static function newsletter_modal() {
		if ( is_admin() ) {
			return;
		}
		$form = self::render( 'newsletter' );
		$logo = Zen_Settings::get( 'general.logo.url', '' );
		echo '<div class="zcf-modal" hidden data-zcf-modal role="dialog" aria-modal="true" aria-labelledby="zcf-nl-title"><div class="zcf-modal__card">';
		echo '<button type="button" class="zcf-modal__x" data-zcf-close aria-label="' . esc_attr( zen_t( 'close' ) ) . '"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>';
		if ( $logo ) {
			echo '<img class="zcf-modal__logo" src="' . esc_url( $logo ) . '" alt="" width="160" height="54" loading="lazy">';
		}
		echo '<h4 id="zcf-nl-title" class="zcf-modal__title">' . esc_html( zen_t( 'newsletter_modal_title' ) ) . '</h4><p class="zcf-modal__text">' . esc_html( zen_t( 'newsletter_text' ) ) . '</p>';
		echo $form; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div></div>';
	}

	/* ------------------------------------------------------------------
	 * REST
	 * ------------------------------------------------------------------ */

	public static function routes() {
		register_rest_route(
			'zen/v1',
			'/form/(?P<type>contact|newsletter|feedback)',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_submit' ),
			)
		);
	}

	public static function rest_submit( WP_REST_Request $req ) {
		$type = $req['type'];
		$p    = $req->get_json_params();
		$p    = is_array( $p ) && $p ? $p : $req->get_body_params();
		$lang = Zen_I18n::is_valid( $p['lang'] ?? '' ) ? $p['lang'] : Zen_I18n::default_lang();
		$sec  = Zen_Security::check( $p, 'form_' . $type, 8, 3600, 3 );
		$resp = function ( $data, $code = 200 ) {
			$r = new WP_REST_Response( $data, $code );
			$r->header( 'Cache-Control', 'no-store' );
			return $r;
		};
		if ( is_wp_error( $sec ) ) {
			return $resp( array( 'ok' => false, 'message' => zen_t( 'fm_err_generic', $lang ) ), 400 );
		}
		$name  = sanitize_text_field( $p['name'] ?? '' );
		$email = sanitize_email( $p['email'] ?? '' );
		if ( empty( $p['gdpr'] ) ) {
			return $resp( array( 'ok' => false, 'message' => zen_t( 'fm_err_gdpr', $lang ) ), 422 );
		}

		if ( 'newsletter' === $type ) {
			if ( ! $name || ! is_email( $email ) ) {
				return $resp( array( 'ok' => false, 'message' => zen_t( 'fm_err_required', $lang ) ), 422 );
			}
			self::subscribe( $name, $email, $lang, 'form' );
			return $resp( array( 'ok' => true, 'message' => zen_t( 'fm_ok_newsletter', $lang ) ) );
		}

		if ( 'contact' === $type ) {
			$msg = sanitize_textarea_field( $p['message'] ?? '' );
			if ( ! $name || ! is_email( $email ) || ! $msg ) {
				return $resp( array( 'ok' => false, 'message' => zen_t( ! is_email( $email ) && $email ? 'fm_err_email' : 'fm_err_required', $lang ) ), 422 );
			}
			$data = array(
				'name'       => $name,
				'email'      => $email,
				'phone'      => sanitize_text_field( $p['phone'] ?? '' ),
				'booking_no' => sanitize_text_field( $p['booking_no'] ?? '' ),
				'message'    => $msg,
				'offers'     => ! empty( $p['offers'] ),
				'lang'       => $lang,
				'page'       => esc_url_raw( $p['page'] ?? '' ),
			);
			if ( Zen_Settings::get( 'forms.store_messages', true ) ) {
				$id = wp_insert_post( array( 'post_type' => self::MSG, 'post_status' => 'private', 'post_title' => 'Üzenet – ' . $name, 'post_content' => $msg ) );
				if ( $id && ! is_wp_error( $id ) ) {
					update_post_meta( $id, '_type', 'contact' );
					update_post_meta( $id, '_data', $data );
				}
			}
			if ( $data['offers'] ) {
				self::subscribe( $name, $email, $lang, 'contact' );
			}
			$to   = Zen_Settings::get( 'forms.recipient', get_option( 'admin_email' ) );
			$body = '<table cellpadding="8" style="font-family:Arial,sans-serif;font-size:14px">';
			foreach ( array( 'Név' => $name, 'E-mail' => $email, 'Telefon' => $data['phone'], 'Foglalási szám' => $data['booking_no'], 'Nyelv' => $lang, 'Oldal' => $data['page'] ) as $k => $v ) {
				$body .= '<tr><td><b>' . esc_html( $k ) . '</b></td><td>' . esc_html( $v ) . '</td></tr>';
			}
			$body .= '<tr><td valign="top"><b>Üzenet</b></td><td>' . nl2br( esc_html( $msg ) ) . '</td></tr></table>';
			wp_mail( $to, '[' . Zen_Settings::get( 'general.site_name' ) . '] ' . zen_t( 'fm_mail_contact', 'hu' ) . ' – ' . $name, $body, array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' ) );
			wp_mail( $email, zen_t( 'fm_mail_contact', $lang ) . ' – ' . Zen_Settings::get( 'general.site_name' ), '<p>' . esc_html( zen_t( 'fm_mail_auto', $lang ) ) . '</p><p>' . esc_html( Zen_Settings::get( 'general.site_name' ) ) . '<br>' . esc_html( Zen_Settings::get( 'general.phone' ) ) . '</p>', array( 'Content-Type: text/html; charset=UTF-8' ) );
			return $resp( array( 'ok' => true, 'message' => zen_t( 'fm_ok_contact', $lang ) ) );
		}

		// feedback
		$overall = (int) ( $p['r_overall'] ?? 0 );
		if ( $overall < 1 || $overall > 5 || ! $name ) {
			return $resp( array( 'ok' => false, 'message' => zen_t( 'fm_err_required', $lang ) ), 422 );
		}
		$ratings = array();
		foreach ( array( 'overall', 'clean', 'location', 'comfort', 'communication', 'value', 'equipment' ) as $k ) {
			$v = (int) ( $p[ 'r_' . $k ] ?? 0 );
			if ( $v >= 1 && $v <= 5 ) {
				$ratings[ $k ] = $v;
			}
		}
		$comment = sanitize_textarea_field( $p['comment'] ?? '' );
		$publish = ! empty( $p['publish'] );
		$auto    = Zen_Settings::get( 'forms.feedback_auto_publish', false ) && $publish && $overall >= (int) Zen_Settings::get( 'forms.feedback_min_publish', 4 );
		$id      = wp_insert_post(
			array(
				'post_type'    => self::FB,
				'post_status'  => $auto ? 'publish' : 'pending',
				'post_title'   => $name . ' – ' . $overall . '★',
				'post_content' => $comment,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_ratings', $ratings );
			update_post_meta( $id, '_overall', $overall );
			update_post_meta( $id, '_apt', sanitize_key( $p['apt'] ?? '' ) );
			update_post_meta( $id, '_month', sanitize_text_field( $p['month'] ?? '' ) );
			update_post_meta( $id, '_name', $name );
			update_post_meta( $id, '_email', is_email( $email ) ? $email : '' );
			update_post_meta( $id, '_publish_ok', $publish ? 1 : 0 );
			update_post_meta( $id, '_lang', $lang );
		}
		$to = Zen_Settings::get( 'forms.recipient', get_option( 'admin_email' ) );
		wp_mail( $to, '[' . Zen_Settings::get( 'general.site_name' ) . '] Új vendégértékelés: ' . $overall . '★ – ' . $name, '<p>' . esc_html( $comment ) . '</p><p>' . esc_html( wp_json_encode( $ratings ) ) . '</p><p><a href="' . esc_url( admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) ) . '">Megnyitás / jóváhagyás</a></p>', array( 'Content-Type: text/html; charset=UTF-8' ) );
		$high = $overall >= 4;
		return $resp(
			array(
				'ok'      => true,
				'message' => zen_t( 'fm_ok_feedback', $lang ),
				'high'    => $high,
				'next'    => $high ? zen_t( 'fm_fb_high', $lang ) : zen_t( 'fm_fb_low', $lang ),
			)
		);
	}

	/** Hírlevél feliratkozó mentése (duplikáció nélkül). */
	public static function subscribe( $name, $email, $lang = 'hu', $source = 'form' ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return false;
		}
		$exists = get_posts( array( 'post_type' => self::MSG, 'post_status' => 'any', 'meta_key' => '_email', 'meta_value' => strtolower( $email ), 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore
		if ( $exists ) {
			return (int) $exists[0];
		}
		$id = wp_insert_post( array( 'post_type' => self::MSG, 'post_status' => 'private', 'post_title' => 'Feliratkozó – ' . $email ) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_type', 'newsletter' );
			update_post_meta( $id, '_email', strtolower( $email ) );
			update_post_meta( $id, '_data', array( 'name' => sanitize_text_field( $name ), 'email' => $email, 'lang' => $lang, 'source' => $source, 'consent_at' => current_time( 'mysql' ), 'ip' => wp_hash( Zen_Security::ip() ) ) );
		}
		return $id;
	}

	/** Jóváhagyott, publikálható vélemények a „reviews” szekcióhoz. */
	public static function reviews( $limit = 9 ) {
		$q   = get_posts(
			array(
				'post_type'   => self::FB,
				'post_status' => 'publish',
				'numberposts' => $limit,
				'meta_query'  => array( array( 'key' => '_publish_ok', 'value' => 1 ) ), // phpcs:ignore
			)
		);
		$out = array();
		foreach ( $q as $p ) {
			$name  = (string) get_post_meta( $p->ID, '_name', true );
			$first = trim( strtok( $name, ' ' ) ?: $name );
			$apt   = get_post_meta( $p->ID, '_apt', true );
			$out[] = array(
				'name'   => $first,
				'text'   => wp_strip_all_tags( $p->post_content ),
				'rating' => (int) get_post_meta( $p->ID, '_overall', true ),
				'date'   => (string) get_post_meta( $p->ID, '_month', true ),
				'origin' => $apt ? zen_apt_name( $apt ) : '',
			);
		}
		return array_values( array_filter( $out, fn( $r ) => '' !== $r['text'] ) );
	}

	/** Átlag értékelés (admin + opcionális schema). */
	public static function stats() {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) c, AVG(CAST(m.meta_value AS DECIMAL(3,2))) a FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='_overall' WHERE p.post_type=%s AND p.post_status='publish'", self::FB ) ); // phpcs:ignore
		return array( 'count' => (int) ( $row->c ?? 0 ), 'avg' => round( (float) ( $row->a ?? 0 ), 2 ) );
	}

	/* ------------------------------------------------------------------
	 * Admin
	 * ------------------------------------------------------------------ */

	public static function admin_menu() {
		add_submenu_page( 'zen-studio', 'Hírlevél feliratkozók', 'Feliratkozók (CSV)', 'edit_posts', 'zen-subscribers', array( __CLASS__, 'subscribers_page' ) );
	}

	public static function msg_columns( $c ) {
		return array( 'cb' => $c['cb'] ?? '', 'title' => 'Tárgy', 'zm_type' => 'Típus', 'zm_from' => 'Feladó', 'zm_lang' => 'Nyelv', 'date' => 'Dátum' );
	}

	public static function msg_column( $col, $id ) {
		$d = (array) get_post_meta( $id, '_data', true );
		if ( 'zm_type' === $col ) {
			$t = get_post_meta( $id, '_type', true );
			echo '<span class="zs-badge zs-badge--' . esc_attr( $t ) . '">' . esc_html( 'newsletter' === $t ? 'Hírlevél' : 'Kapcsolat' ) . '</span>';
		} elseif ( 'zm_from' === $col ) {
			echo esc_html( ( $d['name'] ?? '' ) . ' <' . ( $d['email'] ?? '' ) . '> ' . ( $d['phone'] ?? '' ) );
		} elseif ( 'zm_lang' === $col ) {
			echo esc_html( strtoupper( $d['lang'] ?? '' ) );
		}
	}

	public static function fb_columns( $c ) {
		return array( 'cb' => $c['cb'] ?? '', 'title' => 'Vendég', 'zf_rating' => 'Értékelés', 'zf_apt' => 'Apartman', 'zf_pub' => 'Közzétehető', 'date' => 'Dátum' );
	}

	public static function fb_column( $col, $id ) {
		if ( 'zf_rating' === $col ) {
			echo esc_html( str_repeat( '★', (int) get_post_meta( $id, '_overall', true ) ) );
		} elseif ( 'zf_apt' === $col ) {
			echo esc_html( zen_apt_name( (string) get_post_meta( $id, '_apt', true ), 'hu' ) );
		} elseif ( 'zf_pub' === $col ) {
			$ok = get_post_meta( $id, '_publish_ok', true );
			echo $ok ? '✔ hozzájárult' : '—';
			if ( $ok && 'publish' !== get_post_status( $id ) ) {
				echo ' <a class="button button-small zs-pill" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=zen_fb_publish&id=' . $id ), 'zen_fb_' . $id ) ) . '">Megjelenítés</a>';
			}
		}
	}

	public static function admin_fb_publish() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		check_admin_referer( 'zen_fb_' . $id );
		if ( current_user_can( 'edit_post', $id ) ) {
			wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . self::FB ) );
		exit;
	}

	public static function boxes() {
		add_meta_box( 'zen_msg_box', 'Részletek', array( __CLASS__, 'msg_box' ), self::MSG, 'normal', 'high' );
		add_meta_box( 'zen_fb_box', 'Értékelés részletei', array( __CLASS__, 'fb_box' ), self::FB, 'side', 'high' );
	}

	public static function msg_box( $post ) {
		$d = (array) get_post_meta( $post->ID, '_data', true );
		echo '<table class="widefat striped">';
		foreach ( $d as $k => $v ) {
			echo '<tr><th style="width:160px">' . esc_html( $k ) . '</th><td>' . nl2br( esc_html( is_bool( $v ) ? ( $v ? 'igen' : 'nem' ) : (string) $v ) ) . '</td></tr>';
		}
		echo '</table>';
		if ( ! empty( $d['email'] ) ) {
			echo '<p><a class="button button-primary zs-pill" href="mailto:' . esc_attr( $d['email'] ) . '?subject=' . rawurlencode( 'Re: ' . Zen_Settings::get( 'general.site_name' ) ) . '">Válasz e-mailben</a></p>';
		}
	}

	public static function fb_box( $post ) {
		$r = (array) get_post_meta( $post->ID, '_ratings', true );
		echo '<ul>';
		foreach ( $r as $k => $v ) {
			echo '<li><b>' . esc_html( zen_t( 'fm_fb_' . $k, 'hu' ) ) . ':</b> ' . esc_html( str_repeat( '★', (int) $v ) ) . '</li>';
		}
		echo '</ul><p>Apartman: ' . esc_html( zen_apt_name( (string) get_post_meta( $post->ID, '_apt', true ), 'hu' ) ) . '<br>Mikor: ' . esc_html( get_post_meta( $post->ID, '_month', true ) ) . '<br>E-mail: ' . esc_html( get_post_meta( $post->ID, '_email', true ) ) . '<br>Közzétehető: ' . ( get_post_meta( $post->ID, '_publish_ok', true ) ? 'igen' : 'nem' ) . '</p>';
		echo '<p class="description">Publikálva csak akkor jelenik meg a weboldalon, ha a vendég hozzájárult.</p>';
	}

	public static function subscribers_page() {
		$q = get_posts( array( 'post_type' => self::MSG, 'post_status' => 'any', 'numberposts' => 500, 'meta_key' => '_type', 'meta_value' => 'newsletter' ) ); // phpcs:ignore
		$s = self::stats();
		echo '<div class="wrap zs-wrap"><h1 class="zs-h1">Hírlevél feliratkozók</h1><p class="zs-toolbar"><a class="button button-primary zs-pill" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=zen_export_subscribers' ), 'zen_export' ) ) . '">CSV letöltése (' . count( $q ) . ')</a> <span class="zs-muted">Vendégértékelések átlaga: ' . esc_html( $s['avg'] ) . ' ★ (' . (int) $s['count'] . ' publikált)</span></p>';
		echo '<table class="widefat striped zs-table"><thead><tr><th>Név</th><th>E-mail</th><th>Nyelv</th><th>Forrás</th><th>Hozzájárulás</th></tr></thead><tbody>';
		foreach ( $q as $p ) {
			$d = (array) get_post_meta( $p->ID, '_data', true );
			echo '<tr><td>' . esc_html( $d['name'] ?? '' ) . '</td><td>' . esc_html( $d['email'] ?? '' ) . '</td><td>' . esc_html( $d['lang'] ?? '' ) . '</td><td>' . esc_html( $d['source'] ?? '' ) . '</td><td>' . esc_html( $d['consent_at'] ?? '' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function export_csv() {
		check_admin_referer( 'zen_export' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Nincs jogosultság.' );
		}
		$q = get_posts( array( 'post_type' => self::MSG, 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_type', 'meta_value' => 'newsletter' ) ); // phpcs:ignore
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="zen-feliratkozok-' . gmdate( 'Ymd' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore -- UTF-8 BOM Excelhez
		fputcsv( $out, array( 'name', 'email', 'lang', 'source', 'consent_at' ), ';' );
		foreach ( $q as $p ) {
			$d = (array) get_post_meta( $p->ID, '_data', true );
			fputcsv( $out, array( $d['name'] ?? '', $d['email'] ?? '', $d['lang'] ?? '', $d['source'] ?? '', $d['consent_at'] ?? '' ), ';' );
		}
		fclose( $out ); // phpcs:ignore
		exit;
	}
}
