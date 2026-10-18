<?php
/**
 * Apartman jelvények: a falidíszekből vektorizált logók (Jin-Jang, Lótusz, Relax),
 * accent (arany) színben, sötét üveg jelvényen. Új apartmannál monogram vagy feltöltött kép.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Logos {

	/** Beépített logók: kulcs => [címke, fájl]. Bővíthető: add_filter( 'zen_apt_logos', … ). */
	public static function catalog() {
		return apply_filters(
			'zen_apt_logos',
			array(
				'jinjang' => array( 'Jin-Jang (virágmintás)', ZEN_CORE_DIR . 'assets/logos/jinjang.svg' ),
				'lotus'   => array( 'Lótusz', ZEN_CORE_DIR . 'assets/logos/lotus.svg' ),
				'relax'   => array( 'Relax felirat', ZEN_CORE_DIR . 'assets/logos/relax.svg' ),
			)
		);
	}

	/** Admin legördülő opciói. */
	public static function options() {
		$o = array( array( '', 'Automatikus (ismert apartmannál a saját logó, máskor kép vagy monogram)' ) );
		foreach ( self::catalog() as $k => $v ) {
			$o[] = array( $k, $v[0] );
		}
		$o[] = array( 'monogram', 'Monogram (a név első betűje)' );
		$o[] = array( 'image', 'Feltöltött kép (Ikon / jelvény mező)' );
		$o[] = array( 'none', 'Nincs jelvény' );
		return $o;
	}

	/** Inline SVG a beépített logóból (currentColor). */
	public static function svg( $name, $label = '' ) {
		static $cache = array();
		$cat = self::catalog();
		if ( ! isset( $cat[ $name ] ) ) {
			return '';
		}
		if ( ! isset( $cache[ $name ] ) ) {
			$f               = $cat[ $name ][1];
			$cache[ $name ]  = file_exists( $f ) ? trim( (string) file_get_contents( $f ) ) : ''; // phpcs:ignore
		}
		$s = $cache[ $name ];
		if ( '' === $s ) {
			return '';
		}
		$attr = '' !== $label ? ' role="img" aria-label="' . esc_attr( $label ) . '" focusable="false"' : ' aria-hidden="true" focusable="false"';
		return preg_replace( '/^<svg /', '<svg class="zen-logo zen-logo--' . esc_attr( $name ) . '"' . $attr . ' ', $s, 1 );
	}

	/** Milyen jelvény jár az apartmannak: [típus, érték]. */
	public static function resolve( $apt ) {
		$apt  = (array) $apt;
		$mode = (string) ( $apt['badge'] ?? '' );
		$key  = strtolower( (string) ( $apt['key'] ?? '' ) );
		$cat  = self::catalog();
		$img  = $apt['icon']['url'] ?? '';
		if ( 'none' === $mode ) {
			return array( 'none', '' );
		}
		if ( isset( $cat[ $mode ] ) ) {
			return array( 'logo', $mode );
		}
		if ( 'image' === $mode ) {
			return $img ? array( 'image', $img ) : array( 'monogram', '' );
		}
		if ( 'monogram' === $mode ) {
			return array( 'monogram', '' );
		}
		// Automatikus: ismert kulcs vagy név alapján.
		$name = strtolower( remove_accents( wp_strip_all_tags( is_array( $apt['name'] ?? '' ) ? implode( ' ', $apt['name'] ) : (string) ( $apt['name'] ?? '' ) ) ) );
		$map  = array(
			'jinjang' => array( 'jinjang', 'jin-jang', 'yinyang', 'yin-yang', 'yin yang', 'jin jang' ),
			'lotus'   => array( 'lotus', 'lotusz', 'lótusz' ),
			'relax'   => array( 'relax' ),
		);
		foreach ( $map as $logo => $words ) {
			if ( ! isset( $cat[ $logo ] ) ) {
				continue;
			}
			foreach ( $words as $w ) {
				if ( $key === $w || false !== strpos( $key . ' ' . $name, $w ) ) {
					return array( 'logo', $logo );
				}
			}
		}
		return $img ? array( 'image', $img ) : array( 'monogram', '' );
	}

	/** A kész jelvény HTML (kártyára, részletoldalra). */
	public static function badge( $apt, $class = '' ) {
		$apt   = (array) $apt;
		$label = function_exists( 'zen_apt_name' ) && ! empty( $apt['key'] ) ? zen_apt_name( $apt['key'] ) : '';
		list( $type, $val ) = self::resolve( $apt );
		$cls = trim( 'zen-badge ' . $class );
		$sz  = isset( $apt['badge_size'] ) && '' !== $apt['badge_size'] ? max( 32, min( 140, (int) $apt['badge_size'] ) ) : 0;
		$sty = $sz ? ' style="--zb-size:' . $sz . 'px"' : '';
		switch ( $type ) {
			case 'logo':
				return '<span class="' . esc_attr( $cls . ' zen-badge--' . $val ) . '"' . $sty . '>' . self::svg( $val, $label ) . '</span>';
			case 'image':
				return '<span class="' . esc_attr( $cls . ' zen-badge--image' ) . '"' . $sty . '><img src="' . esc_url( $val ) . '" alt="' . esc_attr( $label ) . '" loading="lazy" width="64" height="64"></span>';
			case 'monogram':
				$src = '' !== $label ? $label : (string) ( $apt['key'] ?? '' );
				$src = trim( preg_replace( '/\b(apartman|apartment|apartmán|apartament|appartamento|appartement|apartma|studio|stúdió)\b/iu', '', $src ) );
				$ch  = mb_strtoupper( mb_substr( '' !== $src ? $src : 'Z', 0, 1, 'UTF-8' ), 'UTF-8' );
				return '<span class="' . esc_attr( $cls . ' zen-badge--monogram' ) . '"' . $sty . ' role="img" aria-label="' . esc_attr( $label ) . '"><span aria-hidden="true">' . esc_html( $ch ) . '</span></span>';
		}
		return '';
	}
}
