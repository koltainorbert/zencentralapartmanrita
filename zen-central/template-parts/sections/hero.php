<?php
/**
 * Hero – kép vagy videó háttér, cím, gombok, foglalósáv, morzsamenü.
 *
 * @var array $s @var array $d @var array $ctx
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_h     = in_array( $d['height'] ?? 'full', array( 'full', 'tall', 'medium', 'small' ), true ) ? $d['height'] : 'full';
$zc_st    = $s['style'] ?? array();
$zc_ov    = isset( $zc_st['overlay'] ) ? max( 0, min( 1, (float) $zc_st['overlay'] ) ) : 0.38;
$zc_first = 0 === (int) $ctx['index'];
$zc_tag   = $zc_first ? 'h1' : 'h2';
$zc_id    = ! empty( $zc_st['anchor'] ) ? sanitize_title( $zc_st['anchor'] ) : 'zc-' . sanitize_html_class( $s['id'] );
?>
<section id="<?php echo esc_attr( $zc_id ); ?>" class="zc-sec zc-hero zc-hero--<?php echo esc_attr( $zc_h ); ?> tone-dark" style="--ov:<?php echo esc_attr( $zc_ov ); ?>">
	<?php if ( ! empty( $d['media'] ) ) : ?>
		<div class="zc-hero__media"><?php echo zc_media( $d['media'], array( 'cover' => true, 'eager' => $zc_first, 'background' => true, 'alt' => $d['title'] ?? '', 'sizes' => '100vw' ) ); // phpcs:ignore ?></div>
	<?php endif; ?>
	<div class="zc-hero__veil" aria-hidden="true"></div>
	<div class="zc-wrap zc-hero__in">
		<?php if ( ! empty( $d['breadcrumbs'] ) ) : ?>
			<?php echo zc_breadcrumbs(); // phpcs:ignore ?>
		<?php endif; ?>
		<?php if ( ! empty( $d['kicker'] ) ) : ?>
			<p class="zc-kicker zc-hero__kicker"><?php echo esc_html( $d['kicker'] ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $d['title'] ) ) : ?>
			<<?php echo esc_attr( $zc_tag ); ?> class="zc-hero__title"><?php echo esc_html( $d['title'] ); ?></<?php echo esc_attr( $zc_tag ); ?>>
		<?php endif; ?>
		<?php if ( ! empty( $d['text'] ) ) : ?>
			<p class="zc-hero__text"><?php echo esc_html( $d['text'] ); ?></p>
		<?php endif; ?>
		<?php echo zc_buttons( $d['buttons'] ?? array(), 'zc-hero__btns' ); // phpcs:ignore ?>
		<?php if ( ! empty( $d['booking_bar'] ) && class_exists( 'Zen_Booking' ) ) : ?>
			<div class="zc-hero__bar"><?php echo Zen_Booking::bar(); // phpcs:ignore ?></div>
		<?php endif; ?>
	</div>
	<?php if ( 'full' === $zc_h ) : ?>
		<a class="zc-hero__scroll" href="#zc-after-hero" aria-label="<?php echo esc_attr( zc_t( 'scroll' ) ); ?>"><span></span></a>
		<span id="zc-after-hero" class="zc-anchor"></span>
	<?php endif; ?>
</section>
