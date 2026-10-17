<?php
/**
 * Zen Media – kép VAGY videó minden médiahelyen, ikonok.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Media {

	/** Az eredeti oldal feltöltési mappája (seed "U/" rövidítés). */
	const ORIGIN = 'https://zencentralapartments.com/wp-content/uploads/';

	public static function init() {
		add_filter( 'upload_mimes', array( __CLASS__, 'mimes' ) );
	}

	public static function mimes( $m ) {
		$m['webp'] = 'image/webp';
		$m['avif'] = 'image/avif';
		$m['webm'] = 'video/webm';
		return $m;
	}

	/** "U/fájl" → teljes URL. */
	public static function expand( $url ) {
		$url = (string) $url;
		if ( 0 === strpos( $url, 'U/' ) ) {
			return self::ORIGIN . substr( $url, 2 );
		}
		return $url;
	}

	/** Bármilyen bemenet → media objektum. */
	public static function normalize( $m ) {
		if ( empty( $m ) ) {
			return array( 'type' => 'image', 'url' => '', 'id' => 0, 'poster' => '', 'alt' => '' );
		}
		if ( is_string( $m ) ) {
			$m = array( 'url' => $m );
		}
		$url  = self::expand( $m['url'] ?? '' );
		$type = $m['type'] ?? ( self::is_video_url( $url ) ? 'video' : 'image' );
		return array(
			'type'   => 'video' === $type ? 'video' : 'image',
			'url'    => $url,
			'id'     => (int) ( $m['id'] ?? 0 ),
			'poster' => self::expand( $m['poster'] ?? '' ),
			'alt'    => (string) ( $m['alt'] ?? '' ),
		);
	}

	public static function is_video_url( $url ) {
		return (bool) preg_match( '~\.(mp4|webm|mov|m4v)(\?|$)|youtube\.com|youtu\.be|vimeo\.com~i', (string) $url );
	}

	public static function youtube_id( $url ) {
		if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', (string) $url, $m ) ) {
			return $m[1];
		}
		return '';
	}

	public static function vimeo_id( $url ) {
		if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', (string) $url, $m ) ) {
			return $m[1];
		}
		return '';
	}

	/** URL (képnél méret szerint; videónál a poster, ha $poster=true). */
	public static function url( $m, $size = 'full', $poster = false ) {
		$m = self::normalize( $m );
		if ( 'video' === $m['type'] ) {
			if ( $poster ) {
				if ( $m['poster'] ) {
					return $m['poster'];
				}
				$yt = self::youtube_id( $m['url'] );
				return $yt ? 'https://i.ytimg.com/vi/' . $yt . '/maxresdefault.jpg' : '';
			}
			return $m['url'];
		}
		if ( $m['id'] ) {
			$u = wp_get_attachment_image_url( $m['id'], $size );
			if ( $u ) {
				return $u;
			}
		}
		return $m['url'];
	}

	/**
	 * HTML kimenet.
	 *
	 * @param array|string $m    media.
	 * @param array        $args class, sizes, eager, cover, autoplay, controls, background, width, height, attrs.
	 */
	public static function html( $m, $args = array() ) {
		$m = self::normalize( $m );
		if ( '' === $m['url'] && ! $m['id'] ) {
			return '';
		}
		$a = wp_parse_args(
			$args,
			array(
				'class'      => '',
				'sizes'      => '100vw',
				'eager'      => false,
				'cover'      => false,
				'autoplay'   => true,
				'controls'   => false,
				'background' => false,
				'size'       => 'full',
				'alt'        => '',
			)
		);
		$class = trim( 'zc-media ' . ( $a['cover'] ? 'zc-media--cover ' : '' ) . $a['class'] );
		$alt   = '' !== $m['alt'] ? $m['alt'] : $a['alt'];

		if ( 'video' === $m['type'] ) {
			return self::video_html( $m, $a, $class, $alt );
		}

		// Kép – médiatárból (srcset) vagy URL.
		if ( $m['id'] && wp_attachment_is_image( $m['id'] ) ) {
			$attr = array(
				'class'    => $class,
				'alt'      => $alt,
				'sizes'    => $a['sizes'],
				'decoding' => 'async',
				'loading'  => $a['eager'] ? 'eager' : 'lazy',
			);
			if ( $a['eager'] ) {
				$attr['fetchpriority'] = 'high';
			}
			return wp_get_attachment_image( $m['id'], $a['size'], false, $attr );
		}
		$w = isset( $a['width'] ) ? ' width="' . (int) $a['width'] . '"' : '';
		$h = isset( $a['height'] ) ? ' height="' . (int) $a['height'] . '"' : '';
		return sprintf(
			'<img src="%1$s" alt="%2$s" class="%3$s" decoding="async" loading="%4$s"%5$s%6$s%7$s>',
			esc_url( $m['url'] ),
			esc_attr( $alt ),
			esc_attr( $class ),
			$a['eager'] ? 'eager' : 'lazy',
			$a['eager'] ? ' fetchpriority="high"' : '',
			$w,
			$h
		);
	}

	protected static function video_html( $m, $a, $class, $alt ) {
		$poster = self::url( $m, 'full', true );
		$yt     = self::youtube_id( $m['url'] );
		$vm     = self::vimeo_id( $m['url'] );
		if ( $yt || $vm ) {
			if ( $a['background'] || $a['autoplay'] ) {
				$src = $yt
					? 'https://www.youtube-nocookie.com/embed/' . $yt . '?autoplay=1&mute=1&loop=1&playlist=' . $yt . '&controls=0&modestbranding=1&playsinline=1&rel=0&showinfo=0'
					: 'https://player.vimeo.com/video/' . $vm . '?background=1&autoplay=1&muted=1&loop=1&dnt=1';
				return sprintf(
					'<div class="%1$s zc-embed zc-embed--bg">%2$s<iframe data-src="%3$s" title="%4$s" allow="autoplay; encrypted-media; picture-in-picture" loading="lazy" tabindex="-1" aria-hidden="true"></iframe></div>',
					esc_attr( $class ),
					$poster ? '<img src="' . esc_url( $poster ) . '" alt="" class="zc-embed__poster" decoding="async"' . ( $a['eager'] ? ' fetchpriority="high"' : ' loading="lazy"' ) . '>' : '',
					esc_url( $src ),
					esc_attr( $alt ?: 'video' )
				);
			}
			$src = $yt ? 'https://www.youtube-nocookie.com/embed/' . $yt . '?autoplay=1&rel=0' : 'https://player.vimeo.com/video/' . $vm . '?autoplay=1&dnt=1';
			return sprintf(
				'<button type="button" class="%1$s zc-embed zc-embed--lite" data-embed="%2$s" aria-label="%3$s">%4$s<span class="zc-embed__play">%5$s</span></button>',
				esc_attr( $class ),
				esc_url( $src ),
				esc_attr( $alt ?: 'Play video' ),
				$poster ? '<img src="' . esc_url( $poster ) . '" alt="" loading="lazy" decoding="async">' : '',
				self::icon( 'play', 28 )
			);
		}
		$type = preg_match( '~\.webm(\?|$)~i', $m['url'] ) ? 'video/webm' : 'video/mp4';
		$attrs = array();
		if ( $a['autoplay'] || $a['background'] ) {
			$attrs[] = 'autoplay muted loop playsinline';
		}
		if ( $a['controls'] && ! $a['background'] ) {
			$attrs[] = 'controls';
		}
		$attrs[] = 'preload="' . ( $a['eager'] ? 'auto' : 'metadata' ) . '"';
		return sprintf(
			'<video class="%1$s" %2$s%3$s aria-label="%4$s" data-zc-video><source src="%5$s" type="%6$s"></video>',
			esc_attr( $class . ' zc-video' ),
			implode( ' ', $attrs ),
			$poster ? ' poster="' . esc_url( $poster ) . '"' : '',
			esc_attr( $alt ?: 'video' ),
			esc_url( $m['url'] ),
			esc_attr( $type )
		);
	}

	/** Inline SVG ikonok (stroke alapú, currentColor). */
	public static function icon( $name, $size = 20 ) {
		$p = array(
			'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
			'mail'     => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/>',
			'pin'      => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
			'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8a0 0 0 0 1 0 0"/>',
			'instagram'=> '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>',
			'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'arrow-left'=> '<path d="M19 12H5M11 6l-6 6 6 6"/>',
			'check'    => '<path d="m5 12 5 5L20 7"/>',
			'star'     => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
			'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
			'moon'     => '<path d="M21 13A9 9 0 1 1 11 3a7 7 0 0 0 10 10z"/>',
			'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
			'menu'     => '<path d="M4 7h16M4 12h16M4 17h10"/>',
			'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
			'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
			'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
			'area'     => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 9h3M4 15h3M9 4v3M15 4v3"/>',
			'bed'      => '<path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7M3 14h18M3 18v2M21 18v2M7 9V6h4v3"/>',
			'wifi'     => '<path d="M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1"/>',
			'parking'  => '<rect x="3" y="3" width="18" height="18" rx="4"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>',
			'snow'     => '<path d="M12 2v20M4 7l16 10M20 7 4 17"/>',
			'tv'       => '<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8"/>',
			'kitchen'  => '<path d="M6 3v8a3 3 0 0 0 6 0V3M9 3v18M17 3c-2 2-2 6 0 8v10"/>',
			'leaf'     => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19 14 10"/>',
			'chat'     => '<path d="M4 5h16v11H9l-5 4z"/>',
			'sparkles' => '<path d="M12 3l1.8 4.7L18.5 9.5 13.8 11.3 12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/>',
			'chevron'  => '<path d="m6 9 6 6 6-6"/>',
			'play'     => '<path d="M8 5v14l11-7z" fill="currentColor"/>',
			'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
			'map'      => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/>',
			'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'shield'   => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
			'heart'    => '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.5-7 10-7 10z"/>',
			'quote'    => '<path d="M7 7h4v6H7c0 2 1 3 3 3v2c-3.5 0-5-2-5-5V9a2 2 0 0 1 2-2zM15 7h4v6h-4c0 2 1 3 3 3v2c-3.5 0-5-2-5-5V9a2 2 0 0 1 2-2z"/>',
			'key'      => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M17 6l3 3M14 9l2 2"/>',
			'lotus'    => '<path d="M12 20c-4 0-8-2-9-6 3 0 6 1 9 4 3-3 6-4 9-4-1 4-5 6-9 6z"/><path d="M12 18c-2-2-3-5-3-8 1.5 1 2.5 2 3 3 .5-1 1.5-2 3-3 0 3-1 6-3 8zM12 13c0-3 0-6 0-9 1.5 2 2 4 2 6"/>',
			'yinyang'  => '<circle cx="12" cy="12" r="9"/><path d="M12 3a4.5 4.5 0 0 1 0 9 4.5 4.5 0 0 0 0 9"/><circle cx="12" cy="7.5" r="1"/><circle cx="12" cy="16.5" r="1"/>',
			'send'     => '<path d="M4 12 20 4l-6 16-3-7z"/>',
			'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
			'grid'     => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
			'zoom'     => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M11 8v6M8 11h6"/>',
			'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
			'card'     => '<rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20"/>',
			'smoke-off'=> '<path d="M3 3l18 18M4 15h9M17 15h3v3M16 12c0-2 2-2 2-4s-2-2-2-4"/>',
			'pet-off'  => '<path d="M3 3l18 18"/><circle cx="7" cy="9" r="1.5"/><circle cx="17" cy="9" r="1.5"/><path d="M8 18c0-3 2-5 4-5s4 2 4 5"/>',
		);
		$path = $p[ $name ] ?? $p['sparkles'];
		$size = (int) $size;
		return '<svg class="zc-ico zc-ico--' . esc_attr( $name ) . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
	}
}
