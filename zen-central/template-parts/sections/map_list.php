<?php
/**
 * Térkép + közeli látnivalók listája.
 *
 * @package zen-central
 */

defined( 'ABSPATH' ) || exit;
echo zc_section_open( $s, 'zc-maplist' ); // phpcs:ignore
?>
<div class="zc-wrap zc-maplist__grid">
	<?php echo zc_map( $d['map_query'] ?? zc_opt( 'general.map_query', '' ), 'zc-maplist__map' ); // phpcs:ignore ?>
	<div class="zc-maplist__body">
		<?php echo zc_head( $d, 'h2', false ); // phpcs:ignore ?>
		<?php if ( ! empty( $d['html'] ) ) : ?><div class="zc-prose"><?php echo zc_html( $d['html'] ); // phpcs:ignore ?></div><?php endif; ?>
		<ul class="zc-distances">
			<?php foreach ( (array) ( $d['items'] ?? array() ) as $zc_it ) : ?>
				<?php
				$zc_parts = array_map( 'trim', explode( ':', (string) $zc_it, 2 ) );
				?>
				<li data-reveal><span class="zc-distances__ico"><?php echo zc_icon( 'pin', 16 ); // phpcs:ignore ?></span><span class="zc-distances__name"><?php echo esc_html( $zc_parts[0] ); ?></span><?php if ( isset( $zc_parts[1] ) ) : ?><b class="zc-distances__val"><?php echo esc_html( $zc_parts[1] ); ?></b><?php endif; ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
<?php
echo zc_section_close(); // phpcs:ignore
