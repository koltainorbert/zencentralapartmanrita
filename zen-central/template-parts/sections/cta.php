<?php
/**
 * Felhívás (CTA) – üveg kártya háttérképpel.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
echo zc_section_open( $s, 'zc-cta' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<div class="zc-cta__card" data-reveal>
		<span class="zc-cta__mark" aria-hidden="true"><?php echo zc_icon( 'lotus', 44 ); // phpcs:ignore ?></span>
		<?php if ( ! empty( $d['title'] ) ) : ?><h2 class="zc-title"><?php echo esc_html( $d['title'] ); ?></h2><?php endif; ?>
		<?php foreach ( (array) ( $d['lines'] ?? array() ) as $zc_l ) : ?>
			<?php if ( '' !== trim( (string) $zc_l ) ) : ?><p class="zc-cta__line"><?php echo esc_html( $zc_l ); ?></p><?php endif; ?>
		<?php endforeach; ?>
		<?php echo zc_buttons( $d['buttons'] ?? array(), 'zc-btns--center' ); // phpcs:ignore ?>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
