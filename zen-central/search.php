<?php
/**
 * Archívum / blog / fallback lista.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="zc-sec zc-hero zc-hero--small tone-dark">
	<div class="zc-wrap zc-hero__in">
		<?php echo zc_breadcrumbs(); // phpcs:ignore ?>
		<h1 class="zc-hero__title"><?php echo esc_html( is_search() ? zc_t( 'search_results', array( 'q' => get_search_query() ) ) : ( is_archive() ? wp_strip_all_tags( get_the_archive_title() ) : zc_t( 'nav_blog' ) ) ); ?></h1>
	</div>
</section>
<section class="zc-sec pad-l">
	<div class="zc-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="zc-post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					echo zc_post_card( get_the_ID() ); // phpcs:ignore
				endwhile;
				?>
			</div>
			<nav class="zc-pager"><?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => zc_t( 'prev' ), 'next_text' => zc_t( 'next' ) ) ); ?></nav>
		<?php else : ?>
			<p class="zc-empty"><?php echo esc_html( zc_t( 'no_posts' ) ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
