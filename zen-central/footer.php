<?php
/**
 * Lábléc: CTA sáv, kapcsolat, hasznos linkek, hírlevél, fizetési logó, jogi sor.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
$zc_phone = zc_opt( 'general.phone', '' );
$zc_email = zc_opt( 'general.email', '' );
$zc_addr  = zc_opt( 'general.address_line', '' );
$zc_pay   = zc_opt( 'general.payment_logo', array() );
?>
</main>

<?php echo function_exists( 'zen_photo_strip' ) ? zen_photo_strip() : ''; // phpcs:ignore ?>
<?php echo function_exists( 'zen_promo_box' ) ? zen_promo_box() : ''; // phpcs:ignore ?>
<footer class="zc-footer">
	<div class="zc-footer__cta">
		<div class="zc-wrap zc-footer__cta-in">
			<p class="zc-footer__cta-text"><?php echo esc_html( zc_t( 'footer_cta' ) ); ?></p>
			<a class="zc-btn zc-btn--light" href="<?php echo esc_url( zc_page_url( 'booking' ) ); ?>"><span><?php echo esc_html( zc_t( 'footer_cta_btn' ) ); ?></span><?php echo zc_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
		</div>
	</div>

	<div class="zc-wrap zc-footer__grid">
		<div class="zc-footer__brand">
			<?php echo zc_logo( 'zc-logo--footer' ); // phpcs:ignore ?>
			<p class="zc-footer__tag"><?php echo esc_html( zc_opt( 'general.tagline', '' ) ); ?></p>
			<ul class="zc-footer__trust">
				<li><?php echo zc_icon( 'parking', 16 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'trust_parking' ) ); ?></li>
				<li><?php echo zc_icon( 'key', 16 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'trust_checkin' ) ); ?></li>
				<li><?php echo zc_icon( 'wifi', 16 ); // phpcs:ignore ?><?php echo esc_html( zc_t( 'trust_wifi' ) ); ?></li>
				<?php $zc_szep = function_exists( 'zen_szep_badge' ) ? zen_szep_badge( 'footer' ) : ''; if ( $zc_szep ) : ?><li><?php echo $zc_szep; // phpcs:ignore ?></li><?php endif; ?>
			</ul>
		</div>

		<div class="zc-footer__col">
			<h4><?php echo esc_html( zc_t( 'footer_contact' ) ); ?></h4>
			<ul class="zc-footer__list">
				<?php if ( $zc_phone ) : ?><li><a href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', $zc_phone ) ); ?>"><?php echo zc_icon( 'phone', 16 ); // phpcs:ignore ?><?php echo esc_html( $zc_phone ); ?></a></li><?php endif; ?>
				<?php if ( $zc_email ) : ?><li><a href="mailto:<?php echo esc_attr( $zc_email ); ?>"><?php echo zc_icon( 'mail', 16 ); // phpcs:ignore ?><?php echo esc_html( $zc_email ); ?></a></li><?php endif; ?>
				<?php if ( $zc_addr ) : ?><li><a href="<?php echo esc_url( zc_page_url( 'contact' ) ); ?>"><?php echo zc_icon( 'pin', 16 ); // phpcs:ignore ?><?php echo esc_html( $zc_addr ); ?></a></li><?php endif; ?>
			</ul>
		</div>

		<div class="zc-footer__col">
			<h4><?php echo esc_html( zc_t( 'footer_links' ) ); ?></h4>
			<ul class="zc-footer__list">
				<li><a href="<?php echo esc_url( zc_page_url( 'booking' ) ); ?>"><?php echo esc_html( zc_t( 'link_booking' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( zc_page_url( 'apartments' ) ); ?>"><?php echo esc_html( zc_t( 'link_apartments' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( zc_page_url( 'gallery' ) ); ?>"><?php echo esc_html( zc_t( 'link_gallery' ) ); ?></a></li>
				<?php if ( zc_core() && Zen_Content::page_id( 'feedback', zc_lang() ) ) : ?><li><a href="<?php echo esc_url( zc_page_url( 'feedback' ) ); ?>"><?php echo esc_html( zc_t( 'link_feedback' ) ); ?></a></li><?php endif; ?>
				<?php if ( zc_core() && Zen_Content::page_id( 'help', zc_lang() ) ) : ?><li><a href="<?php echo esc_url( zc_page_url( 'help' ) ); ?>"><?php echo esc_html( zc_t( 'link_help' ) ); ?></a></li><?php endif; ?>
			</ul>
			<a class="zc-btn zc-btn--sm" href="<?php echo esc_url( zc_page_url( 'booking' ) ); ?>"><span><?php echo esc_html( zc_t( 'footer_book' ) ); ?></span></a>
		</div>

		<div class="zc-footer__col">
			<h4><?php echo esc_html( zc_t( 'newsletter_title' ) ); ?></h4>
			<p><?php echo esc_html( zc_t( 'newsletter_text' ) ); ?></p>
			<div class="zc-footer__actions">
				<button type="button" class="zc-btn zc-btn--ghost zc-btn--sm" data-zen-newsletter><span><?php echo esc_html( zc_t( 'newsletter_btn' ) ); ?></span></button>
				<?php if ( ! empty( $zc_pay['url'] ) ) : ?>
					<a class="zc-footer__pay" href="<?php echo esc_url( zc_opt( 'general.payment_url', '#' ) ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $zc_pay['url'] ); ?>" alt="<?php echo esc_attr( $zc_pay['alt'] ?? '' ); ?>" loading="lazy" width="220" height="40"></a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="zc-footer__bottom">
		<div class="zc-wrap zc-footer__bottom-in">
			<p>
				<span><?php echo esc_html( str_replace( '{year}', (string) zc_opt( 'general.copyright_year', gmdate( 'Y' ) ), zc_t( 'copyright' ) ) ); ?></span>
				<a href="<?php echo esc_url( zc_page_url( 'privacy' ) ); ?>"><?php echo esc_html( zc_t( 'privacy' ) ); ?></a>
				<span><?php echo esc_html( zc_t( 'ntak' ) ); ?></span>
				<?php
				// Készítő: InfoStudio Hungary (szöveg nyelvenként és link adminból állítható; a régi „KOLORdsgn” felirat lecserélve).
				$zc_cr  = zc_opt( 'general.credit_text', '' );
				$zc_cr  = is_array( $zc_cr ) ? ( class_exists( 'Zen_I18n' ) ? Zen_I18n::pick( $zc_cr ) : (string) reset( $zc_cr ) ) : (string) $zc_cr;
				if ( '' === trim( $zc_cr ) || false !== stripos( $zc_cr, 'KOLORdsgn' ) ) {
					$zc_cr = 'hu' === ( function_exists( 'zen_lang' ) ? zen_lang() : 'hu' ) ? 'Készítette: InfoStudio Hungary' : 'Website by InfoStudio Hungary';
				}
				$zc_cru = (string) zc_opt( 'general.credit_url', 'https://www.infostudio.hu' );
				$zc_cru = '' !== trim( $zc_cru ) ? $zc_cru : 'https://www.infostudio.hu';
				?>
				<a class="zc-footer__credit" href="<?php echo esc_url( $zc_cru ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $zc_cr ); ?></a>
			</p>
		</div>
	</div>
</footer>


<div class="zc-lightbox" hidden data-lightbox role="dialog" aria-modal="true">
	<button type="button" class="zc-icon-btn zc-lightbox__close" data-lb-close aria-label="<?php echo esc_attr( zc_t( 'close' ) ); ?>"><?php echo zc_icon( 'close', 22 ); // phpcs:ignore ?></button>
	<button type="button" class="zc-icon-btn zc-lightbox__prev" data-lb-prev aria-label="<?php echo esc_attr( zc_t( 'prev' ) ); ?>"><?php echo zc_icon( 'arrow-left', 22 ); // phpcs:ignore ?></button>
	<figure class="zc-lightbox__stage" data-lb-stage></figure>
	<button type="button" class="zc-icon-btn zc-lightbox__next" data-lb-next aria-label="<?php echo esc_attr( zc_t( 'next' ) ); ?>"><?php echo zc_icon( 'arrow', 22 ); // phpcs:ignore ?></button>
	<p class="zc-lightbox__count" data-lb-count></p>
</div>

<?php echo zc_lang_modal(); // phpcs:ignore ?>

<?php wp_footer(); ?>
</body>
</html>
