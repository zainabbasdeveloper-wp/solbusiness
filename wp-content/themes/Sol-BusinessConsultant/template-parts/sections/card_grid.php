<?php
/** Layout: card_grid — manual cards, or cards built from Services / Engagement examples. */
defined( 'ABSPATH' ) || exit;

$source = sol_get( $s, 'source', 'manual' );
$cards  = 'manual' === $source ? sol_rows( sol_get( $s, 'cards', array() ) ) : sol_cards_from_posts( $source );
$style  = preg_replace( '/[^a-z]/', '', sol_get( $s, 'card_style', 'bordered' ) );
$cols   = max( 1, min( 4, (int) sol_get( $s, 'columns', 3 ) ) );

sol_section_open( $s, 'card_grid' );
?>
<div class="sol-container">
	<?php sol_section_head( $s ); ?>
	<?php if ( $cards ) : ?>
		<div class="sol-grid sol-grid--<?php echo (int) $cols; ?>">
			<?php
			foreach ( $cards as $i => $card ) {
				echo sol_card( $card, $style, $i ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>
	<?php endif; ?>
	<?php echo sol_buttons( sol_get( $s, 'buttons', array() ), 'sol-buttons--after' ); // phpcs:ignore ?>
	<?php if ( sol_get( $s, 'note' ) ) : ?><p class="sol-note"><?php echo esc_html( $s['note'] ); ?></p><?php endif; ?>
</div>
</section>
