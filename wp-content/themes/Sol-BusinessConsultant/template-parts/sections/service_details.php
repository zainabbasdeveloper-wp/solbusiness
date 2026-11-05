<?php
/**
 * Layout: service_details — the body of a service page, in the order a visitor asks:
 * who it's for and what we help with, how it works, why SOL, then questions.
 * Content comes from the current service's fields. It also covers "Why SOL", so a
 * service_overview row on the same page is skipped (see service_overview.php).
 */
defined( 'ABSPATH' ) || exit;

$id = sol_context_id();
if ( ! get_field( 'show_details', $id ) ) {
	return;
}
$GLOBALS['sol_service_details_done'] = true;

$title    = get_the_title( $id );
$who      = sol_list( get_field( 'details_who', $id ) );
$help     = sol_list( get_field( 'features', $id ) );
$help     = $help ? $help : sol_list( get_field( 'details_get', $id ) );
$process  = sol_list( get_field( 'details_process', $id ) );
$timeline = sol_list( get_field( 'details_timeline', $id ) );
$why      = sol_list( get_field( 'why', $id ) );
$faqs     = sol_rows( get_field( 'details_faqs', $id ) );
$heading  = sol_get( $s, 'heading' ) ? $s['heading'] : get_field( 'overview_heading', $id );

if ( $faqs ) {
	sol_faq_schema( array_map( function ( $f ) {
		return array( 'question' => sol_get( $f, 'question' ), 'answer' => wp_strip_all_tags( sol_get( $f, 'answer' ) ) );
	}, $faqs ) );
}
?>
<?php if ( $who || $help ) : ?>
	<section id="service-details" class="sol-section sol-bg-white sol-sd-fit">
		<div class="sol-container">
			<header class="sol-head sol-head--left"><span class="sol-eyebrow">Is this right for you?</span><h2 class="sol-title"><?php echo esc_html( $heading ? $heading : $title ); ?></h2></header>
			<div class="sol-sd-fit__cols">
				<?php if ( $who ) : ?>
					<div id="who-this-is-for">
						<h3 class="sol-sd-fit__label">Who it's for</h3>
						<ul class="sol-lines"><?php foreach ( $who as $w ) : ?><li><?php echo esc_html( $w ); ?></li><?php endforeach; ?></ul>
					</div>
				<?php endif; ?>
				<?php if ( $help ) : ?>
					<div id="what-you-get">
						<h3 class="sol-sd-fit__label">What we help with</h3>
						<?php echo sol_checks( $help, 'sol-checks--lines' ); // phpcs:ignore ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $process ) : ?>
	<section id="service-process" class="sol-section sol-bg-chalk sol-sd-process">
		<div class="sol-container">
			<header class="sol-head sol-head--center"><span class="sol-eyebrow">How it works</span><h2 class="sol-title">From first call to finished work</h2></header>
			<ol class="sol-process sol-process--<?php echo (int) min( 4, count( $process ) ); ?>">
				<?php foreach ( $process as $i => $p ) : ?>
					<li><span class="sol-process__num" aria-hidden="true"><?php echo (int) $i + 1; ?></span><p><?php echo esc_html( $p ); ?></p></li>
				<?php endforeach; ?>
			</ol>
			<?php if ( $timeline ) : ?>
				<div class="sol-sd-timing" id="service-timeline">
					<h3><?php echo sol_icon( 'clock' ); // phpcs:ignore ?> Typical timing</h3>
					<ul><?php foreach ( $timeline as $t ) : ?><li><?php echo esc_html( $t ); ?></li><?php endforeach; ?></ul>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( $why ) : ?>
	<section class="sol-section sol-bg-white sol-sd-why">
		<div class="sol-container">
			<div class="sol-sd-why__grid">
				<?php
				sol_section_head( array(
					'eyebrow' => 'Why SOL',
					'heading' => get_field( 'why_heading', $id ) ? get_field( 'why_heading', $id ) : 'Why work with SOL',
					'intro'   => get_field( 'why_intro', $id ),
				), 'h2', 'left' );
				?>
				<ol class="sol-numlist">
					<?php foreach ( $why as $i => $w ) : ?>
						<li><span aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><p><?php echo esc_html( $w ); ?></p></li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $faqs ) : ?>
	<section id="service-faqs" class="sol-section sol-bg-white sol-faq">
		<div class="sol-container sol-container--mid">
			<header class="sol-head sol-head--center"><span class="sol-eyebrow">Questions</span><h2 class="sol-title"><?php echo esc_html( 'Questions about ' . $title ); ?></h2></header>
			<div class="sol-accordion">
				<?php foreach ( $faqs as $f ) : ?>
					<details class="sol-accordion__item"><summary><?php echo esc_html( sol_get( $f, 'question' ) ); ?><?php echo sol_icon( 'chevron-down', 'sol-accordion__chev' ); // phpcs:ignore ?></summary><div class="sol-accordion__body"><?php echo sol_text( sol_get( $f, 'answer' ) ); // phpcs:ignore ?></div></details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
