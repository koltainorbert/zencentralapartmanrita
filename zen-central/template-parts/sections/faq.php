<?php
/**
 * GYIK – csoportok (pill fülek) + harmonika (natív details).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_groups = array_values( array_filter( (array) ( $d['groups'] ?? array() ), fn( $g ) => ! empty( $g['items'] ) ) );
if ( ! $zc_groups ) {
	return;
}
echo zc_section_open( $s, 'zc-faq' ); // phpcs:ignore
?>
<div class="zc-wrap zc-faq__grid" data-tabs>
	<div class="zc-faq__side">
		<?php echo zc_head( $d, 'h2', false ); // phpcs:ignore ?>
		<div class="zc-pills" role="tablist">
			<?php foreach ( $zc_groups as $zc_gi => $zc_g ) : ?>
				<button type="button" class="zc-pill" role="tab" aria-selected="<?php echo 0 === $zc_gi ? 'true' : 'false'; ?>" data-tab="<?php echo (int) $zc_gi; ?>"><?php echo esc_html( $zc_g['title'] ?? '' ); ?></button>
			<?php endforeach; ?>
		</div>
		<button type="button" class="zc-btn zc-btn--ghost zc-btn--sm zc-faq__ask" data-zen-ai-open><?php echo zc_icon( 'sparkles', 16 ); // phpcs:ignore ?><span><?php echo esc_html( zc_t( 'ai_open' ) ); ?></span></button>
	</div>
	<div class="zc-faq__panels">
		<?php foreach ( $zc_groups as $zc_gi => $zc_g ) : ?>
			<div class="zc-faq__panel" role="tabpanel" data-panel="<?php echo (int) $zc_gi; ?>"<?php echo 0 === $zc_gi ? '' : ' hidden'; ?>>
				<?php foreach ( (array) $zc_g['items'] as $zc_qi => $zc_q ) : ?>
					<details class="zc-acc"<?php echo ( 0 === $zc_qi && 0 === $zc_gi ) ? ' open' : ''; ?>>
						<summary><span><?php echo esc_html( $zc_q['q'] ?? '' ); ?></span><i aria-hidden="true"></i></summary>
						<div class="zc-acc__body zc-prose"><?php echo zc_html( $zc_q['a'] ?? '' ); // phpcs:ignore ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
