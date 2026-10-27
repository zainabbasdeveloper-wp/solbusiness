<?php
/** Layout: faq — accordion with optional FAQPage schema. */
defined( 'ABSPATH' ) || exit;

$items = sol_rows( sol_get( $s, 'items', array() ) );
$cta   = sol_get( $s, 'cta', array() );
if ( ! $items ) {
	return;
}
if ( ! empty( $s['schema'] ) ) {
	sol_faq_schema( array_map( function ( $f ) {
		return array( 'question' => sol_get( $f, 'question' ), 'answer' => wp_strip_all_tags( sol_get( $f, 'answer' ) ) );
	}, $items ) );
}

sol_section_open( $s, 'faq' );
?>
<div class="sol-container sol-container--mid">
	<?php sol_section_head( $s ); ?>
	<div class="sol-accordion">
		<?php foreach ( $items as $f ) : ?>
			<details class="sol-accordion__item">
				<summary><?php echo esc_html( sol_get( $f, 'question' ) ); ?><?php echo sol_icon( 'chevron-down', 'sol-accordion__chev' ); // phpcs:ignore ?></summary>
				<div class="sol-accordion__body"><?php echo sol_text( sol_get( $f, 'answer' ) ); // phpcs:ignore ?></div>
			</details>
		<?php endforeach; ?>
	</div>
	<?php if ( is_array( $cta ) && sol_get( $cta, 'heading' ) ) : ?>
		<div class="sol-inline-cta sol-inline-cta--center">
			<div><h3><?php echo esc_html( $cta['heading'] ); ?></h3><p><?php echo esc_html( sol_get( $cta, 'text' ) ); ?></p></div>
			<?php echo sol_buttons( sol_get( $cta, 'buttons', array() ) ); // phpcs:ignore ?>
		</div>
	<?php endif; ?>
</div>
</section>
