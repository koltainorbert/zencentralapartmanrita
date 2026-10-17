<?php
/**
 * Zen AI – digitális concierge és súgórendszer.
 * - Látja a teljes weboldalt (minden oldal minden szekciója az adott nyelven), apartmanokat, szabályokat,
 *   GYIK-ot, a következő 60 nap szabad időszakait és a Veszprém tudásbázist.
 * - Szolgáltatók: Anthropic Claude, OpenAI, Google Gemini (adminban választható), kulcs nélkül is válaszol (GYIK-motor).
 * - Akció-tokenek → gombok (foglalás előtöltött dátumokkal, oldalak, hívás, e-mail, útvonal).
 * - Minden beszélgetés naplózva (CRM): leadek (e-mail/telefon) kiemelve.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_AI {

	const CPT = 'zen_chat';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'wp_footer', array( __CLASS__, 'launcher' ), 20 );
		add_shortcode( 'zen_help', array( __CLASS__, 'sc_help' ) );
		foreach ( array( 'save_post', 'zen_settings_updated', 'zen_apartments_updated', 'zen_ical_synced', 'zen_sections_saved', 'update_option_zen_kb' ) as $h ) {
			add_action( $h, array( __CLASS__, 'flush_ctx' ) );
		}
		add_action( 'zen_ai_cleanup', array( __CLASS__, 'cleanup' ) );
		add_action( 'init', function () {
			if ( ! wp_next_scheduled( 'zen_ai_cleanup' ) ) {
				wp_schedule_event( time() + 3600, 'daily', 'zen_ai_cleanup' );
			}
		}, 30 );
		if ( is_admin() ) {
			add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
			add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
			add_action( 'add_meta_boxes', array( __CLASS__, 'box' ) );
		}
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'       => array( 'name' => 'Zen AI beszélgetések', 'singular_name' => 'Beszélgetés', 'menu_name' => 'Zen AI napló' ),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'zen-studio',
				'supports'     => array( 'title' ),
				'capabilities' => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap' => true,
			)
		);
	}

	/* ------------------------------------------------------------------
	 * Alapértelmezett rendszer-prompt
	 * ------------------------------------------------------------------ */
	public static function default_prompt() {
		return <<<TXT
Te Zen vagy, a ZEN Central Apartments (Veszprém) nyugodt, kedves és szakértő digitális concierge-e.

Feladatod:
- Segíts a látogatóknak és vendégeknek: apartmanok, foglalás menete, érkezés/távozás, parkolás, fizetés, lemondás, felszereltség, házirend.
- Ismered Veszprémet és a Balaton-felvidéket: ajánlj látnivalókat, programokat, eseményeket, éttermeket, kirándulásokat és napi programtervet – személyre szabva (pár, család, motorsport, eső esetére stb.).
- A weboldal teljes tartalmát látod (lent). Csak a megadott tényekre támaszkodj; ha valamit nem tudsz biztosan, mondd meg őszintén, és add meg a telefonszámot vagy e-mailt.

Stílus:
- Mindig a látogató nyelvén válaszolj (az utolsó üzenet nyelve; ha nem egyértelmű, az oldal nyelve).
- Tömör, barátságos, nyugodt (zen) hangvétel. Legfeljebb ~120 szó, hacsak nem kérnek részletes tervet. Rövid listák megengedettek.
- Ne találj ki árat, és ne ígérj szabad helyet. A megadott szabad időszakokat tájékoztatásként említheted, de a foglalást mindig a foglalási oldalra irányítsd.
- Panasz vagy sürgős ügy esetén add meg a telefonszámot és az e-mail címet.
- Ne kérj szükségtelen személyes adatot.

Akciók (a válasz végére írhatod, a felület gombbá alakítja, a látogató nem látja a nyers szöveget):
[[BOOK from=ÉÉÉÉ-HH-NN to=ÉÉÉÉ-HH-NN adults=N apt=relax|jinjang|lotus]] – foglalás előtöltve (a mezők elhagyhatók)
[[PAGE key=contact|booking|apartments|gallery|feedback|help|apt_relax|apt_jinjang|apt_lotus]]
[[CALL]]  [[MAIL]]  [[MAP]]
Legfeljebb 2 akciót használj válaszonként, csak ha valóban segít.
TXT;
	}

	/* ------------------------------------------------------------------
	 * Kontextus (oldaltudás)
	 * ------------------------------------------------------------------ */
	public static function flush_ctx() {
		foreach ( array_keys( Zen_I18n::all_languages() ) as $l ) {
			delete_transient( 'zen_ai_ctx_' . $l );
		}
	}

	public static function kb() {
		$kb = get_option( 'zen_kb', '' );
		$kb = is_array( $kb ) ? $kb : json_decode( (string) $kb, true );
		if ( ! is_array( $kb ) && file_exists( ZEN_CORE_DIR . 'data/veszprem-kb.json' ) ) {
			$kb = json_decode( (string) file_get_contents( ZEN_CORE_DIR . 'data/veszprem-kb.json' ), true ); // phpcs:ignore
		}
		return is_array( $kb ) ? $kb : array();
	}

	public static function context( $lang ) {
		$cached = get_transient( 'zen_ai_ctx_' . $lang );
		if ( $cached ) {
			return $cached;
		}
		$g   = Zen_Settings::get( 'general' );
		$out = array();
		$out[] = '## SZÁLLÁSHELY';
		$out[] = sprintf( 'Név: %s. Cím: %s. Telefon (általános): %s. Foglalás telefonon: %s. E-mail: %s. Lemondás e-mail: %s. NTAK: %s.', $g['site_name'], $g['address_line'], $g['phone'], $g['phone_booking'], $g['email'], $g['email_cancel'], $g['ntak'] );
		$out[] = sprintf( 'Bejelentkezés %s-tól (kulcsszéf, önálló, késő este is), távozás %s-ig. Előleg: %d%%. Díjmentes lemondás az érkezés előtt %d napig; 15 napon belül 50%%, meg nem jelenés 100%%. Idegenforgalmi adó: %s Ft/fő/éj (18 év felett), érkezéskor. Fizetés: készpénz, átutalás; SZÉP kártyát jelenleg nem fogadnak. Háziállat nem hozható. Nemdohányzó apartmanok. Ingyenes WiFi, ingyenes zárt udvari parkoló (apartmanonként 1), az utcán is ingyenes parkolás.', $g['checkin'], $g['checkout'], (int) $g['deposit_pct'], (int) $g['free_cancel_days'], $g['tourist_tax'] );
		$out[] = 'Foglalási oldal: ' . zen_page_url( 'booking', $lang ) . ' | Kapcsolat: ' . zen_page_url( 'contact', $lang ) . ' | Térkép: ' . ( $g['google_maps'] ?? '' );
		$out[] = '## APARTMANOK';
		foreach ( zen_apartments() as $a ) {
			$out[] = sprintf( '- %s (kulcs: %s): max %d fő, %s m², %s. %s Oldal: %s', zen_apt_name( $a['key'], $lang ), $a['key'], $a['capacity'], $a['size'], ! empty( $a['bedrooms'] ) ? $a['bedrooms'] . ' hálószoba' : 'stúdió', Zen_I18n::pick( $a['short'] ?? array(), $lang ), zen_page_url( 'apt_' . $a['key'], $lang ) );
		}
		// Elérhetőség (következő 60 nap).
		if ( class_exists( 'Zen_Booking' ) ) {
			$from = Zen_Booking::today();
			$to   = Zen_Booking::add_days( $from, 60 );
			$out[] = '## SZABAD IDŐSZAKOK (tájékoztató, ' . $from . ' – ' . $to . ', a foglalás a foglalási oldalon véglegesül)';
			foreach ( zen_apartments() as $a ) {
				$ranges = array();
				$start  = null;
				for ( $d = $from; $d <= $to; $d = Zen_Booking::add_days( $d, 1 ) ) {
					$free = ! Zen_Booking::night_taken( $a['key'], $d );
					if ( $free && ! $start ) {
						$start = $d;
					}
					if ( ( ! $free || $d === $to ) && $start ) {
						$ranges[] = $start . '→' . ( $free ? $d : $d );
						$start    = null;
					}
				}
				$out[] = '- ' . zen_apt_name( $a['key'], $lang ) . ': ' . ( $ranges ? implode( ', ', array_slice( $ranges, 0, 12 ) ) : 'nincs szabad éj' );
			}
			$ev = array();
			foreach ( (array) Zen_Settings::get( 'booking.events', array() ) as $e ) {
				if ( ! empty( $e['from'] ) ) {
					$ev[] = $e['name'] . ' (' . $e['from'] . ' – ' . ( $e['to'] ?? $e['from'] ) . ')';
				}
			}
			if ( $ev ) {
				$out[] = 'Kiemelt események: ' . implode( '; ', $ev );
			}
		}
		// Teljes weboldal az adott nyelven.
		$out[] = '## WEBOLDAL TARTALMA (' . strtoupper( $lang ) . ')';
		$budget = 26000;
		$pages  = Zen_Content::pages( $lang );
		if ( ! $pages && $lang !== Zen_I18n::default_lang() ) {
			$pages = Zen_Content::pages( Zen_I18n::default_lang() );
		}
		foreach ( $pages as $p ) {
			if ( 'privacy' === $p['key'] || 'publish' !== $p['status'] ) {
				continue;
			}
			$txt = Zen_Content::plain_text( $p['id'], 5000 );
			if ( ! $txt ) {
				continue;
			}
			$chunk  = '### ' . $p['title'] . ' (' . $p['url'] . ")\n" . $txt;
			$budget -= mb_strlen( $chunk );
			if ( $budget < 0 ) {
				break;
			}
			$out[] = $chunk;
		}
		// Blog címek.
		$posts = get_posts( array( 'numberposts' => 10, 'post_type' => 'post' ) );
		if ( $posts ) {
			$out[] = '## BLOG';
			foreach ( $posts as $po ) {
				$out[] = '- ' . get_the_title( $po ) . ' – ' . get_permalink( $po );
			}
		}
		// Veszprém tudásbázis.
		$kb = self::kb();
		if ( $kb ) {
			$out[] = '## VESZPRÉM ÉS KÖRNYÉKE (tudásbázis)';
			$dk    = 'hu' === $lang ? 'desc_hu' : 'desc_en';
			foreach ( array( 'sights' => 'Látnivalók', 'daytrips' => 'Kirándulások', 'events' => 'Események', 'food' => 'Gasztronómia', 'practical' => 'Gyakorlati' ) as $k => $label ) {
				if ( empty( $kb[ $k ] ) ) {
					continue;
				}
				$out[] = '### ' . $label;
				foreach ( (array) $kb[ $k ] as $it ) {
					$line  = '- ' . ( $it['name'] ?? $it['topic'] ?? '' );
					$extra = array_filter( array( $it['distance'] ?? '', isset( $it['km'] ) ? $it['km'] . ' km' : '', $it['drive'] ?? '', $it['month'] ?? '' ) );
					if ( $extra ) {
						$line .= ' (' . implode( ', ', array_map( fn( $x ) => is_array( $x ) ? implode( '/', $x ) : $x, $extra ) ) . ')';
					}
					$line .= ': ' . ( $it[ $dk ] ?? $it['desc_hu'] ?? $it[ 'text_' . ( 'hu' === $lang ? 'hu' : 'en' ) ] ?? $it['text_hu'] ?? '' );
					if ( ! empty( $it['url'] ) ) {
						$line .= ' ' . $it['url'];
					}
					$out[] = $line;
				}
			}
			if ( ! empty( $kb['itineraries'] ) ) {
				$out[] = '### Programtervek';
				foreach ( (array) $kb['itineraries'] as $it ) {
					$steps = $it[ 'steps_' . ( 'hu' === $lang ? 'hu' : 'en' ) ] ?? $it['steps_hu'] ?? array();
					$out[] = '- ' . ( $it[ 'title_' . ( 'hu' === $lang ? 'hu' : 'en' ) ] ?? $it['title_hu'] ?? '' ) . ': ' . implode( ' → ', (array) $steps );
				}
			}
		}
		$extra = trim( (string) Zen_Settings::get( 'ai.kb_extra', '' ) );
		if ( $extra ) {
			$out[] = '## TOVÁBBI TUDNIVALÓK (tulajdonos)';
			$out[] = $extra;
		}
		$ctx = implode( "\n", $out );
		set_transient( 'zen_ai_ctx_' . $lang, $ctx, 6 * HOUR_IN_SECONDS );
		return $ctx;
	}

	/* ------------------------------------------------------------------
	 * REST /chat
	 * ------------------------------------------------------------------ */
	public static function routes() {
		register_rest_route(
			'zen/v1',
			'/chat',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'rest_chat' ),
			)
		);
	}

	public static function rest_chat( WP_REST_Request $req ) {
		$p    = (array) $req->get_json_params();
		$lang = Zen_I18n::is_valid( $p['lang'] ?? '' ) ? $p['lang'] : Zen_I18n::default_lang();
		$send = function ( $data, $code = 200 ) {
			$r = new WP_REST_Response( $data, $code );
			$r->header( 'Cache-Control', 'no-store' );
			return $r;
		};
		if ( ! Zen_Settings::get( 'ai.enabled', true ) ) {
			return $send( array( 'reply' => zen_t( 'ai_error', $lang ), 'chips' => array(), 'actions' => array() ), 403 );
		}
		$sec = Zen_Security::check( $p, 'chat', 30, 600, 0 );
		if ( is_wp_error( $sec ) ) {
			return $send( array( 'reply' => zen_t( 'ai_error', $lang ), 'chips' => array(), 'actions' => self::fallback_actions( $lang ) ), 429 );
		}
		$msgs = array();
		foreach ( array_slice( (array) ( $p['messages'] ?? array() ), -12 ) as $m ) {
			$role = ( isset( $m['role'] ) && 'assistant' === $m['role'] ) ? 'assistant' : 'user';
			$text = mb_substr( wp_strip_all_tags( (string) ( $m['content'] ?? '' ) ), 0, 2000 );
			if ( '' !== trim( $text ) ) {
				$msgs[] = array( 'role' => $role, 'content' => $text );
			}
		}
		// A modellek felhasználói üzenettel kezdenek.
		while ( $msgs && 'user' !== $msgs[0]['role'] ) {
			array_shift( $msgs );
		}
		if ( ! $msgs ) {
			return $send( array( 'reply' => zen_t( 'ai_greeting', $lang ), 'chips' => self::chips( $lang ), 'actions' => array() ) );
		}
		$sid  = preg_replace( '/[^a-z0-9]/i', '', (string) ( $p['sid'] ?? '' ) );
		$page = esc_url_raw( (string) ( $p['page'] ?? '' ) );

		$system = trim( (string) Zen_Settings::get( 'ai.system_prompt', '' ) ) ?: self::default_prompt();
		$system .= "\n\nMai dátum: " . wp_date( 'Y-m-d (l)' ) . ' – időzóna: Europe/Budapest. Az oldal nyelve: ' . $lang . '. A látogató jelenlegi oldala: ' . $page . "\n\n" . self::context( $lang );

		$raw = self::call_provider( $system, $msgs );
		if ( is_wp_error( $raw ) || '' === trim( (string) $raw ) ) {
			$raw = self::offline_answer( end( $msgs )['content'], $lang );
		}
		list( $text, $actions ) = self::parse_actions( (string) $raw, $lang );
		self::log( $sid, $lang, $page, end( $msgs )['content'], $text );
		return $send( array( 'reply' => $text, 'chips' => self::chips( $lang, $text ), 'actions' => $actions ) );
	}

	/** Szolgáltató hívása. */
	protected static function call_provider( $system, $msgs ) {
		$provider = Zen_Settings::get( 'ai.provider', 'anthropic' );
		$key      = Zen_Security::decrypt( (string) Zen_Settings::get( 'ai.api_key', '' ) );
		if ( '' === $key ) {
			return new WP_Error( 'nokey', 'No API key' );
		}
		$model = trim( (string) Zen_Settings::get( 'ai.model', '' ) );
		$temp  = (float) Zen_Settings::get( 'ai.temperature', 0.5 );
		$max   = max( 150, (int) Zen_Settings::get( 'ai.max_tokens', 700 ) );
		$args  = array( 'timeout' => 35, 'headers' => array( 'Content-Type' => 'application/json' ) );

		if ( 'openai' === $provider ) {
			$args['headers']['Authorization'] = 'Bearer ' . $key;
			$args['body']                     = wp_json_encode(
				array(
					'model'       => $model ?: 'gpt-4o-mini',
					'temperature' => $temp,
					'max_tokens'  => $max,
					'messages'    => array_merge( array( array( 'role' => 'system', 'content' => $system ) ), $msgs ),
				)
			);
			$res = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $args );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			$j = json_decode( wp_remote_retrieve_body( $res ), true );
			return $j['choices'][0]['message']['content'] ?? new WP_Error( 'api', wp_remote_retrieve_body( $res ) );
		}

		if ( 'gemini' === $provider ) {
			$contents = array();
			foreach ( $msgs as $m ) {
				$contents[] = array( 'role' => 'assistant' === $m['role'] ? 'model' : 'user', 'parts' => array( array( 'text' => $m['content'] ) ) );
			}
			$args['body'] = wp_json_encode(
				array(
					'system_instruction' => array( 'parts' => array( array( 'text' => $system ) ) ),
					'contents'           => $contents,
					'generationConfig'   => array( 'temperature' => $temp, 'maxOutputTokens' => $max ),
				)
			);
			$m   = rawurlencode( $model ?: 'gemini-2.0-flash' );
			$res = wp_remote_post( 'https://generativelanguage.googleapis.com/v1beta/models/' . $m . ':generateContent?key=' . rawurlencode( $key ), $args );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			$j = json_decode( wp_remote_retrieve_body( $res ), true );
			return $j['candidates'][0]['content']['parts'][0]['text'] ?? new WP_Error( 'api', wp_remote_retrieve_body( $res ) );
		}

		// Anthropic Claude (alapértelmezett).
		$args['headers']['x-api-key']         = $key;
		$args['headers']['anthropic-version'] = '2023-06-01';
		$args['body']                         = wp_json_encode(
			array(
				'model'       => $model ?: 'claude-sonnet-5-5',
				'max_tokens'  => $max,
				'temperature' => $temp,
				'system'      => $system,
				'messages'    => $msgs,
			)
		);
		$res = wp_remote_post( 'https://api.anthropic.com/v1/messages', $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$j   = json_decode( wp_remote_retrieve_body( $res ), true );
		$txt = '';
		foreach ( (array) ( $j['content'] ?? array() ) as $c ) {
			if ( 'text' === ( $c['type'] ?? '' ) ) {
				$txt .= $c['text'];
			}
		}
		return '' !== $txt ? $txt : new WP_Error( 'api', wp_remote_retrieve_body( $res ) );
	}

	/** Akció-tokenek → gombok. */
	public static function parse_actions( $text, $lang ) {
		$actions = array();
		$g       = Zen_Settings::get( 'general' );
		$labels  = array( 'contact' => 'ai_action_contact', 'booking' => 'ai_action_booking', 'apartments' => 'ai_action_apartments', 'gallery' => 'ai_action_gallery', 'feedback' => 'ai_action_feedback', 'help' => 'nav_help' );
		$text    = preg_replace_callback(
			'/\[\[(BOOK|PAGE|CALL|MAIL|MAP)([^\]]*)\]\]/i',
			function ( $m ) use ( &$actions, $lang, $g, $labels ) {
				$type = strtoupper( $m[1] );
				parse_str( str_replace( ' ', '&', trim( $m[2] ) ), $a );
				if ( 'BOOK' === $type ) {
					$q = array();
					foreach ( array( 'from', 'to' ) as $k ) {
						if ( ! empty( $a[ $k ] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $a[ $k ] ) ) {
							$q[ $k ] = $a[ $k ];
						}
					}
					if ( ! empty( $a['adults'] ) ) {
						$q['adults'] = max( 1, min( 9, (int) $a['adults'] ) );
					}
					if ( ! empty( $a['apt'] ) && zen_apartment( sanitize_key( $a['apt'] ) ) ) {
						$q['apt'] = sanitize_key( $a['apt'] );
					}
					$actions[] = array( 'type' => 'book', 'url' => add_query_arg( $q, zen_page_url( 'booking', $lang ) ), 'label' => zen_t( $q ? 'ai_action_book' : 'ai_action_booking', $lang ) );
				} elseif ( 'PAGE' === $type && ! empty( $a['key'] ) ) {
					$k = sanitize_key( $a['key'] );
					$label = isset( $labels[ $k ] ) ? zen_t( $labels[ $k ], $lang ) : ( 0 === strpos( $k, 'apt_' ) ? zen_apt_name( substr( $k, 4 ), $lang ) : ucfirst( $k ) );
					$actions[] = array( 'type' => 'page', 'url' => zen_page_url( $k, $lang ), 'label' => $label );
				} elseif ( 'CALL' === $type ) {
					$actions[] = array( 'type' => 'call', 'url' => 'tel:' . preg_replace( '/[^+0-9]/', '', $g['phone'] ), 'label' => zen_t( 'ai_action_call', $lang ) . ' ' . $g['phone'] );
				} elseif ( 'MAIL' === $type ) {
					$actions[] = array( 'type' => 'mail', 'url' => 'mailto:' . $g['email'], 'label' => zen_t( 'ai_action_mail', $lang ) );
				} elseif ( 'MAP' === $type ) {
					$actions[] = array( 'type' => 'map', 'url' => $g['google_maps'], 'label' => zen_t( 'ai_action_map', $lang ) );
				}
				return '';
			},
			$text
		);
		$uniq = array();
		foreach ( $actions as $a ) {
			$uniq[ $a['url'] ] = $a;
		}
		return array( trim( preg_replace( "/\n{3,}/", "\n\n", $text ) ), array_slice( array_values( $uniq ), 0, 3 ) );
	}

	public static function chips( $lang, $reply = '' ) {
		$all = array( 'ai_chip_availability', 'ai_chip_sights', 'ai_chip_apartment', 'ai_chip_parking', 'ai_chip_checkin', 'ai_chip_events', 'ai_chip_daytrip' );
		shuffle( $all );
		$out = array();
		foreach ( array_slice( $all, 0, 4 ) as $k ) {
			$out[] = zen_t( $k, $lang );
		}
		return $out;
	}

	protected static function fallback_actions( $lang ) {
		$g = Zen_Settings::get( 'general' );
		return array(
			array( 'type' => 'call', 'url' => 'tel:' . preg_replace( '/[^+0-9]/', '', $g['phone'] ), 'label' => zen_t( 'ai_action_call', $lang ) ),
			array( 'type' => 'page', 'url' => zen_page_url( 'contact', $lang ), 'label' => zen_t( 'ai_action_contact', $lang ) ),
		);
	}

	/** Kulcsszavas keresés a tudásbázisban (offline mód) – legfeljebb 3 találat, felsorolásként. */
	public static function kb_search( $ql, $lang ) {
		$kb = self::kb();
		if ( ! $kb ) {
			return '';
		}
		$words = array_filter(
			preg_split( '/[^\p{L}\p{N}]+/u', $ql ),
			function ( $w ) {
				return mb_strlen( $w ) >= 4 && ! preg_match( '/^(veszpr|apartm|szállá|szalla|ajánl|ajanl|hol |where|what|nearby|mutasd|lehet|kérem|kerem|szeretn|vannak|there|have|haben|gibt)/u', $w );
			}
		);
		if ( ! $words ) {
			return '';
		}
		$hu   = 'hu' === $lang;
		$hits = array();
		foreach ( array( 'sights', 'daytrips', 'events', 'food', 'practical' ) as $sec ) {
			foreach ( (array) ( $kb[ $sec ] ?? array() ) as $it ) {
				$hay   = mb_strtolower( ( $it['name'] ?? $it['topic'] ?? '' ) . ' ' . ( $it['tags'] ?? '' ) );
				$score = 0;
				foreach ( $words as $w ) {
					// Toldalékok miatt az első 5 betű is elég (pl. „állatkertbe” → „állat”).
					$stem = mb_substr( $w, 0, max( 4, min( 6, mb_strlen( $w ) - 2 ) ) );
					if ( false !== mb_strpos( $hay, $stem ) ) {
						$score++;
					}
				}
				if ( $score ) {
					$name = $it['name'] ?? $it['topic'] ?? '';
					$desc = $hu ? ( $it['desc_hu'] ?? $it['text_hu'] ?? '' ) : ( $it['desc_en'] ?? $it['text_en'] ?? $it['desc_hu'] ?? '' );
					$meta = array_filter( array( isset( $it['km'] ) ? $it['km'] . ' km' : '', $it['drive'] ?? '', $it['month'] ?? '' ) );
					$hits[] = array( $score, '• ' . $name . ( $meta ? ' (' . implode( ', ', $meta ) . ')' : '' ) . ' – ' . $desc );
				}
			}
		}
		if ( ! $hits ) {
			return '';
		}
		usort(
			$hits,
			function ( $a, $b ) {
				return $b[0] <=> $a[0];
			}
		);
		return implode( "\n", array_column( array_slice( $hits, 0, 3 ), 1 ) );
	}

	/** API-kulcs nélküli / hibás esetre: többnyelvű kulcsszavas GYIK-válaszoló. */
	public static function offline_answer( $q, $lang ) {
		$g  = Zen_Settings::get( 'general' );
		$ql = mb_strtolower( $q );
		$hu = 'hu' === $lang;
		$de = 'de' === $lang;
		$rules = array(
			array( '/(check.?in|bejelentkez|érkez|erkez|anreise|ankunft|kulcs|key|schlüssel)/u', $hu ? sprintf( 'Bejelentkezés %s órától, önállóan kulcsszéffel – késő este is gond nélkül. Távozás %s óráig.', $g['checkin'], $g['checkout'] ) : ( $de ? sprintf( 'Check-in ab %s Uhr selbstständig per Schlüsselsafe (auch spät abends), Check-out bis %s Uhr.', $g['checkin'], $g['checkout'] ) : sprintf( 'Check-in from %s, self check-in with a key safe (late arrival is fine). Check-out until %s.', $g['checkin'], $g['checkout'] ) ), '' ),
			array( '/(park|autó|auto|car)/u', $hu ? 'Minden apartmanhoz 1 ingyenes, zárt udvari parkolóhely tartozik, és a Festő utcában is ingyenes a parkolás.' : ( $de ? 'Zu jedem Apartment gehört 1 kostenloser Parkplatz im geschlossenen Hof; auch in der Festő Straße ist das Parken kostenlos.' : 'Each apartment has 1 free parking space in the closed courtyard, and street parking on Festő Street is free too.' ), '' ),
			array( '/(kuty|macsk|kisállat|háziállat|allatot|állatot|állatta|\bpets?\b|\bdogs?\b|\bcats?\b|hund|haustier|katze)/u', $hu ? 'Bár szeretjük az állatokat, apartmanjainkban sajnos nem tudunk kisállatot fogadni.' : ( $de ? 'Leider können wir keine Haustiere aufnehmen.' : 'Sorry, pets are not allowed in our apartments.' ), '' ),
			array( '/(wifi|wi-fi|internet|wlan)/u', $hu ? 'Igen, az egész épületben gyors, ingyenes WiFi van.' : ( $de ? 'Ja, im ganzen Gebäude gibt es schnelles, kostenloses WLAN.' : 'Yes, fast free WiFi is available throughout the building.' ), '' ),
			array( '/(fizet|szép|szep|kártya|kartya|pay|card|zahl|karte|előleg|deposit|anzahlung)/u', $hu ? sprintf( 'Előleg: a teljes összeg %d%%-a, a többit érkezéskor kell rendezni (készpénz vagy átutalás). SZÉP kártyát jelenleg nem fogadunk el.', $g['deposit_pct'] ) : ( $de ? sprintf( 'Anzahlung %d %%, Rest bei Anreise (bar oder Überweisung). SZÉP-Karte wird nicht akzeptiert.', $g['deposit_pct'] ) : sprintf( 'Deposit is %d%% of the total, the rest is paid on arrival (cash or bank transfer). SZÉP card is not accepted.', $g['deposit_pct'] ) ), '' ),
			array( '/(lemond|cancel|storn)/u', $hu ? sprintf( 'Közvetlen foglalásnál az érkezés előtt %d napig díjmentes a lemondás; 15 napon belül 50%%, meg nem jelenés esetén 100%% a kötbér. Lemondás: %s', $g['free_cancel_days'], $g['email_cancel'] ) : ( $de ? sprintf( 'Kostenlose Stornierung bis %d Tage vor Anreise, danach 50 %%, bei Nichterscheinen 100 %%. Stornierung per E-Mail: %s', $g['free_cancel_days'], $g['email_cancel'] ) : sprintf( 'Free cancellation up to %d days before arrival; within 15 days 50%%, no-show 100%%. Please e-mail: %s', $g['free_cancel_days'], $g['email_cancel'] ) ), '[[MAIL]]' ),
			array( '/(cím|cim|hol|where|address|adresse|wo |térkép|map)/u', $hu ? 'Címünk: ' . $g['address_line'] . ' – a belváros pár perc sétára van, csendes kertvárosi utcában.' : ( $de ? 'Adresse: ' . $g['address_line'] . ' – ruhige Lage, wenige Gehminuten von der Innenstadt.' : 'Address: ' . $g['address_line'] . ' – a quiet street, a few minutes’ walk from the centre.' ), '[[MAP]]' ),
			array( '/(szabad|foglal|ár |árak|price|book|availab|frei|buch|preis|időpont)/u', $hu ? 'A szabad időpontokat és az ajánlatkérést a foglalási oldalon találja – pár kattintás az egész.' : ( $de ? 'Freie Termine und Anfragen finden Sie auf der Buchungsseite – nur wenige Klicks.' : 'You can check availability and request a quote on our booking page – it only takes a few clicks.' ), '[[BOOK]]' ),
			array( '/(látnival|latnival|program|mit csin|néz|nez|érdemes|erdemes|kirándul|kirandul|sight|todo|to do|visit|see|sehensw|ausflug|besichtig|balaton|tihany)/u', $hu ? 'Veszprémben ne hagyja ki a Várnegyedet és a Tűztornyot, a Szent Mihály-székesegyházat, a Szent István-völgyhidat, a Séd-völgyet és az állatkertet. Egy fél napos kirándulás Tihanyba (27 km) vagy Balatonfüredre is remek választás!' : ( $de ? 'In Veszprém lohnen sich das Burgviertel mit dem Feuerturm, die St.-Michael-Kathedrale, das Séd-Tal und der Zoo. Ein Ausflug nach Tihany (27 km) am Balaton ist ebenfalls ideal!' : 'In Veszprém don’t miss the Castle District and Fire Tower, St Michael’s Cathedral, the Séd valley and the zoo. A half-day trip to Tihany on Lake Balaton (27 km) is a great idea too!' ), '' ),
		);
		// 1) Tudásbázis: konkrét látnivaló / kirándulás / esemény a kérdés szavai alapján.
		$kb_hit = self::kb_search( $ql, $lang );
		foreach ( $rules as $r ) {
			if ( preg_match( $r[0], $ql ) ) {
				if ( $kb_hit && ( false !== strpos( $r[0], 'látnival' ) || false !== strpos( $r[0], 'térkép' ) ) ) {
					return $kb_hit;
				}
				return $r[1] . ( $r[2] ? "\n" . $r[2] : '' );
			}
		}
		if ( $kb_hit ) {
			return $kb_hit;
		}
		return ( $hu ? 'Szívesen segítek! Írja meg pontosabban kérdését, vagy keressen minket közvetlenül: ' : ( $de ? 'Gerne helfe ich! Schreiben Sie Ihre Frage genauer oder kontaktieren Sie uns direkt: ' : 'Happy to help! Please tell me a bit more, or contact us directly: ' ) ) . $g['phone'] . ' · ' . $g['email'] . "\n[[PAGE key=contact]]";
	}

	/* ------------------------------------------------------------------
	 * Napló (CRM)
	 * ------------------------------------------------------------------ */
	protected static function log( $sid, $lang, $page, $q, $a ) {
		if ( ! Zen_Settings::get( 'ai.log_chats', true ) || ! $sid ) {
			return;
		}
		$existing = get_posts( array( 'post_type' => self::CPT, 'post_status' => 'private', 'meta_key' => '_sid', 'meta_value' => $sid, 'fields' => 'ids', 'numberposts' => 1 ) ); // phpcs:ignore
		$id       = $existing ? (int) $existing[0] : 0;
		if ( ! $id ) {
			$id = wp_insert_post( array( 'post_type' => self::CPT, 'post_status' => 'private', 'post_title' => mb_substr( $q, 0, 80 ) ) );
			if ( ! $id || is_wp_error( $id ) ) {
				return;
			}
			update_post_meta( $id, '_sid', $sid );
			update_post_meta( $id, '_lang', $lang );
			update_post_meta( $id, '_page', $page );
			update_post_meta( $id, '_ip_hash', hash( 'sha256', Zen_Security::ip() . wp_salt() ) );
		}
		$msgs   = (array) get_post_meta( $id, '_messages', true );
		$msgs[] = array( 'r' => 'u', 't' => $q, 'ts' => time() );
		$msgs[] = array( 'r' => 'a', 't' => $a, 'ts' => time() );
		update_post_meta( $id, '_messages', array_slice( $msgs, -80 ) );
		update_post_meta( $id, '_count', count( $msgs ) );
		if ( Zen_Settings::get( 'ai.collect_leads', true ) ) {
			if ( preg_match( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $q, $m ) ) {
				update_post_meta( $id, '_lead_email', sanitize_email( $m[0] ) );
			}
			if ( preg_match( '/(\+?\d[\d\s\/-]{7,}\d)/', $q, $m ) ) {
				update_post_meta( $id, '_lead_phone', sanitize_text_field( $m[1] ) );
			}
		}
		wp_update_post( array( 'ID' => $id ) ); // módosítás ideje frissül
	}

	public static function cleanup() {
		$old = get_posts( array( 'post_type' => self::CPT, 'post_status' => 'any', 'numberposts' => 200, 'fields' => 'ids', 'date_query' => array( array( 'before' => '180 days ago' ) ) ) );
		foreach ( $old as $id ) {
			wp_delete_post( $id, true );
		}
	}

	public static function columns( $c ) {
		return array( 'cb' => $c['cb'] ?? '', 'title' => 'Első kérdés', 'za_lang' => 'Nyelv', 'za_count' => 'Üzenetek', 'za_lead' => 'Lead', 'date' => 'Kezdete' );
	}

	public static function column( $col, $id ) {
		if ( 'za_lang' === $col ) {
			echo esc_html( strtoupper( (string) get_post_meta( $id, '_lang', true ) ) );
		} elseif ( 'za_count' === $col ) {
			echo (int) get_post_meta( $id, '_count', true );
		} elseif ( 'za_lead' === $col ) {
			echo esc_html( trim( get_post_meta( $id, '_lead_email', true ) . ' ' . get_post_meta( $id, '_lead_phone', true ) ) );
		}
	}

	public static function box() {
		add_meta_box(
			'zen_chat_box',
			'Beszélgetés',
			function ( $post ) {
				$msgs = (array) get_post_meta( $post->ID, '_messages', true );
				echo '<div class="zs-chat">';
				foreach ( $msgs as $m ) {
					echo '<div class="zs-chat__m zs-chat__m--' . ( 'u' === ( $m['r'] ?? '' ) ? 'u' : 'a' ) . '"><span>' . nl2br( esc_html( $m['t'] ?? '' ) ) . '</span><small>' . esc_html( wp_date( 'Y-m-d H:i', (int) ( $m['ts'] ?? 0 ) ) ) . '</small></div>';
				}
				echo '</div><p class="description">Oldal: ' . esc_html( get_post_meta( $post->ID, '_page', true ) ) . ' · Lead: ' . esc_html( get_post_meta( $post->ID, '_lead_email', true ) . ' ' . get_post_meta( $post->ID, '_lead_phone', true ) ) . '</p>';
			},
			self::CPT,
			'normal',
			'high'
		);
	}

	/* ------------------------------------------------------------------
	 * Front-end indító + súgóközpont
	 * ------------------------------------------------------------------ */
	public static function launcher() {
		if ( is_admin() || ! Zen_Settings::get( 'ai.enabled', true ) ) {
			return;
		}
		$lang = zen_lang();
		$g    = Zen_Settings::get( 'general' );
		$ava  = Zen_Settings::get( 'ai.avatar.url', '' );
		$keys = array( 'ai_name', 'ai_role', 'ai_open', 'ai_greeting', 'ai_placeholder', 'ai_send', 'ai_typing', 'ai_error', 'ai_reset', 'ai_minimize', 'ai_privacy', 'ai_powered', 'ai_online', 'close' );
		$str  = array();
		foreach ( $keys as $k ) {
			$str[ $k ] = zen_t( $k, $lang );
		}
		$name = Zen_Settings::get( 'ai.name', 'Zen' );
		$cfg  = array(
			'rest'   => esc_url_raw( rest_url( 'zen/v1/chat' ) ),
			'lang'   => $lang,
			'name'   => $name,
			'avatar' => $ava,
			'str'    => $str,
			'chips'  => self::chips( $lang ),
			'phone'  => $g['phone'],
			'email'  => $g['email'],
			'css'    => ZEN_CORE_URL . 'assets/zen-ai.css?v=' . ZEN_CORE_VER . '.' . ( file_exists( ZEN_CORE_DIR . 'assets/zen-ai.css' ) ? filemtime( ZEN_CORE_DIR . 'assets/zen-ai.css' ) : 0 ),
			'js'     => ZEN_CORE_URL . 'assets/zen-ai.js?v=' . ZEN_CORE_VER . '.' . ( file_exists( ZEN_CORE_DIR . 'assets/zen-ai.js' ) ? filemtime( ZEN_CORE_DIR . 'assets/zen-ai.js' ) : 0 ),
			'pos'    => Zen_Settings::get( 'ai.position', 'right' ),
		);
		$mark = $ava ? '<img src="' . esc_url( $ava ) . '" alt="" width="30" height="30">' : '<svg width="26" height="26" viewBox="0 0 32 32" fill="none" aria-hidden="true"><circle cx="16" cy="16" r="12.5" stroke="currentColor" stroke-width="2.2" stroke-dasharray="70 9" stroke-linecap="round"/><path d="M10.5 18.5c1.6 2 3.4 3 5.5 3s3.9-1 5.5-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="13.5" r="1.4" fill="currentColor"/><circle cx="20" cy="13.5" r="1.4" fill="currentColor"/></svg>';
		echo '<script type="application/json" id="zca-config">' . wp_json_encode( $cfg ) . '</script>'; // phpcs:ignore
		echo '<button type="button" class="zca-launcher zca-launcher--' . esc_attr( $cfg['pos'] ) . '" data-zen-ai-open aria-label="' . esc_attr( zen_t( 'ai_open', $lang ) ) . '" style="position:fixed;z-index:80;bottom:max(18px,env(safe-area-inset-bottom));' . ( 'left' === $cfg['pos'] ? 'left' : 'right' ) . ':18px;display:inline-flex;align-items:center;gap:10px;min-height:56px;padding:0 20px 0 14px;border-radius:999px;border:1px solid rgba(255,255,255,.25);background:#0f0d0b;color:#f3d38a;font:700 15px/1 system-ui,sans-serif;box-shadow:0 18px 40px -14px rgba(0,0,0,.55);cursor:pointer"><span class="zca-launcher__mark">' . $mark . '</span><span class="zca-launcher__t">' . esc_html( $name ) . '</span><span class="zca-launcher__dot" aria-hidden="true" style="width:8px;height:8px;border-radius:9px;background:#36c27a"></span></button>'; // phpcs:ignore
		// Apró betöltő: az első kattintásra tölti be a chat CSS+JS-t.
		echo "<script>(function(){var c=JSON.parse(document.getElementById('zca-config').textContent),L=false,Q=null;function load(cb){if(L){cb&&cb();return;}L=true;var l=document.createElement('link');l.rel='stylesheet';l.href=c.css;document.head.appendChild(l);var s=document.createElement('script');s.src=c.js;s.defer=true;s.onload=function(){cb&&cb();};document.body.appendChild(s);}window.ZenAI=window.ZenAI||{open:function(p){Q=p||null;load(function(){window.ZenAI.open(Q);});},ask:function(t){load(function(){window.ZenAI.ask(t);});}};document.addEventListener('click',function(e){var b=e.target.closest('[data-zen-ai-open]');if(!b)return;e.preventDefault();window.ZenAI.open(b.getAttribute('data-zen-ai-prompt'));});})();</script>\n";
	}

	public static function sc_help() {
		$lang = zen_lang();
		$g    = Zen_Settings::get( 'general' );
		$o    = '<div class="zca-help"><form class="zca-help__ask" onsubmit="event.preventDefault();var v=this.q.value.trim();if(v&&window.ZenAI){window.ZenAI.ask(v);this.q.value=\'\';}">';
		$o   .= '<label class="screen-reader-text" for="zca-help-q">' . esc_html( zen_t( 'ai_help_title', $lang ) ) . '</label><span class="zca-help__ico" aria-hidden="true">' . Zen_Media::icon( 'sparkles', 22 ) . '</span>';
		$o   .= '<input id="zca-help-q" name="q" type="text" placeholder="' . esc_attr( zen_t( 'ai_help_ph', $lang ) ) . '" autocomplete="off"><button type="submit" class="zc-btn"><span>' . esc_html( zen_t( 'ai_send', $lang ) ) . '</span>' . Zen_Media::icon( 'send', 18 ) . '</button></form>';
		$o   .= '<p class="zca-help__label">' . esc_html( zen_t( 'ai_help_topics', $lang ) ) . '</p><div class="zca-help__chips">';
		foreach ( array( 'ai_chip_availability', 'ai_chip_checkin', 'ai_chip_parking', 'ai_chip_apartment', 'ai_chip_sights', 'ai_chip_events', 'ai_chip_daytrip' ) as $k ) {
			$o .= '<button type="button" class="zc-pill" data-zen-ai-open data-zen-ai-prompt="' . esc_attr( zen_t( $k, $lang ) ) . '">' . esc_html( zen_t( $k, $lang ) ) . '</button>';
		}
		$o .= '</div><div class="zca-help__human"><p>' . esc_html( zen_t( 'ai_help_contact', $lang ) ) . '</p><div class="zc-btns">';
		$o .= '<a class="zc-btn zc-btn--ghost" href="tel:' . esc_attr( preg_replace( '/[^+0-9]/', '', $g['phone'] ) ) . '">' . Zen_Media::icon( 'phone', 18 ) . '<span>' . esc_html( $g['phone'] ) . '</span></a>';
		$o .= '<a class="zc-btn zc-btn--ghost" href="mailto:' . esc_attr( $g['email'] ) . '">' . Zen_Media::icon( 'mail', 18 ) . '<span>' . esc_html( $g['email'] ) . '</span></a>';
		$o .= '</div></div></div>';
		$o .= '<style>.zca-help{max-width:860px;margin:0 auto;text-align:center}.zca-help__ask{display:flex;align-items:center;gap:8px;padding:8px 8px 8px 20px;border-radius:999px;background:var(--zc-surface);border:1px solid var(--zc-line);box-shadow:var(--zc-shadow)}.zca-help__ask input{flex:1;min-width:0;border:0;background:transparent;color:var(--zc-text);font:500 17px/1.4 var(--zc-font-b);outline:none;min-height:48px}.zca-help__ico{color:var(--zc-accent);display:inline-flex}.zca-help__label{margin:28px 0 12px;font-weight:700;font-size:13px;letter-spacing:.18em;text-transform:uppercase;color:var(--zc-muted)}.zca-help__chips{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}.zca-help__human{margin-top:36px}.zca-help__human .zc-btns{justify-content:center;margin-top:12px}@media(max-width:600px){.zca-help__ask .zc-btn span{display:none}}</style>';
		return $o;
	}
}
