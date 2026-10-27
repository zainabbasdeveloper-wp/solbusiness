<?php
/** Layout: pricing — one or more package sets; more than one shows tabs. */
defined( 'ABSPATH' ) || exit;

$tabs = sol_rows( sol_get( $s, 'tabs', array() ) );
$cols = max( 1, min( 4, (int) sol_get( $s, 'columns', 3 ) ) );
$cta  = sol_get( $s, 'bottom_cta', array() );
$many = count( $tabs ) > 1;

sol_section_open( $s, 'pricing' );
?>
<div class="sol-container">
	<?php sol_section_head( $s ); ?>
	<div class="sol-tabs" <?php echo $many ? 'data-sol-tabs' : ''; ?>>
		<?php if ( $many ) : ?>
			<div class="sol-tabs__list" role="tablist">
				<?php foreach ( $tabs as $i => $t ) : ?>
					<button type="button" role="tab" class="sol-tabs__tab" id="<?php echo esc_attr( $uid . '-tab-' . $i ); ?>" aria-controls="<?php echo esc_attr( $uid . '-panel-' . $i ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>">
						<?php echo sol_icon( sol_get( $t, 'icon' ) ); // phpcs:ignore ?> <?php echo esc_html( sol_get( $t, 'label' ) ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php foreach ( $tabs as $i => $t ) : ?>
			<div class="sol-tabs__panel" <?php echo $many ? 'role="tabpanel"' : ''; ?> id="<?php echo esc_attr( $uid . '-panel-' . $i ); ?>" <?php echo $many ? 'aria-labelledby="' . esc_attr( $uid . '-tab-' . $i ) . '"' : ''; ?> <?php echo ( $many && $i > 0 ) ? 'hidden' : ''; ?>>
				<div class="sol-grid sol-grid--<?php echo (int) $cols; ?> sol-packages">
					<?php
					foreach ( sol_rows( sol_get( $t, 'packages', array() ) ) as $p ) {
						echo sol_package( $p ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</div>
				<?php if ( sol_get( $t, 'note' ) ) : ?><p class="sol-note sol-note--center"><?php echo esc_html( $t['note'] ); ?></p><?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( is_array( $cta ) && ( sol_get( $cta, 'heading' ) || sol_link( sol_get( $cta, 'link' ) ) ) ) : ?>
		<div class="sol-inline-cta">
			<div>
				<?php if ( sol_get( $cta, 'heading' ) ) : ?><h3><?php echo esc_html( $cta['heading'] ); ?></h3><?php endif; ?>
				<?php if ( sol_get( $cta, 'text' ) ) : ?><p><?php echo esc_html( $cta['text'] ); ?></p><?php endif; ?>
			</div>
			<?php echo sol_button( sol_get( $cta, 'link' ), sol_get( $cta, 'heading' ) ? 'primary' : 'outline' ); // phpcs:ignore ?>
		</div>
	<?php endif; ?>
</div>
</section>
