<?php
/**
 * Fejléc: lebegő üveg pill-sáv, automatikus nyelvfüggő menü, nyelvváltó, sötét/világos kapcsoló.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_phone = zc_opt( 'general.phone', '' );
$zc_email = zc_opt( 'general.email', '' );
?><!doctype html>
<html <?php language_attributes(); ?> data-skin="<?php echo esc_attr( zc_opt( 'design.skin', 'zen-sand' ) ); ?>">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="zc-skip" href="#zc-main"><?php echo esc_html( zc_t( 'skip' ) ); ?></a>

<div class="zc-topbar" role="note">
	<div class="zc-wrap zc-topbar__in">
		<p class="zc-topbar__contact">
			<?php if ( $zc_phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', $zc_phone ) ); ?>"><?php echo zc_icon( 'phone', 14 ); // phpcs:ignore ?><?php echo esc_html( $zc_phone ); ?></a><?php endif; ?>
			<span aria-hidden="true">|</span>
			<?php if ( $zc_email ) : ?><a href="mailto:<?php echo esc_attr( $zc_email ); ?>"><?php echo zc_icon( 'mail', 14 ); // phpcs:ignore ?><?php echo esc_html( $zc_email ); ?></a><?php endif; ?>
		</p>
		<p class="zc-topbar__trust"><?php echo zc_icon( 'shield', 14 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'best_price' ) ); ?></p>
	</div>
</div>

<header class="zc-header" data-header>
	<div class="zc-wrap zc-header__in">
		<?php echo zc_logo(); // phpcs:ignore ?>
		<nav class="zc-nav" aria-label="<?php echo esc_attr( zc_t( 'menu' ) ); ?>">
			<ul class="zc-nav__list">
				<?php foreach ( zc_nav_items() as $zc_it ) : ?>
					<li class="zc-nav__item<?php echo ! empty( $zc_it['children'] ) ? ' has-sub' : ''; ?>">
						<a href="<?php echo esc_url( $zc_it['url'] ); ?>"<?php echo $zc_it['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $zc_it['label'] ); ?><?php echo ! empty( $zc_it['children'] ) ? zc_icon( 'chevron', 14 ) : ''; // phpcs:ignore ?></a>
						<?php if ( ! empty( $zc_it['children'] ) ) : ?>
							<ul class="zc-nav__sub">
								<?php foreach ( $zc_it['children'] as $zc_c ) : ?>
									<li><a href="<?php echo esc_url( $zc_c['url'] ); ?>"<?php echo $zc_c['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $zc_c['label'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<div class="zc-header__tools">
			<?php echo zc_lang_switcher(); // phpcs:ignore ?>
			<?php echo zc_theme_toggle(); // phpcs:ignore ?>
			<a class="zc-btn zc-btn--sm zc-header__book" href="<?php echo esc_url( zc_page_url( 'booking' ) ); ?>"><span><?php echo esc_html( zc_t( 'nav_book' ) ); ?></span></a>
			<button type="button" class="zc-burger" aria-expanded="false" aria-controls="zc-drawer" aria-label="<?php echo esc_attr( zc_t( 'menu' ) ); ?>" data-drawer-open><?php echo zc_icon( 'menu', 22 ); // phpcs:ignore ?></button>
		</div>
	</div>
</header>

<div class="zc-drawer" id="zc-drawer" hidden data-drawer>
	<div class="zc-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( zc_t( 'menu' ) ); ?>">
		<div class="zc-drawer__top">
			<?php echo zc_logo(); // phpcs:ignore ?>
			<button type="button" class="zc-icon-btn" data-drawer-close aria-label="<?php echo esc_attr( zc_t( 'close' ) ); ?>"><?php echo zc_icon( 'close', 22 ); // phpcs:ignore ?></button>
		</div>
		<ul class="zc-drawer__nav">
			<?php foreach ( zc_nav_items() as $zc_it ) : ?>
				<li><a href="<?php echo esc_url( $zc_it['url'] ); ?>"<?php echo $zc_it['current'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $zc_it['label'] ); ?></a>
				<?php if ( ! empty( $zc_it['children'] ) ) : ?>
					<ul><?php foreach ( $zc_it['children'] as $zc_c ) : ?><li><a href="<?php echo esc_url( $zc_c['url'] ); ?>"><?php echo esc_html( $zc_c['label'] ); ?></a></li><?php endforeach; ?></ul>
				<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="zc-drawer__tools">
			<?php echo zc_lang_switcher( 'inline' ); // phpcs:ignore ?>
			<?php echo zc_theme_toggle(); // phpcs:ignore ?>
		</div>
		<a class="zc-btn zc-drawer__book" href="<?php echo esc_url( zc_page_url( 'booking' ) ); ?>"><span><?php echo esc_html( zc_t( 'nav_book' ) ); ?></span><?php echo zc_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
		<p class="zc-drawer__contact"><a href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', $zc_phone ) ); ?>"><?php echo esc_html( $zc_phone ); ?></a><a href="mailto:<?php echo esc_attr( $zc_email ); ?>"><?php echo esc_html( $zc_email ); ?></a></p>
	</div>
</div>

<?php
// Oldalsó kapcsolat-sáv (az eredeti függőleges „Lépjen velünk kapcsolatba” jelvény modern változata).
if ( zc_opt( 'design.show_side_badge', true ) ) :
	?>
	<aside class="zc-rail" aria-label="<?php echo esc_attr( zc_t( 'socials' ) ); ?>">
		<?php $zc_fb = zc_opt( 'general.facebook', '' ); ?>
		<?php if ( $zc_fb ) : ?><a href="<?php echo esc_url( $zc_fb ); ?>" target="_blank" rel="noopener" aria-label="Facebook"><?php echo zc_icon( 'facebook', 18 ); // phpcs:ignore ?></a><?php endif; ?>
		<?php $zc_ig = zc_opt( 'general.instagram', '' ); ?>
		<?php if ( $zc_ig ) : ?><a href="<?php echo esc_url( $zc_ig ); ?>" target="_blank" rel="noopener" aria-label="Instagram"><?php echo zc_icon( 'instagram', 18 ); // phpcs:ignore ?></a><?php endif; ?>
		<a href="mailto:<?php echo esc_attr( $zc_email ); ?>" aria-label="E-mail"><?php echo zc_icon( 'mail', 18 ); // phpcs:ignore ?></a>
		<a href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', $zc_phone ) ); ?>" aria-label="<?php echo esc_attr( $zc_phone ); ?>"><?php echo zc_icon( 'phone', 18 ); // phpcs:ignore ?></a>
		<span class="zc-rail__label"><?php echo esc_html( zc_t( 'socials' ) ); ?></span>
	</aside>
<?php endif; ?>

<main id="zc-main" class="zc-main" tabindex="-1">
