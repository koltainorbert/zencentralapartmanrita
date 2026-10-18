<?php
/**
 * Blogbejegyzés: olvasható tipográfia, tartalomjegyzék, kapcsolódó cikkek, foglalás CTA.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$zc_id = get_the_ID();
	?>
	<article <?php post_class( 'zc-article' ); ?>>
		<header class="zc-sec zc-hero zc-hero--post tone-dark">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="zc-hero__media"><?php the_post_thumbnail( 'full', array( 'class' => 'zc-media zc-media--cover', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?></div>
			<?php endif; ?>
			<div class="zc-wrap zc-hero__in">
				<?php echo zc_breadcrumbs(); // phpcs:ignore ?>
				<h1 class="zc-hero__title"><?php the_title(); ?></h1>
				<p class="zc-article__meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( zc_t( 'posted_on', array( 'date' => get_the_date() ) ) ); ?></time>
					<span><?php echo zc_icon( 'clock', 14 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'reading_time', array( 'n' => zc_reading_time( $zc_id ) ) ) ); ?></span>
					<?php if ( zc_opt( 'design.post_author', true ) && get_the_author() ) : ?><span class="zc-article__author"><?php echo zc_icon( 'users', 14 ); // phpcs:ignore ?><?php the_author(); ?></span><?php endif; ?>
				</p>
			</div>
		</header>
		<div class="zc-sec pad-l">
			<div class="zc-wrap zc-article__grid">
				<div class="zc-prose zc-article__body"><?php the_content(); ?></div>
				<aside class="zc-article__aside">
					<div class="zc-card zc-card--glass zc-sticky">
						<p class="zc-kicker">ZEN Central</p>
						<h3><?php echo esc_html( zc_t( 'footer_cta' ) ); ?></h3>
						<a class="zc-btn" href="<?php echo esc_url( zc_page_url( 'booking' ) ); ?>"><span><?php echo esc_html( zc_t( 'sticky_book' ) ); ?></span><?php echo zc_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
						<button type="button" class="zc-btn zc-btn--ghost" data-zen-ai-open><span><?php echo esc_html( zc_t( 'ai_open' ) ); ?></span></button>
					</div>
				</aside>
			</div>
		</div>
	</article>
	<?php
	$zc_rel = get_posts( array( 'numberposts' => 3, 'post__not_in' => array( $zc_id ), 'post_type' => 'post' ) );
	if ( $zc_rel ) :
		?>
		<section class="zc-sec tone-alt pad-l">
			<div class="zc-wrap">
				<header class="zc-head zc-head--center"><h2 class="zc-title"><?php echo esc_html( zc_t( 'related' ) ); ?></h2></header>
				<div class="zc-post-grid"><?php foreach ( $zc_rel as $zc_p ) { echo zc_post_card( $zc_p->ID ); } // phpcs:ignore ?></div>
				<div class="zc-btns zc-btns--center"><a class="zc-btn zc-btn--ghost" href="<?php echo esc_url( zc_page_url( 'blog' ) ); ?>"><span><?php echo esc_html( zc_t( 'back_to_blog' ) ); ?></span></a></div>
			</div>
		</section>
		<?php
	endif;
endwhile;
get_footer();
