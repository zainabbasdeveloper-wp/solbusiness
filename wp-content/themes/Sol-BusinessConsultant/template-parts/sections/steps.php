<?php
/** Layout: steps — numbered lists, timelines or step cards, in one or more groups. */
defined( 'ABSPATH' ) || exit;

$style  = preg_replace( '/[^a-z]/', '', sol_get( $s, 'style', 'numbered' ) );
$groups = sol_rows( sol_get( $s, 'groups', array() ) );
$cols   = max( 1, min( 4, (int) sol_get( $s, 'columns', 2 ) ) );
$image  = sol_get( $s, 'image' );
$multi  = count( $groups ) > 1;

sol_section_open( $s, 'steps', 'sol-steps--' . $style );
?>
<div class="sol-container">
	<?php sol_section_head( $s ); ?>
	<div class="<?php echo $image ? 'sol-split' : ''; ?>">
		<?php if ( $image ) : ?>
			<div class="sol-split__media"><?php echo sol_image( $image, 'large', wp_strip_all_tags( sol_get( $s, 'heading' ) ), 'sol-rounded-img' ); // phpcs:ignore ?></div>
		<?php endif; ?>
		<div class="<?php echo $multi ? 'sol-grid sol-grid--2' : ''; ?>">
			<?php foreach ( $groups as $g ) : ?>
				<?php $steps = sol_rows( sol_get( $g, 'steps', array() ) ); ?>
				<div class="sol-step-group<?php echo $multi ? ' sol-step-group--boxed' : ''; ?>">
					<?php if ( sol_get( $g, 'title' ) ) : ?>
						<h3 class="sol-step-group__title"><?php if ( sol_get( $g, 'icon' ) ) : ?><span class="sol-card__icon"><?php echo sol_icon( $g['icon'] ); // phpcs:ignore ?></span><?php endif; ?><?php echo esc_html( $g['title'] ); ?></h3>
					<?php endif; ?>
					<ol class="sol-step-list<?php echo 'cards' === $style ? ' sol-grid sol-grid--' . (int) ( $multi ? 1 : $cols ) : ''; ?>">
						<?php foreach ( $steps as $i => $st ) : ?>
							<?php $aid = sanitize_title( sol_get( $st, 'anchor_id' ) ); ?>
							<li class="sol-step"<?php echo $aid ? ' id="' . esc_attr( $aid ) . '"' : ''; ?>>
								<span class="sol-step__marker">
									<?php
									if ( 'cards' === $style && sol_get( $st, 'icon' ) ) {
										echo sol_icon( $st['icon'] ); // phpcs:ignore
									} elseif ( 'timeline' === $style && sol_get( $st, 'icon' ) ) {
										echo sol_icon( $st['icon'] ); // phpcs:ignore
									} else {
										echo esc_html( sol_get( $st, 'label', (string) ( $i + 1 ) ) );
									}
									?>
								</span>
								<div class="sol-step__body">
									<?php if ( sol_get( $st, 'label' ) && ( 'cards' === $style || 'timeline' === $style ) ) : ?><span class="sol-step__label"><?php echo esc_html( $st['label'] ); ?></span><?php endif; ?>
									<h4 class="sol-step__title"><?php echo esc_html( sol_get( $st, 'title' ) ); ?></h4>
									<?php if ( sol_get( $st, 'text' ) ) : ?><p><?php echo esc_html( $st['text'] ); ?></p><?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
					<?php echo sol_buttons( sol_get( $g, 'buttons', array() ), 'sol-step-group__buttons' ); // phpcs:ignore ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php echo sol_buttons( sol_get( $s, 'buttons', array() ), 'sol-buttons--after sol-buttons--center' ); // phpcs:ignore ?>
	<?php if ( sol_get( $s, 'note' ) ) : ?><p class="sol-note"><?php echo esc_html( $s['note'] ); ?></p><?php endif; ?>
</div>
</section>
