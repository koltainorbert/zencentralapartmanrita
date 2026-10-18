<?php
/**
 * Térkép + közeli látnivalók – szimmetrikus: fent középre zárt fejléc, alatta két egyforma oszlop
 * (bal: térkép, jobb: a helyek egymás mellett, egyforma kártyákon).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_items = array_values( array_filter( array_map( 'trim', (array) ( $d['items'] ?? array() ) ) ) );
echo zc_section_open( $s, 'zc-maplist' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<?php echo zc_head( $d, 'h2', true ); // phpcs:ignore ?>
	<?php if ( ! empty( $d['html'] ) ) : ?><div class="zc-prose zc-prose--center zc-maplist__intro"><?php echo zc_html( $d['html'] ); // phpcs:ignore ?></div><?php endif; ?>
	<div class="zc-maplist__grid">
		<?php echo zc_map( $d['map_query'] ?? zc_opt( 'general.map_query', '' ), 'zc-maplist__map' ); // phpcs:ignore ?>
		<ul class="zc-distances" data-routes>
			<?php foreach ( $zc_items as $zc_i => $zc_it ) : ?>
				<?php
				$zc_parts = array_map( 'trim', explode( ':', (string) $zc_it, 2 ) );
				$zc_dist  = $zc_parts[1] ?? '';
				// Távolság → mód: 2,5 km felett autóval, alatta gyalog. Helyi helyhez a város is a célba kerül.
				$zc_m     = 0;
				if ( preg_match( '/([\d.,]+)\s*(km|m)\b/i', $zc_dist, $zc_mm ) ) {
					$zc_m = (float) str_replace( ',', '.', $zc_mm[1] ) * ( 'km' === strtolower( $zc_mm[2] ) ? 1000 : 1 );
				}
				$zc_mode = $zc_m > 2500 ? 'driving' : 'walking';
				$zc_city = (string) zc_opt( 'general.city', 'Veszprém' );
				$zc_dest = ( $zc_m >= 5000 || false !== stripos( $zc_parts[0], $zc_city ) ) ? $zc_parts[0] : $zc_parts[0] . ', ' . trim( zc_opt( 'general.zip', '' ) . ' ' . $zc_city );
				?>
				<li data-reveal style="--d:<?php echo (int) ( $zc_i % 6 ); ?>">
					<button type="button" class="zc-distances__btn" data-route="<?php echo esc_attr( $zc_dest ); ?>" data-mode="<?php echo esc_attr( $zc_mode ); ?>" aria-pressed="false">
						<span class="zc-distances__ico"><?php echo zc_icon( 'walking' === $zc_mode ? 'pin' : 'map', 16 ); // phpcs:ignore ?></span>
						<span class="zc-distances__name"><?php echo esc_html( $zc_parts[0] ); ?></span>
						<?php if ( '' !== $zc_dist ) : ?><b class="zc-distances__val"><?php echo esc_html( $zc_dist ); ?></b><?php endif; ?>
						<span class="zc-distances__go" aria-hidden="true"><?php echo zc_icon( 'arrow', 16 ); // phpcs:ignore ?></span>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php echo zc_section_deco( $s, 'card' ); // phpcs:ignore – lótusz a lista jobb felső sarkán ?>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
