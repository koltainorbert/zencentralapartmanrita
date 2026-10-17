<?php
/**
 * Apartman / tartalom kártyák.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_items = array();
if ( 'apartments' === ( $d['source'] ?? 'manual' ) && function_exists( 'zen_apartments' ) ) {
	foreach ( zen_apartments() as $zc_a ) {
		if ( ! empty( $d['exclude'] ) && $d['exclude'] === $zc_a['key'] ) {
			continue;
		}
		$zc_items[] = array(
			'title' => zen_apt_name( $zc_a['key'] ),
			'apt'   => $zc_a['key'],
			'url'   => '{page:apt_' . $zc_a['key'] . '}',
			'media' => $zc_a['cover'] ?? array(),
		);
	}
} else {
	$zc_items = (array) ( $d['items'] ?? array() );
}
if ( ! $zc_items ) {
	return;
}
echo zc_section_open( $s, 'zc-cards' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<?php if ( ! empty( $d['html'] ) ) : ?><div class="zc-prose zc-prose--center zc-lead"><?php echo zc_html( $d['html'] ); // phpcs:ignore ?></div><?php endif; ?>
	<div class="zc-card-grid zc-card-grid--<?php echo (int) min( 3, count( $zc_items ) ); ?>">
		<?php foreach ( $zc_items as $zc_i => $zc_it ) : ?>
			<?php
			$zc_apt   = $zc_it['apt'] ?? '';
			$zc_aptd  = ( $zc_apt && function_exists( 'zen_apartment' ) ) ? zen_apartment( $zc_apt ) : null;
			$zc_short = $zc_aptd ? Zen_I18n::pick( $zc_aptd['short'] ?? array() ) : ( $zc_it['meta'] ?? '' );
			?>
			<article class="zc-apt-card" data-reveal style="--d:<?php echo (int) $zc_i; ?>">
				<a class="zc-apt-card__media" href="<?php echo zc_url( $zc_it['url'] ?? '#' ); // phpcs:ignore ?>" tabindex="-1" aria-hidden="true">
					<?php echo zc_media( $zc_it['media'] ?? array(), array( 'cover' => true, 'alt' => $zc_it['title'] ?? '', 'size' => 'zc-card', 'sizes' => '(min-width: 900px) 33vw, 100vw' ) ); // phpcs:ignore ?>
					<?php if ( $zc_aptd && ! empty( $zc_aptd['icon']['url'] ) ) : ?><span class="zc-apt-card__icon"><img src="<?php echo esc_url( $zc_aptd['icon']['url'] ); ?>" alt="" loading="lazy" width="64" height="64"></span><?php endif; ?>
				</a>
				<div class="zc-apt-card__body">
					<h3 class="zc-apt-card__title"><a href="<?php echo zc_url( $zc_it['url'] ?? '#' ); // phpcs:ignore ?>"><?php echo esc_html( $zc_it['title'] ?? '' ); ?></a></h3>
					<?php echo $zc_apt ? zc_apt_meta( $zc_apt ) : ''; // phpcs:ignore ?>
					<?php if ( $zc_short ) : ?><p class="zc-apt-card__text"><?php echo esc_html( $zc_short ); ?></p><?php endif; ?>
					<div class="zc-btns zc-btns--tight">
						<a class="zc-btn zc-btn--sm zc-btn--ghost" href="<?php echo zc_url( $zc_it['url'] ?? '#' ); // phpcs:ignore ?>"><span><?php echo esc_html( zc_t( 'details' ) ); ?></span></a>
						<a class="zc-btn zc-btn--sm" href="<?php echo esc_url( add_query_arg( 'apt', $zc_apt, zc_page_url( 'booking' ) ) ); ?>"><span><?php echo esc_html( zc_t( 'book_this' ) ); ?></span><?php echo zc_icon( 'arrow', 16 ); // phpcs:ignore ?></a>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
