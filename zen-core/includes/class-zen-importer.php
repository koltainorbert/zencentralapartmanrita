<?php
/**
 * Zen Importer – az eredeti oldal teljes tartalmának betöltése (oldalak HU/EN/DE + további nyelvek,
 * blogposztok, adatkezelés), képek letöltése a saját médiatárba.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Importer {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'maybe_auto_import' ) );
		add_action( 'wp_ajax_zen_import', array( __CLASS__, 'ajax_import' ) );
		add_action( 'wp_ajax_zen_sideload', array( __CLASS__, 'ajax_sideload' ) );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'zen import', array( __CLASS__, 'cli_import' ) );
			WP_CLI::add_command( 'zen sideload', array( __CLASS__, 'cli_sideload' ) );
		}
	}

	/** Aktiválás után egyszer automatikusan lefut → azonnal kész oldal. */
	public static function maybe_auto_import() {
		if ( ! get_option( 'zen_needs_import' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		delete_option( 'zen_needs_import' );
		self::run( array( 'force' => false ) );
	}

	public static function ajax_import() {
		check_ajax_referer( 'zen_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		$force = ! empty( $_POST['force'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$only  = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : ''; // phpcs:ignore
		wp_send_json_success( self::run( array( 'force' => $force, 'lang' => $only ) ) );
	}

	public static function ajax_sideload() {
		check_ajax_referer( 'zen_admin', 'nonce' );
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		wp_send_json_success( self::sideload_batch( 8 ) );
	}

	public static function cli_import( $args, $assoc ) {
		$r = self::run( array( 'force' => isset( $assoc['force'] ), 'lang' => $assoc['lang'] ?? '' ) );
		WP_CLI::success( wp_json_encode( $r ) );
	}

	public static function cli_sideload() {
		do {
			$r = self::sideload_batch( 20 );
			WP_CLI::log( wp_json_encode( $r ) );
		} while ( $r['remaining'] > 0 && $r['done'] > 0 );
		WP_CLI::success( 'Kész' );
	}

	/** Seed fájl beolvasása. */
	public static function seed( $lang ) {
		$file = ZEN_CORE_DIR . 'data/seed/' . sanitize_key( $lang ) . '.json';
		if ( ! file_exists( $file ) ) {
			return null;
		}
		$json = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		return is_array( $json ) ? $json : null;
	}

	/** Elérhető seed nyelvek (hu elöl). */
	public static function seed_langs() {
		$out = array();
		foreach ( (array) glob( ZEN_CORE_DIR . 'data/seed/*.json' ) as $f ) {
			$out[] = basename( $f, '.json' );
		}
		usort(
			$out,
			function ( $a, $b ) {
				$d = Zen_I18n::default_lang();
				return ( $a === $d ? -1 : ( $b === $d ? 1 : strcmp( $a, $b ) ) );
			}
		);
		return $out;
	}

	/**
	 * Teljes import.
	 *
	 * @param array $opt force (bool) – meglévő szekciókat is felülírja; lang – csak egy nyelv.
	 */
	public static function run( $opt = array() ) {
		$force  = ! empty( $opt['force'] );
		$only   = $opt['lang'] ?? '';
		$report = array( 'pages_created' => 0, 'pages_updated' => 0, 'pages_skipped' => 0, 'posts' => 0, 'langs' => array() );

		if ( ! get_option( 'permalink_structure' ) ) {
			global $wp_rewrite;
			$wp_rewrite->set_permalink_structure( '/%postname%/' );
		}
		if ( false === get_option( Zen_Settings::OPT_APTS, false ) ) {
			Zen_Settings::save_apartments( Zen_Settings::default_apartments() );
		}

		$def = Zen_I18n::default_lang();
		foreach ( self::seed_langs() as $lang ) {
			if ( $only && $only !== $lang ) {
				continue;
			}
			$seed = self::seed( $lang );
			if ( ! $seed || empty( $seed['pages'] ) ) {
				continue;
			}
			$report['langs'][] = $lang;
			$parent = 0;
			if ( $lang !== $def ) {
				$parent = self::ensure_lang_root( $lang );
			}
			$snips = $seed['snippets'] ?? array();
			$order = 0;
			foreach ( $seed['pages'] as $key => $p ) {
				$order++;
				if ( ! empty( $opt['keys'] ) && ! in_array( $key, (array) $opt['keys'], true ) ) {
					continue; // csak a megadott oldalak frissítése (célzott javításhoz)
				}
				$r = self::import_page( $key, $lang, $p, $snips, $parent, $force, $order );
				$report[ $r ]++;
			}
		}

		if ( ! $only || $only === $def ) {
			$report['posts'] = self::import_posts( $force );
		}

		// Kezdőlap beállítása.
		$home = 0;
		Zen_Content::rebuild_map();
		$home = Zen_Content::page_id( 'home', $def );
		if ( $home ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home );
			update_option( 'page_for_posts', 0 );
		}
		$privacy = Zen_Content::page_id( 'privacy', $def );
		if ( $privacy ) {
			update_option( 'wp_page_for_privacy_policy', $privacy );
		}
		update_option( 'blogname', Zen_Settings::get( 'general.site_name' ) );
		update_option( 'blogdescription', Zen_Settings::get( 'general.tagline' ) );
		update_option( 'zen_import_version', ZEN_CORE_VER );
		update_option( 'zen_import_report', $report + array( 'time' => time() ) );
		flush_rewrite_rules( false );
		do_action( 'zen_imported', $report );
		return $report;
	}

	/** /xx/ szülőoldal. */
	protected static function ensure_lang_root( $lang ) {
		$id = Zen_Content::page_id( 'lang_root', $lang );
		if ( $id ) {
			return $id;
		}
		$existing = get_page_by_path( $lang );
		if ( $existing && 0 === (int) $existing->post_parent ) {
			$id = $existing->ID;
		} else {
			$all = Zen_I18n::all_languages();
			$id  = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => $all[ $lang ]['native'] ?? strtoupper( $lang ),
					'post_name'   => $lang,
					'menu_order'  => 900,
				)
			);
		}
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_zen_key', 'lang_root' );
			update_post_meta( $id, '_zen_lang', $lang );
			Zen_Content::rebuild_map();
			return (int) $id;
		}
		return 0;
	}

	/**
	 * Egy oldal importja. Meglévő oldalt átvesz (ID marad → Rank Math adatok megmaradnak).
	 *
	 * @return string pages_created|pages_updated|pages_skipped
	 */
	protected static function import_page( $key, $lang, $p, $snips, $parent, $force, $order ) {
		$slug = sanitize_title( $p['slug'] ?? $key );
		$id   = Zen_Content::page_id( $key, $lang );

		if ( ! $id ) {
			// Meglévő oldal átvétele útvonal alapján (régi Polylang/Elementor oldal is).
			$path  = $parent ? get_post_field( 'post_name', $parent ) . '/' . $slug : $slug;
			$found = get_page_by_path( $path );
			if ( ! $found && $parent ) {
				$found = get_page_by_path( $slug );
				if ( $found && (int) $found->post_parent !== 0 ) {
					$found = null;
				}
				// Csak akkor vesszük át, ha nincs más nyelvhez rendelve.
				if ( $found && get_post_meta( $found->ID, '_zen_lang', true ) && get_post_meta( $found->ID, '_zen_lang', true ) !== $lang ) {
					$found = null;
				}
			}
			if ( ! $found && ! empty( $p['front'] ) ) {
				$front = (int) get_option( 'page_on_front' );
				if ( $front && ! get_post_meta( $front, '_zen_key', true ) ) {
					$found = get_post( $front );
				}
			}
			if ( $found ) {
				$id = (int) $found->ID;
			}
		}

		$sections = self::expand_sections( $p['sections'] ?? array(), $snips, $lang );
		$created  = false;

		if ( ! $id ) {
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $p['title'] ?? $key,
					'post_name'    => $slug,
					'post_parent'  => $parent,
					'menu_order'   => $order,
					'post_content' => '',
				),
				true
			);
			if ( is_wp_error( $id ) ) {
				return 'pages_skipped';
			}
			$created = true;
		} else {
			$upd = array( 'ID' => $id );
			if ( $parent && (int) get_post_field( 'post_parent', $id ) !== $parent ) {
				$upd['post_parent'] = $parent;
			}
			if ( $force ) {
				$upd['post_title'] = $p['title'] ?? get_the_title( $id );
			}
			if ( count( $upd ) > 1 ) {
				wp_update_post( $upd );
			}
		}

		update_post_meta( $id, '_zen_key', $key );
		update_post_meta( $id, '_zen_lang', $lang );
		// Elementor ne vegye át a renderelést.
		delete_post_meta( $id, '_elementor_edit_mode' );
		update_post_meta( $id, '_wp_page_template', 'default' );

		$had = Zen_Content::has_sections( $id );
		if ( $force || ! $had ) {
			Zen_Content::save_sections( $id, $sections );
		}

		// SEO meta: saját + Rank Math (csak ha üres, vagy force).
		$seo = array(
			'_zen_seo_title'          => $p['seo_title'] ?? '',
			'_zen_seo_desc'           => $p['seo_desc'] ?? '',
			'rank_math_title'         => $p['seo_title'] ?? '',
			'rank_math_description'   => $p['seo_desc'] ?? '',
			'rank_math_focus_keyword' => $p['focus_kw'] ?? '',
		);
		foreach ( $seo as $mk => $mv ) {
			if ( '' === $mv ) {
				continue;
			}
			if ( $force || '' === (string) get_post_meta( $id, $mk, true ) ) {
				update_post_meta( $id, $mk, $mv );
			}
		}
		if ( ! empty( $p['og'] ) ) {
			update_post_meta( $id, '_zen_og', Zen_Media::expand( $p['og'] ) );
			if ( $force || ! get_post_meta( $id, 'rank_math_facebook_image', true ) ) {
				update_post_meta( $id, 'rank_math_facebook_image', Zen_Media::expand( $p['og'] ) );
			}
		}
		if ( ! empty( $p['noindex'] ) ) {
			update_post_meta( $id, 'rank_math_robots', array( 'noindex' ) );
		}

		return $created ? 'pages_created' : ( ( $force || ! $had ) ? 'pages_updated' : 'pages_skipped' );
	}

	/** Snippetek, @aside, html_file, média normalizálás, egyedi ID-k. */
	public static function expand_sections( $list, $snips, $lang ) {
		$out = array();
		foreach ( (array) $list as $i => $s ) {
			if ( isset( $s['ref'] ) && isset( $snips[ $s['ref'] ] ) && is_array( $snips[ $s['ref'] ] ) ) {
				$base = $snips[ $s['ref'] ];
				if ( isset( $s['data'] ) ) {
					$base['data'] = array_merge( $base['data'] ?? array(), $s['data'] );
				}
				if ( isset( $s['style'] ) ) {
					$base['style'] = array_merge( $base['style'] ?? array(), $s['style'] );
				}
				if ( isset( $s['enabled'] ) ) {
					$base['enabled'] = $s['enabled'];
				}
				$s = $base;
			}
			if ( empty( $s['type'] ) ) {
				continue;
			}
			$data = $s['data'] ?? array();
			if ( ! empty( $data['@aside'] ) && isset( $snips['aside'] ) ) {
				unset( $data['@aside'] );
				$data = array_merge( $data, $snips['aside'] );
			}
			if ( ! empty( $data['html_file'] ) ) {
				$f = ZEN_CORE_DIR . 'data/' . basename( $data['html_file'] );
				$data['html'] = file_exists( $f ) ? self::expand_html( (string) file_get_contents( $f ) ) : ''; // phpcs:ignore
				unset( $data['html_file'] );
			}
			$data  = self::normalize_media_tree( $data );
			$style = self::normalize_media_tree( $s['style'] ?? array() );
			$style = wp_parse_args( $style, array( 'bg' => null, 'tone' => 'auto', 'pad' => 'm', 'overlay' => 0.35, 'anchor' => '' ) );
			$out[] = array(
				'id'      => substr( md5( $lang . $s['type'] . $i . wp_json_encode( $data ) ), 0, 8 ),
				'type'    => $s['type'],
				'enabled' => ! isset( $s['enabled'] ) || (bool) $s['enabled'],
				'style'   => $style,
				'data'    => $data,
			);
		}
		return $out;
	}

	/** media/bg/side_badge mezők, gallery és csoport-elemek media objektummá alakítása. */
	public static function normalize_media_tree( $d, $parent = '' ) {
		if ( ! is_array( $d ) ) {
			return $d;
		}
		foreach ( $d as $k => $v ) {
			if ( in_array( $k, array( 'media', 'bg', 'side_badge' ), true ) ) {
				$d[ $k ] = ( null === $v || '' === $v ) ? null : Zen_Media::normalize( $v );
			} elseif ( 'gallery' === $k && is_array( $v ) ) {
				$d[ $k ] = array_map( array( 'Zen_Media', 'normalize' ), $v );
			} elseif ( 'items' === $k && is_array( $v ) && 'groups' === $parent ) {
				$d[ $k ] = array_map(
					function ( $it ) {
						return is_string( $it ) ? array( 'media' => Zen_Media::normalize( $it ) ) : ( isset( $it['media'] ) ? array( 'media' => Zen_Media::normalize( $it['media'] ) ) : $it );
					},
					$v
				);
			} elseif ( is_array( $v ) ) {
				$d[ $k ] = self::normalize_media_tree( $v, is_int( $k ) ? $parent : (string) $k );
			}
		}
		return $d;
	}

	/** HTML-ben "U/" képforrások kibontása. */
	public static function expand_html( $html ) {
		return preg_replace( '~(src|href)="U/~', '$1="' . Zen_Media::ORIGIN, (string) $html );
	}

	/** Blogposztok (csak ha még nem léteznek). */
	protected static function import_posts( $force ) {
		$file = ZEN_CORE_DIR . 'data/posts.json';
		if ( ! file_exists( $file ) ) {
			return 0;
		}
		$list = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore
		$n    = 0;
		foreach ( (array) $list as $p ) {
			$existing = get_page_by_path( $p['slug'], OBJECT, 'post' );
			if ( $existing && ! $force ) {
				update_post_meta( $existing->ID, '_zen_lang', $p['lang'] ?? 'hu' );
				if ( ! has_post_thumbnail( $existing->ID ) ) {
					update_post_meta( $existing->ID, '_zen_featured_url', Zen_Media::expand( $p['image'] ) );
				}
				continue;
			}
			$hf   = ZEN_CORE_DIR . 'data/posts/' . basename( $p['slug'] ) . '.html';
			$html = file_exists( $hf ) ? self::expand_html( (string) file_get_contents( $hf ) ) : ''; // phpcs:ignore
			$arr  = array(
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_title'    => $p['title'],
				'post_name'     => $p['slug'],
				'post_content'  => $html,
				'post_excerpt'  => $p['excerpt'] ?? '',
				'post_date'     => $p['date'],
				'post_date_gmt' => get_gmt_from_date( $p['date'] ),
			);
			if ( $existing ) {
				$arr['ID'] = $existing->ID;
				$id        = wp_update_post( $arr, true );
			} else {
				$id = wp_insert_post( $arr, true );
			}
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_zen_lang', $p['lang'] ?? 'hu' );
			$img = Zen_Media::expand( $p['image'] );
			$att = attachment_url_to_postid( $img );
			if ( $att ) {
				set_post_thumbnail( $id, $att );
			} else {
				update_post_meta( $id, '_zen_featured_url', $img );
			}
			$n++;
		}
		return $n;
	}

	/* ------------------------------------------------------------------
	 * Képek letöltése a saját médiatárba (kötegelve, újrafuttatható)
	 * ------------------------------------------------------------------ */

	/** Minden hivatkozott távoli kép URL-je. */
	public static function collect_remote_media() {
		$urls = array();
		$add  = function ( $m ) use ( &$urls ) {
			if ( is_array( $m ) && ! empty( $m['url'] ) && empty( $m['id'] ) && 'image' === ( $m['type'] ?? 'image' ) ) {
				$urls[ $m['url'] ] = true;
			}
			if ( is_array( $m ) && ! empty( $m['poster'] ) ) {
				$urls[ $m['poster'] ] = true;
			}
		};
		$walk = function ( $v ) use ( &$walk, $add ) {
			if ( ! is_array( $v ) ) {
				return;
			}
			if ( isset( $v['url'] ) && isset( $v['type'] ) ) {
				$add( $v );
				return;
			}
			foreach ( $v as $vv ) {
				$walk( $vv );
			}
		};
		foreach ( Zen_Content::pages() as $p ) {
			$walk( Zen_Content::get_sections( $p['id'] ) );
		}
		$walk( Zen_Settings::apartments() );
		$walk( Zen_Settings::get( 'general' ) );
		$walk( Zen_Settings::get( 'seo' ) );
		$q = get_posts( array( 'post_type' => 'post', 'numberposts' => 200, 'meta_key' => '_zen_featured_url', 'fields' => 'ids' ) ); // phpcs:ignore
		foreach ( $q as $pid ) {
			$urls[ get_post_meta( $pid, '_zen_featured_url', true ) ] = true;
		}
		$home = wp_parse_url( home_url(), PHP_URL_HOST );
		$out  = array();
		foreach ( array_keys( $urls ) as $u ) {
			if ( ! $u || ! preg_match( '~^https?://~', $u ) ) {
				continue;
			}
			$out[] = $u;
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Egy köteg letöltése. Ha a fájl már a saját médiatárban van (azonos domain), csak az ID-t köti hozzá.
	 */
	public static function sideload_batch( $limit = 8 ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$map  = get_option( 'zen_media_map', array() );
		$map  = is_array( $map ) ? $map : array();
		$all  = self::collect_remote_media();
		$todo = array_values( array_diff( $all, array_keys( $map ) ) );
		$done = 0;
		$err  = array();
		foreach ( array_slice( $todo, 0, $limit ) as $url ) {
			$id = attachment_url_to_postid( $url );
			if ( ! $id ) {
				$id = self::find_by_filename( basename( wp_parse_url( $url, PHP_URL_PATH ) ) );
			}
			if ( ! $id ) {
				$id = media_sideload_image( $url, 0, null, 'id' );
			}
			if ( is_wp_error( $id ) || ! $id ) {
				$err[]       = $url;
				$map[ $url ] = 0;
			} else {
				$map[ $url ] = (int) $id;
				$done++;
			}
		}
		update_option( 'zen_media_map', $map, false );
		self::apply_media_map( $map );
		return array(
			'done'      => $done,
			'errors'    => $err,
			'remaining' => max( 0, count( $todo ) - $limit ),
			'total'     => count( $all ),
		);
	}

	protected static function find_by_filename( $file ) {
		global $wpdb;
		if ( ! $file ) {
			return 0;
		}
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1", '%' . $wpdb->esc_like( $file ) ) ); // phpcs:ignore
		return (int) $id;
	}

	/** URL → attachment ID csere minden tárolt helyen. */
	public static function apply_media_map( $map ) {
		$fix = function ( $v ) use ( &$fix, $map ) {
			if ( ! is_array( $v ) ) {
				return $v;
			}
			if ( isset( $v['url'], $v['type'] ) && empty( $v['id'] ) && ! empty( $map[ $v['url'] ] ) ) {
				$v['id']  = (int) $map[ $v['url'] ];
				$local    = wp_get_attachment_url( $v['id'] );
				$v['url'] = $local ? $local : $v['url'];
				return $v;
			}
			foreach ( $v as $k => $vv ) {
				$v[ $k ] = $fix( $vv );
			}
			return $v;
		};
		foreach ( Zen_Content::pages() as $p ) {
			$s = Zen_Content::get_sections( $p['id'] );
			$n = $fix( $s );
			if ( $n !== $s ) {
				Zen_Content::save_sections( $p['id'], $n );
			}
		}
		$apts = Zen_Settings::apartments();
		$na   = $fix( $apts );
		if ( $na !== $apts ) {
			Zen_Settings::save_apartments( $na );
		}
		$g  = Zen_Settings::get( 'general' );
		$ng = $fix( $g );
		if ( $ng !== $g ) {
			Zen_Settings::update( array( 'general' => $ng ) );
		}
		$q = get_posts( array( 'post_type' => 'post', 'numberposts' => 200, 'meta_key' => '_zen_featured_url', 'fields' => 'ids' ) ); // phpcs:ignore
		foreach ( $q as $pid ) {
			$u = get_post_meta( $pid, '_zen_featured_url', true );
			if ( ! empty( $map[ $u ] ) ) {
				set_post_thumbnail( $pid, (int) $map[ $u ] );
				delete_post_meta( $pid, '_zen_featured_url' );
			}
		}
	}
}
