<?php
/**
 * Űrlap szekció (kapcsolat / hírlevél / elégedettség).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_type = in_array( $d['form'] ?? 'contact', array( 'contact', 'newsletter', 'feedback' ), true ) ? $d['form'] : 'contact';
$zc_html = function_exists( 'zen_form' ) ? zen_form( $zc_type ) : '';
if ( ! $zc_html ) {
	return;
}
echo zc_section_open( $s, 'zc-formsec zc-formsec--' . $zc_type ); // phpcs:ignore
?>
<div class="zc-wrap zc-wrap--narrow">
	<div class="zc-card zc-card--glass zc-formsec__card" data-reveal>
		<?php echo zc_head( $d ); // phpcs:ignore ?>
		<?php if ( ! empty( $d['text'] ) ) : ?><p class="zc-lead zc-center"><?php echo esc_html( $d['text'] ); ?></p><?php endif; ?>
		<?php echo $zc_html; // phpcs:ignore ?>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
