<?php
/**
 * Oldal: Zen szekciók renderelése; ha nincsenek, a klasszikus tartalom.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	if ( ! zc_render_sections( get_the_ID() ) ) :
		?>
		<section class="zc-sec zc-sec--hero zc-hero zc-hero--small tone-dark">
			<div class="zc-wrap zc-hero__in"><?php echo zc_breadcrumbs(); // phpcs:ignore ?><h1 class="zc-hero__title"><?php the_title(); ?></h1></div>
		</section>
		<section class="zc-sec pad-l"><div class="zc-wrap zc-prose"><?php the_content(); ?></div></section>
		<?php
	endif;
endwhile;
get_footer();
