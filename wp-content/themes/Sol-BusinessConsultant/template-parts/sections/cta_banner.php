<?php
/** Layout: cta_banner — dark, amber, light or image-overlay call to action. */
defined( 'ABSPATH' ) || exit;

$style = preg_replace( '/[^a-z]/', '', sol_get( $s, 'style', 'dark' ) );
$image = sol_get( $s, 'image' );
$list  = sol_list( sol_get( $s, 'list', array() ) );
$id    = sanitize_title( sol_get( $s, 'section_id' ) );
?>
<section<?php echo $id ? ' id="' . esc_attr( $id ) . '"' : ''; ?> class="sol-section sol-cta sol-cta--<?php echo esc_attr( $style ); ?>">
	<div class="sol-container">
		<div class="sol-cta__box">
			<?php if ( 'image' === $style && $image ) : ?>
				<?php echo sol_image( $image, 'full', '', 'sol-cta__bg' ); // phpcs:ignore ?>
			<?php endif; ?>
			<div class="sol-cta__content">
				<?php if ( sol_get( $s, 'eyebrow' ) ) : ?><span class="sol-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span><?php endif; ?>
				<?php if ( sol_get( $s, 'heading' ) ) : ?><h2 class="sol-cta__title"><?php echo sol_heading( $s['heading'] ); // phpcs:ignore ?></h2><?php endif; ?>
				<?php if ( sol_get( $s, 'text' ) ) : ?><div class="sol-cta__text"><?php echo sol_text( $s['text'] ); // phpcs:ignore ?></div><?php endif; ?>
				<?php echo sol_checks( $list, 'sol-checks--inline' ); // phpcs:ignore ?>
			</div>
			<?php echo sol_buttons( sol_get( $s, 'buttons', array() ), 'sol-cta__buttons' ); // phpcs:ignore ?>
			<?php if ( sol_get( $s, 'note' ) ) : ?><p class="sol-cta__note"><?php echo esc_html( $s['note'] ); ?></p><?php endif; ?>
		</div>
	</div>
</section>
