<?php
/** Layout: rich_text. */
defined( 'ABSPATH' ) || exit;

$wide = 'wide' === sol_get( $s, 'width' );
sol_section_open( $s, 'rich_text' );
?>
<div class="sol-container <?php echo $wide ? '' : 'sol-container--narrow'; ?>">
	<?php sol_section_head( $s ); ?>
	<div class="sol-prose"><?php echo sol_rich( sol_get( $s, 'content' ) ); // phpcs:ignore ?></div>
</div>
</section>
