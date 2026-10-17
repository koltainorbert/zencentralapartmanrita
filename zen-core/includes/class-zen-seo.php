<?php
/**
 * Zen SEO + AI SEO.
 * - Rank Math aktív: a cím/leírás/OG Rank Mathé; mi a schema @graph-ot bővítjük (rank_math/json_ld).
 * - Rank Math nélkül: teljes saját meta, OG, Twitter, canonical, JSON-LD.
 * - Mindig: hreflang (13 nyelv + x-default), LodgingBusiness + Apartment + FAQPage + ItemList + Speakable,
 *   /llms.txt és /llms-full.txt (AI-asszisztenseknek), AI-bot barát robots.txt, IndexNow ping,
 *   Consent Mode v2 süti-hozzájárulás (Site Kit / GA4 / GTM kompatibilis).
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_SEO {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrites' ) );
		add_action( 'parse_request', array( __CLASS__, 'serve_special' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'consent_default' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'head' ), 3 );
		add_action( 'wp_footer', array( __CLASS__, 'cookie_banner' ), 30 );
		add_filter( 'robots_txt', array( __CLASS__, 'robots' ), 20, 2 );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'rank_math_schema' ), 99, 2 );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'title' ), 20 );
		add_filter( 'wp_robots', array( __CLASS__, 'wp_robots' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'sitemap_exclude' ), 10, 2 );
		add_filter( 'rank_math/sitemap/entry', array( __CLASS__, 'rm_sitemap_entry' ), 10, 3 );
		add_action( 'transition_post_status', array( __CLASS__, 'indexnow' ), 10, 3 );
	}

	public static function rank_math() {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
	}

	public static function site_kit() {
		return defined( 'GOOGLESITEKIT_VERSION' );
	}

	/* ------------------------------------------------------------------
	 * llms.txt / llms-full.txt / IndexNow kulcs
	 * ------------------------------------------------------------------ */
	public static function rewrites() {
		add_rewrite_rule( '^llms\.txt$', 'index.php?zen_llms=1', 'top' );
		add_rewrite_rule( '^llms-full\.txt$', 'index.php?zen_llms=2', 'top' );
		add_filter( 'query_vars', fn( $v ) => array_merge( $v, array( 'zen_llms' ) ) );
	}

	public static function serve_special( $wp ) {
		$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore
		$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $home && 0 === strpos( $path, $home ) ) {
			$path = trim( substr( $path, strlen( $home ) ), '/' );
		}
		$key = (string) Zen_Settings::get( 'seo.indexnow_key', '' );
		if ( $key && $path === $key . '.txt' ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo esc_html( $key );
			exit;
		}
		if ( ( 'llms.txt' === $path || 'llms-full.txt' === $path || ! empty( $wp->query_vars['zen_llms'] ) ) && Zen_Settings::get( 'seo.llms_enabled', true ) ) {
			$full = 'llms-full.txt' === $path || '2' === (string) ( $wp->query_vars['zen_llms'] ?? '' );
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'Cache-Control: public, max-age=3600' );
			header( 'X-Robots-Tag: noindex' );
			echo self::llms( $full ); // phpcs:ignore WordPress.Security.EscapeOutput -- sima szöveg
			exit;
		}
	}

	/** Markdown összefoglaló az AI-asszisztenseknek (llmstxt.org formátum). */
	public static function llms( $full = false ) {
		$g   = Zen_Settings::get( 'general' );
		$def = Zen_I18n::default_lang();
		$o   = array();
		$o[] = '# ' . $g['site_name'];
		$o[] = '';
		$o[] = '> Boutique self-catering accommodation with 3 modern, air-conditioned apartments (RELAX for 2, LOTUS for 3, JIN-JANG with 2 bedrooms for 4) on a quiet street in downtown Veszprém, Hungary – 400 m from the Old Town Square, 27 km from Tihany / Lake Balaton. Free closed courtyard parking, fast WiFi, fully equipped kitchens, self check-in with key safe. Direct booking on the website at the guaranteed best price.';
		$o[] = '';
		$o[] = '## Key facts';
		$o[] = '- Address: ' . $g['address_line'] . ' (GPS ' . $g['lat'] . ', ' . $g['lng'] . ')';
		$o[] = '- Phone: ' . $g['phone'] . ' · Booking phone: ' . $g['phone_booking'] . ' · E-mail: ' . $g['email'];
		$o[] = '- Check-in from ' . $g['checkin'] . ' (self check-in, key safe), check-out until ' . $g['checkout'];
		$o[] = '- Deposit ' . (int) $g['deposit_pct'] . '%, free cancellation up to ' . (int) $g['free_cancel_days'] . ' days before arrival (direct bookings); tourist tax HUF ' . $g['tourist_tax'] . '/person/night (18+)';
		$o[] = '- Non-smoking, no pets, free WiFi, free parking (1 private space per apartment), SZÉP card not accepted';
		$o[] = '- Languages on the website: ' . implode( ', ', array_map( fn( $l ) => $l['native'], Zen_I18n::live() ) );
		$o[] = '- Registration (NTAK): ' . $g['ntak'];
		$o[] = '';
		$o[] = '## Apartments';
		foreach ( zen_apartments() as $a ) {
			$o[] = '- [' . zen_apt_name( $a['key'], 'en' ) . '](' . zen_page_url( 'apt_' . $a['key'], 'en' ) . '): up to ' . $a['capacity'] . ' guests, ' . $a['size'] . ' m², ' . ( ! empty( $a['bedrooms'] ) ? $a['bedrooms'] . ' bedrooms' : 'studio' ) . ' – ' . Zen_I18n::pick( $a['short'] ?? array(), 'en' );
		}
		$o[] = '';
		$o[] = '## Pages';
		foreach ( Zen_I18n::live() as $lang => $meta ) {
			$pages = Zen_Content::pages( $lang );
			if ( ! $pages ) {
				continue;
			}
			$o[] = '### ' . $meta['native'];
			foreach ( $pages as $p ) {
				if ( 'publish' !== $p['status'] ) {
					continue;
				}
				$desc = get_post_meta( $p['id'], '_zen_seo_desc', true );
				$o[]  = '- [' . $p['title'] . '](' . $p['url'] . ')' . ( $desc ? ': ' . $desc : '' );
			}
		}
		$posts = get_posts( array( 'numberposts' => 20, 'post_type' => 'post' ) );
		if ( $posts ) {
			$o[] = '';
			$o[] = '## Blog (Hungarian)';
			foreach ( $posts as $p ) {
				$o[] = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . ')';
			}
		}
		$o[] = '';
		$o[] = '## Optional';
		$o[] = '- [Full text for AI assistants](' . home_url( '/llms-full.txt' ) . ')';
		$o[] = '- [Booking](' . zen_page_url( 'booking', 'en' ) . ')';
		$o[] = '- [Contact](' . zen_page_url( 'contact', 'en' ) . ')';
		if ( $full ) {
			foreach ( array_unique( array( $def, 'en', 'de' ) ) as $lang ) {
				if ( ! isset( Zen_I18n::live()[ $lang ] ) ) {
					continue;
				}
				$o[] = '';
				$o[] = '# ' . strtoupper( $lang ) . ' – full page text';
				foreach ( Zen_Content::pages( $lang ) as $p ) {
					if ( 'publish' !== $p['status'] || 'privacy' === $p['key'] ) {
						continue;
					}
					$o[] = '';
					$o[] = '## ' . $p['title'] . ' (' . $p['url'] . ')';
					$o[] = Zen_Content::plain_text( $p['id'], 8000 );
				}
			}
		}
		return implode( "\n", $o ) . "\n";
	}

	/* ------------------------------------------------------------------
	 * robots.txt – AI-botok engedélyezése, sitemap, llms.txt
	 * ------------------------------------------------------------------ */
	public static function robots( $out, $public ) {
		if ( ! $public ) {
			return $out;
		}
		$bots  = array( 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot', 'anthropic-ai', 'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'Bingbot', 'CCBot', 'Meta-ExternalAgent', 'Amazonbot', 'DuckAssistBot', 'MistralAI-User', 'YouBot' );
		$allow = Zen_Settings::get( 'seo.ai_bots_allow', true );
		$add   = "\n# ZEN Central – AI és keresőmotor barát beállítások\n";
		foreach ( $bots as $b ) {
			$add .= 'User-agent: ' . $b . "\n" . ( $allow ? "Allow: /\nDisallow: /wp-admin/\n" : "Disallow: /\n" ) . "\n";
		}
		$add .= "Disallow: /*?zen_ical=\n";
		if ( ! self::rank_math() ) {
			$add .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
		} else {
			$add .= 'Sitemap: ' . home_url( '/sitemap_index.xml' ) . "\n";
		}
		$add .= '# LLM summary: ' . home_url( '/llms.txt' ) . "\n";
		return $out . $add;
	}

	/* ------------------------------------------------------------------
	 * Cím, meta, OG (csak Rank Math nélkül)
	 * ------------------------------------------------------------------ */
	public static function title( $title ) {
		if ( self::rank_math() || ! is_singular() ) {
			return $title;
		}
		$t = get_post_meta( get_queried_object_id(), '_zen_seo_title', true );
		return $t ? $t : $title;
	}

	public static function wp_robots( $r ) {
		if ( is_page() && 'lang_root' === get_post_meta( get_queried_object_id(), '_zen_key', true ) ) {
			$r['noindex'] = true;
		}
		return $r;
	}

	protected static function desc() {
		if ( is_singular() ) {
			$id = get_queried_object_id();
			$d  = get_post_meta( $id, '_zen_seo_desc', true );
			if ( ! $d ) {
				$d = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) ), 28 );
			}
			return $d;
		}
		return (string) Zen_Settings::get( 'general.tagline', '' );
	}

	protected static function og_image() {
		if ( is_singular() ) {
			$id = get_queried_object_id();
			$og = get_post_meta( $id, '_zen_og', true );
			if ( $og ) {
				return $og;
			}
			if ( has_post_thumbnail( $id ) ) {
				return get_the_post_thumbnail_url( $id, 'large' );
			}
			$f = get_post_meta( $id, '_zen_featured_url', true );
			if ( $f ) {
				return $f;
			}
		}
		return Zen_Media::url( Zen_Settings::get( 'seo.default_og', array() ) );
	}

	public static function head() {
		if ( is_admin() ) {
			return;
		}
		$lang = zen_lang();
		$all  = Zen_I18n::all_languages();
		// hreflang (mindig – Rank Math többnyelvű kiegészítő nélkül nem ad ilyet).
		if ( is_singular() ) {
			$tr = zen_translations( get_queried_object_id() );
			if ( count( $tr ) > 1 ) {
				foreach ( $tr as $l => $u ) {
					echo '<link rel="alternate" hreflang="' . esc_attr( $all[ $l ]['hreflang'] ?? $l ) . '" href="' . esc_url( $u ) . '">' . "\n";
				}
				$def = Zen_I18n::default_lang();
				if ( isset( $tr[ $def ] ) ) {
					echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $tr[ $def ] ) . '">' . "\n";
				}
			}
		} elseif ( is_front_page() ) {
			echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( home_url( '/' ) ) . '">' . "\n";
		}

		if ( ! self::rank_math() ) {
			$desc  = self::desc();
			$img   = self::og_image();
			$title = wp_get_document_title();
			$url   = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );
			if ( $desc ) {
				echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
			}
			echo '<meta property="og:type" content="' . ( is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
			echo '<meta property="og:site_name" content="' . esc_attr( Zen_Settings::get( 'general.site_name' ) ) . '">' . "\n";
			echo '<meta property="og:locale" content="' . esc_attr( $all[ $lang ]['locale'] ?? 'hu_HU' ) . '">' . "\n";
			echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
			if ( $desc ) {
				echo '<meta property="og:description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
			}
			echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
			if ( $img ) {
				echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n" . '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
			}
			echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
			$gv = Zen_Settings::get( 'seo.google_verify', '' );
			if ( $gv ) {
				echo '<meta name="google-site-verification" content="' . esc_attr( $gv ) . '">' . "\n";
			}
			$bv = Zen_Settings::get( 'seo.bing_verify', '' );
			if ( $bv ) {
				echo '<meta name="msvalidate.01" content="' . esc_attr( $bv ) . '">' . "\n";
			}
			echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => self::graph( true ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		}

		// Saját analitika csak ha Site Kit nincs (különben a Site Kit kezeli).
		if ( ! self::site_kit() ) {
			$gtm = trim( (string) Zen_Settings::get( 'seo.gtm_id', '' ) );
			$ga  = trim( (string) Zen_Settings::get( 'seo.ga4_id', '' ) );
			if ( $gtm && preg_match( '/^GTM-[A-Z0-9]+$/', $gtm ) ) {
				echo "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','" . esc_js( $gtm ) . "');</script>\n";
			} elseif ( $ga && preg_match( '/^G-[A-Z0-9]+$/', $ga ) ) {
				echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $ga ) . '"></script><script>gtag("js",new Date());gtag("config",' . wp_json_encode( $ga ) . ',{anonymize_ip:true});</script>' . "\n";
			}
		}
	}

	/** Rank Math schema bővítése. */
	public static function rank_math_schema( $data, $jsonld = null ) {
		foreach ( self::graph( false ) as $i => $node ) {
			$data[ 'zen_' . $i ] = $node;
		}
		return $data;
	}

	/* ------------------------------------------------------------------
	 * Schema.org gráf
	 * ------------------------------------------------------------------ */
	public static function lodging_id() {
		return home_url( '/#lodging' );
	}

	public static function graph( $standalone ) {
		$g     = Zen_Settings::get( 'general' );
		$lang  = zen_lang();
		$nodes = array();
		$imgs  = array();
		foreach ( zen_apartments() as $a ) {
			$u = Zen_Media::url( $a['cover'] ?? array(), 'large' );
			if ( $u ) {
				$imgs[] = $u;
			}
		}
		$og = Zen_Media::url( Zen_Settings::get( 'seo.default_og', array() ) );
		if ( $og ) {
			array_unshift( $imgs, $og );
		}
		$amen = array();
		foreach ( (array) Zen_Settings::get( 'seo.amenities', array() ) as $am ) {
			$amen[] = array( '@type' => 'LocationFeatureSpecification', 'name' => $am, 'value' => true );
		}
		$langs = array_values( array_map( fn( $l ) => $l['hreflang'], Zen_I18n::live() ) );
		$apt_refs = array();
		foreach ( zen_apartments() as $a ) {
			$apt_refs[] = array( '@id' => zen_page_url( 'apt_' . $a['key'], 'en' ) . '#apartment' );
		}
		$lodging = array(
			'@type'             => array( 'LodgingBusiness', 'LocalBusiness' ),
			'@id'               => self::lodging_id(),
			'name'              => $g['site_name'],
			'alternateName'     => array( 'ZEN Centrál Apartmanok', 'Zen Central Apartmanok Veszprém' ),
			'url'               => home_url( '/' ),
			'logo'              => Zen_Media::url( $g['logo'] ?? array() ),
			'image'             => array_values( array_unique( $imgs ) ),
			'description'       => self::desc() ?: $g['tagline'],
			'telephone'         => $g['phone'],
			'email'             => $g['email'],
			'priceRange'        => Zen_Settings::get( 'seo.price_range', '€€' ),
			'currenciesAccepted'=> 'HUF, EUR',
			'paymentAccepted'   => 'Cash, Bank transfer, Credit card',
			'address'           => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $g['street'],
				'addressLocality' => $g['city'],
				'postalCode'      => $g['zip'],
				'addressRegion'   => $g['region'],
				'addressCountry'  => $g['country'],
			),
			'geo'               => array( '@type' => 'GeoCoordinates', 'latitude' => (float) $g['lat'], 'longitude' => (float) $g['lng'] ),
			'hasMap'            => $g['google_maps'],
			'checkinTime'       => $g['checkin'],
			'checkoutTime'      => $g['checkout'],
			'petsAllowed'       => false,
			'smokingAllowed'    => false,
			'numberOfRooms'     => count( zen_apartments() ),
			'amenityFeature'    => $amen,
			'availableLanguage' => $langs,
			'knowsLanguage'     => $langs,
			'sameAs'            => array_values( array_filter( (array) Zen_Settings::get( 'seo.same_as', array() ) ) ),
			'containsPlace'     => $apt_refs,
			'potentialAction'   => array(
				'@type'  => 'ReserveAction',
				'target' => array( '@type' => 'EntryPoint', 'urlTemplate' => zen_page_url( 'booking', $lang ) . '?from={checkin}&to={checkout}&adults={adults}', 'inLanguage' => $lang, 'actionPlatform' => array( 'https://schema.org/DesktopWebPlatform', 'https://schema.org/MobileWebPlatform' ) ),
				'result' => array( '@type' => 'LodgingReservation', 'name' => 'Reservation' ),
			),
			'identifier'        => array( '@type' => 'PropertyValue', 'propertyID' => 'NTAK', 'value' => $g['ntak'] ),
		);
		if ( Zen_Settings::get( 'seo.schema_reviews', false ) && class_exists( 'Zen_Forms' ) ) {
			$st = Zen_Forms::stats();
			if ( $st['count'] >= 3 ) {
				$lodging['aggregateRating'] = array( '@type' => 'AggregateRating', 'ratingValue' => $st['avg'], 'reviewCount' => $st['count'], 'bestRating' => 5 );
			}
		}
		$nodes[] = $lodging;

		if ( $standalone ) {
			$nodes[] = array(
				'@type'           => 'WebSite',
				'@id'             => home_url( '/#website' ),
				'url'             => home_url( '/' ),
				'name'            => $g['site_name'],
				'inLanguage'      => $langs,
				'publisher'       => array( '@id' => self::lodging_id() ),
				'potentialAction' => array( '@type' => 'SearchAction', 'target' => home_url( '/?s={search_term_string}' ), 'query-input' => 'required name=search_term_string' ),
			);
		}

		// Apartmanok (minden oldalon hivatkozható; az apartman oldalán fő entitás).
		$cur_key = is_page() ? get_post_meta( get_queried_object_id(), '_zen_key', true ) : '';
		foreach ( zen_apartments() as $a ) {
			$gallery = array();
			foreach ( array_slice( (array) ( $a['gallery'] ?? array() ), 0, 8 ) as $m ) {
				$u = Zen_Media::url( $m, 'large' );
				if ( $u ) {
					$gallery[] = $u;
				}
			}
			$nodes[] = array(
				'@type'            => array( 'Apartment', 'Accommodation' ),
				'@id'              => zen_page_url( 'apt_' . $a['key'], 'en' ) . '#apartment',
				'name'             => zen_apt_name( $a['key'], $lang ),
				'url'              => zen_page_url( 'apt_' . $a['key'], $lang ),
				'description'      => Zen_I18n::pick( $a['short'] ?? array(), $lang ),
				'image'            => $gallery ?: array( Zen_Media::url( $a['cover'] ?? array() ) ),
				'occupancy'        => array( '@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => (int) $a['capacity'], 'unitText' => 'guests' ),
				'floorSize'        => array( '@type' => 'QuantitativeValue', 'value' => (float) $a['size'], 'unitCode' => 'MTK' ),
				'numberOfRooms'    => max( 1, (int) ( $a['bedrooms'] ?? 0 ) + 1 ),
				'numberOfBedrooms' => (int) ( $a['bedrooms'] ?? 0 ),
				'petsAllowed'      => false,
				'smokingAllowed'   => false,
				'amenityFeature'   => $amen,
				'containedInPlace' => array( '@id' => self::lodging_id() ),
				'address'          => $lodging['address'],
			);
			if ( 'apt_' . $a['key'] === $cur_key ) {
				$nodes[ count( $nodes ) - 1 ]['mainEntityOfPage'] = get_permalink( get_queried_object_id() );
			}
		}

		// Oldalfüggő: GYIK, közeli látnivalók, beszélhető részek, morzsamenü, blog.
		if ( is_page() ) {
			$pid = get_queried_object_id();
			$qa  = array();
			$near = array();
			foreach ( Zen_Content::get_sections( $pid ) as $s ) {
				if ( ! $s['enabled'] ) {
					continue;
				}
				if ( 'faq' === $s['type'] ) {
					foreach ( (array) ( $s['data']['groups'] ?? array() ) as $gr ) {
						foreach ( (array) ( $gr['items'] ?? array() ) as $it ) {
							if ( ! empty( $it['q'] ) && ! empty( $it['a'] ) ) {
								$qa[] = array( '@type' => 'Question', 'name' => wp_strip_all_tags( $it['q'] ), 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $it['a'] ) ) );
							}
						}
					}
				}
				if ( 'map_list' === $s['type'] ) {
					foreach ( (array) ( $s['data']['items'] ?? array() ) as $i => $it ) {
						$parts  = array_map( 'trim', explode( ':', (string) $it, 2 ) );
						$near[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'item' => array( '@type' => 'TouristAttraction', 'name' => $parts[0], 'description' => isset( $parts[1] ) ? 'Distance from ZEN Central Apartments: ' . $parts[1] : '' ) );
					}
				}
			}
			if ( $qa ) {
				$nodes[] = array( '@type' => 'FAQPage', '@id' => get_permalink( $pid ) . '#faq', 'inLanguage' => $lang, 'mainEntity' => $qa );
			}
			if ( $near ) {
				$nodes[] = array( '@type' => 'ItemList', '@id' => get_permalink( $pid ) . '#nearby', 'name' => 'Nearby attractions', 'itemListElement' => $near );
			}
			if ( $standalone ) {
				$nodes[] = array(
					'@type'      => 'WebPage',
					'@id'        => get_permalink( $pid ) . '#webpage',
					'url'        => get_permalink( $pid ),
					'name'       => wp_get_document_title(),
					'inLanguage' => Zen_I18n::all_languages()[ $lang ]['hreflang'] ?? $lang,
					'isPartOf'   => array( '@id' => home_url( '/#website' ) ),
					'about'      => array( '@id' => self::lodging_id() ),
					'speakable'  => array( '@type' => 'SpeakableSpecification', 'cssSelector' => array( '.zc-hero__title', '.zc-hero__text', '.zc-intro__text .zc-prose' ) ),
				);
				$crumbs = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => 'ZEN Central', 'item' => zen_page_url( 'home', $lang ) ) );
				if ( ! is_front_page() && 'home' !== get_post_meta( $pid, '_zen_key', true ) ) {
					$crumbs[] = array( '@type' => 'ListItem', 'position' => 2, 'name' => get_the_title( $pid ), 'item' => get_permalink( $pid ) );
				}
				$nodes[] = array( '@type' => 'BreadcrumbList', '@id' => get_permalink( $pid ) . '#breadcrumb', 'itemListElement' => $crumbs );
			}
		} elseif ( is_singular( 'post' ) && $standalone ) {
			$pid     = get_queried_object_id();
			$nodes[] = array(
				'@type'         => 'BlogPosting',
				'@id'           => get_permalink( $pid ) . '#article',
				'headline'      => get_the_title( $pid ),
				'datePublished' => get_the_date( 'c', $pid ),
				'dateModified'  => get_the_modified_date( 'c', $pid ),
				'image'         => self::og_image(),
				'inLanguage'    => 'hu',
				'author'        => array( '@id' => self::lodging_id() ),
				'publisher'     => array( '@id' => self::lodging_id() ),
				'mainEntityOfPage' => get_permalink( $pid ),
			);
		}
		return $nodes;
	}

	/* ------------------------------------------------------------------
	 * Sitemap: /xx/ gyökéroldalak kizárása
	 * ------------------------------------------------------------------ */
	protected static function lang_root_ids() {
		$ids = array();
		foreach ( (array) ( Zen_Content::map()['lang_root'] ?? array() ) as $id ) {
			$ids[] = (int) $id;
		}
		return $ids;
	}

	public static function sitemap_exclude( $args, $type ) {
		if ( 'page' === $type ) {
			$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), self::lang_root_ids() );
		}
		return $args;
	}

	public static function rm_sitemap_entry( $url, $type, $object ) {
		if ( 'post' === $type && is_object( $object ) && in_array( (int) ( $object->ID ?? 0 ), self::lang_root_ids(), true ) ) {
			return false;
		}
		return $url;
	}

	/* ------------------------------------------------------------------
	 * IndexNow (Bing, Yandex, Seznam… – a Bing az AI-keresők egyik forrása)
	 * ------------------------------------------------------------------ */
	public static function indexnow( $new, $old, $post ) {
		$key = (string) Zen_Settings::get( 'seo.indexnow_key', '' );
		if ( ! $key || 'publish' !== $new || ! in_array( $post->post_type, array( 'page', 'post' ), true ) || wp_is_post_revision( $post ) ) {
			return;
		}
		if ( get_transient( 'zen_indexnow_' . $post->ID ) ) {
			return;
		}
		set_transient( 'zen_indexnow_' . $post->ID, 1, 10 * MINUTE_IN_SECONDS );
		wp_remote_post(
			'https://api.indexnow.org/indexnow',
			array(
				'blocking' => false,
				'timeout'  => 5,
				'headers'  => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'     => wp_json_encode(
					array(
						'host'        => wp_parse_url( home_url(), PHP_URL_HOST ),
						'key'         => $key,
						'keyLocation' => home_url( '/' . $key . '.txt' ),
						'urlList'     => array( get_permalink( $post ) ),
					)
				),
			)
		);
	}

	/* ------------------------------------------------------------------
	 * Consent Mode v2 + süti sáv
	 * ------------------------------------------------------------------ */
	public static function consent_default() {
		if ( is_admin() ) {
			return;
		}
		echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('consent','default',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied',functionality_storage:'granted',security_storage:'granted',wait_for_update:500});try{if(localStorage.getItem('zc-consent')==='all'){gtag('consent','update',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted'});}}catch(e){}</script>\n";
	}

	public static function cookie_banner() {
		if ( is_admin() ) {
			return;
		}
		$privacy = zen_page_url( 'privacy' );
		echo '<div class="zc-cookie" hidden data-cookie role="dialog" aria-live="polite" aria-label="Cookie" style="position:fixed;z-index:90;left:16px;right:16px;bottom:16px;max-width:560px;margin:0 auto;padding:18px 20px;border-radius:24px;background:var(--zc-glass,rgba(255,255,255,.9));backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border:1px solid var(--zc-glass-line,#eee);box-shadow:0 24px 60px -24px rgba(0,0,0,.45);color:var(--zc-text,#1f1b16);font-size:14px;line-height:1.5">';
		echo '<p style="margin:0 0 12px">' . esc_html( zen_t( 'cookie_text' ) ) . ' <a href="' . esc_url( $privacy ) . '" style="color:var(--zc-accent)">' . esc_html( zen_t( 'cookie_more' ) ) . '</a></p>';
		echo '<div style="display:flex;gap:8px;flex-wrap:wrap"><button type="button" class="zc-btn zc-btn--sm" data-consent="all"><span>' . esc_html( zen_t( 'cookie_accept' ) ) . '</span></button><button type="button" class="zc-btn zc-btn--sm zc-btn--ghost" data-consent="necessary"><span>' . esc_html( zen_t( 'cookie_reject' ) ) . '</span></button></div></div>';
		echo "<script>(function(){var b=document.querySelector('[data-cookie]');if(!b)return;var v=null;try{v=localStorage.getItem('zc-consent');}catch(e){}if(!v){b.hidden=false;}b.addEventListener('click',function(e){var x=e.target.closest('[data-consent]');if(!x)return;var c=x.getAttribute('data-consent');try{localStorage.setItem('zc-consent',c);}catch(e){}if(c==='all'&&window.gtag){gtag('consent','update',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted'});}b.hidden=true;});})();</script>\n";
	}
}
