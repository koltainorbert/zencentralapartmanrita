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
		<ul class="zc-distances">
			<?php foreach ( $zc_items as $zc_i => $zc_it ) : ?>
				<?php $zc_parts = array_map( 'trim', explode( ':', (string) $zc_it, 2 ) ); ?>
				<li data-reveal style="--d:<?php echo (int) ( $zc_i % 6 ); ?>">
					<span class="zc-distances__ico"><?php echo zc_icon( 'pin', 16 ); // phpcs:ignore ?></span>
					<span class="zc-distances__name"><?php echo esc_html( $zc_parts[0] ); ?></span>
					<?php if ( isset( $zc_parts[1] ) && '' !== $zc_parts[1] ) : ?><b class="zc-distances__val"><?php echo esc_html( $zc_parts[1] ); ?></b><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
