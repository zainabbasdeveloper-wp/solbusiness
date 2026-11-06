<?php
/** Layout: service_overview — "What we offer" + "Why SOL" from the current service. */
defined( 'ABSPATH' ) || exit;

$id       = sol_context_id();
$features = sol_list( get_field( 'features', $id ) );
$why      = sol_list( get_field( 'why', $id ) );
if ( ! $features && ! $why ) {
	return;
}
?>
<?php if ( $features ) : ?>
	<section class="sol-section sol-bg-chalk sol-service-overview">
		<div class="sol-container">
			<?php sol_section_head( array( 'eyebrow' => sol_get( $s, 'eyebrow', 'Overview' ), 'heading' => get_field( 'overview_heading', $id ) ) ); ?>
			<div class="sol-grid sol-grid--3">
				<?php foreach ( $features as $i => $f ) : ?>
					<article class="sol-card sol-card--bordered"><div class="sol-card__top"><span class="sol-card__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span></div><p class="sol-card__text"><?php echo esc_html( $f ); ?></p></article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
<?php if ( $why ) : ?>
	<section class="sol-section sol-bg-white">
		<div class="sol-container">
			<div class="sol-split">
				<div class="sol-split__content">
					<?php sol_section_head( array( 'eyebrow' => 'Why SOL', 'heading' => get_field( 'why_heading', $id ), 'intro' => get_field( 'why_intro', $id ) ), 'h2', 'left' ); ?>
				</div>
				<div><?php echo sol_checks( $why, 'sol-checks--boxed' ); // phpcs:ignore ?></div>
			</div>
		</div>
	</section>
<?php endif; ?>
