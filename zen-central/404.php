<?php
/**
 * 404 – barátságos, Zen-hangulatú.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="zc-sec zc-404 pad-l">
	<div class="zc-wrap zc-404__in">
		<span class="zc-404__mark" aria-hidden="true"><?php echo zc_icon( 'lotus', 96 ); // phpcs:ignore ?></span>
		<p class="zc-kicker">404</p>
		<h1 class="zc-title"><?php echo esc_html( zc_t( 'not_found_title' ) ); ?></h1>
		<p class="zc-lead"><?php echo esc_html( zc_t( 'not_found_text' ) ); ?></p>
		<div class="zc-btns zc-btns--center">
			<a class="zc-btn" href="<?php echo esc_url( zc_page_url( 'home' ) ); ?>"><span><?php echo esc_html( zc_t( 'back_home' ) ); ?></span></a>
			<a class="zc-btn zc-btn--ghost" href="<?php echo esc_url( zc_page_url( 'apartments' ) ); ?>"><span><?php echo esc_html( zc_t( 'nav_apartments' ) ); ?></span></a>
			<button type="button" class="zc-btn zc-btn--ghost" data-zen-ai-open><span><?php echo esc_html( zc_t( 'ai_open' ) ); ?></span></button>
		</div>
	</div>
</section>
<?php
get_footer();
