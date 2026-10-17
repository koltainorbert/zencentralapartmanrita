<?php
/**
 * Számok (stat csempék).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_items = array_filter( (array) ( $d['items'] ?? array() ), fn( $i ) => ! empty( $i['value'] ) );
if ( ! $zc_items ) {
	return;
}
echo zc_section_open( $s, 'zc-stats' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<ul class="zc-stats__grid">
		<?php foreach ( $zc_items as $zc_i ) : ?>
			<li data-reveal><b data-count><?php echo esc_html( $zc_i['value'] ); ?></b><span><?php echo esc_html( $zc_i['label'] ?? '' ); ?></span></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
