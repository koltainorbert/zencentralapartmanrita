<?php
/**
 * Veszprém kalauz – a Zen tudásbázisából (látnivalók, kirándulások, események).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_kb = get_option( 'zen_kb', '' );
$zc_kb = is_array( $zc_kb ) ? $zc_kb : json_decode( (string) $zc_kb, true );
if ( ! is_array( $zc_kb ) && defined( 'ZEN_CORE_DIR' ) && file_exists( ZEN_CORE_DIR . 'data/veszprem-kb.json' ) ) {
	$zc_kb = json_decode( (string) file_get_contents( ZEN_CORE_DIR . 'data/veszprem-kb.json' ), true ); // phpcs:ignore
}
if ( ! is_array( $zc_kb ) ) {
	return;
}
$zc_cat  = $d['category'] ?? 'sight';
$zc_map  = array( 'sight' => 'sights', 'daytrip' => 'daytrips', 'event' => 'events', 'food' => 'food' );
$zc_list = array();
if ( 'all' === $zc_cat ) {
	foreach ( $zc_map as $zc_k ) {
		$zc_list = array_merge( $zc_list, (array) ( $zc_kb[ $zc_k ] ?? array() ) );
	}
} else {
	$zc_list = (array) ( $zc_kb[ $zc_map[ $zc_cat ] ?? 'sights' ] ?? array() );
}
$zc_list = array_slice( $zc_list, 0, max( 1, (int) ( $d['limit'] ?? 6 ) ) );
if ( ! $zc_list ) {
	return;
}
$zc_lang = zc_lang();
echo zc_section_open( $s, 'zc-guide' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<?php echo zc_head( $d ); // phpcs:ignore ?>
	<?php if ( ! empty( $d['text'] ) ) : ?><p class="zc-lead zc-center"><?php echo esc_html( $d['text'] ); ?></p><?php endif; ?>
	<div class="zc-guide__grid">
		<?php foreach ( $zc_list as $zc_i => $zc_g ) : ?>
			<?php
			$zc_desc = $zc_g[ 'desc_' . $zc_lang ] ?? ( 'hu' === $zc_lang ? ( $zc_g['desc_hu'] ?? '' ) : ( $zc_g['desc_en'] ?? ( $zc_g['desc_hu'] ?? '' ) ) );
			$zc_name = $zc_g[ 'name_' . $zc_lang ] ?? ( $zc_g['name'] ?? '' );
			$zc_dist = $zc_g['distance'] ?? ( isset( $zc_g['km'] ) ? $zc_g['km'] . ' km' : ( $zc_g['month'] ?? '' ) );
			?>
			<article class="zc-guide__card" data-reveal style="--d:<?php echo (int) $zc_i; ?>">
				<span class="zc-guide__num"><?php echo esc_html( str_pad( (string) ( $zc_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
				<h3><?php echo esc_html( $zc_name ); ?></h3>
				<?php if ( $zc_dist ) : ?><p class="zc-guide__dist"><?php echo zc_icon( 'pin', 14 ); // phpcs:ignore ?><?php echo esc_html( is_array( $zc_dist ) ? implode( ', ', $zc_dist ) : $zc_dist ); ?></p><?php endif; ?>
				<p><?php echo esc_html( wp_trim_words( (string) $zc_desc, 30 ) ); ?></p>
				<div class="zc-guide__actions">
					<button type="button" class="zc-link" data-zen-ai-open data-zen-ai-prompt="<?php echo esc_attr( $zc_name ); ?>"><?php echo zc_icon( 'sparkles', 14 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'guide_more' ) ); ?></button>
					<?php if ( ! empty( $zc_g['url'] ) ) : ?><a class="zc-link" href="<?php echo esc_url( $zc_g['url'] ); ?>" target="_blank" rel="noopener nofollow"><?php echo zc_icon( 'external', 14 ); // phpcs:ignore ?></a><?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
