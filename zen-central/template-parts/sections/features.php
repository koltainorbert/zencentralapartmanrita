<?php
/**
 * Jellemzők lista.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_items = array_filter( (array) ( $d['items'] ?? array() ) );
if ( ! $zc_items ) {
	return;
}
echo zc_section_open( $s, 'zc-features' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<ul class="zc-feature-grid" style="--cols:<?php echo (int) max( 2, min( 4, (int) ( $d['columns'] ?? 3 ) ) ); ?>">
		<?php foreach ( $zc_items as $zc_it ) : ?>
			<li class="zc-feature" data-reveal><?php echo zc_icon( 'check', 18 ); // phpcs:ignore ?><span><?php echo esc_html( is_array( $zc_it ) ? ( $zc_it['text'] ?? '' ) : $zc_it ); ?></span></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
