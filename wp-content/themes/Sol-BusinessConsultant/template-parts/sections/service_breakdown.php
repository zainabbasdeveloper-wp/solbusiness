<?php
/** Layout: service_breakdown — expandable inclusions / exclusions per service. */
defined( 'ABSPATH' ) || exit;

$items = sol_rows( sol_get( $s, 'items', array() ) );
sol_section_open( $s, 'service_breakdown' );
?>
<div class="sol-container sol-container--mid">
	<?php sol_section_head( $s ); ?>
	<div class="sol-breakdown">
		<?php foreach ( $items as $i => $it ) : ?>
			<details class="sol-breakdown__item" <?php echo 0 === $i ? 'open' : ''; ?>>
				<summary>
					<span class="sol-card__icon"><?php echo sol_icon( sol_get( $it, 'icon', 'package' ) ); // phpcs:ignore ?></span>
					<span><strong><?php echo esc_html( sol_get( $it, 'name' ) ); ?></strong><small><?php echo esc_html( sol_get( $it, 'tagline' ) ); ?></small></span>
					<?php echo sol_icon( 'chevron-down', 'sol-accordion__chev' ); // phpcs:ignore ?>
				</summary>
				<div class="sol-breakdown__body">
					<div class="sol-grid sol-grid--2">
						<div>
							<h4>What's Included</h4>
							<?php echo sol_checks( sol_list( sol_get( $it, 'included', array() ) ), 'sol-checks--sm' ); // phpcs:ignore ?>
						</div>
						<div>
							<h4>What's NOT Included</h4>
							<ul class="sol-dashes"><?php foreach ( sol_list( sol_get( $it, 'not_included', array() ) ) as $x ) : ?><li><?php echo esc_html( $x ); ?></li><?php endforeach; ?></ul>
						</div>
					</div>
					<dl class="sol-breakdown__facts">
						<?php if ( sol_get( $it, 'timeline' ) ) : ?><div><dt>Timeline</dt><dd><?php echo esc_html( $it['timeline'] ); ?></dd></div><?php endif; ?>
						<?php if ( sol_get( $it, 'price' ) ) : ?><div><dt>Price</dt><dd><?php echo esc_html( $it['price'] ); ?></dd></div><?php endif; ?>
					</dl>
					<?php $del = sol_list( sol_get( $it, 'deliverables', array() ) ); ?>
					<?php if ( $del ) : ?><h4>Deliverables</h4><?php echo sol_checks( $del, 'sol-checks--sm' ); // phpcs:ignore ?><?php endif; ?>
					<?php if ( sol_get( $it, 'support' ) ) : ?><p class="sol-breakdown__support"><strong>Support Included:</strong> <?php echo esc_html( $it['support'] ); ?></p><?php endif; ?>
					<?php echo sol_button( sol_get( $it, 'link' ), 'primary' ); // phpcs:ignore ?>
				</div>
			</details>
		<?php endforeach; ?>
	</div>
</div>
</section>
