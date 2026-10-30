<?php
/**
 * Accessibility toolbar: a floating button (bottom-left) that opens display and reading options.
 * Text size, grayscale, high contrast, negative contrast, light background, underlined links and read aloud.
 * Choices are kept in the visitor's browser (localStorage "sol-a11y") and reapplied before the page paints.
 * Turn it off in SOL Site → Widgets & Forms.
 *
 * @package Sol-BusinessConsultant
 */

defined( 'ABSPATH' ) || exit;

function sol_a11y_enabled() {
	return (bool) apply_filters( 'sol_a11y_enabled', sol_opt( 'a11y_enabled', true ) );
}

// Reapply saved choices in <head> so the page never flashes the default look.
add_action( 'wp_head', function () {
	if ( ! sol_a11y_enabled() ) {
		return;
	}
	?>
<script>(function(){try{var s=JSON.parse(localStorage.getItem('sol-a11y')||'{}'),h=document.documentElement;if(s.text){h.style.setProperty('--a11y-zoom',1+s.text/10);h.classList.add('a11y-text');}['gray','contrast','negative','light','links'].forEach(function(k){if(s[k])h.classList.add('a11y-'+k);});}catch(e){}})();</script>
	<?php
}, 2 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! sol_a11y_enabled() ) {
		return;
	}
	wp_enqueue_style( 'sol-a11y', SOL_URI . 'assets/css/sol-a11y.css', array( 'sol' ), SOL_THEME_VERSION );
	wp_enqueue_script( 'sol-a11y', SOL_URI . 'assets/js/sol-a11y.js', array(), SOL_THEME_VERSION, true );
}, 20 );

/** Inline Lucide icon. */
function sol_a11y_svg( $paths ) {
	return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $paths . '</svg>';
}

add_action( 'wp_footer', function () {
	if ( ! sol_a11y_enabled() ) {
		return;
	}
	$icons = array(
		'launch'   => '<circle cx="16" cy="4" r="1"/><path d="m18 19 1-7-6 1"/><path d="m5 8 3-3 5.5 3-2.36 3.5"/><path d="M4.24 14.5a5 5 0 0 0 6.88 6"/><path d="M13.76 17.5a5 5 0 0 0-6.88-6"/>',
		'close'    => '<path d="M18 6 6 18M6 6l12 12"/>',
		'type'     => '<path d="M4 7V4h16v3M9 20h6M12 4v16"/>',
		'minus'    => '<path d="M5 12h14"/>',
		'plus'     => '<path d="M5 12h14M12 5v14"/>',
		'gray'     => '<circle cx="9" cy="9" r="7"/><circle cx="15" cy="15" r="7"/>',
		'contrast' => '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
		'negative' => '<circle cx="12" cy="12" r="10"/><path d="M12 18a6 6 0 0 0 0-12v12z"/>',
		'light'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
		'links'    => '<path d="M6 4v6a6 6 0 0 0 12 0V4"/><path d="M4 20h16"/>',
		'read'     => '<path d="M11 4.7a.7.7 0 0 0-1.2-.5L6.4 7.6a1.4 1.4 0 0 1-1 .4H3a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2.4a1.4 1.4 0 0 1 1 .4l3.4 3.4a.7.7 0 0 0 1.2-.5z"/><path d="M16 9a5 5 0 0 1 0 6"/><path d="M19.36 18.36a9 9 0 0 0 0-12.72"/>',
		'reset'    => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
		'check'    => '<path d="M20 6 9 17l-5-5"/>',
	);
	$options = array(
		'gray'     => 'Grayscale',
		'contrast' => 'High Contrast',
		'negative' => 'Negative Contrast',
		'light'    => 'Light Background',
		'links'    => 'Links Underline',
		'read'     => 'Read Aloud',
	);
	?>
	<div class="sol-a11y" data-sol-a11y>
		<button type="button" class="sol-a11y__launch" data-a11y-toggle aria-expanded="false" aria-controls="sol-a11y-panel" aria-label="Accessibility options">
			<?php echo sol_a11y_svg( $icons['launch'] ); // phpcs:ignore ?>
			<span class="sol-a11y__dot" aria-hidden="true"></span>
		</button>

		<section class="sol-a11y__panel" id="sol-a11y-panel" role="dialog" aria-labelledby="sol-a11y-title" hidden>
			<header class="sol-a11y__head">
				<span class="sol-a11y__badge"><?php echo sol_a11y_svg( $icons['launch'] ); // phpcs:ignore ?></span>
				<div>
					<p class="sol-a11y__title" id="sol-a11y-title">Accessibility Toolbar</p>
					<p class="sol-a11y__sub">Display and reading options</p>
				</div>
				<button type="button" class="sol-a11y__close" data-a11y-close aria-label="Close accessibility options"><?php echo sol_a11y_svg( $icons['close'] ); // phpcs:ignore ?></button>
			</header>

			<div class="sol-a11y__body">
				<div class="sol-a11y__size">
					<span class="sol-a11y__size-label"><?php echo sol_a11y_svg( $icons['type'] ); // phpcs:ignore ?>Text size</span>
					<div class="sol-a11y__stepper">
						<button type="button" data-a11y-text="-1" aria-label="Decrease text" title="Decrease Text"><?php echo sol_a11y_svg( $icons['minus'] ); // phpcs:ignore ?></button>
						<output data-a11y-size aria-live="polite">100%</output>
						<button type="button" data-a11y-text="1" aria-label="Increase text" title="Increase Text"><?php echo sol_a11y_svg( $icons['plus'] ); // phpcs:ignore ?></button>
					</div>
				</div>

				<div class="sol-a11y__grid">
					<?php foreach ( $options as $key => $label ) : ?>
						<button type="button" class="sol-a11y__opt" data-a11y-opt="<?php echo esc_attr( $key ); ?>" aria-pressed="false">
							<span class="sol-a11y__icon"><?php echo sol_a11y_svg( $icons[ $key ] ); // phpcs:ignore ?></span>
							<span class="sol-a11y__label"><?php echo esc_html( $label ); ?></span>
							<span class="sol-a11y__tick"><?php echo sol_a11y_svg( $icons['check'] ); // phpcs:ignore ?></span>
						</button>
					<?php endforeach; ?>
				</div>
				<p class="sol-a11y__hint" data-a11y-hint hidden>Read Aloud is on. Click any text, or select some, to hear it.</p>

				<button type="button" class="sol-a11y__reset" data-a11y-reset><?php echo sol_a11y_svg( $icons['reset'] ); // phpcs:ignore ?>Reset all settings</button>
			</div>
		</section>
	</div>
	<?php
}, 6 );
