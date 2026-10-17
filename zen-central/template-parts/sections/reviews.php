<?php
/**
 * Vendégvélemények (elégedettségi portálról jóváhagyott vagy kézi).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_items = array();
if ( 'feedback' === ( $d['source'] ?? 'feedback' ) && class_exists( 'Zen_Forms' ) && method_exists( 'Zen_Forms', 'reviews' ) ) {
	$zc_items = Zen_Forms::reviews( 9 );
}
$zc_items = array_merge( $zc_items, (array) ( $d['items'] ?? array() ) );
$zc_is_fb = zc_core() && get_queried_object_id() === Zen_Content::page_id( 'feedback', zc_lang() );
if ( ! $zc_items && ! $zc_is_fb ) {
	return; // üresen nem jelenik meg (csak az elégedettségi oldalon)
}
echo zc_section_open( $s, 'zc-reviews' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<?php if ( $zc_items ) : ?>
		<div class="zc-review-track" data-track>
			<?php foreach ( $zc_items as $zc_r ) : ?>
				<figure class="zc-review" data-reveal>
					<span class="zc-review__stars" aria-label="<?php echo esc_attr( zc_t( 'fm_fb_rate', array( 'n' => (int) ( $zc_r['rating'] ?? 5 ) ) ) ); ?>"><?php echo str_repeat( zc_icon( 'star', 16 ), max( 1, min( 5, (int) ( $zc_r['rating'] ?? 5 ) ) ) ); // phpcs:ignore ?></span>
					<blockquote><?php echo esc_html( $zc_r['text'] ?? '' ); ?></blockquote>
					<figcaption><b><?php echo esc_html( $zc_r['name'] ?? '' ); ?></b><?php if ( ! empty( $zc_r['origin'] ) || ! empty( $zc_r['date'] ) ) : ?> <span><?php echo esc_html( trim( ( $zc_r['origin'] ?? '' ) . ' ' . ( $zc_r['date'] ?? '' ) ) ); ?></span><?php endif; ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="zc-empty"><?php echo esc_html( zc_t( 'reviews_empty' ) ); ?></p>
	<?php endif; ?>
	<?php if ( ! $zc_is_fb && zc_core() && Zen_Content::page_id( 'feedback', zc_lang() ) ) : ?>
		<div class="zc-btns zc-btns--center"><a class="zc-btn zc-btn--ghost" href="<?php echo esc_url( zc_page_url( 'feedback' ) ); ?>"><span><?php echo esc_html( zc_t( 'reviews_cta' ) ); ?></span></a></div>
	<?php endif; ?>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
