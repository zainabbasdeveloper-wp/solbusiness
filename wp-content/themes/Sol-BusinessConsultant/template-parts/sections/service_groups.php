<?php
/** Layout: service_groups — a few service categories, each with a photo, a short description and its services. */
defined( 'ABSPATH' ) || exit;

$groups = sol_rows( sol_get( $s, 'groups', array() ) );
if ( ! $groups ) {
	return;
}

sol_section_open( $s, 'service_groups' );
?>
<div class="sol-container">
	<?php sol_section_head( $s ); ?>
	<?php foreach ( $groups as $i => $g ) : ?>
		<?php
		$anchor = sanitize_title( sol_get( $g, 'anchor' ) );
		$links  = sol_rows( sol_get( $g, 'links', array() ) );
		$hid    = 'sol-sg-' . ( $anchor ? $anchor : $i );
		?>
		<article class="sol-sgroup"<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $hid ); ?>">
			<div class="sol-sgroup__intro">
				<?php if ( sol_get( $g, 'image' ) ) : ?>
					<div class="sol-sgroup__media"><?php echo sol_image( $g['image'], 'medium_large', sol_get( $g, 'image_alt' ) ); // phpcs:ignore ?></div>
				<?php endif; ?>
				<span class="sol-sgroup__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
				<h2 class="sol-sgroup__title" id="<?php echo esc_attr( $hid ); ?>"><?php echo esc_html( sol_get( $g, 'title' ) ); ?></h2>
				<?php if ( sol_get( $g, 'text' ) ) : ?><p class="sol-sgroup__text"><?php echo esc_html( $g['text'] ); ?></p><?php endif; ?>
			</div>
			<?php if ( $links ) : ?>
				<ul class="sol-sgroup__list">
					<?php foreach ( $links as $l ) : ?>
						<?php $u = sol_get( $l, 'url' ); ?>
						<li>
							<a href="<?php echo esc_url( sol_url( $u ) ); ?>">
								<span class="sol-sgroup__name"><?php echo esc_html( sol_get( $l, 'title' ) ); ?></span>
								<?php if ( sol_get( $l, 'text' ) ) : ?><span class="sol-sgroup__line"><?php echo esc_html( $l['text'] ); ?></span><?php endif; ?>
								<?php echo sol_icon( 'arrow-right', 'sol-sgroup__arrow' ); // phpcs:ignore ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
	<?php echo sol_buttons( sol_get( $s, 'buttons', array() ), 'sol-buttons--after' ); // phpcs:ignore ?>
</div>
</section>
