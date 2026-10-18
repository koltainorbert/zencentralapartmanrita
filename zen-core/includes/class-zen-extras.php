<?php
/**
 * Zen Extras – három, adminból ki-be kapcsolható funkció:
 *  1) Vendégkalauz: foglalás után automatikus e-mail (cím, parkolás, kulcs, wifi, házirend, be/kijelentkezés).
 *  2) SZÉP-kártya elfogadás jól látható jelzése.
 *  3) Akciós / last minute doboz, a hírlevéllel összekötve.
 * Minden szöveg nyelvenként szerkeszthető (Zen Studio → „Vendégkalauz, SZÉP, akció”).
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Extras {

	/** Beépített, 13 nyelvű alapszövegek: kulcs => [nyelv => szöveg]. */
	public static function texts() {
		return array(
			'g_subject' => array(
				'hu' => 'Vendégkalauz az érkezéshez – {ref}', 'en' => 'Your arrival guide – {ref}', 'de' => 'Ihr Ankunftsguide – {ref}', 'fr' => 'Votre guide d\'arrivée – {ref}',
				'it' => 'La sua guida all\'arrivo – {ref}', 'es' => 'Su guía de llegada – {ref}', 'pl' => 'Przewodnik dla gościa – {ref}', 'cs' => 'Průvodce pro hosta – {ref}',
				'sk' => 'Sprievodca pre hosťa – {ref}', 'ro' => 'Ghidul oaspetelui – {ref}', 'hr' => 'Vodič za goste – {ref}', 'sl' => 'Vodnik za goste – {ref}', 'nl' => 'Uw gastengids – {ref}',
			),
			'g_intro' => array(
				'hu' => 'Köszönjük a foglalását! Összegyűjtöttük, amire az érkezéshez szüksége lehet.',
				'en' => 'Thank you for your booking! Here is everything you need for your arrival.',
				'de' => 'Vielen Dank für Ihre Buchung! Hier finden Sie alles, was Sie für Ihre Anreise brauchen.',
				'fr' => 'Merci pour votre réservation ! Voici tout ce qu\'il vous faut pour votre arrivée.',
				'it' => 'Grazie per la prenotazione! Ecco tutto ciò che serve per il suo arrivo.',
				'es' => '¡Gracias por su reserva! Aquí tiene todo lo necesario para su llegada.',
				'pl' => 'Dziękujemy za rezerwację! Oto wszystko, czego potrzebujesz na przyjazd.',
				'cs' => 'Děkujeme za rezervaci! Zde je vše, co k příjezdu potřebujete.',
				'sk' => 'Ďakujeme za rezerváciu! Tu je všetko, čo potrebujete k príchodu.',
				'ro' => 'Vă mulțumim pentru rezervare! Iată tot ce vă trebuie pentru sosire.',
				'hr' => 'Hvala na rezervaciji! Ovdje je sve što vam treba za dolazak.',
				'sl' => 'Hvala za rezervacijo! Tukaj je vse, kar potrebujete za prihod.',
				'nl' => 'Bedankt voor uw boeking! Hier vindt u alles voor uw aankomst.',
			),
			'g_footer' => array(
				'hu' => 'Kellemes tartózkodást kívánunk! Kérdés esetén hívjon minket: {phone}.',
				'en' => 'We wish you a pleasant stay! If you have any questions, call us: {phone}.',
				'de' => 'Wir wünschen Ihnen einen angenehmen Aufenthalt! Bei Fragen rufen Sie uns an: {phone}.',
				'fr' => 'Nous vous souhaitons un agréable séjour ! Pour toute question, appelez-nous : {phone}.',
				'it' => 'Le auguriamo un piacevole soggiorno! Per qualsiasi domanda ci chiami: {phone}.',
				'es' => '¡Le deseamos una agradable estancia! Si tiene alguna pregunta, llámenos: {phone}.',
				'pl' => 'Życzymy miłego pobytu! W razie pytań prosimy o telefon: {phone}.',
				'cs' => 'Přejeme příjemný pobyt! V případě dotazů nám zavolejte: {phone}.',
				'sk' => 'Prajeme príjemný pobyt! V prípade otázok nám zavolajte: {phone}.',
				'ro' => 'Vă dorim o ședere plăcută! Pentru întrebări, sunați-ne: {phone}.',
				'hr' => 'Želimo vam ugodan boravak! U slučaju pitanja nazovite nas: {phone}.',
				'sl' => 'Želimo vam prijetno bivanje! Za vprašanja nas pokličite: {phone}.',
				'nl' => 'Wij wensen u een prettig verblijf! Heeft u vragen, bel ons gerust: {phone}.',
			),
			'g_l_address' => array( 'hu' => 'Cím', 'en' => 'Address', 'de' => 'Adresse', 'fr' => 'Adresse', 'it' => 'Indirizzo', 'es' => 'Dirección', 'pl' => 'Adres', 'cs' => 'Adresa', 'sk' => 'Adresa', 'ro' => 'Adresa', 'hr' => 'Adresa', 'sl' => 'Naslov', 'nl' => 'Adres' ),
			'g_l_checkin' => array( 'hu' => 'Bejelentkezés', 'en' => 'Check-in', 'de' => 'Check-in', 'fr' => 'Arrivée', 'it' => 'Check-in', 'es' => 'Entrada', 'pl' => 'Zameldowanie', 'cs' => 'Příjezd', 'sk' => 'Príchod', 'ro' => 'Check-in', 'hr' => 'Prijava', 'sl' => 'Prijava', 'nl' => 'Inchecken' ),
			'g_l_checkout' => array( 'hu' => 'Kijelentkezés', 'en' => 'Check-out', 'de' => 'Check-out', 'fr' => 'Départ', 'it' => 'Check-out', 'es' => 'Salida', 'pl' => 'Wymeldowanie', 'cs' => 'Odjezd', 'sk' => 'Odchod', 'ro' => 'Check-out', 'hr' => 'Odjava', 'sl' => 'Odjava', 'nl' => 'Uitchecken' ),
			'g_l_parking' => array( 'hu' => 'Parkolás', 'en' => 'Parking', 'de' => 'Parken', 'fr' => 'Stationnement', 'it' => 'Parcheggio', 'es' => 'Aparcamiento', 'pl' => 'Parking', 'cs' => 'Parkování', 'sk' => 'Parkovanie', 'ro' => 'Parcare', 'hr' => 'Parkiranje', 'sl' => 'Parkiranje', 'nl' => 'Parkeren' ),
			'g_l_key' => array( 'hu' => 'Kulcsátvétel / kapukód', 'en' => 'Key pick-up / gate code', 'de' => 'Schlüsselübergabe / Türcode', 'fr' => 'Remise des clés / code', 'it' => 'Consegna chiavi / codice', 'es' => 'Entrega de llaves / código', 'pl' => 'Odbiór kluczy / kod', 'cs' => 'Předání klíčů / kód', 'sk' => 'Odovzdanie kľúčov / kód', 'ro' => 'Predarea cheilor / cod', 'hr' => 'Preuzimanje ključeva / kod', 'sl' => 'Prevzem ključev / koda', 'nl' => 'Sleutelovergave / code' ),
			'g_l_wifi' => array( 'hu' => 'Wi-Fi', 'en' => 'Wi-Fi', 'de' => 'WLAN', 'fr' => 'Wi-Fi', 'it' => 'Wi-Fi', 'es' => 'Wi-Fi', 'pl' => 'Wi-Fi', 'cs' => 'Wi-Fi', 'sk' => 'Wi-Fi', 'ro' => 'Wi-Fi', 'hr' => 'Wi-Fi', 'sl' => 'Wi-Fi', 'nl' => 'Wifi' ),
			'g_l_rules' => array( 'hu' => 'Házirend', 'en' => 'House rules', 'de' => 'Hausordnung', 'fr' => 'Règlement intérieur', 'it' => 'Regole della casa', 'es' => 'Normas de la casa', 'pl' => 'Regulamin', 'cs' => 'Domovní řád', 'sk' => 'Domáci poriadok', 'ro' => 'Regulament', 'hr' => 'Kućni red', 'sl' => 'Hišni red', 'nl' => 'Huisregels' ),
			'g_l_tips' => array( 'hu' => 'Helyi tippek', 'en' => 'Local tips', 'de' => 'Lokale Tipps', 'fr' => 'Bons plans locaux', 'it' => 'Consigli locali', 'es' => 'Consejos locales', 'pl' => 'Lokalne wskazówki', 'cs' => 'Místní tipy', 'sk' => 'Miestne tipy', 'ro' => 'Sfaturi locale', 'hr' => 'Lokalni savjeti', 'sl' => 'Lokalni nasveti', 'nl' => 'Lokale tips' ),
			'g_l_pass' => array( 'hu' => 'jelszó', 'en' => 'password', 'de' => 'Passwort', 'fr' => 'mot de passe', 'it' => 'password', 'es' => 'contraseña', 'pl' => 'hasło', 'cs' => 'heslo', 'sk' => 'heslo', 'ro' => 'parolă', 'hr' => 'lozinka', 'sl' => 'geslo', 'nl' => 'wachtwoord' ),
			'g_from' => array( 'hu' => '{t}-tól', 'en' => 'from {t}', 'de' => 'ab {t}', 'fr' => 'à partir de {t}', 'it' => 'dalle {t}', 'es' => 'desde las {t}', 'pl' => 'od {t}', 'cs' => 'od {t}', 'sk' => 'od {t}', 'ro' => 'de la {t}', 'hr' => 'od {t}', 'sl' => 'od {t}', 'nl' => 'vanaf {t}' ),
			'g_until' => array( 'hu' => '{t}-ig', 'en' => 'until {t}', 'de' => 'bis {t}', 'fr' => 'jusqu\'à {t}', 'it' => 'entro le {t}', 'es' => 'hasta las {t}', 'pl' => 'do {t}', 'cs' => 'do {t}', 'sk' => 'do {t}', 'ro' => 'până la {t}', 'hr' => 'do {t}', 'sl' => 'do {t}', 'nl' => 'tot {t}' ),
			'g_parking' => array(
				'hu' => 'Udvaron belül apartmanonként 1 db gépkocsi, de az utcán is ingyenesen lehet parkolni.',
				'en' => 'Inside the courtyard 1 car per apartment; parking on the street is also free.',
				'de' => 'Im Innenhof 1 Pkw pro Apartment; auch auf der Straße kann kostenlos geparkt werden.',
				'fr' => 'Dans la cour : 1 voiture par appartement ; le stationnement dans la rue est également gratuit.',
				'it' => 'Nel cortile 1 auto per appartamento; anche in strada il parcheggio è gratuito.',
				'es' => 'En el patio 1 coche por apartamento; también se puede aparcar gratis en la calle.',
				'pl' => 'Na dziedzińcu 1 samochód na apartament; parkowanie na ulicy również jest bezpłatne.',
				'cs' => 'Na dvoře 1 auto na apartmán; parkování na ulici je také zdarma.',
				'sk' => 'Na dvore 1 auto na apartmán; parkovanie na ulici je tiež zadarmo.',
				'ro' => 'În curte 1 mașină per apartament; parcarea pe stradă este de asemenea gratuită.',
				'hr' => 'U dvorištu 1 automobil po apartmanu; parkiranje na ulici također je besplatno.',
				'sl' => 'Na dvorišču 1 avto na apartma; parkiranje na ulici je prav tako brezplačno.',
				'nl' => 'In de binnenplaats 1 auto per appartement; parkeren op straat is ook gratis.',
			),
			's_label' => array(
				'hu' => 'SZÉP-kártyát elfogadunk', 'en' => 'SZÉP card accepted', 'de' => 'SZÉP-Karte wird akzeptiert', 'fr' => 'Carte SZÉP acceptée', 'it' => 'Carta SZÉP accettata',
				'es' => 'Se acepta la tarjeta SZÉP', 'pl' => 'Karta SZÉP akceptowana', 'cs' => 'Karta SZÉP přijímána', 'sk' => 'Karta SZÉP akceptovaná', 'ro' => 'Cardul SZÉP este acceptat',
				'hr' => 'SZÉP kartica prihvaćena', 'sl' => 'Kartica SZÉP sprejeta', 'nl' => 'SZÉP-kaart geaccepteerd',
			),
			'p_badge' => array( 'hu' => 'Last minute', 'en' => 'Last minute', 'de' => 'Last Minute', 'fr' => 'Dernière minute', 'it' => 'Last minute', 'es' => 'Última hora', 'pl' => 'Last minute', 'cs' => 'Last minute', 'sk' => 'Last minute', 'ro' => 'Last minute', 'hr' => 'Last minute', 'sl' => 'Last minute', 'nl' => 'Last minute' ),
			'p_title' => array(
				'hu' => 'Akciók és last minute ajánlatok', 'en' => 'Special offers & last-minute deals', 'de' => 'Angebote & Last-Minute-Deals', 'fr' => 'Offres spéciales et dernière minute',
				'it' => 'Offerte speciali e last minute', 'es' => 'Ofertas especiales y de última hora', 'pl' => 'Oferty specjalne i last minute', 'cs' => 'Akční nabídky a last minute',
				'sk' => 'Akčné ponuky a last minute', 'ro' => 'Oferte speciale și last minute', 'hr' => 'Posebne ponude i last minute', 'sl' => 'Posebne ponudbe in last minute', 'nl' => 'Aanbiedingen & last-minute deals',
			),
			'p_text' => array(
				'hu' => 'Iratkozzon fel hírlevelünkre, és elsőként értesül a kedvezményekről, a szezonális és a last minute ajánlatainkról.',
				'en' => 'Subscribe to our newsletter and be the first to hear about discounts, seasonal and last-minute offers.',
				'de' => 'Abonnieren Sie unseren Newsletter und erfahren Sie als Erster von Rabatten sowie saisonalen und Last-Minute-Angeboten.',
				'fr' => 'Abonnez-vous à notre newsletter et soyez le premier informé de nos réductions et offres saisonnières ou de dernière minute.',
				'it' => 'Si iscriva alla nostra newsletter e sia il primo a sapere di sconti e offerte stagionali e last minute.',
				'es' => 'Suscríbase a nuestro boletín y sea el primero en conocer descuentos y ofertas de temporada y de última hora.',
				'pl' => 'Zapisz się do newslettera i jako pierwszy dowiedz się o rabatach oraz ofertach sezonowych i last minute.',
				'cs' => 'Přihlaste se k odběru newsletteru a dozvíte se první o slevách a sezónních i last minute nabídkách.',
				'sk' => 'Prihláste sa na odber newslettera a dozviete sa ako prví o zľavách a sezónnych aj last minute ponukách.',
				'ro' => 'Abonați-vă la newsletter și aflați primii despre reduceri și oferte sezoniere sau last minute.',
				'hr' => 'Prijavite se na newsletter i prvi saznajte za popuste te sezonske i last minute ponude.',
				'sl' => 'Prijavite se na novice in prvi izvedite za popuste ter sezonske in last minute ponudbe.',
				'nl' => 'Meld u aan voor onze nieuwsbrief en ontvang als eerste bericht over kortingen en seizoens- en last-minute aanbiedingen.',
			),
			'p_cta' => array(
				'hu' => 'Feliratkozom a hírlevélre', 'en' => 'Subscribe to the newsletter', 'de' => 'Zum Newsletter anmelden', 'fr' => 'S\'abonner à la newsletter', 'it' => 'Iscriviti alla newsletter',
				'es' => 'Suscribirse al boletín', 'pl' => 'Zapisz się do newslettera', 'cs' => 'Přihlásit k odběru', 'sk' => 'Prihlásiť na odber', 'ro' => 'Abonare la newsletter',
				'hr' => 'Prijava na newsletter', 'sl' => 'Prijava na novice', 'nl' => 'Aanmelden voor nieuwsbrief',
			),
		);
	}

	/** Alapértelmezett beállítások (a Zen_Settings::defaults() „extras” csoportja). */
	public static function defaults() {
		$t = self::texts();
		return array(
			'guide' => array(
				'enabled'    => true,
				'send_on'    => 'confirmed', // confirmed: a visszaigazolás pillanatában | created: a foglalás leadásakor
				'subject'    => $t['g_subject'],
				'intro'      => $t['g_intro'],
				'footer'     => $t['g_footer'],
				'parking'    => $t['g_parking'],
				'key'        => array(),  // kulcsátvétel / kapukód – az ügyfél még nem adta meg
				'wifi_name'  => 'Apartman',
				'wifi_pass'  => '',
				'checkin'    => '',       // üres = Általános → Bejelentkezés
				'checkout'   => '',       // üres = Általános → Távozás
				'rules'      => array(),  // házirend – az ügyfél még nem adta meg
				'tips'       => array(),
				'l_address'  => $t['g_l_address'],
				'l_checkin'  => $t['g_l_checkin'],
				'l_checkout' => $t['g_l_checkout'],
				'l_parking'  => $t['g_l_parking'],
				'l_key'      => $t['g_l_key'],
				'l_wifi'     => $t['g_l_wifi'],
				'l_rules'    => $t['g_l_rules'],
				'l_tips'     => $t['g_l_tips'],
			),
			'szep'  => array(
				'enabled'     => true,   // a funkció (jelzés) be van kapcsolva
				'accepted'    => false,  // az ügyfél válasza: jelenleg NEM fogadnak SZÉP-kártyát → a jelvény addig rejtve marad
				'label'       => $t['s_label'],
				'in_footer'   => true,
				'in_booking'  => true,
			),
			'promo' => array(
				'enabled'    => true,
				'badge'      => $t['p_badge'],
				'discount'   => '',
				'title'      => $t['p_title'],
				'text'       => $t['p_text'],
				'cta'        => $t['p_cta'],
				'show_book'  => true,
				'from'       => '',
				'to'         => '',
			),
		);
	}

	public static function opt( $path, $default = null ) {
		return Zen_Settings::get( 'extras.' . $path, $default );
	}

	/** Nyelvi tömb → szöveg (kért nyelv, majd magyar, majd bármely kitöltött). */
	protected static function pick( $val, $lang ) {
		if ( ! is_array( $val ) ) {
			return (string) $val;
		}
		foreach ( array( $lang, 'hu', 'en' ) as $l ) {
			if ( isset( $val[ $l ] ) && '' !== trim( (string) $val[ $l ] ) ) {
				return (string) $val[ $l ];
			}
		}
		foreach ( $val as $v ) {
			if ( is_string( $v ) && '' !== trim( $v ) ) {
				return $v;
			}
		}
		return '';
	}

	/* ==================================================================
	 * Indítás
	 * ================================================================== */

	public static function init() {
		add_action( 'zen_booking_created', array( __CLASS__, 'on_created' ), 20, 2 );
		add_action( 'zen_booking_status', array( __CLASS__, 'on_status' ), 20, 3 );
		add_action( 'admin_post_zen_guide_resend', array( __CLASS__, 'admin_resend' ) );
		add_action( 'admin_post_zen_guide_test', array( __CLASS__, 'admin_test' ) );
		add_shortcode( 'zen_szep', array( __CLASS__, 'sc_szep' ) );
		add_shortcode( 'zen_promo', array( __CLASS__, 'sc_promo' ) );
	}

	/* ==================================================================
	 * 1) Vendégkalauz
	 * ================================================================== */

	public static function on_created( $pid, $status ) {
		if ( ! self::opt( 'guide.enabled', true ) ) {
			return;
		}
		$when = self::opt( 'guide.send_on', 'confirmed' );
		if ( 'confirmed' === $status || 'created' === $when ) {
			self::send_guide( $pid );
		}
	}

	public static function on_status( $pid, $status, $old ) {
		if ( 'confirmed' === $status && self::opt( 'guide.enabled', true ) ) {
			self::send_guide( $pid );
		}
	}

	/** Vendégkalauz e-mail küldése egy foglaláshoz. $force: újraküldés. */
	public static function send_guide( $pid, $force = false ) {
		if ( ! $force && ( ! self::opt( 'guide.enabled', true ) || get_post_meta( $pid, '_guide_sent', true ) ) ) {
			return false;
		}
		$g = (array) get_post_meta( $pid, '_guest', true );
		if ( empty( $g['email'] ) || ! is_email( $g['email'] ) ) {
			return false;
		}
		$lang = get_post_meta( $pid, '_lang', true );
		$lang = $lang && Zen_I18n::is_valid( $lang ) ? $lang : Zen_I18n::default_lang();
		$ctx  = array(
			'name'  => (string) ( $g['name'] ?? '' ),
			'ref'   => (string) get_post_meta( $pid, '_ref', true ),
			'items' => (array) get_post_meta( $pid, '_items', true ),
			'lang'  => $lang,
		);
		$subject = str_replace( '{ref}', $ctx['ref'], self::pick( self::opt( 'guide.subject', array() ), $lang ) );
		$html    = self::guide_html( $ctx );
		$headers = array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . Zen_Settings::get( 'general.email' ) );
		$ok      = wp_mail( $g['email'], $subject, Zen_Booking::wrap_html( $subject, $html ), $headers );
		if ( $ok ) {
			update_post_meta( $pid, '_guide_sent', time() );
		}
		return $ok;
	}

	/** A kalauz törzse (HTML). $ctx: name, ref, items[], lang. */
	public static function guide_html( $ctx ) {
		$lang  = $ctx['lang'];
		$t     = self::texts();
		$tr    = function ( $k ) use ( $t, $lang ) {
			return self::pick( $t[ $k ] ?? array(), $lang );
		};
		$o     = function ( $path ) use ( $lang ) {
			return self::pick( self::opt( $path, array() ), $lang );
		};
		$phone = Zen_Settings::get( 'general.phone_booking', Zen_Settings::get( 'general.phone', '' ) );
		$ci    = trim( (string) self::opt( 'guide.checkin', '' ) ) ?: (string) Zen_Settings::get( 'general.checkin', '14:00' );
		$co    = trim( (string) self::opt( 'guide.checkout', '' ) ) ?: (string) Zen_Settings::get( 'general.checkout', '10:00' );
		$label = function ( $key, $fallback ) use ( $o, $tr ) {
			$v = $o( 'guide.' . $key );
			return '' !== $v ? $v : $tr( $fallback );
		};

		$h  = '<p>' . esc_html( zen_t( 'bk_mail_hello', $lang, array( 'name' => $ctx['name'] ) ) ) . '</p>';
		$h .= '<p>' . esc_html( $o( 'guide.intro' ) ) . '</p>';
		if ( $ctx['ref'] ) {
			$h .= '<p style="font-size:13px;color:#6b6358">#' . esc_html( $ctx['ref'] ) . '</p>';
		}
		$stay = array();
		foreach ( (array) $ctx['items'] as $it ) {
			$stay[] = esc_html( zen_apt_name( $it['apt'] ?? '', $lang ) ) . ': ' . esc_html( ( $it['from'] ?? '' ) . ' → ' . ( $it['to'] ?? '' ) );
		}
		$rows   = array();
		if ( $stay ) {
			$rows[] = array( '🛏', implode( '<br>', $stay ), '' );
		}
		$addr  = (string) Zen_Settings::get( 'general.address_line', '' );
		$maps  = (string) Zen_Settings::get( 'general.google_maps', '' );
		if ( $addr ) {
			$rows[] = array( $label( 'l_address', 'g_l_address' ), esc_html( $addr ) . ( $maps ? ' · <a href="' . esc_url( $maps ) . '" style="color:#a9844c">Google Maps</a>' : '' ), 'addr' );
		}
		$rows[] = array( $label( 'l_checkin', 'g_l_checkin' ), esc_html( str_replace( '{t}', $ci, $tr( 'g_from' ) ) ), 'ci' );
		$rows[] = array( $label( 'l_checkout', 'g_l_checkout' ), esc_html( str_replace( '{t}', $co, $tr( 'g_until' ) ) ), 'co' );
		$key = $o( 'guide.key' );
		if ( '' !== trim( $key ) ) {
			$rows[] = array( $label( 'l_key', 'g_l_key' ), nl2br( esc_html( $key ) ), 'key' );
		}
		$park = $o( 'guide.parking' );
		if ( '' !== trim( $park ) ) {
			$rows[] = array( $label( 'l_parking', 'g_l_parking' ), nl2br( esc_html( $park ) ), 'park' );
		}
		$wn = trim( (string) self::opt( 'guide.wifi_name', '' ) );
		$wp = trim( (string) self::opt( 'guide.wifi_pass', '' ) );
		if ( '' !== $wn || '' !== $wp ) {
			$w = '' !== $wn ? '<b>' . esc_html( $wn ) . '</b>' : '';
			if ( '' !== $wp ) {
				$w .= ( '' !== $w ? '<br>' : '' ) . esc_html( $tr( 'g_l_pass' ) ) . ': <b>' . esc_html( $wp ) . '</b>';
			}
			$rows[] = array( $label( 'l_wifi', 'g_l_wifi' ), $w, 'wifi' );
		}
		$rules = $o( 'guide.rules' );
		if ( '' !== trim( $rules ) ) {
			$rows[] = array( $label( 'l_rules', 'g_l_rules' ), nl2br( esc_html( $rules ) ), 'rules' );
		}
		$tips = $o( 'guide.tips' );
		if ( '' !== trim( $tips ) ) {
			$rows[] = array( $label( 'l_tips', 'g_l_tips' ), nl2br( esc_html( $tips ) ), 'tips' );
		}

		$h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:12px 0 18px;font-size:14px">';
		foreach ( $rows as $r ) {
			$h .= '<tr><td valign="top" style="padding:12px 14px 12px 0;border-bottom:1px solid #eee;width:34%;color:#6b6358;font-weight:600">' . esc_html( $r[0] ) . '</td>'
				. '<td valign="top" style="padding:12px 0;border-bottom:1px solid #eee">' . $r[1] . '</td></tr>';
		}
		$h .= '</table>';
		$h .= '<p>' . esc_html( str_replace( '{phone}', $phone, $o( 'guide.footer' ) ) ) . '</p>';
		return $h;
	}

	/** Admin: vendégkalauz újraküldése egy foglaláshoz. */
	public static function admin_resend() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( 'Nincs jogosultság.' );
		}
		check_admin_referer( 'zen_guide_' . $id );
		$ok = self::send_guide( $id, true );
		wp_safe_redirect( add_query_arg( 'zen_guide', $ok ? 'ok' : 'err', get_edit_post_link( $id, 'url' ) ) );
		exit;
	}

	/** Admin: próba-levél a belépett felhasználónak minta-adatokkal. */
	public static function admin_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Nincs jogosultság.' );
		}
		check_admin_referer( 'zen_guide_test' );
		$u    = wp_get_current_user();
		$lang = isset( $_GET['lang'] ) && Zen_I18n::is_valid( sanitize_key( wp_unslash( $_GET['lang'] ) ) ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : Zen_I18n::default_lang(); // phpcs:ignore
		$ctx  = array( 'name' => $u->display_name, 'ref' => 'ZC-PROBA', 'lang' => $lang, 'items' => array( array( 'apt' => 'relax', 'from' => wp_date( 'Y-m-d', time() + 7 * DAY_IN_SECONDS ), 'to' => wp_date( 'Y-m-d', time() + 9 * DAY_IN_SECONDS ) ) ) );
		$subject = str_replace( '{ref}', 'ZC-PROBA', self::pick( self::opt( 'guide.subject', array() ), $lang ) );
		$ok = wp_mail( $u->user_email, $subject, Zen_Booking::wrap_html( $subject, self::guide_html( $ctx ) ), array( 'Content-Type: text/html; charset=UTF-8' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=zen-studio&zen_guide_test=' . ( $ok ? 'ok' : 'err' ) . '#extras' ) );
		exit;
	}

	/** Gombok a foglalás szerkesztő oldalán (Zen_Booking::box_actions hívja). */
	public static function booking_box_button( $pid ) {
		if ( ! self::opt( 'guide.enabled', true ) || ! get_post_meta( $pid, '_ref', true ) ) {
			return;
		}
		$sent = (int) get_post_meta( $pid, '_guide_sent', true );
		$url  = wp_nonce_url( admin_url( 'admin-post.php?action=zen_guide_resend&id=' . $pid ), 'zen_guide_' . $pid );
		echo '<p class="description">Vendégkalauz: ' . ( $sent ? 'elküldve ' . esc_html( wp_date( 'Y-m-d H:i', $sent ) ) : 'még nem ment ki' ) . '</p>';
		echo '<p><a class="button zs-pill" href="' . esc_url( $url ) . '">' . ( $sent ? 'Vendégkalauz újraküldése' : 'Vendégkalauz küldése most' ) . '</a></p>';
		if ( isset( $_GET['zen_guide'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<p class="description">' . ( 'ok' === $_GET['zen_guide'] ? 'A vendégkalauz elküldve.' : 'A küldés nem sikerült.' ) . '</p>'; // phpcs:ignore
		}
	}

	/* ==================================================================
	 * 2) SZÉP-kártya jelzés
	 * ================================================================== */

	public static function szep_active( $place = '' ) {
		if ( ! self::opt( 'szep.enabled', true ) || ! self::opt( 'szep.accepted', false ) ) {
			return false;
		}
		if ( 'footer' === $place && ! self::opt( 'szep.in_footer', true ) ) {
			return false;
		}
		if ( 'booking' === $place && ! self::opt( 'szep.in_booking', true ) ) {
			return false;
		}
		return true;
	}

	public static function szep_html( $place = '' ) {
		if ( ! self::szep_active( $place ) ) {
			return '';
		}
		$lang  = function_exists( 'zen_lang' ) ? zen_lang() : 'hu';
		$label = self::pick( self::opt( 'szep.label', array() ), $lang );
		if ( '' === $label ) {
			$label = self::pick( self::texts()['s_label'], $lang );
		}
		$icon = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="3"/><path d="M2.5 10h19M6.5 15h4"/></svg>';
		return '<span class="zc-szep" role="note">' . $icon . '<span>' . esc_html( $label ) . '</span></span>';
	}

	public static function sc_szep() {
		return self::szep_html();
	}

	/* ==================================================================
	 * 3) Akciós / last minute doboz
	 * ================================================================== */

	public static function promo_active() {
		if ( ! self::opt( 'promo.enabled', true ) ) {
			return false;
		}
		$today = wp_date( 'Y-m-d' );
		$from  = (string) self::opt( 'promo.from', '' );
		$to    = (string) self::opt( 'promo.to', '' );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) && $today < $from ) {
			return false;
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) && $today > $to ) {
			return false;
		}
		return true;
	}

	public static function promo_html() {
		if ( ! self::promo_active() ) {
			return '';
		}
		$lang  = function_exists( 'zen_lang' ) ? zen_lang() : 'hu';
		$p     = function ( $k ) use ( $lang ) {
			return self::pick( self::opt( 'promo.' . $k, array() ), $lang );
		};
		$disc  = trim( (string) self::opt( 'promo.discount', '' ) );
		$h     = '<section class="zc-promo" aria-label="' . esc_attr( $p( 'title' ) ) . '"><div class="zc-wrap zc-promo__in">';
		$h    .= '<div class="zc-promo__txt">';
		if ( '' !== $p( 'badge' ) || '' !== $disc ) {
			$h .= '<span class="zc-promo__badge">' . esc_html( trim( $p( 'badge' ) . ( '' !== $disc ? ' · ' . $disc : '' ) ) ) . '</span>';
		}
		$h    .= '<h3 class="zc-promo__title">' . esc_html( $p( 'title' ) ) . '</h3>';
		if ( '' !== $p( 'text' ) ) {
			$h .= '<p class="zc-promo__text">' . esc_html( $p( 'text' ) ) . '</p>';
		}
		$h    .= '</div><div class="zc-promo__act">';
		$h    .= '<button type="button" class="zc-btn zc-btn--sm" data-zen-newsletter><span>' . esc_html( '' !== $p( 'cta' ) ? $p( 'cta' ) : zen_t( 'newsletter_btn' ) ) . '</span></button>';
		if ( self::opt( 'promo.show_book', true ) && function_exists( 'zen_page_url' ) ) {
			$h .= '<a class="zc-btn zc-btn--ghost zc-btn--sm" href="' . esc_url( zen_page_url( 'booking' ) ) . '"><span>' . esc_html( zen_t( 'book_this' ) ) . '</span></a>';
		}
		$h    .= '</div></div></section>';
		return $h;
	}

	public static function sc_promo() {
		return self::promo_html();
	}
}

Zen_Extras::init();
