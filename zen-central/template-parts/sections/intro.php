<?php
/**
 * Bevezető: szöveg + kép/videó (bal/jobb/középre).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_layout = in_array( $d['layout'] ?? 'right', array( 'left', 'right', 'center' ), true ) ? $d['layout'] : 'right';
echo zc_section_open( $s, 'zc-intro zc-intro--' . $zc_layout ); // phpcs:ignore
?>
<div class="zc-wrap zc-intro__grid">
	<div class="zc-intro__text">
		<?php echo zc_head( $d, 'h2', 'center' === $zc_layout ); // phpcs:ignore ?>
		<?php if ( ! empty( $d['html'] ) ) : ?><div class="zc-prose"><?php echo zc_html( $d['html'] ); // phpcs:ignore ?></div><?php endif; ?>
		<?php echo zc_buttons( $d['buttons'] ?? array() ); // phpcs:ignore ?>
	</div>
	<?php if ( ! empty( $d['media']['url'] ) && 'center' !== $zc_layout ) : ?>
		<figure class="zc-intro__media" data-parallax><?php echo zc_media( $d['media'], array( 'cover' => true, 'alt' => $d['title'] ?? '', 'sizes' => '(min-width: 900px) 50vw, 100vw' ) ); // phpcs:ignore ?></figure>
		<?php echo zc_section_deco( $s, 'card' ); // phpcs:ignore – lótusz a kép sarkán ?>
	<?php endif; ?>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
