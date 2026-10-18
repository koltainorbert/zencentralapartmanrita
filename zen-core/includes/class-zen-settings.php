<?php
/**
 * Zen Settings – a teljes beállítás-szerkezet, alapértékek, mentés.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Settings {

	const OPT      = 'zen_settings';
	const OPT_APTS = 'zen_apartments';

	/** @var array|null */
	protected static $cache = null;

	/**
	 * Teljes alapértelmezett beállítás-szerkezet.
	 */
	public static function defaults() {
		return array(
			'general'    => array(
				'site_name'      => 'ZEN Central Apartments',
				'tagline'        => 'Szálláshely Veszprém szívében',
				'phone'          => '+36 30 116 9431',
				'phone_booking'  => '+36 30 116 9431',
				'email'          => 'info@zencentralapartments.com',
				'email_cancel'   => 'zencentralapartments@gmail.com',
				'address_line'   => 'H-8200 Veszprém, Festő utca 6.',
				'street'         => 'Festő utca 6.',
				'city'           => 'Veszprém',
				'zip'            => '8200',
				'region'         => 'Veszprém vármegye',
				'country'        => 'HU',
				'lat'            => '47.0960',
				'lng'            => '17.9053',
				'ntak'           => 'MA23062185',
				'tax_number'     => '41130960-1-39',
				'owner'          => 'Kulcsár Rita Katalin',
				'checkin'        => '14:00',
				'checkout'       => '10:00',
				'tourist_tax'    => 500,
				'deposit_pct'    => 50,
				'free_cancel_days' => 15,
				'facebook'       => 'https://www.facebook.com/p/Central-Apartments-Veszpr%C3%A9m-100063516369252/',
				'instagram'      => '',
				'google_maps'    => 'https://maps.google.com/?q=ZEN+Central+Apartments+Veszpr%C3%A9m',
				'map_query'      => 'ZEN Central Apartments Veszprém',
				'map_provider'   => 'osm',   // osm (sütimentes, magától betölt) | google (kattintásra)
				'logo'           => array( 'type' => 'image', 'url' => 'https://zencentralapartments.com/wp-content/uploads/ZenCentralApartments_logo.webp', 'id' => 0, 'poster' => '', 'alt' => 'ZEN Central Apartments' ),
				'logo_dark'      => array( 'type' => 'image', 'url' => '', 'id' => 0, 'poster' => '', 'alt' => 'ZEN Central Apartments' ),
				'side_badge'     => array( 'type' => 'image', 'url' => 'https://zencentralapartments.com/wp-content/uploads/Hotel_Szarvaskut_Lepjen_velunk_kapcsolatba.svg', 'id' => 0, 'poster' => '', 'alt' => '' ),
				'payment_logo'   => array( 'type' => 'image', 'url' => 'https://zencentralapartments.com/wp-content/uploads/pmgw-logo-wide-black.png', 'id' => 0, 'poster' => '', 'alt' => 'PMGW' ),
				'payment_url'    => 'https://paymentgateway.hu',
				'credit_text'    => 'Designed by KOLORdsgn',
				'copyright_year' => '2024',
			),
			'design'     => array(
				'skin'           => 'zen-sand',
				'mode_default'   => 'auto',   // auto|light|dark
				'mode_toggle'    => true,
				'font_source'    => 'bunny',  // bunny|google|system
				'font_heading'   => 'Fraunces',
				'font_body'      => 'Manrope',
				'accent_override'=> '',
				'header_style'   => 'glass',  // glass|solid|transparent
				'animations'     => true,
				'grain'          => false,
				'radius'         => 22,
				'container'      => 1240,
				'custom_css'     => '',
				'show_side_badge'=> false,  // régi kapcsoló (nem használt) – helyette: rail
				'rail'           => 'off',  // off|glass|glass-r|dock|minimal|accent|dark|tab|bubbles|outline|gradient
				'sticky_book'    => true,
				'post_author'    => true,   // blogbejegyzés szerzőjének kiírása
			),
			'languages'  => array(
				'enabled' => array( 'hu', 'en', 'de', 'fr', 'it', 'es', 'pl', 'cs', 'sk', 'ro', 'hr', 'sl', 'nl' ),
				'default' => 'hu',
				'switcher'=> 'dropdown', // régi (nem használt)
				'ui'      => 'popup',    // popup|dropdown – felugró üveg ablak vagy legördülő
				'detect'  => false,      // böngészőnyelv szerinti javaslat-sáv
			),
			'booking'    => array(
				// native = SAJÁT Zen foglalómotor (alapértelmezett), kétirányú iCal szinkron a Booking.com és az Airbnb felé.
				// nhb = régi NetHotelBooking beágyazás (csak tartaléknak, nem szükséges).
				'engine'        => 'native', // native|nhb
				'sync_minutes'  => 15,      // Booking.com / Airbnb naptár lehúzása ennyi percenként
				'resync_before_booking' => 5, // foglalás előtt friss lehúzás, ha a gyorsítótár ennél (perc) régebbi
				'bank_details'  => '',      // előleg átutaláshoz (név, bank, számlaszám) – a visszaigazoló e-mailbe kerül
				'card_payment_url' => '',   // opcionális online kártyás fizetési link (pl. Stripe/SimplePay/Barion fizetési link)
				'nhb_url'       => 'https://nethotelbooking.net/hotels/zencentralapartments/',
				'nhb_script'    => 'https://nethotelbooking.net/embed.RESnWEB.js?id=OXF71',
				'currency'      => 'HUF',
				'min_nights'    => 1,
				'max_advance'   => 540,
				'max_rooms'     => 3,
				'auto_confirm'  => false,
				'notify_email'  => 'info@zencentralapartments.com',
				'cleaning_fee'  => 0,
				'seasons'       => array(), // [{name,from,to,prices:{relax:0},min_nights}]
				'events'        => array(
					array( 'name' => 'VeszprémFest', 'from' => '', 'to' => '' ),
				),
				'blocked'       => array(), // [{apt,from,to,note}]
				'payments'      => array( 'transfer', 'cash', 'card' ),
				'extras'        => array(
					array( 'key' => 'parking', 'label' => array( 'hu' => 'Saját parkolóhely az udvarban', 'en' => 'Private parking space in the courtyard', 'de' => 'Eigener Parkplatz im Innenhof', 'fr' => 'Place de parking privée dans la cour', 'nl' => 'Eigen parkeerplaats op de binnenplaats' ), 'price' => 0, 'per' => 'stay' ),
					array( 'key' => 'early', 'label' => array( 'hu' => 'Korai érkezés (egyeztetés szerint, felár)', 'en' => 'Early check-in (on request, surcharge)', 'de' => 'Früher Check-in (auf Anfrage, Aufpreis)', 'fr' => 'Arrivée anticipée (sur demande, avec supplément)', 'nl' => 'Vroege aankomst (op aanvraag, tegen meerprijs)' ), 'price' => 0, 'per' => 'stay' ),
					array( 'key' => 'late', 'label' => array( 'hu' => 'Késői távozás (egyeztetés szerint, felár)', 'en' => 'Late check-out (on request, surcharge)', 'de' => 'Später Check-out (auf Anfrage, Aufpreis)', 'fr' => 'Départ tardif (sur demande, avec supplément)', 'nl' => 'Laat vertrek (op aanvraag, tegen meerprijs)' ), 'price' => 0, 'per' => 'stay' ),
				),
				
			),
			'ai'         => array(
				'enabled'      => true,
				'name'         => 'Zen',
				'provider'     => 'anthropic', // anthropic|openai|gemini
				'api_key'      => '',
				'model'        => 'claude-sonnet-5-5',
				'temperature'  => 0.5,
				'max_tokens'   => 900,
				'web_search'   => true,  // élő webes keresés (Anthropic web_search / Gemini Google Search)
				'web_search_max' => 3,   // keresések száma válaszonként
				'event_feeds'  => array( 'https://veszprem.hu/wp-json/wp/v2/posts?categories=63&per_page=30', 'https://agoraveszprem.hu/tc-events/feed/' ), // hivatalos programnaptárak (iCal / RSS / JSON) – naponta kétszer frissül
				'system_prompt'=> '',
				'kb_extra'     => '',
				'log_chats'    => true,
				'collect_leads'=> true,
				'avatar'       => array( 'type' => 'image', 'url' => '', 'id' => 0, 'poster' => '', 'alt' => 'Zen' ),
				'position'     => 'right',
				'auto_open_delay' => 0,
			),
			'seo'        => array(
				'ai_bots_allow'  => true,
				'llms_enabled'   => true,
				'indexnow_key'   => '',
				'gtm_id'         => '',
				'ga4_id'         => '',
				'price_range'    => '€€',
				'same_as'        => array(
					'https://www.facebook.com/p/Central-Apartments-Veszpr%C3%A9m-100063516369252/',
				),
				'schema_reviews' => false,
				'default_og'     => array( 'type' => 'image', 'url' => 'https://zencentralapartments.com/wp-content/uploads/ZEN_Central_Apartments_fooldal.webp', 'id' => 0, 'poster' => '', 'alt' => '' ),
				'google_verify'  => '',
				'bing_verify'    => '',
				'amenities'      => array( 'Free WiFi', 'Free parking', 'Air conditioning', 'Heating', 'Kitchen', 'Smart TV', 'Terrace', 'Non-smoking', 'Self check-in (key safe)', 'Hair dryer' ),
			),
			'forms'      => array(
				'recipient'          => 'info@zencentralapartments.com',
				'store_messages'     => true,
				'newsletter_double'  => false,
				'feedback_auto_publish' => false,
				'google_review_url'  => '',
				'booking_review_url' => '',
				'feedback_min_publish' => 4,
			),
			'cloudflare' => array(
				'zone_id'       => '',
				'api_token'     => '',
				'purge_on_save' => true,
				'html_ttl'      => 0,
			),
			'strings'    => array(), // [lang => [key => value]]
		);
	}

	/**
	 * Alapértelmezett apartmanok (eredeti oldal adatai).
	 */
	public static function default_apartments() {
		$u = 'https://zencentralapartments.com/wp-content/uploads/';
		$m = function ( $file, $alt = '' ) use ( $u ) {
			return array( 'type' => 'image', 'url' => $u . $file, 'id' => 0, 'poster' => '', 'alt' => $alt );
		};
		return array(
			array(
				'key'      => 'relax',
				'name'     => array( 'hu' => 'RELAX Apartman', 'en' => 'RELAX Apartment', 'de' => 'RELAX Apartment' ),
				'capacity' => 2, 'size' => 20, 'bedrooms' => 0, 'price' => 0, 'price_weekend' => 0, 'min_nights' => 1,
				'short'    => array( 'hu' => 'Stúdió 2 főre, 20 m², terasz, zárt udvari parkoló.', 'en' => 'Studio for 2, 20 m², terrace, closed courtyard parking.', 'de' => 'Studio für 2, 20 m², Terrasse, geschlossener Hofparkplatz.' ),
				'cover'    => $m( 'Zen_Central_Relax.webp', 'RELAX Apartman' ),
				'icon'     => $m( 'ZEN_Central_Relax_icon.webp', 'RELAX' ),
				'gallery'  => array_map( $m, array( 'Zen_Central_Relax_apartman_8.webp', 'Zen_Central_Relax_apartman_6.webp', 'Zen_Central_Relax_apartman_1.webp', 'Zen_Central_Relax_apartman_3.webp', 'Zen_Central_Relax_apartman_4.webp', 'Zen_Central_Relax_apartman_5.webp', 'Zen_Central_Relax_apartman_7.webp', 'Zen_Central_Relax_apartman_9.webp' ) ),
				'color'    => '#B08D57',
				'ical_booking' => '', 'ical_airbnb' => '', 'ical_other' => array(),
				'ical_token' => '',
			),
			array(
				'key'      => 'jinjang',
				'name'     => array( 'hu' => 'JIN-JANG Apartman', 'en' => 'JIN-JANG Apartment', 'de' => 'JIN-JANG Apartment' ),
				'capacity' => 4, 'size' => 35, 'bedrooms' => 2, 'price' => 0, 'price_weekend' => 0, 'min_nights' => 1,
				'short'    => array( 'hu' => '2 hálószobás apartman 4 főre, 35 m², terasz.', 'en' => '2-bedroom apartment for 4, 35 m², terrace.', 'de' => 'Apartment mit 2 Schlafzimmern für 4, 35 m², Terrasse.' ),
				'cover'    => $m( 'Zen_Central_Jin_Jang.webp', 'JIN-JANG Apartman' ),
				'icon'     => $m( 'ZEN_Central_YinYang_icon.webp', 'JIN-JANG' ),
				'gallery'  => array_map( $m, array( 'Zen_Central_Jin_Jang_1.webp', 'Zen_Central_Jin_Jang_2.webp', 'Zen_Central_Jin_Jang_4.webp', 'Zen_Central_Jin_Jang_7.webp', 'Zen_Central_Jin_Jang_6.webp', 'Zen_Central_Jin_Jang_5.webp', 'Zen_Central_Jin_Jang_3.webp', 'Zen_Central_apartman_5.webp' ) ),
				'color'    => '#6E8B74',
				'ical_booking' => '', 'ical_airbnb' => '', 'ical_other' => array(),
				'ical_token' => '',
			),
			array(
				'key'      => 'lotus',
				'name'     => array( 'hu' => 'LOTUS Apartman', 'en' => 'LOTUS Apartment', 'de' => 'LOTUS Apartment' ),
				'capacity' => 3, 'size' => 25, 'bedrooms' => 0, 'price' => 0, 'price_weekend' => 0, 'min_nights' => 1,
				'short'    => array( 'hu' => 'Stúdió 3 főre, 25 m², terasz, zárt udvari parkoló.', 'en' => 'Studio for 3, 25 m², terrace, closed courtyard parking.', 'de' => 'Studio für 3, 25 m², Terrasse, geschlossener Hofparkplatz.' ),
				'cover'    => $m( 'Zen_Central_Lotus.webp', 'LOTUS Apartman' ),
				'icon'     => $m( 'ZEN_Central_Lotus_icon.webp', 'LOTUS' ),
				'gallery'  => array_map( $m, array( 'Zen_Central_Lotus_apartman_8.webp', 'Zen_Central_Lotus_apartman_9.webp', 'Zen_Central_Lotus_apartman_7.webp', 'Zen_Central_Lotus_apartman_3.webp', 'Zen_Central_Lotus_apartman_2.webp', 'Zen_Central_Lotus_apartman_6.webp', 'Zen_Central_Lotus_apartman_11.webp', 'Zen_Central_Lotus_apartman_12.webp', 'Zen_Central_Lotus_apartman_5.webp', 'Zen_Central_Lotus_apartman_4.webp', 'Zen_Central_Lotus_apartman_1jpg.webp', 'Zen_Central_Lotus_apartman_10.webp', 'Zen_Central_apartman_6.webp' ) ),
				'color'    => '#C97C8A',
				'ical_booking' => '', 'ical_airbnb' => '', 'ical_other' => array(),
				'ical_token' => '',
			),
		);
	}

	/**
	 * A 10 megjelenési forma (skin). Minden skin világos + sötét palettával.
	 */
	public static function skins() {
		return array(
			'zen-sand'      => array( 'name' => 'Zen Homok', 'mood' => 'Meleg bézs, arany – az eredeti hangulat', 'h' => 'Fraunces', 'b' => 'Manrope',
				'light' => array( 'bg' => '#F7F3EC', 'surface' => '#FFFFFF', 'surface2' => '#EFE8DC', 'text' => '#1F1B16', 'muted' => '#6B6358', 'line' => '#E3DACB', 'accent' => '#A9844C', 'accent2' => '#6E8B74', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#110F0C', 'surface' => '#1B1814', 'surface2' => '#25211B', 'text' => '#F3EDE3', 'muted' => '#A79E90', 'line' => '#332D25', 'accent' => '#D6B47C', 'accent2' => '#93B39A', 'ink' => '#17130D' ) ),
			'lotus-blush'   => array( 'name' => 'Lótusz Rózsa', 'mood' => 'Púder rózsa, nyugodt türkiz', 'h' => 'Cormorant Garamond', 'b' => 'DM Sans',
				'light' => array( 'bg' => '#FBF6F5', 'surface' => '#FFFFFF', 'surface2' => '#F4E7E6', 'text' => '#2A1E21', 'muted' => '#7A666A', 'line' => '#EAD9D8', 'accent' => '#C2707F', 'accent2' => '#5F9493', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#140E10', 'surface' => '#1E1618', 'surface2' => '#2A1F22', 'text' => '#F6ECEE', 'muted' => '#B39CA1', 'line' => '#3A2B2F', 'accent' => '#E39AA7', 'accent2' => '#84BDBB', 'ink' => '#1A0F12' ) ),
			'bamboo-sage'   => array( 'name' => 'Bambusz Zsálya', 'mood' => 'Friss zöld, japán minimalizmus', 'h' => 'Instrument Serif', 'b' => 'Figtree',
				'light' => array( 'bg' => '#F3F5EF', 'surface' => '#FFFFFF', 'surface2' => '#E6EBDD', 'text' => '#1A2116', 'muted' => '#5E6A57', 'line' => '#D7DFCB', 'accent' => '#5C7F4E', 'accent2' => '#B28B4A', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#0E120C', 'surface' => '#161C13', 'surface2' => '#1F271B', 'text' => '#ECF2E6', 'muted' => '#9DAA94', 'line' => '#2B3626', 'accent' => '#8DB97A', 'accent2' => '#D7B072', 'ink' => '#0D1309' ) ),
			'ink-mono'      => array( 'name' => 'Tus Monokróm', 'mood' => 'Fekete-fehér galéria, éles kontraszt', 'h' => 'Space Grotesk', 'b' => 'Inter',
				'light' => array( 'bg' => '#FAFAF9', 'surface' => '#FFFFFF', 'surface2' => '#F0F0EE', 'text' => '#0B0B0B', 'muted' => '#5D5D5A', 'line' => '#E2E2DF', 'accent' => '#111111', 'accent2' => '#8C7A5B', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#0A0A0A', 'surface' => '#141414', 'surface2' => '#1D1D1D', 'text' => '#F5F5F4', 'muted' => '#A3A3A0', 'line' => '#2A2A2A', 'accent' => '#F5F5F4', 'accent2' => '#C2AE88', 'ink' => '#0A0A0A' ) ),
			'balaton-blue'  => array( 'name' => 'Balaton Kék', 'mood' => 'Tavi kék, homokos part', 'h' => 'DM Serif Display', 'b' => 'Plus Jakarta Sans',
				'light' => array( 'bg' => '#F3F7FA', 'surface' => '#FFFFFF', 'surface2' => '#E3EDF4', 'text' => '#0F1E2B', 'muted' => '#566A7C', 'line' => '#D3E1EB', 'accent' => '#1F6E9C', 'accent2' => '#D2A86A', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#08121B', 'surface' => '#0F1C28', 'surface2' => '#162736', 'text' => '#E8F1F8', 'muted' => '#93A9BC', 'line' => '#203445', 'accent' => '#5FB3E4', 'accent2' => '#E6C18B', 'ink' => '#061019' ) ),
			'bakony-forest' => array( 'name' => 'Bakonyi Erdő', 'mood' => 'Mély erdőzöld, borostyán fény', 'h' => 'Fraunces', 'b' => 'Outfit',
				'light' => array( 'bg' => '#F2F3EE', 'surface' => '#FFFFFF', 'surface2' => '#E2E6DA', 'text' => '#132018', 'muted' => '#56665B', 'line' => '#D2D9CB', 'accent' => '#2F5D46', 'accent2' => '#C98A2E', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#0A120D', 'surface' => '#111C15', 'surface2' => '#18271D', 'text' => '#E9F1EB', 'muted' => '#93A79A', 'line' => '#223428', 'accent' => '#6FB08E', 'accent2' => '#E9AE57', 'ink' => '#08110B' ) ),
			'royal-terracotta' => array( 'name' => 'Királyné Terrakotta', 'mood' => 'Veszprémi kő, terrakotta tetők', 'h' => 'Playfair Display', 'b' => 'DM Sans',
				'light' => array( 'bg' => '#F8F2EE', 'surface' => '#FFFFFF', 'surface2' => '#F0E2D9', 'text' => '#2A1A13', 'muted' => '#7A6155', 'line' => '#E7D4C8', 'accent' => '#B4573A', 'accent2' => '#4F6E78', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#140C09', 'surface' => '#1E1410', 'surface2' => '#2A1C16', 'text' => '#F7ECE6', 'muted' => '#B89C90', 'line' => '#3A2820', 'accent' => '#E28A6B', 'accent2' => '#8CB2BE', 'ink' => '#170C07' ) ),
			'midnight-gold' => array( 'name' => 'Éjféli Arany', 'mood' => 'Luxus éjkék és arany, sötét-első', 'h' => 'Cormorant Garamond', 'b' => 'Manrope',
				'light' => array( 'bg' => '#F5F3EE', 'surface' => '#FFFFFF', 'surface2' => '#EAE6DC', 'text' => '#121726', 'muted' => '#5C6274', 'line' => '#DCD7CA', 'accent' => '#9C7A3C', 'accent2' => '#26355C', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#070A14', 'surface' => '#0E1322', 'surface2' => '#151C30', 'text' => '#F1EEE6', 'muted' => '#9EA4B8', 'line' => '#1F2840', 'accent' => '#D9B36A', 'accent2' => '#8EA2DA', 'ink' => '#0A0D17' ) ),
			'aurora-tech'   => array( 'name' => 'Aurora Tech', 'mood' => 'Futurisztikus ibolya–cián, high-tech', 'h' => 'Sora', 'b' => 'Inter',
				'light' => array( 'bg' => '#F6F6FB', 'surface' => '#FFFFFF', 'surface2' => '#ECEBF7', 'text' => '#13122B', 'muted' => '#5F5D7E', 'line' => '#DEDCF0', 'accent' => '#6A4DF4', 'accent2' => '#0FA7B8', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#07071A', 'surface' => '#0F0F28', 'surface2' => '#171738', 'text' => '#EEEDFC', 'muted' => '#A09EC6', 'line' => '#25254A', 'accent' => '#9B86FF', 'accent2' => '#3FE0EE', 'ink' => '#08071C' ) ),
			'nordic-mist'   => array( 'name' => 'Északi Pára', 'mood' => 'Hűvös szürkéskék, skandináv nyugalom', 'h' => 'Manrope', 'b' => 'Manrope',
				'light' => array( 'bg' => '#F4F6F7', 'surface' => '#FFFFFF', 'surface2' => '#E7ECEE', 'text' => '#162024', 'muted' => '#5E6E75', 'line' => '#D8E0E3', 'accent' => '#3F6B7A', 'accent2' => '#B9906A', 'ink' => '#FFFFFF' ),
				'dark'  => array( 'bg' => '#0B1013', 'surface' => '#121A1E', 'surface2' => '#1A2429', 'text' => '#EAF0F2', 'muted' => '#98A9B0', 'line' => '#25333A', 'accent' => '#7FB2C4', 'accent2' => '#DDB38B', 'ink' => '#0A1114' ) ),
		);
	}

	/** Elérhető betűtípusok. */
	public static function fonts() {
		return array( 'Fraunces', 'Cormorant Garamond', 'Playfair Display', 'DM Serif Display', 'Instrument Serif', 'Space Grotesk', 'Sora', 'Manrope', 'Inter', 'Outfit', 'DM Sans', 'Figtree', 'Plus Jakarta Sans', 'Urbanist', 'Lora' );
	}

	/** Teljes, összefésült beállítások. */
	public static function all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPT, array() );
			self::$cache = self::merge( self::defaults(), is_array( $saved ) ? $saved : array() );
		}
		return self::$cache;
	}

	/** Pont-útvonalas lekérés. */
	public static function get( $path, $default = null ) {
		$val = self::all();
		foreach ( explode( '.', $path ) as $k ) {
			if ( is_array( $val ) && array_key_exists( $k, $val ) ) {
				$val = $val[ $k ];
			} else {
				return $default;
			}
		}
		return $val;
	}

	/** Részleges frissítés (mély merge, listák cserélődnek). */
	public static function update( array $partial ) {
		$saved = get_option( self::OPT, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$new   = self::merge( $saved, $partial );
		update_option( self::OPT, $new, true );
		self::$cache = null;
		do_action( 'zen_settings_updated', $new );
		return self::all();
	}

	/**
	 * Mély merge: asszociatív tömbök rekurzívan, listák (0..n kulcs) teljesen cserélődnek.
	 */
	public static function merge( $base, $over ) {
		if ( ! is_array( $base ) || ! is_array( $over ) ) {
			return $over;
		}
		if ( self::is_list( $over ) || self::is_list( $base ) && ! empty( $base ) ) {
			return $over;
		}
		foreach ( $over as $k => $v ) {
			if ( isset( $base[ $k ] ) && is_array( $base[ $k ] ) && is_array( $v ) && ! self::is_list( $v ) && ! self::is_list( $base[ $k ] ) ) {
				$base[ $k ] = self::merge( $base[ $k ], $v );
			} else {
				$base[ $k ] = $v;
			}
		}
		return $base;
	}

	public static function is_list( $arr ) {
		if ( ! is_array( $arr ) || array() === $arr ) {
			return false;
		}
		return array_keys( $arr ) === range( 0, count( $arr ) - 1 );
	}

	public static function flush() {
		self::$cache = null;
	}

	/* ---------- Apartmanok ---------- */

	public static function apartments() {
		$apts = get_option( self::OPT_APTS, null );
		if ( ! is_array( $apts ) || empty( $apts ) ) {
			$apts = self::default_apartments();
			if ( function_exists( 'zen_lang_fill_data' ) ) {
				$fill = zen_lang_fill_data();
				foreach ( $apts as &$a ) {
					foreach ( array( 'name', 'short' ) as $f ) {
						$a[ $f ] += $fill['apts'][ $a['key'] ][ $f ] ?? array();
					}
				}
				unset( $a );
			}
		}
		return $apts;
	}

	public static function save_apartments( array $apts ) {
		$clean = array();
		foreach ( $apts as $a ) {
			if ( empty( $a['key'] ) ) {
				continue;
			}
			$a['key']      = sanitize_key( $a['key'] );
			$a['capacity'] = max( 1, (int) ( $a['capacity'] ?? 1 ) );
			$a['size']     = (float) ( $a['size'] ?? 0 );
			$a['price']    = (float) ( $a['price'] ?? 0 );
			$a['price_weekend'] = (float) ( $a['price_weekend'] ?? 0 );
			$a['min_nights'] = max( 1, (int) ( $a['min_nights'] ?? 1 ) );
			if ( empty( $a['ical_token'] ) ) {
				$a['ical_token'] = wp_generate_password( 20, false );
			}
			$clean[] = $a;
		}
		update_option( self::OPT_APTS, $clean, true );
		do_action( 'zen_apartments_updated', $clean );
		return $clean;
	}

	/** Media objektum normalizálása. */
	public static function media( $m ) {
		$m = is_array( $m ) ? $m : array( 'url' => (string) $m );
		$type = ( isset( $m['type'] ) && 'video' === $m['type'] ) ? 'video' : 'image';
		return array(
			'type'   => $type,
			'url'    => esc_url_raw( $m['url'] ?? '' ),
			'id'     => (int) ( $m['id'] ?? 0 ),
			'poster' => esc_url_raw( $m['poster'] ?? '' ),
			'alt'    => sanitize_text_field( $m['alt'] ?? '' ),
		);
	}
}
