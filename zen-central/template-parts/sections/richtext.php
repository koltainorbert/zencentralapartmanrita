<?php
/**
 * Szabad szöveg (HTML + rövidkódok).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
if ( empty( $d['html'] ) && empty( $d['title'] ) ) {
	return;
}
echo zc_section_open( $s, 'zc-rich' ); // phpcs:ignore
?>
<div class="zc-wrap zc-wrap--text">
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<div class="zc-prose"><?php echo zc_html( $d['html'] ?? '' ); // phpcs:ignore ?></div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
