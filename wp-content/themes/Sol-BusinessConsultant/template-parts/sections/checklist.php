<?php
/** Layout: checklist — ticks, pills or a numbered list. */
defined( 'ABSPATH' ) || exit;

$items = sol_list( sol_get( $s, 'items', array() ) );
$style = preg_replace( '/[^a-z]/', '', sol_get( $s, 'style', 'checks' ) );
$cols  = max( 1, min( 4, (int) sol_get( $s, 'columns', 3 ) ) );
if ( ! $items ) {
	return;
}

sol_section_open( $s, 'checklist', 'sol-checklist--' . $style );
?>
<div class="sol-container">
	<?php sol_section_head( $s ); ?>
	<?php if ( 'pills' === $style ) : ?>
		<ul class="sol-pills sol-pills--<?php echo (int) $cols; ?>"><?php foreach ( $items as $it ) : ?><li><?php echo esc_html( $it ); ?></li><?php endforeach; ?></ul>
	<?php elseif ( 'numbered' === $style ) : ?>
		<ol class="sol-numbered"><?php foreach ( $items as $i => $it ) : ?><li><span><?php echo (int) $i + 1; ?></span><p><?php echo esc_html( $it ); ?></p></li><?php endforeach; ?></ol>
	<?php else : ?>
		<?php echo sol_checks( $items, 'sol-checks--cols-' . (int) $cols ); // phpcs:ignore ?>
	<?php endif; ?>
	<?php echo sol_buttons( sol_get( $s, 'buttons', array() ), 'sol-buttons--after' ); // phpcs:ignore ?>
</div>
</section>
