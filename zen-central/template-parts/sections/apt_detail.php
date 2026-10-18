<?php
/**
 * Apartman részletek: felül két egyforma oszlop (kép/videó | üveg foglalókártya), alatta leírás, tények, felszereltség, galéria.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_apt  = $d['apt'] ?? '';
$zc_aptd = $zc_apt && function_exists( 'zen_apartment' ) ? zen_apartment( $zc_apt ) : null;
$zc_gal  = array_values( array_filter( (array) ( $d['gallery'] ?? array() ), fn( $m ) => ! empty( $m['url'] ) || ! empty( $m['id'] ) ) );
$zc_ico  = array( 'Idegenforgalmi' => 'card', 'Tourist' => 'card', 'Tourism' => 'card', 'Maximum' => 'users', 'Maximale' => 'users', 'Szoba' => 'area', 'Room' => 'area', 'Zimmer' => 'area' );
echo zc_section_open( $s, 'zc-apt' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<div class="zc-apt__top">
		<?php if ( ! empty( $d['media']['url'] ) || ! empty( $d['media']['id'] ) ) : ?>
			<button type="button" class="zc-apt__hero" data-lb="<?php echo esc_url( zen_media_url( $d['media'], 'full' ) ); ?>" data-type="<?php echo esc_attr( $d['media']['type'] ?? 'image' ); ?>" data-lb-group="apt-<?php echo esc_attr( $s['id'] ); ?>" aria-label="<?php echo esc_attr( zc_t( 'open_gallery' ) ); ?>">
				<?php echo zc_media( $d['media'], array( 'cover' => true, 'alt' => $d['title'] ?? '', 'sizes' => '(min-width: 1000px) 50vw, 100vw', 'eager' => true ) ); // phpcs:ignore ?>
				<?php if ( $zc_gal ) : ?><span class="zc-apt__count"><?php echo zc_icon( 'grid', 16 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'photos', array( 'n' => count( $zc_gal ) ) ) ); ?></span><?php endif; ?>
			</button>
		<?php endif; ?>
		<aside class="zc-apt__aside zc-card zc-card--glass zc-quote" data-reveal>
			<?php if ( ! empty( $d['title'] ) ) : ?><h2 class="zc-apt__name"><?php echo esc_html( $d['title'] ); ?></h2><?php endif; ?>
			<?php echo $zc_apt ? zc_apt_meta( $zc_apt ) : ''; // phpcs:ignore ?>
			<?php if ( ! empty( $d['aside_title'] ) ) : ?><h3><?php echo esc_html( $d['aside_title'] ); ?></h3><?php endif; ?>
			<?php if ( ! empty( $d['aside_text'] ) ) : ?><p><?php echo esc_html( $d['aside_text'] ); ?></p><?php endif; ?>
			<?php if ( class_exists( 'Zen_Booking' ) ) : ?>
				<?php echo Zen_Booking::bar( array( 'apt' => $zc_apt, 'compact' => true ) ); // phpcs:ignore ?>
			<?php elseif ( ! empty( $d['aside_button']['label'] ) ) : ?>
				<?php echo zc_buttons( array( $d['aside_button'] ) ); // phpcs:ignore ?>
			<?php endif; ?>
			<ul class="zc-quote__trust">
				<li><?php echo zc_icon( 'shield', 16 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'best_price' ) ); ?></li>
				<li><?php echo zc_icon( 'parking', 16 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'trust_parking' ) ); ?></li>
				<li><?php echo zc_icon( 'key', 16 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'trust_checkin' ) ); ?></li>
			</ul>
			<button type="button" class="zc-btn zc-btn--ghost zc-btn--sm" data-zen-ai-open data-zen-ai-prompt="<?php echo esc_attr( $d['title'] ?? '' ); ?>"><?php echo zc_icon( 'sparkles', 16 ); // phpcs:ignore ?><span><?php echo esc_html( zc_t( 'ai_open' ) ); ?></span></button>
		</aside>
	</div>

	<div class="zc-apt__body">
		<?php if ( ! empty( $d['html'] ) ) : ?><div class="zc-apt__intro zc-prose"><?php echo zc_html( $d['html'] ); // phpcs:ignore ?></div><?php endif; ?>

		<?php if ( ! empty( $d['facts'] ) ) : ?>
			<ul class="zc-facts">
				<?php foreach ( (array) $d['facts'] as $zc_f ) : ?>
					<?php
					$zc_i = 'info';
					foreach ( $zc_ico as $zc_k => $zc_v ) {
						if ( 0 === stripos( (string) $zc_f, $zc_k ) ) {
							$zc_i = $zc_v;
							break;
						}
					}
					?>
					<li data-reveal><?php echo zc_icon( $zc_i, 18 ); // phpcs:ignore ?><span><?php echo esc_html( $zc_f ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $d['amenities'] ) ) : ?>
			<div class="zc-amenities">
				<?php if ( ! empty( $d['amenities_title'] ) ) : ?><h3 class="zc-amenities__title"><?php echo esc_html( $d['amenities_title'] ); ?></h3><?php endif; ?>
				<ul class="zc-amenities__list">
					<?php foreach ( (array) $d['amenities'] as $zc_a ) : ?>
						<li><?php echo zc_icon( 'check', 16 ); // phpcs:ignore ?><span><?php echo esc_html( $zc_a ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<?php echo zc_buttons( $d['buttons'] ?? array(), 'zc-btns--center' ); // phpcs:ignore ?>
	</div>
</div>

<?php if ( $zc_gal ) : ?>
	<div class="zc-wrap zc-apt__gallery" data-gallery>
		<div class="zc-masonry zc-masonry--apt">
			<?php foreach ( $zc_gal as $zc_m ) : ?>
				<button type="button" class="zc-masonry__item" data-lb="<?php echo esc_url( zen_media_url( $zc_m, 'full' ) ); ?>" data-type="<?php echo esc_attr( $zc_m['type'] ?? 'image' ); ?>" aria-label="<?php echo esc_attr( zc_t( 'open_gallery' ) ); ?>" data-reveal>
					<?php echo zc_media( $zc_m, array( 'alt' => $zc_m['alt'] ?: ( $d['title'] ?? '' ), 'sizes' => '(min-width: 1100px) 25vw, 50vw', 'size' => 'large' ) ); // phpcs:ignore ?>
					<span class="zc-masonry__zoom" aria-hidden="true"><?php echo zc_icon( 'zoom', 18 ); // phpcs:ignore ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
<?php
echo zc_section_close(); // phpcs:ignore
