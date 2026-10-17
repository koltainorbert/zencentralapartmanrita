<?php
/**
 * Galéria – szűrő pillek, masonry, lightbox (kép és videó).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_groups = array_values( array_filter( (array) ( $d['groups'] ?? array() ), fn( $g ) => ! empty( $g['items'] ) ) );
if ( ! $zc_groups ) {
	return;
}
echo zc_section_open( $s, 'zc-gallery' ); // phpcs:ignore
?>
<div class="zc-wrap" data-gallery>
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<?php if ( count( $zc_groups ) > 1 ) : ?>
		<div class="zc-pills zc-pills--center" data-filter>
			<button type="button" class="zc-pill" aria-pressed="true" data-f="*"><?php echo esc_html( zc_t( 'all' ) ); ?></button>
			<?php foreach ( $zc_groups as $zc_gi => $zc_g ) : ?>
				<?php if ( ! empty( $zc_g['title'] ) ) : ?><button type="button" class="zc-pill" aria-pressed="false" data-f="g<?php echo (int) $zc_gi; ?>"><?php echo esc_html( $zc_g['title'] ); ?></button><?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<div class="zc-masonry">
		<?php foreach ( $zc_groups as $zc_gi => $zc_g ) : ?>
			<?php foreach ( (array) $zc_g['items'] as $zc_it ) : ?>
				<?php
				$zc_m = $zc_it['media'] ?? $zc_it;
				if ( empty( $zc_m['url'] ) && empty( $zc_m['id'] ) ) {
					continue;
				}
				$zc_full = zen_media_url( $zc_m, 'full' );
				?>
				<button type="button" class="zc-masonry__item" data-g="g<?php echo (int) $zc_gi; ?>" data-lb="<?php echo esc_url( $zc_full ); ?>" data-type="<?php echo esc_attr( $zc_m['type'] ?? 'image' ); ?>" aria-label="<?php echo esc_attr( zc_t( 'open_gallery' ) ); ?>" data-reveal>
					<?php echo zc_media( $zc_m, array( 'alt' => $zc_m['alt'] ?: ( $zc_g['title'] ?? '' ), 'autoplay' => true, 'sizes' => '(min-width: 1100px) 25vw, 50vw', 'size' => 'large' ) ); // phpcs:ignore ?>
					<span class="zc-masonry__zoom" aria-hidden="true"><?php echo zc_icon( 'zoom', 18 ); // phpcs:ignore ?></span>
				</button>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
