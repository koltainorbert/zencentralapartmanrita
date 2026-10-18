<?php
/**
 * Sablon segédfüggvények.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------
 * Skin (10 megjelenési forma) → CSS változók
 * ------------------------------------------------------------------ */
function zc_skin_css() {
	$skins = function_exists( 'zen_skins' ) ? zen_skins() : array();
	$key   = zc_opt( 'design.skin', 'zen-sand' );
	$skin  = $skins[ $key ] ?? ( $skins['zen-sand'] ?? null );
	if ( ! $skin ) {
		$skin = array(
			'h' => 'Fraunces', 'b' => 'Manrope',
			'light' => array( 'bg' => '#F7F3EC', 'surface' => '#FFFFFF', 'surface2' => '#EFE8DC', 'text' => '#1F1B16', 'muted' => '#6B6358', 'line' => '#E3DACB', 'accent' => '#A9844C', 'accent2' => '#6E8B74', 'ink' => '#FFFFFF' ),
			'dark'  => array( 'bg' => '#110F0C', 'surface' => '#1B1814', 'surface2' => '#25211B', 'text' => '#F3EDE3', 'muted' => '#A79E90', 'line' => '#332D25', 'accent' => '#D6B47C', 'accent2' => '#93B39A', 'ink' => '#17130D' ),
		);
	}
	$fh  = zc_opt( 'design.font_heading', $skin['h'] ) ?: $skin['h'];
	$fb  = zc_opt( 'design.font_body', $skin['b'] ) ?: $skin['b'];
	$acc = trim( (string) zc_opt( 'design.accent_override', '' ) );
	$rad = (int) zc_opt( 'design.radius', 22 );
	$con = (int) zc_opt( 'design.container', 1240 );

	$vars = function ( $p, $dark ) use ( $acc ) {
		$a = $acc && preg_match( '/^#[0-9a-f]{3,8}$/i', $acc ) ? $acc : $p['accent'];
		return sprintf(
			'--zc-bg:%1$s;--zc-surface:%2$s;--zc-surface-2:%3$s;--zc-text:%4$s;--zc-muted:%5$s;--zc-line:%6$s;--zc-accent:%7$s;--zc-accent-2:%8$s;--zc-accent-ink:%9$s;--zc-glass:%10$s;--zc-glass-line:%11$s;--zc-shadow:%12$s;color-scheme:%13$s;',
			$p['bg'], $p['surface'], $p['surface2'], $p['text'], $p['muted'], $p['line'], $a, $p['accent2'], $p['ink'],
			$dark ? 'rgba(20,18,16,.62)' : 'rgba(255,255,255,.62)',
			$dark ? 'rgba(255,255,255,.08)' : 'rgba(255,255,255,.55)',
			$dark ? '0 24px 60px -24px rgba(0,0,0,.65)' : '0 24px 60px -28px rgba(31,27,22,.28)',
			$dark ? 'dark' : 'light'
		);
	};
	$font = function ( $f ) {
		$serif = in_array( $f, array( 'Fraunces', 'Cormorant Garamond', 'Playfair Display', 'DM Serif Display', 'Instrument Serif', 'Lora' ), true );
		return "'" . str_replace( "'", '', $f ) . "'," . ( $serif ? 'Georgia,serif' : 'system-ui,-apple-system,Segoe UI,Roboto,sans-serif' );
	};
	if ( 'system' === zc_opt( 'design.font_source', 'bunny' ) ) {
		$fhs = 'ui-serif,Georgia,serif';
		$fbs = 'system-ui,-apple-system,Segoe UI,Roboto,sans-serif';
	} else {
		$fhs = $font( $fh );
		$fbs = $font( $fb );
	}
	$common = sprintf( '--zc-font-h:%s;--zc-font-b:%s;--zc-radius:%dpx;--zc-radius-s:%dpx;--zc-container:%dpx;', $fhs, $fbs, $rad, max( 10, (int) round( $rad * 0.62 ) ), $con );

	$css  = ':root{' . $common . $vars( $skin['light'], false ) . '}';
	$css .= ':root[data-theme="dark"]{' . $vars( $skin['dark'], true ) . '}';
	$custom = (string) zc_opt( 'design.custom_css', '' );
	if ( $custom ) {
		$css .= wp_strip_all_tags( $custom );
	}
	return $css;
}

/** Fejléc eszközök: villanásmentes témaváltás, betűk, skin. */
function zc_head_assets() {
	$mode = zc_opt( 'design.mode_default', 'auto' );
	// Villanásgátló: a localStorage vagy a rendszerbeállítás alapján még festés előtt beállítja a témát.
	echo "<script>(function(){try{var d=document.documentElement,s=localStorage.getItem('zc-theme'),m=" . wp_json_encode( $mode ) . ";var t=s||(m==='auto'?(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'):m);d.setAttribute('data-theme',t);}catch(e){}})();</script>\n";
	$src = zc_opt( 'design.font_source', 'bunny' );
	if ( 'system' !== $src ) {
		$skins = function_exists( 'zen_skins' ) ? zen_skins() : array();
		$skin  = $skins[ zc_opt( 'design.skin', 'zen-sand' ) ] ?? array( 'h' => 'Fraunces', 'b' => 'Manrope' );
		$fh    = zc_opt( 'design.font_heading', '' ) ?: $skin['h'];
		$fb    = zc_opt( 'design.font_body', '' ) ?: $skin['b'];
		$fams  = array_unique( array( $fh, $fb ) );
		if ( 'google' === $src ) {
			$q = implode( '&', array_map( fn( $f ) => 'family=' . str_replace( ' ', '+', $f ) . ':wght@400;500;600;700', $fams ) );
			echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
			echo '<link rel="stylesheet" href="' . esc_url( 'https://fonts.googleapis.com/css2?' . $q . '&display=swap' ) . '">' . "\n";
		} else {
			$q = implode( '|', array_map( fn( $f ) => strtolower( str_replace( ' ', '-', $f ) ) . ':400,500,600,700', $fams ) );
			echo '<link rel="preconnect" href="https://fonts.bunny.net" crossorigin>';
			echo '<link rel="stylesheet" href="' . esc_url( 'https://fonts.bunny.net/css?family=' . $q . '&display=swap' ) . '">' . "\n";
		}
	}
	echo '<style id="zc-skin">' . zc_skin_css() . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<meta name="theme-color" content="' . esc_attr( '#F7F3EC' ) . '" media="(prefers-color-scheme: light)"><meta name="theme-color" content="#110F0C" media="(prefers-color-scheme: dark)">' . "\n";

	// Hero kép előtöltése (LCP).
	if ( is_page() && function_exists( 'zen_sections' ) ) {
		$secs = zen_sections( get_queried_object_id() );
		if ( $secs && 'hero' === $secs[0]['type'] && ! empty( $secs[0]['data']['media'] ) ) {
			$m   = $secs[0]['data']['media'];
			$url = function_exists( 'zen_media_url' ) ? zen_media_url( $m, 'full', 'video' === ( $m['type'] ?? '' ) ) : '';
			if ( $url ) {
				echo '<link rel="preload" as="image" href="' . esc_url( $url ) . '" fetchpriority="high">' . "\n";
			}
		}
	}
}

/* ------------------------------------------------------------------
 * Szekciók renderelése
 * ------------------------------------------------------------------ */
function zc_render_sections( $post_id ) {
	if ( ! function_exists( 'zen_sections' ) ) {
		return false;
	}
	$sections = zen_sections( $post_id );
	if ( ! $sections ) {
		return false;
	}
	// Az oldal összes galéria szekciója egy galériává (egy szűrősor: mind a 3 apartman + ház);
	// az így egymás mellé kerülő azonos CTA-k közül csak egy marad.
	$merged = array();
	$gal    = null;
	foreach ( $sections as $s ) {
		if ( 'gallery' === ( $s['type'] ?? '' ) && ! empty( $s['enabled'] ) ) {
			if ( null !== $gal ) {
				$merged[ $gal ]['data']['groups'] = array_merge( (array) ( $merged[ $gal ]['data']['groups'] ?? array() ), (array) ( $s['data']['groups'] ?? array() ) );
				continue;
			}
			$gal = count( $merged );
		}
		$last = count( $merged ) - 1;
		if ( $last >= 0 && 'cta' === ( $s['type'] ?? '' ) && 'cta' === $merged[ $last ]['type'] && ! empty( $s['enabled'] ) ) {
			continue;
		}
		$merged[] = $s;
	}
	$sections = $merged;
	$i = 0;
	foreach ( $sections as $s ) {
		if ( empty( $s['enabled'] ) ) {
			continue;
		}
		$file = locate_template( 'template-parts/sections/' . sanitize_file_name( $s['type'] ) . '.php' );
		if ( ! $file ) {
			continue;
		}
		$ctx = array( 'lang' => zc_lang(), 'post_id' => $post_id, 'index' => $i );
		$d   = $s['data'];
		ob_start();
		include $file;
		$html = trim( ob_get_clean() );
		if ( '' !== $html ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- a szekció sablonok escape-elnek
			$i++;
		}
	}
	return true;
}

/** Szekció nyitó tag (háttér, tónus, padding, horgony). */
function zc_section_open( $s, $extra_class = '' ) {
	$st    = $s['style'] ?? array();
	$tone  = sanitize_html_class( $st['tone'] ?? 'auto' );
	$pad   = sanitize_html_class( $st['pad'] ?? 'm' );
	$id    = ! empty( $st['anchor'] ) ? sanitize_title( $st['anchor'] ) : 'zc-' . sanitize_html_class( $s['id'] );
	$class = trim( 'zc-sec zc-sec--' . sanitize_html_class( $s['type'] ) . ' tone-' . $tone . ' pad-' . $pad . ' ' . $extra_class );
	$bg    = $st['bg'] ?? null;
	$out   = '<section id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" data-reveal>';
	if ( $bg && ! empty( $bg['url'] ) ) {
		$ov   = isset( $st['overlay'] ) ? max( 0, min( 1, (float) $st['overlay'] ) ) : 0.35;
		$out .= '<div class="zc-sec__bg" aria-hidden="true" style="--ov:' . esc_attr( $ov ) . '">' . zc_media( $bg, array( 'cover' => true, 'background' => true, 'alt' => '' ) ) . '</div>';
	}
	return $out;
}

function zc_section_close() {
	return '</section>';
}

/** Szekció fejléc (kicker + cím + bevezető). */
function zc_head( $d, $tag = 'h2', $center = true ) {
	$k = $d['kicker'] ?? '';
	$t = $d['title'] ?? '';
	if ( '' === $k && '' === $t ) {
		return '';
	}
	$o = '<header class="zc-head' . ( $center ? ' zc-head--center' : '' ) . '">';
	if ( $k ) {
		$o .= '<p class="zc-kicker">' . esc_html( $k ) . '</p>';
	}
	if ( $t ) {
		$o .= '<' . $tag . ' class="zc-title">' . esc_html( $t ) . '</' . $tag . '>';
	}
	return $o . '</header>';
}

/** Gombok. */
function zc_buttons( $buttons, $class = '' ) {
	if ( empty( $buttons ) || ! is_array( $buttons ) ) {
		return '';
	}
	$o = '<div class="zc-btns ' . esc_attr( $class ) . '">';
	foreach ( $buttons as $i => $b ) {
		if ( empty( $b['label'] ) ) {
			continue;
		}
		$style = ( $b['style'] ?? ( 0 === $i ? 'primary' : 'ghost' ) );
		$cls   = 'zc-btn' . ( 'ghost' === $style ? ' zc-btn--ghost' : '' ) . ( 'light' === $style ? ' zc-btn--light' : '' );
		$url   = $b['url'] ?? '#';
		$ext   = preg_match( '~^https?://~', $url ) && false === strpos( $url, wp_parse_url( home_url(), PHP_URL_HOST ) );
		$o    .= '<a class="' . esc_attr( $cls ) . '" href="' . zc_url( $url ) . '"' . ( $ext ? ' target="_blank" rel="noopener"' : '' ) . '><span>' . esc_html( $b['label'] ) . '</span>' . zc_icon( 'arrow', 18 ) . '</a>';
	}
	return $o . '</div>';
}

/** Morzsamenü (BreadcrumbList schema-t a SEO modul adja). */
function zc_breadcrumbs() {
	$home = zc_page_url( 'home' );
	$o    = '<nav class="zc-crumbs" aria-label="Breadcrumb"><ol><li><a href="' . esc_url( $home ) . '">' . esc_html( zc_t( 'breadcrumb_home' ) ) . '</a></li>';
	if ( is_singular( 'post' ) ) {
		$o .= '<li><a href="' . esc_url( zc_page_url( 'blog' ) ) . '">' . esc_html( zc_t( 'nav_blog' ) ) . '</a></li>';
	} elseif ( is_page() ) {
		$key = get_post_meta( get_queried_object_id(), '_zen_key', true );
		if ( 0 === strpos( (string) $key, 'apt_' ) ) {
			$o .= '<li><a href="' . esc_url( zc_page_url( 'apartments' ) ) . '">' . esc_html( zc_t( 'nav_apartments' ) ) . '</a></li>';
		}
	}
	$o .= '<li aria-current="page">' . esc_html( wp_strip_all_tags( get_the_title() ) ) . '</li></ol></nav>';
	return $o;
}

/** WordPress menü (Megjelenés → Menük) elemei az adott nyelvre, ha hozzá van rendelve. */
function zc_wp_menu_items() {
	$lang = zc_lang();
	$locs = get_nav_menu_locations();
	$def  = zc_core() ? Zen_I18n::default_lang() : $lang;
	$loc  = ( $lang !== $def && ! empty( $locs[ 'primary_' . $lang ] ) ) ? 'primary_' . $lang : ( $lang === $def ? 'primary' : '' );
	if ( ! $loc || empty( $locs[ $loc ] ) ) {
		return null;
	}
	$raw = wp_get_nav_menu_items( $locs[ $loc ], array( 'update_post_term_cache' => false ) );
	if ( ! $raw ) {
		return null;
	}
	_wp_menu_item_classes_by_context( $raw );
	$cur_url = untrailingslashit( strtok( home_url( add_query_arg( array() ) ), '?' ) );
	$by_id   = array();
	$top     = array();
	foreach ( $raw as $m ) {
		$is_cur = in_array( 'current-menu-item', (array) $m->classes, true ) || untrailingslashit( $m->url ) === $cur_url;
		$by_id[ $m->ID ] = array(
			'key'      => 'menu-' . $m->ID,
			'label'    => $m->title,
			'url'      => $m->url,
			'current'  => $is_cur,
			'target'   => $m->target,
			'parent'   => (int) $m->menu_item_parent,
			'children' => array(),
		);
	}
	foreach ( $by_id as $id => $it ) {
		if ( $it['parent'] && isset( $by_id[ $it['parent'] ] ) ) {
			$by_id[ $it['parent'] ]['children'][] = $it;
		}
	}
	foreach ( array_keys( $by_id ) as $id ) {
		$p = $by_id[ $id ]['parent'];
		if ( ! $p || ! isset( $by_id[ $p ] ) ) {
			$it            = $by_id[ $id ];
			$it['current'] = $it['current'] || (bool) array_filter( $it['children'], fn( $c ) => $c['current'] );
			$top[]         = $it;
		}
	}
	return $top;
}

/** Navigáció elemei: hozzárendelt WP menü, különben automatikus, nyelvfüggő. */
function zc_nav_items() {
	$wp_menu = zc_wp_menu_items();
	if ( $wp_menu ) {
		return apply_filters( 'zc_nav_items', $wp_menu );
	}
	$items = array(
		array( 'key' => 'home', 'label' => zc_t( 'nav_home' ) ),
		array(
			'key'      => 'apartments',
			'label'    => zc_t( 'nav_apartments' ),
			'children' => array(
				array( 'key' => 'apt_relax', 'label' => function_exists( 'zen_apt_name' ) ? zen_apt_name( 'relax' ) : 'RELAX' ),
				array( 'key' => 'apt_jinjang', 'label' => function_exists( 'zen_apt_name' ) ? zen_apt_name( 'jinjang' ) : 'JIN-JANG' ),
				array( 'key' => 'apt_lotus', 'label' => function_exists( 'zen_apt_name' ) ? zen_apt_name( 'lotus' ) : 'LOTUS' ),
			),
		),
		array( 'key' => 'gallery', 'label' => zc_t( 'nav_gallery' ) ),
		array( 'key' => 'blog', 'label' => zc_t( 'nav_blog' ) ),
		array( 'key' => 'contact', 'label' => zc_t( 'nav_contact' ) ),
	);
	// Csak ami létezik az adott nyelven (blog csak ott, ahol van oldal) – a menüben csak megnyitható elem áll.
	$lang = zc_lang();
	$out  = array();
	foreach ( $items as $it ) {
		if ( zc_core() && 'home' !== $it['key'] && ! Zen_Content::page_id( $it['key'], $lang ) ) {
			continue;
		}
		$it['url']     = zc_page_url( $it['key'] );
		$it['current'] = zc_core() && is_page() && get_queried_object_id() === Zen_Content::page_id( $it['key'], $lang );
		if ( ! empty( $it['children'] ) ) {
			$kids = array();
			foreach ( $it['children'] as $c ) {
				if ( zc_core() && ! Zen_Content::page_id( $c['key'], $lang ) ) {
					continue;
				}
				$c['url']     = zc_page_url( $c['key'] );
				$c['current'] = zc_core() && is_page() && get_queried_object_id() === Zen_Content::page_id( $c['key'], $lang );
				$kids[]       = $c;
			}
			$it['children'] = $kids;
		}
		$out[] = $it;
	}
	return apply_filters( 'zc_nav_items', $out );
}

/** Élő nyelvek az aktuális oldal fordítási URL-jeivel: code => [url, native, hreflang, name]. */
function zc_lang_links() {
	if ( ! zc_core() ) {
		return array();
	}
	$langs = Zen_I18n::live();
	if ( count( $langs ) < 2 ) {
		return array();
	}
	$trans = is_singular() ? zen_translations( get_queried_object_id() ) : array();
	$out   = array();
	foreach ( $langs as $code => $meta ) {
		$out[ $code ] = array(
			'url'      => $trans[ $code ] ?? zen_page_url( 'home', $code ),
			'native'   => $meta['native'],
			'name'     => $meta['name'] ?? $meta['native'],
			'hreflang' => $meta['hreflang'],
		);
	}
	return $out;
}

/** Nyelvváltó gomb. Alapértelmezés: felugró, középre zárt üveg ablak (languages.ui = popup); opcionálisan legördülő. */
function zc_lang_switcher( $variant = 'dropdown' ) {
	$links = zc_lang_links();
	if ( ! $links ) {
		return '';
	}
	$cur = zc_lang();
	$ui  = zc_opt( 'languages.ui', 'popup' );
	if ( 'popup' === $ui || 'inline' === $variant ) {
		$cls = 'zc-lang__btn' . ( 'inline' === $variant ? ' zc-lang__btn--wide' : '' );
		$lab = 'inline' === $variant ? '<span class="zc-lang__native">' . esc_html( $links[ $cur ]['native'] ?? strtoupper( $cur ) ) . '</span>' : '<span>' . esc_html( strtoupper( $cur ) ) . '</span>';
		return '<button type="button" class="' . esc_attr( $cls ) . '" data-lang-open aria-haspopup="dialog" aria-controls="zc-langmodal" aria-label="' . esc_attr( zc_t( 'choose_language' ) ) . '">' . zc_icon( 'globe', 18 ) . $lab . zc_icon( 'chevron', 14 ) . '</button>';
	}
	$o  = '<div class="zc-lang" data-lang><button type="button" class="zc-lang__btn" aria-haspopup="true" aria-expanded="false" aria-label="' . esc_attr( zc_t( 'language' ) ) . '">' . zc_icon( 'globe', 18 ) . '<span>' . esc_html( strtoupper( $cur ) ) . '</span>' . zc_icon( 'chevron', 14 ) . '</button>';
	$o .= '<ul class="zc-lang__list" role="menu">';
	foreach ( $links as $code => $l ) {
		$o .= '<li role="none"><a role="menuitem" hreflang="' . esc_attr( $l['hreflang'] ) . '" lang="' . esc_attr( $l['hreflang'] ) . '" href="' . esc_url( $l['url'] ) . '"' . ( $code === $cur ? ' aria-current="true"' : '' ) . '><b>' . esc_html( strtoupper( $code ) ) . '</b> ' . esc_html( $l['native'] ) . '</a></li>';
	}
	return $o . '</ul></div>';
}

/** Felugró nyelvválasztó ablak (egyszer, a lábléc után): széles, alacsony üveg rács, homályosított háttér. */
function zc_lang_modal() {
	$links = zc_lang_links();
	if ( ! $links ) {
		return '';
	}
	$cur = zc_lang();
	$o   = '<div class="zc-langmodal" id="zc-langmodal" hidden data-langmodal><div class="zc-langmodal__box" role="dialog" aria-modal="true" aria-labelledby="zc-langmodal-t">';
	$o  .= '<header class="zc-langmodal__head"><span class="zc-langmodal__ico" aria-hidden="true">' . zc_icon( 'globe', 22 ) . '</span><p class="zc-langmodal__t" id="zc-langmodal-t">' . esc_html( zc_t( 'choose_language' ) ) . '</p><button type="button" class="zc-icon-btn zc-langmodal__x" data-lang-close aria-label="' . esc_attr( zc_t( 'close' ) ) . '">' . zc_icon( 'close', 20 ) . '</button></header>';
	$o  .= '<ul class="zc-langmodal__grid">';
	foreach ( $links as $code => $l ) {
		$o .= '<li><a class="zc-langmodal__item" hreflang="' . esc_attr( $l['hreflang'] ) . '" lang="' . esc_attr( $l['hreflang'] ) . '" href="' . esc_url( $l['url'] ) . '"' . ( $code === $cur ? ' aria-current="true"' : '' ) . '><b>' . esc_html( strtoupper( $code ) ) . '</b><span>' . esc_html( $l['native'] ) . '</span>' . ( $code === $cur ? zc_icon( 'check', 16 ) : '' ) . '</a></li>';
	}
	return $o . '</ul></div></div>';
}

/** Oldalsó kapcsolat-sáv stílusai (Zen Studio → Design). */
function zc_rail_styles() {
	return array(
		'off'      => 'Kikapcsolva',
		'glass'    => 'Üveg pill – bal oldal',
		'glass-r'  => 'Üveg pill – jobb oldal',
		'dock'     => 'Lebegő dokk – alul középen',
		'minimal'  => 'Minimál ikonsor vonallal',
		'accent'   => 'Kiemelő színű pill',
		'dark'     => 'Sötét pill',
		'tab'      => 'Fül a szélén (rámutatásra nyílik)',
		'bubbles'  => 'Különálló buborékok – jobb oldal',
		'outline'  => 'Körvonalas pill',
		'gradient' => 'Színátmenetes pill',
	);
}

/** Oldalsó kapcsolat-sáv – alapból kikapcsolva, 10 stílus. */
function zc_rail() {
	$style = (string) zc_opt( 'design.rail', 'off' );
	if ( 'off' === $style || ! array_key_exists( $style, zc_rail_styles() ) ) {
		return '';
	}
	$phone = zc_opt( 'general.phone', '' );
	$email = zc_opt( 'general.email', '' );
	$links = array();
	foreach ( array( 'facebook' => 'Facebook', 'instagram' => 'Instagram' ) as $zc_k => $zc_l ) {
		$zc_u = (string) zc_opt( 'general.' . $zc_k, '' );
		if ( '' !== $zc_u ) {
			$links[] = array( $zc_u, $zc_k, $zc_l, true );
		}
	}
	if ( $email ) {
		$links[] = array( 'mailto:' . $email, 'mail', $email, false );
	}
	if ( $phone ) {
		$links[] = array( 'tel:' . preg_replace( '/[^+0-9]/', '', $phone ), 'phone', $phone, false );
	}
	$zc_map = (string) zc_opt( 'general.google_maps', '' );
	if ( '' !== $zc_map ) {
		$links[] = array( $zc_map, 'pin', zc_t( 'open_maps' ), true );
	}
	if ( ! $links ) {
		return '';
	}
	$o = '<aside class="zc-rail zc-rail--' . esc_attr( $style ) . '" aria-label="' . esc_attr( zc_t( 'socials' ) ) . '"><div class="zc-rail__in">';
	foreach ( $links as $l ) {
		$o .= '<a href="' . esc_url( $l[0] ) . '"' . ( $l[3] ? ' target="_blank" rel="noopener"' : '' ) . ' aria-label="' . esc_attr( $l[2] ) . '" title="' . esc_attr( $l[2] ) . '">' . zc_icon( $l[1], 18 ) . '</a>';
	}
	return $o . '</div><span class="zc-rail__label">' . esc_html( zc_t( 'nav_contact' ) ) . '</span></aside>';
}

/** Világos/sötét kapcsoló – felirat nélkül (csak aria), körülötte ≥6px hely. */
function zc_theme_toggle() {
	if ( ! zc_opt( 'design.mode_toggle', true ) ) {
		return '';
	}
	return '<button type="button" class="zc-switch zc-mode" role="switch" aria-checked="false" aria-label="' . esc_attr( zc_t( 'theme_dark' ) ) . '" data-mode-toggle><span class="zc-switch__track"><span class="zc-switch__side zc-switch__side--sun" aria-hidden="true">' . zc_icon( 'sun', 13 ) . '</span><span class="zc-switch__side zc-switch__side--moon" aria-hidden="true">' . zc_icon( 'moon', 13 ) . '</span><span class="zc-switch__thumb">' . zc_icon( 'sun', 14 ) . zc_icon( 'moon', 14 ) . '</span></span></button>';
}

/** Logó (világos/sötét változat). */
function zc_logo( $class = '' ) {
	$logo  = zc_opt( 'general.logo', array() );
	$dark  = zc_opt( 'general.logo_dark', array() );
	$name  = zc_opt( 'general.site_name', get_bloginfo( 'name' ) );
	$url   = is_array( $logo ) ? ( $logo['url'] ?? '' ) : '';
	$durl  = is_array( $dark ) ? ( $dark['url'] ?? '' ) : '';
	$o     = '<a class="zc-logo ' . esc_attr( $class ) . '" href="' . esc_url( zc_page_url( 'home' ) ) . '" aria-label="' . esc_attr( $name ) . '">';
	if ( $url ) {
		$o .= '<img class="zc-logo__img' . ( $durl ? ' zc-logo__img--light' : '' ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( $name ) . '" width="180" height="60" decoding="async">';
		if ( $durl ) {
			$o .= '<img class="zc-logo__img zc-logo__img--dark" src="' . esc_url( $durl ) . '" alt="" width="180" height="60" decoding="async">';
		}
	} else {
		$o .= '<span class="zc-logo__text">' . esc_html( $name ) . '</span>';
	}
	return $o . '</a>';
}

/** Apartman meta chipek (fő, m², hálószoba). */
function zc_apt_meta( $key ) {
	if ( ! function_exists( 'zen_apartment' ) ) {
		return '';
	}
	$a = zen_apartment( $key );
	if ( ! $a ) {
		return '';
	}
	$o  = '<ul class="zc-chips">';
	$o .= '<li>' . zc_icon( 'users', 16 ) . esc_html( zc_t( 'capacity', array( 'n' => (int) $a['capacity'] ) ) ) . '</li>';
	if ( ! empty( $a['size'] ) ) {
		$o .= '<li>' . zc_icon( 'area', 16 ) . esc_html( zc_t( 'size', array( 'n' => rtrim( rtrim( number_format( (float) $a['size'], 1, ',', '' ), '0' ), ',' ) ) ) ) . '</li>';
	}
	$o .= '<li>' . zc_icon( 'bed', 16 ) . esc_html( ! empty( $a['bedrooms'] ) ? zc_t( 'bedrooms', array( 'n' => (int) $a['bedrooms'] ) ) : zc_t( 'studio' ) ) . '</li>';
	return $o . '</ul>';
}

/**
 * Google utcatérkép (alapértelmezés) – útvonaltervezéssel. API-kulccsal a hivatalos Maps Embed API-t használja,
 * kulcs nélkül a kulcs nélküli Google beágyazást. Betöltés: süti-hozzájárulás után magától, előtte egy kattintással
 * (general.map_autoload = consent|always). OpenStreetMap választható (general.map_provider = osm).
 */
function zc_map_cfg() {
	return array(
		'lat'  => (float) zc_opt( 'general.lat', 0 ),
		'lng'  => (float) zc_opt( 'general.lng', 0 ),
		'key'  => trim( (string) zc_opt( 'general.google_maps_key', '' ) ),
		'q'    => (string) zc_opt( 'general.map_query', '' ),
		'auto' => (string) zc_opt( 'general.map_autoload', 'consent' ),
	);
}

function zc_map_src( $dest = '', $mode = 'walking' ) {
	$c      = zc_map_cfg();
	$origin = ( $c['lat'] && $c['lng'] ) ? sprintf( '%.6f,%.6f', $c['lat'], $c['lng'] ) : $c['q'];
	if ( $c['key'] ) {
		return '' === $dest
			? 'https://www.google.com/maps/embed/v1/place?key=' . rawurlencode( $c['key'] ) . '&q=' . rawurlencode( $c['q'] ?: $origin ) . '&zoom=16'
			: 'https://www.google.com/maps/embed/v1/directions?key=' . rawurlencode( $c['key'] ) . '&origin=' . rawurlencode( $origin ) . '&destination=' . rawurlencode( $dest ) . '&mode=' . rawurlencode( $mode );
	}
	return '' === $dest
		? 'https://maps.google.com/maps?q=' . rawurlencode( $c['q'] ?: $origin ) . '&t=m&z=16&hl=' . rawurlencode( zc_lang() ) . '&output=embed'
		: 'https://maps.google.com/maps?saddr=' . rawurlencode( $origin ) . '&daddr=' . rawurlencode( $dest ) . '&dirflg=' . ( 'driving' === $mode ? 'd' : 'w' ) . '&hl=' . rawurlencode( zc_lang() ) . '&output=embed';
}

function zc_map( $query, $class = '' ) {
	$c   = zc_map_cfg();
	$lnk = zc_opt( 'general.google_maps', 'https://maps.google.com/?q=' . rawurlencode( (string) $query ) );
	if ( 'osm' === zc_opt( 'general.map_provider', 'google' ) && $c['lat'] && $c['lng'] ) {
		$d   = 0.0065;
		$src = 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode( sprintf( '%.5f,%.5f,%.5f,%.5f', $c['lng'] - $d * 1.6, $c['lat'] - $d, $c['lng'] + $d * 1.6, $c['lat'] + $d ) ) . '&layer=mapnik&marker=' . rawurlencode( sprintf( '%.5f,%.5f', $c['lat'], $c['lng'] ) );
		return '<div class="zc-map zc-map--osm ' . esc_attr( $class ) . '" data-map="' . esc_url( $src ) . '" data-map-auto="always"><div class="zc-map__ph">' . zc_icon( 'map', 34 ) . '<button type="button" class="zc-btn zc-btn--sm" data-map-load>' . esc_html( zc_t( 'map_load' ) ) . '</button></div><a class="zc-map__open" href="' . esc_url( $lnk ) . '" target="_blank" rel="noopener">' . zc_icon( 'pin', 16 ) . '<span>' . esc_html( zc_t( 'open_maps' ) ) . '</span>' . zc_icon( 'external', 14 ) . '</a></div>';
	}
	$origin = ( $c['lat'] && $c['lng'] ) ? sprintf( '%.6f,%.6f', $c['lat'], $c['lng'] ) : $c['q'];
	return '<div class="zc-map zc-map--google ' . esc_attr( $class ) . '" data-map="' . esc_url( zc_map_src() ) . '" data-map-auto="' . esc_attr( 'always' === $c['auto'] ? 'always' : 'consent' ) . '" data-origin="' . esc_attr( $origin ) . '" data-key="' . esc_attr( $c['key'] ) . '" data-hl="' . esc_attr( zc_lang() ) . '">'
		. '<div class="zc-map__ph">' . zc_icon( 'map', 34 ) . '<button type="button" class="zc-btn zc-btn--sm" data-map-load>' . esc_html( zc_t( 'map_load' ) ) . '</button><small>' . esc_html( zc_t( 'map_note' ) ) . '</small></div>'
		. '<a class="zc-map__open" href="' . esc_url( $lnk ) . '" target="_blank" rel="noopener" data-map-open>' . zc_icon( 'pin', 16 ) . '<span>' . esc_html( zc_t( 'open_maps' ) ) . '</span>' . zc_icon( 'external', 14 ) . '</a></div>';
}

/** Kártya média URL (bejegyzés kép). */
function zc_post_card( $post_id ) {
	$o  = '<article class="zc-post-card" data-reveal><a href="' . esc_url( get_permalink( $post_id ) ) . '" class="zc-post-card__media">';
	$o .= get_the_post_thumbnail( $post_id, 'zc-wide', array( 'loading' => 'lazy', 'decoding' => 'async' ) );
	$o .= '</a><div class="zc-post-card__body"><time datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . esc_html( get_the_date( '', $post_id ) ) . '</time>';
	$o .= '<h3><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></h3>';
	$o .= '<p>' . esc_html( wp_trim_words( get_the_excerpt( $post_id ), 26 ) ) . '</p>';
	$o .= '<a class="zc-link" href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( zc_t( 'read_more' ) ) . zc_icon( 'arrow', 16 ) . '</a></div></article>';
	return $o;
}

/** Olvasási idő (perc). */
function zc_reading_time( $post_id ) {
	$words = count( preg_split( '/\s+/u', trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) ) ) );
	return max( 1, (int) ceil( $words / 220 ) );
}
