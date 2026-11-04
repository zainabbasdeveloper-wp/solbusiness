<?php
/**
 * Accessibility menu: a tab docked to the left edge (under the cart tab) that opens the full set of
 * accessibility adjustments: one-click profiles, content, colour and orientation adjustments, plus a
 * shortcut to the AI assistant (inc/chat.php). Everything runs in the visitor's browser, with no outside service.
 *
 * Choices are kept in the visitor's browser (localStorage "sol-a11y") and reapplied in <head> before the page paints.
 * Turn it off in SOL Site → Widgets & Forms.
 *
 * @package Sol-BusinessConsultant
 */

defined( 'ABSPATH' ) || exit;

function sol_a11y_enabled() {
	return (bool) apply_filters( 'sol_a11y_enabled', sol_opt( 'a11y_enabled', true ) );
}

// Reapply saved choices in <head> so the page never flashes the default look.
// The script stores the finished result as {c: [classes], v: {css variables}}, so this only copies it onto <html>.
add_action( 'wp_head', function () {
	if ( ! sol_a11y_enabled() ) {
		return;
	}
	?>
<script>(function(){try{var d=JSON.parse(localStorage.getItem('sol-a11y')||'{}'),h=document.documentElement;(d.c||[]).forEach(function(c){h.classList.add(c);});var v=d.v||{};for(var k in v){h.style.setProperty(k,v[k]);}if(sessionStorage.getItem('sol-a11y-hide'))h.classList.add('a11y-ui-hidden');}catch(e){}})();</script>
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

/** Lucide icon paths used by the menu. */
function sol_a11y_icons() {
	return array(
		'launch'   => '<circle cx="16" cy="4" r="1"/><path d="m18 19 1-7-6 1"/><path d="m5 8 3-3 5.5 3-2.36 3.5"/><path d="M4.24 14.5a5 5 0 0 0 6.88 6"/><path d="M13.76 17.5a5 5 0 0 0-6.88-6"/>',
		'close'    => '<path d="M18 6 6 18M6 6l12 12"/>',
		'minus'    => '<path d="M5 12h14"/>',
		'plus'     => '<path d="M5 12h14M12 5v14"/>',
		'check'    => '<path d="M20 6 9 17l-5-5"/>',
		'reset'    => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
		'doc'      => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4M10 9H8M16 13H8M16 17H8"/>',
		'hide'     => '<path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.53 13.53 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="m2 2 20 20"/>',
		'spark'    => '<path d="M9.94 15.5A2 2 0 0 0 8.5 14.06l-6.14-1.58a.5.5 0 0 1 0-.96L8.5 9.94A2 2 0 0 0 9.94 8.5l1.58-6.14a.5.5 0 0 1 .96 0L14.06 8.5A2 2 0 0 0 15.5 9.94l6.14 1.58a.5.5 0 0 1 0 .96L15.5 14.06a2 2 0 0 0-1.44 1.44l-1.58 6.14a.5.5 0 0 1-.96 0z"/><path d="M20 3v4M22 5h-4"/>',
		'arrow'    => '<path d="M5 12h14M12 5l7 7-7 7"/>',
		// Profiles
		'seizure'  => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
		'vision'   => '<path d="M2.06 12.35a1 1 0 0 1 0-.7 10.75 10.75 0 0 1 19.88 0 1 1 0 0 1 0 .7 10.75 10.75 0 0 1-19.88 0"/><circle cx="12" cy="12" r="3"/>',
		'adhd'     => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
		'cognitive' => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
		'keyboard' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="M6 8h.01M10 8h.01M14 8h.01M18 8h.01M8 12h.01M12 12h.01M16 12h.01M7 16h10"/>',
		'screen'   => '<path d="M11 4.7a.7.7 0 0 0-1.2-.5L6.4 7.6a1.4 1.4 0 0 1-1 .4H3a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2.4a1.4 1.4 0 0 1 1 .4l3.4 3.4a.7.7 0 0 0 1.2-.5z"/><path d="M16 9a5 5 0 0 1 0 6"/><path d="M19.36 18.36a9 9 0 0 0 0-12.72"/>',
		'older'    => '<circle cx="6" cy="15" r="4"/><circle cx="18" cy="15" r="4"/><path d="M14 15a2 2 0 0 0-2-1 2 2 0 0 0-2 1"/><path d="M2.5 13 5 7c.7-1.3 1.4-2 3-2"/><path d="M21.5 13 19 7c-.7-1.3-1.5-2-3-2"/>',
		// Content
		'scale'    => '<path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="m21 3-7 7"/><path d="m3 21 7-7"/>',
		'font'     => '<path d="M4 7V4h16v3M9 20h6M12 4v16"/>',
		'line'     => '<path d="M21 6H11M21 12H11M21 18H11"/><path d="m3 8 3-3 3 3"/><path d="m3 16 3 3 3-3"/><path d="M6 5v14"/>',
		'space'    => '<path d="m2 18 4-12 4 12"/><path d="M3.5 14h5"/><path d="M14 6v12M20 6v12"/><path d="M14 12h6"/>',
		'readable' => '<path d="m3 15 4-8 4 8"/><path d="M4 13h6"/><circle cx="18" cy="12" r="3"/><path d="M21 9v6"/>',
		'titles'   => '<path d="M6 12h12"/><path d="M6 20V4"/><path d="M18 20V4"/>',
		'links'    => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
		'magnify'  => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/>',
		'left'     => '<path d="M21 6H3M15 12H3M17 18H3"/>',
		'center'   => '<path d="M21 6H3M17 12H7M19 18H5"/>',
		'right'    => '<path d="M21 6H3M21 12H9M21 18H7"/>',
		// Colour
		'dark'     => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
		'light'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
		'contrast' => '<circle cx="12" cy="12" r="10"/><path d="M12 18a6 6 0 0 0 0-12v12z"/>',
		'high'     => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>',
		'low'      => '<path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/><path d="m3 3 18 18"/>',
		'mono'     => '<circle cx="9" cy="9" r="7"/><circle cx="15" cy="15" r="7"/>',
		// Orientation
		'read'     => '<path d="M11 4.7a.7.7 0 0 0-1.2-.5L6.4 7.6a1.4 1.4 0 0 1-1 .4H3a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2.4a1.4 1.4 0 0 1 1 .4l3.4 3.4a.7.7 0 0 0 1.2-.5z"/><path d="M16 9a5 5 0 0 1 0 6"/><path d="M19.36 18.36a9 9 0 0 0 0-12.72"/>',
		'mute'     => '<path d="M11 4.7a.7.7 0 0 0-1.2-.5L6.4 7.6a1.4 1.4 0 0 1-1 .4H3a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h2.4a1.4 1.4 0 0 1 1 .4l3.4 3.4a.7.7 0 0 0 1.2-.5z"/><path d="m22 9-6 6M16 9l6 6"/>',
		'noimg'    => '<path d="m2 2 20 20"/><path d="M10.41 10.41a2 2 0 1 1-2.83-2.83"/><path d="M13.5 13.5 6 21"/><path d="M18 12l3 3"/><path d="M3.59 3.59A2 2 0 0 0 3 5v14a2 2 0 0 0 2 2h14c.55 0 1.05-.22 1.41-.59"/><path d="M21 15V5a2 2 0 0 0-2-2H9"/>',
		'readmode' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
		'guide'    => '<rect x="2" y="9" width="20" height="6" rx="2"/><path d="M6 9v3M10 9v3M14 9v3M18 9v3"/>',
		'still'    => '<circle cx="12" cy="12" r="10"/><path d="M10 15V9M14 15V9"/>',
		'mask'     => '<path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/>',
		'hover'    => '<path d="M14 4.1 12 6"/><path d="m5.1 8-2.9-.8"/><path d="m6 12-1.9 2"/><path d="M7.2 2.2 8 5.1"/><path d="M9.04 9.04a.5.5 0 0 1 .65-.65l11 4.5a.5.5 0 0 1-.07.95l-4.12 1.07a2 2 0 0 0-1.43 1.43l-1.07 4.12a.5.5 0 0 1-.95.07z"/>',
		'focus'    => '<path d="M5 3a2 2 0 0 0-2 2M19 3a2 2 0 0 1 2 2M21 19a2 2 0 0 1-2 2M5 21a2 2 0 0 1-2-2M9 3h1M9 21h1M14 3h1M14 21h1M3 9v1M21 9v1M3 14v1M21 14v1"/>',
		'cursor'   => '<path d="M4.04 4.95a.5.5 0 0 1 .65-.65l15.5 6a.5.5 0 0 1-.03.95l-6.8 2a1 1 0 0 0-.68.68l-2 6.8a.5.5 0 0 1-.94.03z"/>',
		'compass'  => '<circle cx="12" cy="12" r="10"/><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36z"/>',
		'palette'  => '<circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.84-.44-1.13-.29-.29-.44-.65-.44-1.13a1.64 1.64 0 0 1 1.67-1.67h2c3.05 0 5.55-2.5 5.55-5.55C21.97 6.01 17.46 2 12 2z"/>',
	);
}

/** Renders one option tile. $set is "key" for on/off, or "key=value" for one-of-a-group choices. */
function sol_a11y_tile( $set, $label, $icon ) {
	$icons = sol_a11y_icons();
	printf(
		'<button type="button" class="sol-a11y__opt" data-a11y-set="%s" aria-pressed="false"><span class="sol-a11y__icon">%s</span><span class="sol-a11y__label">%s</span><span class="sol-a11y__tick">%s</span></button>',
		esc_attr( $set ),
		sol_a11y_svg( $icons[ $icon ] ), // phpcs:ignore WordPress.Security.EscapeOutput
		esc_html( $label ),
		sol_a11y_svg( $icons['check'] ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
}

/** Renders a − value + stepper card. */
function sol_a11y_stepper( $key, $label, $icon ) {
	$icons = sol_a11y_icons();
	?>
	<div class="sol-a11y__size" data-a11y-step="<?php echo esc_attr( $key ); ?>">
		<span class="sol-a11y__size-label"><?php echo sol_a11y_svg( $icons[ $icon ] ); // phpcs:ignore ?><?php echo esc_html( $label ); ?></span>
		<div class="sol-a11y__stepper">
			<button type="button" data-a11y-by="-1" aria-label="<?php echo esc_attr( 'Decrease ' . strtolower( $label ) ); ?>"><?php echo sol_a11y_svg( $icons['minus'] ); // phpcs:ignore ?></button>
			<output aria-live="polite">Default</output>
			<button type="button" data-a11y-by="1" aria-label="<?php echo esc_attr( 'Increase ' . strtolower( $label ) ); ?>"><?php echo sol_a11y_svg( $icons['plus'] ); // phpcs:ignore ?></button>
		</div>
	</div>
	<?php
}

/** Renders a row of colour swatches for text, titles or background. */
function sol_a11y_swatches( $key, $label ) {
	$colours = array(
		'#0f172a' => 'Black',
		'#ffffff' => 'White',
		'#b45309' => 'Amber',
		'#b91c1c' => 'Red',
		'#15803d' => 'Green',
		'#1d4ed8' => 'Blue',
		'#6d28d9' => 'Purple',
		'#fde047' => 'Yellow',
	);
	$icons = sol_a11y_icons();
	?>
	<div class="sol-a11y__colours" role="group" aria-label="<?php echo esc_attr( $label ); ?>" data-a11y-colour="<?php echo esc_attr( $key ); ?>">
		<span class="sol-a11y__size-label"><?php echo sol_a11y_svg( $icons['palette'] ); // phpcs:ignore ?><?php echo esc_html( $label ); ?></span>
		<div class="sol-a11y__swatches">
			<?php foreach ( $colours as $hex => $name ) : ?>
				<button type="button" class="sol-a11y__swatch" style="--sw:<?php echo esc_attr( $hex ); ?>" data-a11y-hex="<?php echo esc_attr( $hex ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( $name ); ?>" title="<?php echo esc_attr( $name ); ?>"></button>
			<?php endforeach; ?>
			<button type="button" class="sol-a11y__swatch-off" data-a11y-hex="" aria-label="<?php echo esc_attr( 'Cancel ' . strtolower( $label ) ); ?>">Cancel</button>
		</div>
	</div>
	<?php
}

/** One-click profiles: what each one turns on (read by the script from data-a11y-bundle). */
function sol_a11y_profiles() {
	return array(
		'seizure'   => array( 'Seizure Safe', 'Reduces flashing and motion', 'Stops animations and tones down strong colours so flashing or moving content is less likely to cause discomfort.', array( 'still' => 1, 'sat' => 'low' ) ),
		'vision'    => array( 'Low Vision', 'Larger, clearer, higher contrast', 'Increases text size and contrast, switches to an easy-to-read font and shows a clear outline around whatever has keyboard focus.', array( 'font' => 2, 'color' => 'contrast', 'readable' => 1, 'focus' => 1 ) ),
		'adhd'      => array( 'ADHD Friendly', 'Fewer distractions, more focus', 'Stops animations and adds a reading mask that dims everything except the lines you are reading.', array( 'still' => 1, 'mask' => 1 ) ),
		'cognitive' => array( 'Reading & Cognitive', 'Easier reading and navigation', 'Highlights headings and links, uses an easy-to-read font and stops moving content.', array( 'titles' => 1, 'links' => 1, 'readable' => 1, 'still' => 1 ) ),
		'keyboard'  => array( 'Keyboard Navigation', 'Use the website with the keyboard', 'Shows a strong focus outline and adds shortcuts: H headings, L links, B buttons, F form fields, G images, M menus. Hold Shift to go back.', array( 'focus' => 1, 'kbd' => 1 ) ),
		'screen'    => array( 'Screen Reader Support', 'Hear the page read aloud', 'Reads text aloud when you click it or move to it with the keyboard. If you already use a screen reader such as JAWS, NVDA, VoiceOver or TalkBack, the site works with it and you don\'t need this.', array( 'read' => 1, 'focus' => 1 ) ),
		'older'     => array( 'Older Adults', 'Bigger text, calmer pages', 'Increases text size, line spacing and contrast, and stops animations for a calmer, easier read.', array( 'font' => 2, 'line' => 1, 'color' => 'contrast', 'still' => 1 ) ),
	);
}

add_action( 'wp_footer', function () {
	if ( ! sol_a11y_enabled() ) {
		return;
	}
	$icons     = sol_a11y_icons();
	$chat      = function_exists( 'sol_chat_enabled' ) && sol_chat_enabled();
	$statement = get_page_by_path( 'accessibility' );
	$statement = ( $statement && 'publish' === $statement->post_status ) ? get_permalink( $statement ) : '';
	?>
	<div class="sol-a11y" data-sol-a11y>
		<button type="button" class="sol-a11y__launch" data-a11y-toggle aria-expanded="false" aria-controls="sol-a11y-panel" aria-label="Accessibility menu (Alt + A)">
			<span class="sol-a11y__tile"><?php echo sol_a11y_svg( $icons['launch'] ); // phpcs:ignore ?><span class="sol-a11y__dot" aria-hidden="true"></span></span>
			<span class="sol-a11y__tab-label" aria-hidden="true">Accessibility</span>
		</button>

		<section class="sol-a11y__panel" id="sol-a11y-panel" role="dialog" aria-labelledby="sol-a11y-title" hidden>
			<header class="sol-a11y__head">
				<div class="sol-a11y__head-top">
					<span class="sol-a11y__badge"><?php echo sol_a11y_svg( $icons['launch'] ); // phpcs:ignore ?></span>
					<div>
						<p class="sol-a11y__title" id="sol-a11y-title">Accessibility Adjustments</p>
						<p class="sol-a11y__sub">Shortcut: Alt + A</p>
					</div>
					<button type="button" class="sol-a11y__close" data-a11y-close aria-label="Close accessibility menu"><?php echo sol_a11y_svg( $icons['close'] ); // phpcs:ignore ?></button>
				</div>
				<div class="sol-a11y__actions">
					<button type="button" data-a11y-reset><?php echo sol_a11y_svg( $icons['reset'] ); // phpcs:ignore ?>Reset settings</button>
					<?php if ( $statement ) : ?>
						<a href="<?php echo esc_url( $statement ); ?>"><?php echo sol_a11y_svg( $icons['doc'] ); // phpcs:ignore ?>Statement</a>
					<?php endif; ?>
					<button type="button" data-a11y-hide><?php echo sol_a11y_svg( $icons['hide'] ); // phpcs:ignore ?>Hide menu</button>
				</div>
			</header>

			<div class="sol-a11y__body">
				<?php if ( $chat ) : ?>
					<button type="button" class="sol-a11y__ai" data-a11y-chat>
						<span class="sol-a11y__ai-icon"><?php echo sol_a11y_svg( $icons['spark'] ); // phpcs:ignore ?></span>
						<span class="sol-a11y__ai-text"><strong>AI Assistant</strong><span>Ask about our services, or how to use this menu</span></span>
						<span class="sol-a11y__ai-go">Start chat<?php echo sol_a11y_svg( $icons['arrow'] ); // phpcs:ignore ?></span>
					</button>
				<?php endif; ?>

				<h2 class="sol-a11y__h">Choose a profile</h2>
				<div class="sol-a11y__profiles">
					<?php foreach ( sol_a11y_profiles() as $key => $p ) : ?>
						<div class="sol-a11y__profile" data-a11y-profile="<?php echo esc_attr( $key ); ?>" data-a11y-bundle="<?php echo esc_attr( wp_json_encode( $p[3] ) ); ?>">
							<button type="button" class="sol-a11y__switch" role="switch" aria-checked="false" aria-describedby="sol-a11y-p-<?php echo esc_attr( $key ); ?>">
								<span class="sol-a11y__track" aria-hidden="true"><span>OFF</span><span>ON</span></span>
								<span class="sol-a11y__p-icon"><?php echo sol_a11y_svg( $icons[ $key ] ); // phpcs:ignore ?></span>
								<span class="sol-a11y__p-text"><strong><?php echo esc_html( $p[0] ); ?></strong><span><?php echo esc_html( $p[1] ); ?></span></span>
							</button>
							<p class="sol-a11y__p-desc" id="sol-a11y-p-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $p[2] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>

				<h2 class="sol-a11y__h">Content adjustments</h2>
				<?php
				sol_a11y_stepper( 'scale', 'Content scaling', 'scale' );
				sol_a11y_stepper( 'font', 'Font size', 'font' );
				sol_a11y_stepper( 'line', 'Line height', 'line' );
				sol_a11y_stepper( 'space', 'Letter spacing', 'space' );
				?>
				<div class="sol-a11y__grid">
					<?php
					sol_a11y_tile( 'readable', 'Readable Font', 'readable' );
					sol_a11y_tile( 'titles', 'Highlight Titles', 'titles' );
					sol_a11y_tile( 'links', 'Highlight Links', 'links' );
					sol_a11y_tile( 'magnify', 'Text Magnifier', 'magnify' );
					sol_a11y_tile( 'align=left', 'Align Left', 'left' );
					sol_a11y_tile( 'align=center', 'Align Center', 'center' );
					sol_a11y_tile( 'align=right', 'Align Right', 'right' );
					?>
				</div>

				<h2 class="sol-a11y__h">Colour adjustments</h2>
				<div class="sol-a11y__grid">
					<?php
					sol_a11y_tile( 'color=dark', 'Dark Contrast', 'dark' );
					sol_a11y_tile( 'color=light', 'Light Contrast', 'light' );
					sol_a11y_tile( 'color=contrast', 'High Contrast', 'contrast' );
					sol_a11y_tile( 'sat=high', 'High Saturation', 'high' );
					sol_a11y_tile( 'sat=low', 'Low Saturation', 'low' );
					sol_a11y_tile( 'sat=mono', 'Monochrome', 'mono' );
					?>
				</div>
				<?php
				sol_a11y_swatches( 'txc', 'Text colour' );
				sol_a11y_swatches( 'ttc', 'Title colour' );
				sol_a11y_swatches( 'bgc', 'Background colour' );
				?>

				<h2 class="sol-a11y__h">Orientation adjustments</h2>
				<label class="sol-a11y__links-pick">
					<span class="sol-a11y__size-label"><?php echo sol_a11y_svg( $icons['compass'] ); // phpcs:ignore ?>Useful links</span>
					<select data-a11y-links><option value="">Select an option</option></select>
				</label>
				<div class="sol-a11y__grid">
					<?php
					sol_a11y_tile( 'read', 'Read Aloud', 'read' );
					sol_a11y_tile( 'readmode', 'Read Mode', 'readmode' );
					sol_a11y_tile( 'guide', 'Reading Guide', 'guide' );
					sol_a11y_tile( 'mask', 'Reading Mask', 'mask' );
					sol_a11y_tile( 'still', 'Stop Animations', 'still' );
					sol_a11y_tile( 'mute', 'Mute Sounds', 'mute' );
					sol_a11y_tile( 'noimg', 'Hide Images', 'noimg' );
					sol_a11y_tile( 'hover', 'Highlight Hover', 'hover' );
					sol_a11y_tile( 'focus', 'Highlight Focus', 'focus' );
					sol_a11y_tile( 'cursor=black', 'Big Black Cursor', 'cursor' );
					sol_a11y_tile( 'cursor=white', 'Big White Cursor', 'cursor' );
					?>
				</div>
				<p class="sol-a11y__hint" data-a11y-hint="read" hidden>Read Aloud is on. Click any text, or select some, to hear it.</p>
				<p class="sol-a11y__hint" data-a11y-hint="kbd" hidden>Keyboard shortcuts are on: H headings, L links, B buttons, F form fields, G images, M menus. Add Shift to go back.</p>

				<button type="button" class="sol-a11y__reset" data-a11y-reset><?php echo sol_a11y_svg( $icons['reset'] ); // phpcs:ignore ?>Reset all settings</button>
				<p class="sol-a11y__foot">Your choices are saved in this browser only.</p>
			</div>
		</section>

		<div class="sol-a11y__guide" data-a11y-layer="guide" aria-hidden="true" hidden></div>
		<div class="sol-a11y__mask" data-a11y-layer="mask" aria-hidden="true" hidden><span></span><span></span></div>
		<div class="sol-a11y__lens" data-a11y-layer="magnify" aria-hidden="true" hidden></div>
		<section class="sol-a11y__reader" data-a11y-reader role="dialog" aria-modal="true" aria-label="Read mode" hidden>
			<div class="sol-a11y__reader-bar">
				<strong>Read mode</strong>
				<button type="button" data-a11y-reader-close><?php echo sol_a11y_svg( $icons['close'] ); // phpcs:ignore ?>Close read mode</button>
			</div>
			<article class="sol-a11y__reader-body" data-a11y-reader-body></article>
		</section>
	</div>
	<?php
}, 6 );
