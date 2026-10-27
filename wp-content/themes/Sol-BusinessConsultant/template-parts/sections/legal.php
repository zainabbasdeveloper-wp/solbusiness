<?php
/** Layout: legal — meta lines and titled sections. */
defined( 'ABSPATH' ) || exit;

$meta     = sol_list( sol_get( $s, 'meta_lines', array() ) );
$sections = sol_rows( sol_get( $s, 'sections', array() ) );
?>
<section class="sol-section sol-bg-white sol-legal">
	<div class="sol-container sol-container--narrow">
		<?php if ( $meta ) : ?>
			<div class="sol-legal__meta"><?php foreach ( $meta as $m ) : ?><p><?php echo esc_html( $m ); ?></p><?php endforeach; ?></div>
		<?php endif; ?>
		<?php foreach ( $sections as $sec ) : ?>
			<section class="sol-legal__section">
				<?php if ( sol_get( $sec, 'title' ) ) : ?><h2><?php echo esc_html( $sec['title'] ); ?></h2><?php endif; ?>
				<div class="sol-prose"><?php echo sol_rich( sol_get( $sec, 'content' ) ); // phpcs:ignore ?></div>
			</section>
		<?php endforeach; ?>
	</div>
</section>
