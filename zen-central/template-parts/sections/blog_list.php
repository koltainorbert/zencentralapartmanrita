<?php
/**
 * Bloglista.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$zc_q     = new WP_Query(
	array(
		'post_type'           => 'post',
		'posts_per_page'      => max( 1, (int) ( $d['count'] ?? 12 ) ),
		'paged'               => $zc_paged,
		'ignore_sticky_posts' => true,
	)
);
echo zc_section_open( $s, 'zc-bloglist' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<?php if ( $zc_q->have_posts() ) : ?>
		<div class="zc-post-grid">
			<?php
			while ( $zc_q->have_posts() ) :
				$zc_q->the_post();
				echo zc_post_card( get_the_ID() ); // phpcs:ignore
			endwhile;
			wp_reset_postdata();
			?>
		</div>
		<?php if ( $zc_q->max_num_pages > 1 ) : ?>
			<nav class="zc-pager"><?php echo paginate_links( array( 'total' => $zc_q->max_num_pages, 'current' => $zc_paged, 'prev_text' => esc_html( zc_t( 'prev' ) ), 'next_text' => esc_html( zc_t( 'next' ) ) ) ); // phpcs:ignore ?></nav>
		<?php endif; ?>
	<?php else : ?>
		<p class="zc-empty"><?php echo esc_html( zc_t( 'no_posts' ) ); ?></p>
	<?php endif; ?>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
