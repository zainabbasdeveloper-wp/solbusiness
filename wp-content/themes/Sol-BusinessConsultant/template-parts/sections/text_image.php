<?php
/** Layout: text_image — copy with image, stats, checklist, buttons and a callout box. */
defined( 'ABSPATH' ) || exit;

$image   = sol_get( $s, 'image' );
$pos     = 'right' === sol_get( $s, 'image_position' ) ? 'right' : 'left';
$stats   = sol_rows( sol_get( $s, 'stats', array() ) );
$checks  = sol_list( sol_get( $s, 'checklist', array() ) );
$callout = sol_get( $s, 'callout', array() );
$has_co  = is_array( $callout ) && ( sol_get( $callout, 'text' ) || sol_get( $callout, 'title' ) );
$split   = $image || $has_co;

ob_start();
?>
<div class="sol-split__content">
	<?php sol_section_head( $s, 'h2', 'left' ); ?>
	<?php if ( sol_get( $s, 'content' ) ) : ?><div class="sol-prose"><?php echo sol_rich( $s['content'] ); // phpcs:ignore ?></div><?php endif; ?>
	<?php if ( $stats ) : ?>
		<dl class="sol-stats"><?php foreach ( $stats as $st ) : ?><div><dt><?php echo esc_html( sol_get( $st, 'value' ) ); ?></dt><dd><?php echo esc_html( sol_get( $st, 'label' ) ); ?></dd></div><?php endforeach; ?></dl>
	<?php endif; ?>
	<?php echo sol_checks( $checks ); // phpcs:ignore ?>
	<?php echo sol_buttons( sol_get( $s, 'buttons', array() ) ); // phpcs:ignore ?>
	<?php if ( $has_co && $image ) : ?>
		<div class="sol-callout"><?php echo sol_icon( sol_get( $callout, 'icon', 'info' ) ); // phpcs:ignore ?><div><?php if ( sol_get( $callout, 'title' ) ) : ?><strong><?php echo esc_html( $callout['title'] ); ?></strong><?php endif; ?><p><?php echo esc_html( sol_get( $callout, 'text' ) ); ?></p></div></div>
	<?php endif; ?>
</div>
<?php
$content = ob_get_clean();

sol_section_open( $s, 'text_image' );
?>
<div class="sol-container">
	<?php if ( $split ) : ?>
		<div class="sol-split sol-split--<?php echo esc_attr( $pos ); ?>">
			<?php if ( $image ) : ?>
				<div class="sol-split__media">
					<?php echo sol_image( $image, 'large', sol_get( $s, 'image_alt' ), 'sol-rounded-img' ); // phpcs:ignore ?>
					<?php if ( sol_get( $s, 'image_badge' ) ) : ?><span class="sol-img-badge"><span class="sol-dot"></span><?php echo esc_html( $s['image_badge'] ); ?></span><?php endif; ?>
				</div>
				<?php echo $content; // phpcs:ignore ?>
			<?php else : ?>
				<?php echo $content; // phpcs:ignore ?>
				<div class="sol-callout sol-callout--box">
					<?php if ( sol_get( $callout, 'icon' ) ) : ?><?php echo sol_icon( $callout['icon'] ); // phpcs:ignore ?><?php endif; ?>
					<?php if ( sol_get( $callout, 'title' ) ) : ?><h3><?php echo esc_html( $callout['title'] ); ?></h3><?php endif; ?>
					<?php echo sol_text( sol_get( $callout, 'text' ) ); // phpcs:ignore ?>
				</div>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="sol-container--mid sol-flush"><?php echo $content; // phpcs:ignore ?></div>
	<?php endif; ?>
</div>
</section>
