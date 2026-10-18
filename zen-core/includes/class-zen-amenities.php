<?php
/**
 * Felszereltség ikonok: egységes, accent színű (currentColor) duotone SVG készlet,
 * automatikus felismerés a sor szövegéből (13 nyelv), kézi felülírás „[[ikon]] szöveg” előtaggal.
 *
 * @package zen-core
 */

defined( 'ABSPATH' ) || exit;

class Zen_Amenities {

	/** Kézi ikon előtag: „[[wifi]] Gyors WIFI”. */
	const PREFIX_RE = '/^\s*\[\[([a-z0-9-]+)\]\]\s*/u';

	/**
	 * Ikonkészlet: név => [magyar címke, SVG elemek].
	 * 24×24 rács, 1.6 vonal, kerek végek; a class="d" elemek halvány (duotone) kitöltést kapnak.
	 */
	public static function icons() {
		static $i = null;
		if ( null !== $i ) {
			return $i;
		}
		$i = array(
			'check'      => array( 'Pipa (alap)', '<circle class="d" cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="9"/><path d="m8 12.3 2.7 2.7L16.2 9.5"/>' ),
			'nosmoke'    => array( 'Nem dohányzó', '<circle cx="12" cy="12" r="9"/><rect class="d" x="5.5" y="11" width="11" height="3" rx=".8"/><rect x="5.5" y="11" width="11" height="3" rx=".8"/><path d="M18.5 11v3M16 8.6c0-1.1 1.2-1.4 1.2-2.6M5.6 5.6l12.8 12.8"/>' ),
			'ac'         => array( 'Légkondi', '<rect class="d" x="2.5" y="4" width="19" height="9" rx="2.6"/><rect x="2.5" y="4" width="19" height="9" rx="2.6"/><path d="M6 9.5h12M8 16.5c0 1.4-1 1.7-1 3M12 16.5v4M16 16.5c0 1.4 1 1.7 1 3"/>' ),
			'heating'    => array( 'Fűtés', '<path class="d" d="M12 21a6 6 0 0 0 6-6c0-4-3-5.6-4-9-1.8 1.4-2.6 3.1-2.6 4.9C9.8 10 9 9 9 7.5 7 9.2 6 11.7 6 15a6 6 0 0 0 6 6z"/><path d="M12 21a6 6 0 0 0 6-6c0-4-3-5.6-4-9-1.8 1.4-2.6 3.1-2.6 4.9C9.8 10 9 9 9 7.5 7 9.2 6 11.7 6 15a6 6 0 0 0 6 6z"/><path d="M12 21a2.5 2.5 0 0 1-2.5-2.5c0-1.6 1.2-2.3 2.5-3.8 1.3 1.5 2.5 2.2 2.5 3.8A2.5 2.5 0 0 1 12 21z"/>' ),
			'floor'      => array( 'Parketta / padló', '<rect class="d" x="3" y="3" width="18" height="18" rx="3"/><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M9 3v6M3 9h12M15 9v6M9 15h12M9 15v6"/>' ),
			'wardrobe'   => array( 'Gardrób / szekrény', '<rect class="d" x="4.5" y="3" width="15" height="16.5" rx="2"/><rect x="4.5" y="3" width="15" height="16.5" rx="2"/><path d="M12 3v16.5M10 10.3v2.4M14 10.3v2.4M7 19.5V21M17 19.5V21"/>' ),
			'tv'         => array( 'Smart TV', '<rect class="d" x="2.5" y="4" width="19" height="13" rx="2.6"/><rect x="2.5" y="4" width="19" height="13" rx="2.6"/><path d="M8.5 21h7M12 17v4"/><path d="M10.5 8.4v5.2l4.3-2.6z"/>' ),
			'wifi'       => array( 'Wi-Fi', '<path d="M2.5 9.5a14 14 0 0 1 19 0M5.6 13a9.5 9.5 0 0 1 12.8 0M8.7 16.4a5 5 0 0 1 6.6 0"/><circle class="f" cx="12" cy="19.6" r="1.4"/>' ),
			'shower'     => array( 'Fürdőszoba / zuhany', '<path d="M5 21V8a5 5 0 0 1 10 0"/><path class="d" d="M10.5 8h9l-1.4 3h-6.2z"/><path d="M10.5 8h9l-1.4 3h-6.2zM12.8 14.2v1M15 15.2v1M17.2 14.2v1M13.9 18.4v1M16.1 18.4v1"/>' ),
			'bathtub'    => array( 'Kád', '<path class="d" d="M3 12h18v2.5a4.5 4.5 0 0 1-4.5 4.5h-9A4.5 4.5 0 0 1 3 14.5z"/><path d="M3 12h18v2.5a4.5 4.5 0 0 1-4.5 4.5h-9A4.5 4.5 0 0 1 3 14.5zM6 12V5.8a2.3 2.3 0 0 1 4.4-1M7 19l-1 2M17 19l1 2"/>' ),
			'towel'      => array( 'Törölköző', '<path d="M3 5h18"/><path class="d" d="M6 5v13.5a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5z"/><path d="M6 5v13.5a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5M6 15h12"/>' ),
			'hairdryer'  => array( 'Hajszárító', '<circle class="d" cx="9" cy="9.5" r="5.5"/><circle cx="9" cy="9.5" r="5.5"/><circle cx="9" cy="9.5" r="2"/><path d="M14 7h6.5v5H14M7.6 15l-1.1 6h3l1.1-5.6"/>' ),
			'toiletries' => array( 'Piperecikkek', '<path d="M9 3h6M10.2 3v3M13.8 3v3"/><rect class="d" x="6.5" y="6" width="11" height="15" rx="3"/><rect x="6.5" y="6" width="11" height="15" rx="3"/><path d="M9.5 12h5M9.5 15h3"/>' ),
			'kitchen'    => array( 'Konyha', '<path class="d" d="M4 10h16v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/><path d="M4 10h16v6a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4zM2 12h2M20 12h2M5 10a7 3.2 0 0 1 14 0"/><circle cx="12" cy="5.6" r="1"/>' ),
			'stove'      => array( 'Főzőlap', '<rect class="d" x="3" y="3" width="18" height="18" rx="3"/><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="2.3"/><circle cx="15.5" cy="8.5" r="2.3"/><circle cx="8.5" cy="15.5" r="2.3"/><circle cx="15.5" cy="15.5" r="2.3"/>' ),
			'microwave'  => array( 'Mikrohullámú sütő', '<rect class="d" x="2.5" y="5" width="19" height="14" rx="2.6"/><rect x="2.5" y="5" width="19" height="14" rx="2.6"/><rect x="5.5" y="8" width="9" height="8" rx="1.3"/><path d="M18 8.6v.01M18 12v.01M17.3 15.5h1.4"/>' ),
			'fridge'     => array( 'Hűtőszekrény', '<rect class="d" x="6" y="2.5" width="12" height="19" rx="2.6"/><rect x="6" y="2.5" width="12" height="19" rx="2.6"/><path d="M6 9.5h12M9 5.5v1.5M9 12.5v3"/>' ),
			'kettle'     => array( 'Vízforraló', '<path class="d" d="M7 20l1-9h8l1 9z"/><path d="M6 20h12M7 20l1-9h8l1 9M9.5 11l.5-3h4l.5 3M16.3 12.5c2.3 0 3.2 1.3 3.2 3.2 0 1.6-.8 2.6-2 2.8M8 13.5 5 11"/>' ),
			'coffee'     => array( 'Kávéfőző', '<path class="d" d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5zM17 10.5h1.5a2.5 2.5 0 0 1 0 5H17M3 21h16M8.5 3.5c-.8.9-.8 1.6 0 2.5M12.5 3.5c-.8.9-.8 1.6 0 2.5"/>' ),
			'toaster'    => array( 'Kenyérpirító', '<path class="d" d="M4 11a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v8H4z"/><path d="M4 11a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v8H4zM8 7V4.5a1.5 1.5 0 0 1 3 0V7M13 7V4.5a1.5 1.5 0 0 1 3 0V7M20 13h1.5M3 21h18"/>' ),
			'dishwasher' => array( 'Mosogatógép', '<rect class="d" x="4" y="2.5" width="16" height="19" rx="2.6"/><rect x="4" y="2.5" width="16" height="19" rx="2.6"/><path d="M4 7.5h16M8.5 11.5v5M12 10.5v6M15.5 11.5v5M7 17.5h10M7.5 5h.01M10 5h.01"/>' ),
			'washer'     => array( 'Mosógép', '<rect class="d" x="4" y="2.5" width="16" height="19" rx="2.6"/><rect x="4" y="2.5" width="16" height="19" rx="2.6"/><path d="M4 7h16M7.5 4.8h.01M10 4.8h.01"/><circle cx="12" cy="14" r="4.3"/><path d="M9.6 14.6c1.2-.9 2.4.9 4.8 0"/>' ),
			'dryer'      => array( 'Ruhaszárító', '<path d="M3 5.5h18M5 5.5 3.5 21M19 5.5 20.5 21M4.3 13h15.4"/><path class="d" d="M7.5 5.5v6.5a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V5.5z"/><path d="M7.5 5.5v6.5a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V5.5M15 5.5v4.5a1.2 1.2 0 0 0 2.4 0V5.5"/>' ),
			'iron'       => array( 'Vasaló', '<path class="d" d="M3 18c0-4.4 3.6-7.5 8-7.5h10V18z"/><path d="M3 18c0-4.4 3.6-7.5 8-7.5h10V18zM8 10.5V7a1.5 1.5 0 0 1 1.5-1.5H18a3 3 0 0 1 3 3v2M7.5 15h.01M10.5 15h.01"/>' ),
			'bed'        => array( 'Ágy', '<path class="d" d="M11 9h7a3 3 0 0 1 3 3v2H11z"/><path d="M3 6v13M3 14h18v5M21 14v-2a3 3 0 0 0-3-3h-7v5"/><rect x="5.3" y="9.6" width="3.6" height="3" rx="1.2"/>' ),
			'linen'      => array( 'Ágynemű', '<rect class="d" x="3.5" y="12" width="17" height="4" rx="1.5"/><rect x="5" y="8" width="14" height="4" rx="1.5"/><rect x="3.5" y="12" width="17" height="4" rx="1.5"/><rect x="5" y="16" width="14" height="4" rx="1.5"/>' ),
			'crib'       => array( 'Kiságy', '<rect class="d" x="4" y="8.5" width="16" height="9"/><path d="M4 4v17M20 4v17M4 8.5h16M4 17.5h16M8 8.5v9M12 8.5v9M16 8.5v9"/>' ),
			'desk'       => array( 'Munkaterület', '<rect class="d" x="5" y="4.5" width="14" height="10" rx="1.6"/><rect x="5" y="4.5" width="14" height="10" rx="1.6"/><path d="M2.5 19h19M3.5 19 5 14.5h14l1.5 4.5"/>' ),
			'parking'    => array( 'Parkoló', '<rect class="d" x="3" y="3" width="18" height="18" rx="4.5"/><rect x="3" y="3" width="18" height="18" rx="4.5"/><path d="M9.5 17V7.5h3.7a3 3 0 0 1 0 6H9.5"/>' ),
			'balcony'    => array( 'Erkély / terasz', '<circle class="d" cx="12" cy="6" r="2.6"/><circle cx="12" cy="6" r="2.6"/><path d="M3 12h18M3 21h18M5.5 12v9M9.8 12v9M14.2 12v9M18.5 12v9"/>' ),
			'garden'     => array( 'Kert', '<circle class="d" cx="12" cy="9" r="5.5"/><circle cx="12" cy="9" r="5.5"/><path d="M12 21v-7M12 14l-2.3-2.3M12 16.5l2.3-2.3M7 21h10"/>' ),
			'view'       => array( 'Kilátás', '<path class="d" d="M3 19l6-8 4 5 3-3.5 5 6.5z"/><path d="M3 19l6-8 4 5 3-3.5 5 6.5z"/><circle cx="16.5" cy="6.5" r="2"/>' ),
			'key'        => array( 'Önálló bejutás / kulcs', '<circle class="d" cx="8" cy="15" r="4.5"/><circle cx="8" cy="15" r="4.5"/><path d="M11.2 11.8 20 3M16.5 6.5 19 9M14 9l2 2"/>' ),
			'safe'       => array( 'Széf', '<rect class="d" x="3.5" y="3.5" width="17" height="15.5" rx="2.6"/><rect x="3.5" y="3.5" width="17" height="15.5" rx="2.6"/><circle cx="12" cy="11.2" r="3.4"/><path d="M12 7.8v1.2M6.5 19v2M17.5 19v2"/>' ),
			'elevator'   => array( 'Lift', '<rect class="d" x="4" y="2.5" width="16" height="19" rx="2.6"/><rect x="4" y="2.5" width="16" height="19" rx="2.6"/><path d="M12 2.5v19M6.7 10l1.6-2.2L9.9 10M14.1 8l1.6 2.2L17.3 8"/>' ),
			'pet'        => array( 'Háziállat', '<path class="d" d="M12 12c2.6 0 5 3 5 5.2 0 1.6-1.2 2.3-2.6 2.3-1 0-1.6-.5-2.4-.5s-1.4.5-2.4.5C8.2 19.5 7 18.8 7 17.2 7 15 9.4 12 12 12z"/><path d="M12 12c2.6 0 5 3 5 5.2 0 1.6-1.2 2.3-2.6 2.3-1 0-1.6-.5-2.4-.5s-1.4.5-2.4.5C8.2 19.5 7 18.8 7 17.2 7 15 9.4 12 12 12z"/><circle cx="6" cy="10" r="1.7"/><circle cx="9.6" cy="5.8" r="1.7"/><circle cx="14.4" cy="5.8" r="1.7"/><circle cx="18" cy="10" r="1.7"/>' ),
			'bike'       => array( 'Kerékpár', '<circle class="d" cx="6" cy="16" r="3.6"/><circle class="d" cx="18" cy="16" r="3.6"/><circle cx="6" cy="16" r="3.6"/><circle cx="18" cy="16" r="3.6"/><path d="M6 16l4-7h5l3 7M10 9l4.5 7H6M8.8 6.5h3"/>' ),
			'grill'      => array( 'Grill', '<path class="d" d="M5 9h14a7 7 0 0 1-14 0z"/><path d="M5 9h14a7 7 0 0 1-14 0zM8.5 15.5 6.5 21M15.5 15.5l2 5.5M12 16v5M9.5 3.5c-.7.8-.7 1.7 0 2.5M14.5 3.5c-.7.8-.7 1.7 0 2.5"/>' ),
			'firstaid'   => array( 'Elsősegély', '<rect class="d" x="3" y="6.5" width="18" height="14" rx="2.6"/><rect x="3" y="6.5" width="18" height="14" rx="2.6"/><path d="M9 6.5v-2h6v2M12 10.5v6M9 13.5h6"/>' ),
			'clean'      => array( 'Takarítás', '<path class="d" d="M12 3l1.8 4.7 4.7 1.8-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M12 3l1.8 4.7 4.7 1.8-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8zM19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/>' ),
		);
		/** Bővíthető: add_filter( 'zen_amenity_icons', … ). */
		$i = apply_filters( 'zen_amenity_icons', $i );
		return $i;
	}

	/**
	 * Felismerési szabályok: ikon => kulcsszavak (kisbetűvel, 13 nyelv).
	 * A szövegben LEGKORÁBBAN előforduló kulcsszó nyer – így „Fürdőszoba … hajszárító” fürdő, „Konyha (mikró…)” konyha.
	 * A szóközzel kezdődő kulcsszó szóelejére illeszkedik.
	 */
	public static function rules() {
		$r = array(
			'nosmoke'    => array( 'dohány', 'non-smok', 'non smok', 'no smok', 'nichtraucher', 'nekuř', 'nefajč', 'no fumador', 'non-fumeur', 'non fumeur', 'nepuša', 'non fumator', 'rookvrij', 'niepalą', 'nefumător', 'nekadil' ),
			'ac'         => array( 'légkond', 'klíma', 'klima', 'air condition', 'air-condition', 'aircondition', 'airco', 'aire acond', 'climatis', 'aria condiz', 'aer condi' ),
			'heating'    => array( 'fűtés', 'heating', 'heizung', 'topení', 'calefacc', 'chauffage', 'grijanj', 'riscaldam', 'verwarming', 'ogrzewan', 'încălzir', 'kúrenie', 'ogrevanj', 'radiátor', 'radiator' ),
			'floor'      => array( 'parkett', 'parquet', 'parketvloer', 'wood floor', 'wooden floor', 'holzboden', 'dřevěná podl', 'drevená podl', 'suelo de madera', 'drveni pod', 'pavimento in legno', 'podłoga', 'pardoseal', 'lesena tla', 'padló' ),
			'wardrobe'   => array( 'gardrób', 'szekrény', 'wardrobe', 'cupboard', 'closet', 'kleiderschrank', 'šatn', 'skříň', 'skriň', 'vestidor', 'armario', 'penderie', 'armoire', 'ormar', 'guardaroba', 'armadio', 'kledingkast', 'garderob', 'szafa', 'dulap', 'șifonier', 'garderobn' ),
			'tv'         => array( ' tv', 'smart-tv', 'smarttv', 'televízi', 'televisi', 'television', 'fernseh', 'netflix' ),
			'wifi'       => array( 'wifi', 'wi-fi', 'wlan', 'internet' ),
			'bathtub'    => array( 'kád', 'bathtub', 'badewanne', 'baignoire', 'vasca da bagno', 'bañera', 'vana', 'ligbad', 'wanna' ),
			'shower'     => array( 'fürdő', 'zuhany', 'bathroom', 'badezimmer', 'koupeln', 'kúpeľň', 'baño', 'salle de bain', 'kupaonic', 'bagno', 'badkamer', 'łazienk', ' baie', 'kopalnic', 'shower', 'dusche', 'douche', 'ducha', 'doccia', 'prysznic', 'sprch', ' tuš', 'duș' ),
			'towel'      => array( 'törölköző', 'towel', 'handtuch', 'ručník', 'uterák', 'toalla', 'serviette', 'ručnik', 'asciugaman', 'handdoek', 'ręcznik', 'prosoap', 'brisač' ),
			'hairdryer'  => array( 'hajszárító', 'hair dryer', 'hairdryer', 'haartrockner', 'föhn', 'secador', 'sèche-cheveux', 'sušilo za kosu', 'asciugacapelli', 'haardroger', 'suszarka do włosów', 'uscător de păr' ),
			'toiletries' => array( 'pipere', 'tusfürdő', 'sampon', 'toiletries', 'shampoo', 'kosmetik', 'artículos de aseo', 'articles de toilette' ),
			'kitchen'    => array( 'konyh', 'kitchen', 'küche', 'kuchyň', 'cocina', 'cuisine', 'kuhinj', 'cucina', 'keuken', 'kuchni', 'bucătări', 'teakonyha', 'kitchenette' ),
			'stove'      => array( 'főzőlap', 'tűzhely', 'stove', 'hob', 'cooktop', 'kochfeld', 'herd' ),
			'microwave'  => array( 'mikrohull', 'mikró', 'microwave', 'mikrowelle', 'mikrovln', 'microondas', 'micro-ondes', 'mikrovaln', 'microonde', 'magnetron', 'mikrofal', 'cuptor cu micro' ),
			'fridge'     => array( 'hűtő', 'fridge', 'refrigerator', 'kühlschrank', 'lednič', 'chladnič', 'nevera', 'frigo', 'hladnjak', 'koelkast', 'lodówk', 'hladilnik' ),
			'kettle'     => array( 'vízforraló', 'kettle', 'wasserkocher', 'rychlovarn', 'hervidor', 'bouilloire', 'kuhalo za vodu', 'bollitore', 'waterkoker', 'czajnik', 'fierbător', 'kanvica' ),
			'coffee'     => array( 'kávé', 'coffee', 'kaffee', 'espresso', 'kotyogó', 'nespresso', 'moka', 'café', 'caffè', 'kava', 'kawa', 'koffie' ),
			'toaster'    => array( 'kenyérpirító', 'toaster', 'tostadora', 'grille-pain', 'tostapane', 'broodrooster' ),
			'dishwasher' => array( 'mosogatógép', 'dishwasher', 'spülmaschine', 'geschirrspül', 'myčk', 'lavavajillas', 'lave-vaisselle', 'lavastoviglie', 'vaatwasser', 'zmywark' ),
			'washer'     => array( 'mosógép', 'washing machine', 'waschmaschine', 'pračk', 'lavadora', 'lave-linge', 'perilica', 'lavatrice', 'wasmachine', 'pralk', 'mașină de spălat', 'pralni stroj' ),
			'dryer'      => array( 'ruhaszárító', 'szárító', 'clothes dryer', 'drying rack', 'clothes horse', 'wäscheständer', 'wäschetrockner', 'sušák', 'sušiak', 'sušilo za rublje', 'sušilnik za perilo', 'tendedero', 'étendoir', 'stendibiancheria', 'droogrek', 'suszarka na pranie', 'uscător de rufe' ),
			'iron'       => array( 'vasaló', 'ironing', ' iron', 'bügel', 'žehli', 'plancha', 'fer à repasser', 'ferro da stiro', 'strijkijzer', 'żelazko', 'fier de călcat' ),
			'bed'        => array( 'boxspring', 'franciaágy', 'ágy', ' bed', 'doppelbett', 'lůžk', 'postel', ' cama', ' lit ', ' lits', 'krevet', 'letto', 'letti', 'łóżk', 'paturi', ' pat ' ),
			'linen'      => array( 'ágynemű', 'bed linen', 'bettwäsche', 'ropa de cama', 'linge de lit', 'lenzuol', 'beddengoed', 'pościel' ),
			'crib'       => array( 'kiságy', 'baby cot', 'crib', 'babybett', 'kinderbett', 'cuna', 'lit bébé', 'culla', 'babybedje', 'łóżeczk' ),
			'desk'       => array( 'íróasztal', 'munkaterület', 'workspace', 'work desk', 'desk', 'schreibtisch', 'escritorio', 'bureau', 'scrivania' ),
			'parking'    => array( 'parkol', 'parking', 'parkplatz', 'garázs', 'garage', 'aparcamiento', 'parcheggio', 'parkoviště', 'parkovisk', 'parkiran', 'parcare' ),
			'balcony'    => array( 'erkély', 'terasz', 'balcony', 'terrace', 'balkon', 'terrasse', 'balcón', 'terraza', 'balcon', 'terrazza', 'terras', 'taras' ),
			'garden'     => array( 'kert', 'garden', 'garten', 'jardín', 'jardin', 'giardino', 'tuin', 'ogród', 'zahrad', 'vrt ' ),
			'view'       => array( 'kilátás', 'panoráma', 'view', 'aussicht', 'vista', 'vue ', 'uitzicht', 'widok', 'výhled' ),
			'key'        => array( 'kulcs', 'self check', 'self-check', 'key', 'schlüssel', 'kódzár', 'okos zár', 'smart lock', 'check-in' ),
			'safe'       => array( 'széf', ' safe', 'tresor', 'caja fuerte', 'coffre-fort', 'cassaforte', 'kluis', 'sejf' ),
			'elevator'   => array( 'lift', 'elevator', 'aufzug', 'ascensor', 'ascenseur', 'ascensore', 'výtah', 'dizalo', 'winda' ),
			'pet'        => array( 'háziállat', 'kisállat', 'kutya', ' pet ', ' pets', 'pet-friendly', 'haustier', 'hund', 'mascota', 'animaux', 'animali', 'huisdier', 'zwierz' ),
			'bike'       => array( 'kerékpár', 'bicikli', 'bicycle', 'bike', 'fahrrad', 'bicicleta', 'vélo', 'bicicletta', 'fiets', 'rower', 'kolo' ),
			'grill'      => array( 'grill', 'bbq', 'barbecue', 'bogrács' ),
			'firstaid'   => array( 'elsősegély', 'first aid', 'erste hilfe', 'botiquín', 'premiers secours', 'pronto soccorso', 'ehbo', 'apteczk' ),
			'clean'      => array( 'takarítás', 'cleaning', 'reinigung', 'limpieza', 'ménage', 'pulizia', 'schoonmaak', 'sprzątan', 'úklid' ),
		);
		return apply_filters( 'zen_amenity_rules', $r );
	}

	/** Szétbontja a sort: [ ikon, tiszta szöveg, kézi-e ]. */
	public static function parse( $text ) {
		$text = (string) $text;
		if ( preg_match( self::PREFIX_RE, $text, $m ) ) {
			$name = $m[1];
			$text = (string) preg_replace( self::PREFIX_RE, '', $text );
			if ( isset( self::icons()[ $name ] ) ) {
				return array( $name, $text, true );
			}
		}
		return array( self::match( $text ), $text, false );
	}

	/** Automatikus ikon a szövegből (a legkorábban talált kulcsszó nyer). */
	public static function match( $text ) {
		$t    = ' ' . mb_strtolower( wp_strip_all_tags( (string) $text ), 'UTF-8' ) . ' ';
		$best = 'check';
		$pos  = PHP_INT_MAX;
		$len  = 0;
		foreach ( self::rules() as $icon => $words ) {
			foreach ( (array) $words as $w ) {
				$w = (string) $w;
				$p = '' === $w ? false : mb_strpos( $t, $w, 0, 'UTF-8' );
				// Legkorábbi találat nyer; azonos helyen a hosszabb (pontosabb) kulcsszó.
				if ( false !== $p && ( $p < $pos || ( $p === $pos && mb_strlen( $w, 'UTF-8' ) > $len ) ) ) {
					$pos  = $p;
					$len  = mb_strlen( $w, 'UTF-8' );
					$best = $icon;
				}
			}
		}
		return $best;
	}

	/** Inline SVG (currentColor; a duotone rész halvány kitöltéssel). */
	public static function svg( $name, $size = 22 ) {
		$all = self::icons();
		$p   = isset( $all[ $name ] ) ? $all[ $name ][1] : $all['check'][1];
		$p   = str_replace( array( 'class="d"', 'class="f"' ), array( 'fill="currentColor" fill-opacity=".16" stroke="none"', 'fill="currentColor" stroke="none"' ), $p );
		$s   = (int) $size;
		return '<svg class="zen-amen-svg" width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
	}

	/** A sablonnak: [ 'icon' => név, 'svg' => html, 'text' => tiszta szöveg ]. */
	public static function item( $text, $size = 22 ) {
		list( $icon, $clean ) = self::parse( $text );
		return array(
			'icon' => $icon,
			'svg'  => self::svg( $icon, $size ),
			'text' => $clean,
		);
	}

	/** Az admin ikonválasztónak: katalógus + szabályok (az élő előnézethez). */
	public static function admin_data() {
		$icons = array();
		foreach ( self::icons() as $k => $v ) {
			$icons[ $k ] = array(
				'l'   => $v[0],
				'svg' => self::svg( $k, 20 ),
			);
		}
		return array(
			'icons' => $icons,
			'rules' => self::rules(),
		);
	}
}
