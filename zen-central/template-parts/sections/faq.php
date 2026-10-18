<?php
/**
 * GYIK – egyetlen üveg „dashboard” blokk, két egyforma oldallal:
 * bal: fejléc, statisztika, témakör-pillek, Zen AI kártya · jobb: lenyíló kérdések (pill formájúak).
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_groups = array_values( array_filter( (array) ( $d['groups'] ?? array() ), fn( $g ) => ! empty( $g['items'] ) ) );
if ( ! $zc_groups ) {
	return;
}
$zc_total = array_sum( array_map( fn( $g ) => count( (array) $g['items'] ), $zc_groups ) );
$zc_phone = zc_opt( 'general.phone', '' );
$zc_uid   = 'zcf' . sanitize_html_class( $s['id'] );
echo zc_section_open( $s, 'zc-faq' ); // phpcs:ignore
?>
<div class="zc-wrap">
	<div class="zc-faq__board" data-tabs>
		<div class="zc-faq__side">
			<div class="zc-faq__intro">
				<?php echo zc_head( $d, 'h2', false ); // phpcs:ignore ?>
				<?php if ( ! empty( $d['text'] ) ) : ?><p class="zc-faq__lead"><?php echo esc_html( $d['text'] ); ?></p><?php endif; ?>
				<ul class="zc-faq__stats">
					<li><b><?php echo (int) $zc_total; ?></b><span><?php echo esc_html( trim( str_replace( (string) $zc_total, '', zc_t( 'faq_questions', array( 'n' => $zc_total ) ) ), ' :' ) ); ?></span></li>
					<li><b><?php echo (int) count( $zc_groups ); ?></b><span><?php echo esc_html( trim( str_replace( (string) count( $zc_groups ), '', zc_t( 'faq_topics', array( 'n' => count( $zc_groups ) ) ) ), ' :' ) ); ?></span></li>
					<li><b>24/7</b><span><?php echo esc_html( zc_t( 'ai_name' ) ); ?> AI</span></li>
				</ul>
			</div>
			<div class="zc-faq__tabs" role="tablist" aria-label="<?php echo esc_attr( $d['title'] ?? '' ); ?>">
				<?php foreach ( $zc_groups as $zc_gi => $zc_g ) : ?>
					<button type="button" class="zc-faq__tab" role="tab" id="<?php echo esc_attr( $zc_uid . 't' . $zc_gi ); ?>" aria-controls="<?php echo esc_attr( $zc_uid . 'p' . $zc_gi ); ?>" aria-selected="<?php echo 0 === $zc_gi ? 'true' : 'false'; ?>" data-tab="<?php echo (int) $zc_gi; ?>"><span><?php echo esc_html( $zc_g['title'] ?? '' ); ?></span><i><?php echo (int) count( (array) $zc_g['items'] ); ?></i></button>
				<?php endforeach; ?>
			</div>
			<div class="zc-faq__help">
				<span class="zc-faq__help-ico" aria-hidden="true"><?php echo zc_icon( 'sparkles', 22 ); // phpcs:ignore ?></span>
				<div class="zc-faq__help-txt">
					<b><?php echo esc_html( zc_t( 'faq_help_title' ) ); ?></b>
					<span><?php echo esc_html( zc_t( 'faq_help_text' ) ); ?></span>
				</div>
				<div class="zc-faq__help-btns">
					<button type="button" class="zc-btn zc-btn--sm" data-zen-ai-open><span><?php echo esc_html( zc_t( 'ai_open' ) ); ?></span></button>
					<?php if ( $zc_phone ) : ?><a class="zc-btn zc-btn--ghost zc-btn--sm" href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', $zc_phone ) ); ?>"><?php echo zc_icon( 'phone', 16 ); // phpcs:ignore ?><span><?php echo esc_html( $zc_phone ); ?></span></a><?php endif; ?>
				</div>
			</div>
		</div>
		<div class="zc-faq__panels">
			<?php foreach ( $zc_groups as $zc_gi => $zc_g ) : ?>
				<div class="zc-faq__panel" role="tabpanel" id="<?php echo esc_attr( $zc_uid . 'p' . $zc_gi ); ?>" aria-labelledby="<?php echo esc_attr( $zc_uid . 't' . $zc_gi ); ?>" data-panel="<?php echo (int) $zc_gi; ?>"<?php echo 0 === $zc_gi ? '' : ' hidden'; ?>>
					<?php foreach ( (array) $zc_g['items'] as $zc_qi => $zc_q ) : ?>
						<details class="zc-acc"<?php echo ( 0 === $zc_qi && 0 === $zc_gi ) ? ' open' : ''; ?>>
							<summary><span class="zc-acc__n"><?php echo esc_html( str_pad( (string) ( $zc_qi + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><span class="zc-acc__q"><?php echo esc_html( $zc_q['q'] ?? '' ); ?></span><i aria-hidden="true"></i></summary>
							<div class="zc-acc__body zc-prose"><?php echo zc_html( $zc_q['a'] ?? '' ); // phpcs:ignore ?></div>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
