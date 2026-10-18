<?php
/**
 * Kapcsolat: elérhetőségek, foglalás gomb, közösség, megközelítés, bejelentkezés, térkép.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
echo zc_section_open( $s, 'zc-contact' ); // phpcs:ignore
?>
<div class="zc-wrap zc-contact__grid">
	<?php
	$zc_visual = in_array( (string) ( $d['visual'] ?? 'collage' ), array( 'collage', 'media', 'none' ), true ) ? (string) ( $d['visual'] ?? 'collage' ) : 'collage';
	$zc_coll   = 'collage' === $zc_visual ? zc_apt_collage() : '';
	?>
	<?php if ( '' !== $zc_coll ) : ?>
		<?php echo $zc_coll; // phpcs:ignore ?>
	<?php elseif ( 'media' === $zc_visual && ( ! empty( $d['media']['url'] ) || ! empty( $d['media']['id'] ) ) ) : ?>
		<figure class="zc-contact__media" data-reveal><?php echo zc_media( $d['media'], array( 'cover' => true, 'alt' => $d['title'] ?? '', 'sizes' => '(min-width: 900px) 45vw, 100vw' ) ); // phpcs:ignore ?></figure>
	<?php endif; ?>
	<div class="zc-contact__body">
		<?php echo zc_head( $d, 'h2', false ); // phpcs:ignore ?>
		<?php if ( ! empty( $d['notice'] ) ) : ?><p class="zc-notice"><?php echo zc_icon( 'info', 18 ); // phpcs:ignore ?><span><?php echo esc_html( $d['notice'] ); ?></span></p><?php endif; ?>
		<?php if ( ! empty( $d['info_title'] ) ) : ?><h4 class="zc-contact__h"><?php echo esc_html( $d['info_title'] ); ?></h4><?php endif; ?>
		<ul class="zc-contact__info">
			<?php foreach ( (array) ( $d['info'] ?? array() ) as $zc_i ) : ?>
				<li><a href="<?php echo zc_url( $zc_i['url'] ?? '#' ); // phpcs:ignore ?>"<?php echo ( 0 === strpos( (string) ( $zc_i['url'] ?? '' ), 'http' ) ) ? ' target="_blank" rel="noopener"' : ''; ?>><span class="zc-contact__ico"><?php echo zc_icon( $zc_i['icon'] ?? 'info', 18 ); // phpcs:ignore ?></span><?php echo esc_html( $zc_i['label'] ?? '' ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php if ( ! empty( $d['booking_title'] ) ) : ?><h4 class="zc-contact__h"><?php echo esc_html( $d['booking_title'] ); ?></h4><?php endif; ?>
		<?php echo zc_buttons( $d['buttons'] ?? array() ); // phpcs:ignore ?>
		<?php if ( ! empty( $d['community_title'] ) ) : ?>
			<h4 class="zc-contact__h"><?php echo esc_html( $d['community_title'] ); ?></h4>
			<p class="zc-socials">
				<?php if ( zc_opt( 'general.facebook' ) ) : ?><a class="zc-icon-btn" href="<?php echo esc_url( zc_opt( 'general.facebook' ) ); ?>" target="_blank" rel="noopener" aria-label="Facebook"><?php echo zc_icon( 'facebook', 20 ); // phpcs:ignore ?></a><?php endif; ?>
				<?php if ( zc_opt( 'general.instagram' ) ) : ?><a class="zc-icon-btn" href="<?php echo esc_url( zc_opt( 'general.instagram' ) ); ?>" target="_blank" rel="noopener" aria-label="Instagram"><?php echo zc_icon( 'instagram', 20 ); // phpcs:ignore ?></a><?php endif; ?>
				<a class="zc-icon-btn" href="mailto:<?php echo esc_attr( zc_opt( 'general.email' ) ); ?>" aria-label="E-mail"><?php echo zc_icon( 'mail', 20 ); // phpcs:ignore ?></a>
				<a class="zc-icon-btn" href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', (string) zc_opt( 'general.phone' ) ) ); ?>" aria-label="Telefon"><?php echo zc_icon( 'phone', 20 ); // phpcs:ignore ?></a>
			</p>
		<?php endif; ?>
	</div>
</div>
<div class="zc-wrap zc-contact__more">
	<?php echo zc_map( $d['map_query'] ?? zc_opt( 'general.map_query', '' ), 'zc-contact__map' ); // phpcs:ignore ?>
	<div class="zc-contact__cards">
		<?php if ( ! empty( $d['access_html'] ) ) : ?>
			<div class="zc-card" data-reveal><h3><?php echo zc_icon( 'map', 20 ); // phpcs:ignore ?><?php echo esc_html( $d['access_title'] ?? '' ); ?></h3><div class="zc-prose"><?php echo zc_html( $d['access_html'] ); // phpcs:ignore ?></div></div>
		<?php endif; ?>
		<?php if ( ! empty( $d['checkin_html'] ) ) : ?>
			<div class="zc-card" data-reveal><h3><?php echo zc_icon( 'key', 20 ); // phpcs:ignore ?><?php echo esc_html( $d['checkin_title'] ?? '' ); ?></h3><div class="zc-prose"><?php echo zc_html( $d['checkin_html'] ); // phpcs:ignore ?></div></div>
		<?php endif; ?>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
