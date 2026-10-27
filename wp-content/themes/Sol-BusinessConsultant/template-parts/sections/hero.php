<?php
/** Layout: hero. Also used for service heroes (extra keys: checks, scroll_hint). */
defined( 'ABSPATH' ) || exit;

$style  = sol_get( $s, 'style', 'split' );
$image  = sol_get( $s, 'image' );
$badges = sol_rows( sol_get( $s, 'badges', array() ) );
$stats  = sol_rows( sol_get( $s, 'stats', array() ) );
$checks = (array) sol_get( $s, 'checks', array() );
$hasimg = $image && 'centered' !== $style;
?>
<section class="sol-hero sol-hero--<?php echo esc_attr( $style ); ?><?php echo $hasimg ? '' : ' sol-hero--noimg'; ?>">
	<div class="sol-container sol-hero__grid">
		<div class="sol-hero__content">
			<?php if ( sol_get( $s, 'eyebrow' ) ) : ?><span class="sol-pill"><?php echo esc_html( $s['eyebrow'] ); ?></span><?php endif; ?>
			<h1 class="sol-hero__title"><?php echo sol_heading( sol_get( $s, 'heading' ) ); // phpcs:ignore ?></h1>
			<?php if ( sol_get( $s, 'text' ) ) : ?><div class="sol-hero__text"><?php echo sol_text( $s['text'] ); // phpcs:ignore ?></div><?php endif; ?>
			<?php if ( $checks ) : ?>
				<div class="sol-hero__checks"><?php foreach ( $checks as $c ) : ?><span><?php echo sol_icon( 'check-circle' ); // phpcs:ignore ?> <?php echo esc_html( $c ); ?></span><?php endforeach; ?></div>
			<?php endif; ?>
			<?php echo sol_buttons( sol_get( $s, 'buttons', array() ), 'sol-hero__buttons' ); // phpcs:ignore ?>
			<?php if ( $badges ) : ?>
				<div class="sol-hero__badges">
					<?php foreach ( $badges as $b ) : ?><span><?php echo sol_icon( sol_get( $b, 'icon', 'check' ) ); // phpcs:ignore ?> <?php echo esc_html( sol_get( $b, 'label' ) ); ?></span><?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( $stats ) : ?>
				<dl class="sol-hero__stats">
					<?php foreach ( $stats as $st ) : ?><div><dt><?php echo esc_html( sol_get( $st, 'value' ) ); ?></dt><dd><?php echo esc_html( sol_get( $st, 'label' ) ); ?></dd></div><?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( sol_get( $s, 'disclaimer' ) ) : ?><p class="sol-hero__small"><?php echo esc_html( $s['disclaimer'] ); ?></p><?php endif; ?>
			<?php if ( sol_get( $s, 'scroll_hint' ) ) : ?><a class="sol-hero__scroll" href="<?php echo esc_attr( $s['scroll_hint'] ); ?>"><?php echo sol_icon( 'arrow-down' ); // phpcs:ignore ?> Scroll to form</a><?php endif; ?>
		</div>
		<?php if ( $hasimg ) : ?>
			<div class="sol-hero__media">
				<div class="sol-hero__frame"><?php echo sol_image( $image, 'large', sol_get( $s, 'image_alt' ), 'sol-hero__img', true ); // phpcs:ignore ?></div>
				<?php if ( sol_get( $s, 'card_title' ) ) : ?>
					<div class="sol-hero__card">
						<p class="sol-hero__card-title"><span><?php echo sol_icon( 'check-circle' ); // phpcs:ignore ?></span><?php echo esc_html( $s['card_title'] ); ?></p>
						<p><?php echo esc_html( sol_get( $s, 'card_text' ) ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
