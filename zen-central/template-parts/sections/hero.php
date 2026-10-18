<?php
/**
 * Hero – kép vagy videó háttér, cím, gombok, morzsamenü.
 * Ha a foglalás be van kapcsolva: két egyforma oszlop – bal oldalt a szöveg, jobb oldalt üveg foglalódoboz.
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
$zc_split = ! empty( $d['booking_bar'] ) && class_exists( 'Zen_Booking' );
?>
<section id="<?php echo esc_attr( $zc_id ); ?>" class="zc-sec zc-hero zc-hero--<?php echo esc_attr( $zc_h ); ?><?php echo $zc_split ? ' zc-hero--split' : ''; ?> tone-dark" style="--ov:<?php echo esc_attr( $zc_ov ); ?>">
	<?php if ( ! empty( $d['media'] ) ) : ?>
		<div class="zc-hero__media"><?php echo zc_media( $d['media'], array( 'cover' => true, 'eager' => $zc_first, 'background' => true, 'alt' => $d['title'] ?? '', 'sizes' => '100vw' ) ); // phpcs:ignore ?></div>
	<?php endif; ?>
	<div class="zc-hero__veil" aria-hidden="true"></div>
	<div class="zc-wrap zc-hero__in">
		<div class="zc-hero__copy">
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
		</div>
		<?php if ( $zc_split ) : ?>
			<aside class="zc-hero__panel" aria-label="<?php echo esc_attr( zc_t( 'hero_panel_title' ) ); ?>">
				<div class="zc-hero__panel-head">
					<span class="zc-hero__panel-ico" aria-hidden="true"><?php echo zc_icon( 'calendar', 22 ); // phpcs:ignore ?></span>
					<div>
						<p class="zc-hero__panel-title"><?php echo esc_html( ! empty( $d['panel_title'] ) ? $d['panel_title'] : zc_t( 'hero_panel_title' ) ); ?></p>
						<p class="zc-hero__panel-sub"><?php echo zc_icon( 'shield', 14 ); // phpcs:ignore ?><span><?php echo esc_html( ! empty( $d['panel_text'] ) ? $d['panel_text'] : zc_t( 'best_price' ) ); ?></span></p>
					</div>
				</div>
				<?php echo Zen_Booking::bar( array( 'variant' => 'panel' ) ); // phpcs:ignore ?>
			</aside>
		<?php endif; ?>
	</div>
</section>
