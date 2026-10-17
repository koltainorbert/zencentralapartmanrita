<?php
/**
 * Foglaló szekció.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_engine = ( $d['engine'] ?? 'inherit' );
$zc_html   = function_exists( 'zen_booking_widget' ) ? zen_booking_widget( 'inherit' === $zc_engine ? array() : array( 'engine' => $zc_engine ) ) : '';
if ( ! $zc_html ) {
	return;
}
echo zc_section_open( $s, 'zc-bookingsec' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<?php if ( ! empty( $d['text'] ) ) : ?><p class="zc-lead zc-center"><?php echo esc_html( $d['text'] ); ?></p><?php endif; ?>
	<?php echo $zc_html; // phpcs:ignore ?>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
