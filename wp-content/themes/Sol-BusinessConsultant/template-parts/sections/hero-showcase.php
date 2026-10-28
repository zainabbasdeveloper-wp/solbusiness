<?php
/**
 * Hero, "showcase" style: dark navy banner with an outlined headline, a framed photo,
 * a floating card and counting stats. Load animations live in sol.css (.sol-anim) and sol.js.
 * Heading: one line per row; plain lines are outlined, a [bracketed] line is solid amber.
 */
defined( 'ABSPATH' ) || exit;

$image  = sol_get( $s, 'image' );
$badges = sol_rows( sol_get( $s, 'badges', array() ) );
$stats  = sol_rows( sol_get( $s, 'stats', array() ) );
$lines  = array_filter( array_map( 'trim', explode( "\n", (string) sol_get( $s, 'heading' ) ) ), 'strlen' );
$btns   = sol_rows( sol_get( $s, 'buttons', array() ) );
?>
<section class="sol-hero sol-sh" data-sol-showcase>
	<span class="sol-sh__spot" aria-hidden="true"></span>
	<div class="sol-container sol-sh__grid">
		<div class="sol-sh__content">
			<?php if ( sol_get( $s, 'eyebrow' ) ) : ?><span class="sol-sh__kick"><?php echo esc_html( $s['eyebrow'] ); ?></span><?php endif; ?>
			<h1 class="sol-sh__title">
				<?php foreach ( $lines as $line ) : ?>
					<?php if ( preg_match( '/^\[(.+)\]$/', $line, $m ) ) : ?>
						<?php $dot = '.' === substr( $m[1], -1 ); ?>
						<span class="sol-sh__ln sol-sh__ln--accent"><span class="sol-sh__accent"><?php echo esc_html( $dot ? substr( $m[1], 0, -1 ) : $m[1] ); ?></span><?php if ( $dot ) : ?><span class="sol-sh__dot" aria-hidden="true"></span><span class="screen-reader-text">.</span><?php endif; ?></span>
					<?php else : ?>
						<span class="sol-sh__ln"><span class="sol-sh__outline"><?php echo esc_html( str_replace( array( '[', ']' ), '', $line ) ); ?></span></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</h1>
			<?php if ( sol_get( $s, 'text' ) ) : ?><div class="sol-sh__text"><?php echo sol_text( $s['text'] ); // phpcs:ignore ?></div><?php endif; ?>
			<?php if ( $btns ) : ?>
				<div class="sol-sh__btns">
					<?php foreach ( $btns as $i => $b ) : ?>
						<?php $l = sol_get( $b, 'link' ); ?>
						<?php if ( empty( $l['url'] ) ) { continue; } ?>
						<a class="sol-sh__btn <?php echo 0 === $i ? 'sol-sh__btn--pri' : 'sol-sh__btn--sec'; ?>" href="<?php echo esc_url( sol_url( $l['url'] ) ); ?>"<?php echo ! empty( $l['target'] ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $l['title'] ); ?><?php if ( 0 === $i ) : ?> <i aria-hidden="true">→</i><?php endif; ?></a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( $badges ) : ?>
				<div class="sol-sh__ticks">
					<?php foreach ( $badges as $b ) : ?><span><?php echo sol_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( sol_get( $b, 'label' ) ); ?></span><?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( $stats ) : ?>
				<dl class="sol-sh__stats">
					<?php foreach ( $stats as $st ) : ?>
						<?php
						$val = (string) sol_get( $st, 'value' );
						$num = preg_match( '/^(\d+)(.*)$/', $val, $mm ) ? $mm : null;
						?>
						<div>
							<?php if ( $num ) : ?>
								<dt data-sol-count="<?php echo esc_attr( $num[1] ); ?>"><?php echo esc_html( $num[1] ); ?><?php if ( '' !== $num[2] ) : ?><b><?php echo esc_html( $num[2] ); ?></b><?php endif; ?></dt>
							<?php else : ?>
								<dt><?php echo esc_html( $val ); ?></dt>
							<?php endif; ?>
							<dd><?php echo esc_html( sol_get( $st, 'label' ) ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>
		<?php if ( $image ) : ?>
			<div class="sol-sh__vis">
				<span class="sol-sh__ring" aria-hidden="true"></span>
				<div class="sol-sh__frame"><div class="sol-sh__frame-in"><?php echo sol_image( $image, 'large', sol_get( $s, 'image_alt' ), 'sol-sh__img', true ); // phpcs:ignore ?></div></div>
				<?php if ( sol_get( $s, 'card_title' ) ) : ?>
					<div class="sol-sh__card">
						<span class="sol-sh__check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
						<div><b><?php echo esc_html( $s['card_title'] ); ?></b><?php if ( sol_get( $s, 'card_text' ) ) : ?><p><?php echo esc_html( $s['card_text'] ); ?></p><?php endif; ?></div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
