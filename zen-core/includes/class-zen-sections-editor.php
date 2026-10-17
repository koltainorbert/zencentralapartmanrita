<?php
/**
 * Zen szekció-szerkesztő – az oldalak teljes tartalma (szövegek, képek, videók, gombok, sorrend) vizuálisan.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Sections_Editor {

	public static function init() {
		add_action( 'add_meta_boxes_page', array( __CLASS__, 'box' ) );
		add_action( 'save_post_page', array( __CLASS__, 'save' ), 10, 2 );
		add_filter( 'use_block_editor_for_post', array( __CLASS__, 'classic_for_zen' ), 20, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'edit_form_after_title', array( __CLASS__, 'hint' ) );
		add_action( 'wp_ajax_zen_copy_sections', array( __CLASS__, 'ajax_copy' ) );
	}

	public static function is_zen( $post ) {
		return $post && 'page' === $post->post_type && ( get_post_meta( $post->ID, '_zen_key', true ) || '' !== (string) get_post_meta( $post->ID, '_zen_sections', true ) );
	}

	/** Zen oldalakon klasszikus szerkesztő + szekció-szerkesztő (a Gutenberg nem kell). */
	public static function classic_for_zen( $use, $post ) {
		return self::is_zen( $post ) ? false : $use;
	}

	public static function hint( $post ) {
		if ( ! self::is_zen( $post ) ) {
			return;
		}
		echo '<style>#postdivrich{display:none}</style>';
		$tr = zen_translations( $post->ID );
		echo '<div class="zs-hint"><b>Zen oldal</b> · kulcs: <code>' . esc_html( get_post_meta( $post->ID, '_zen_key', true ) ) . '</code> · nyelv: <code>' . esc_html( get_post_meta( $post->ID, '_zen_lang', true ) ) . '</code>';
		if ( $tr ) {
			echo ' · fordítások: ';
			foreach ( $tr as $l => $u ) {
				$id = Zen_Content::page_id( get_post_meta( $post->ID, '_zen_key', true ), $l );
				echo '<a class="zs-chip' . ( $id === $post->ID ? ' is-on' : '' ) . '" href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( strtoupper( $l ) ) . '</a> ';
			}
		}
		echo ' · <a href="' . esc_url( get_permalink( $post ) ) . '" target="_blank">Megtekintés ↗</a> · SEO cím/leírás: ' . ( Zen_SEO::rank_math() ? 'Rank Math doboz (lent)' : 'lent a „Zen SEO” dobozban' ) . '</div>';
	}

	public static function box( $post ) {
		add_meta_box( 'zen_sections', 'Zen szekciók – az oldal teljes tartalma', array( __CLASS__, 'render' ), 'page', 'normal', 'high' );
		if ( ! Zen_SEO::rank_math() ) {
			add_meta_box( 'zen_seo', 'Zen SEO', array( __CLASS__, 'seo_box' ), 'page', 'normal', 'default' );
		}
	}

	public static function render( $post ) {
		wp_nonce_field( 'zen_sections_save', 'zen_sections_nonce' );
		$json = wp_json_encode( Zen_Content::get_sections( $post->ID ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		echo '<textarea name="zen_sections_json" id="zen-sections-json" hidden>' . esc_textarea( $json ) . '</textarea>';
		echo '<div id="zen-sections-app" class="zs-sections"><p class="zs-muted">Betöltés…</p></div>';
		if ( ! self::is_zen( $post ) ) {
			echo '<p class="zs-help">Ha ehhez az oldalhoz szekciót adsz hozzá, a ZEN Central téma a szekciókat jeleníti meg a klasszikus tartalom helyett.</p>';
		}
	}

	public static function seo_box( $post ) {
		echo '<p><label class="zs-label">SEO cím (≤ 60 karakter)<input class="zs-input" type="text" name="zen_seo_title" value="' . esc_attr( get_post_meta( $post->ID, '_zen_seo_title', true ) ) . '" maxlength="90"></label></p>';
		echo '<p><label class="zs-label">Meta leírás (≤ 158 karakter)<textarea class="zs-input" name="zen_seo_desc" rows="3" maxlength="300">' . esc_textarea( get_post_meta( $post->ID, '_zen_seo_desc', true ) ) . '</textarea></label></p>';
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['zen_sections_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zen_sections_nonce'] ) ), 'zen_sections_save' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_page', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( isset( $_POST['zen_sections_json'] ) ) {
			$arr = json_decode( wp_unslash( $_POST['zen_sections_json'] ), true ); // phpcs:ignore -- clean_tree tisztítja
			if ( is_array( $arr ) ) {
				Zen_Content::save_sections( $post_id, $arr );
			}
		}
		if ( isset( $_POST['zen_seo_title'] ) ) {
			update_post_meta( $post_id, '_zen_seo_title', sanitize_text_field( wp_unslash( $_POST['zen_seo_title'] ) ) );
		}
		if ( isset( $_POST['zen_seo_desc'] ) ) {
			update_post_meta( $post_id, '_zen_seo_desc', sanitize_textarea_field( wp_unslash( $_POST['zen_seo_desc'] ) ) );
		}
	}

	public static function assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'page' !== $screen->post_type ) {
			return;
		}
		$post = get_post();
		$v    = ZEN_CORE_VER . '.' . ( file_exists( ZEN_CORE_DIR . 'admin/sections.js' ) ? filemtime( ZEN_CORE_DIR . 'admin/sections.js' ) : 0 );
		wp_enqueue_media();
		wp_enqueue_script( 'zen-fields', ZEN_CORE_URL . 'admin/zen-fields.js', array(), $v, true );
		wp_enqueue_script( 'zen-sections', ZEN_CORE_URL . 'admin/sections.js', array( 'zen-fields' ), $v, true );
		$apts = array();
		foreach ( zen_apartments() as $a ) {
			$apts[] = array( $a['key'], zen_apt_name( $a['key'], 'hu' ) );
		}
		$tr = array();
		if ( $post ) {
			$key = get_post_meta( $post->ID, '_zen_key', true );
			foreach ( Zen_I18n::enabled() as $l => $m ) {
				$id = $key ? Zen_Content::page_id( $key, $l ) : 0;
				if ( $id && $id !== $post->ID ) {
					$tr[] = array( 'id' => $id, 'lang' => $l, 'name' => $m['native'] );
				}
			}
		}
		wp_add_inline_script(
			'zen-sections',
			'window.ZenSections=' . wp_json_encode(
				array(
					'apartments' => $apts,
					'keys'       => Zen_Content::keys(),
					'ajax'       => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'zen_copy' ),
					'post'       => $post ? $post->ID : 0,
					'trans'      => $tr,
				)
			) . ';',
			'before'
		);
	}

	public static function ajax_copy() {
		check_ajax_referer( 'zen_copy', 'nonce' );
		$from = isset( $_POST['from'] ) ? (int) $_POST['from'] : 0;
		if ( ! current_user_can( 'edit_page', $from ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		wp_send_json_success( Zen_Content::get_sections( $from ) );
	}
}
