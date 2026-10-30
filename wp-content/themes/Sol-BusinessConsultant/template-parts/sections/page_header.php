<?php
/** Layout: page_header — eyebrow, H1, intro and optional info cards. */
defined( 'ABSPATH' ) || exit;

$cards = sol_rows( sol_get( $s, 'info_cards', array() ) );
?>
<section class="sol-page-header">
	<div class="sol-container">
		<?php if ( sol_get( $s, 'eyebrow' ) ) : ?><span class="sol-eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span><?php endif; ?>
		<h1 class="sol-page-header__title"><?php echo sol_heading( sol_get( $s, 'heading' ) ); // phpcs:ignore ?></h1>
		<?php if ( sol_get( $s, 'text' ) ) : ?><div class="sol-page-header__text"><?php echo sol_text( $s['text'] ); // phpcs:ignore ?></div><?php endif; ?>
		<?php if ( $cards ) : ?>
			<div class="sol-info-cards">
				<?php foreach ( $cards as $c ) : ?>
					<?php $u = sol_get( $c, 'url' ); ?>
					<div class="sol-info-card">
						<?php echo sol_icon( sol_get( $c, 'icon', 'info' ) ); // phpcs:ignore ?>
						<div>
							<p class="sol-info-card__label"><?php echo esc_html( sol_get( $c, 'label' ) ); ?></p>
							<?php if ( $u ) : ?>
								<a class="sol-info-card__value" href="<?php echo esc_url( sol_url( $u ) ); ?>"><?php echo esc_html( sol_get( $c, 'value' ) ); ?></a>
							<?php else : ?>
								<p class="sol-info-card__value"><?php echo esc_html( sol_get( $c, 'value' ) ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php echo sol_buttons( sol_get( $s, 'buttons', array() ) ); // phpcs:ignore ?>
	</div>
</section>
