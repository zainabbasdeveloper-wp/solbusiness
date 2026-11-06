<?php
/** Layout: service_details — who / what / process / timeline / FAQs from the current service. */
defined( 'ABSPATH' ) || exit;

$id = sol_context_id();
if ( ! get_field( 'show_details', $id ) ) {
	return;
}
$panels = array(
	'who-this-is-for' => array( 'Who this is for', 'users-round', sol_list( get_field( 'details_who', $id ) ) ),
	'what-you-get'    => array( 'What you get', 'package-check', sol_list( get_field( 'details_get', $id ) ) ),
	'service-process' => array( 'Process', 'clipboard-list', sol_list( get_field( 'details_process', $id ) ) ),
	'service-timeline' => array( 'Timeline', 'calendar-clock', sol_list( get_field( 'details_timeline', $id ) ) ),
);
$faqs  = sol_rows( get_field( 'details_faqs', $id ) );
$title = sol_get( $s, 'heading', get_the_title( $id ) . ': scope, process, timeline and FAQs' );
?>
<section id="service-details" class="sol-section sol-bg-white sol-service-details">
	<div class="sol-container">
		<nav class="sol-subnav" aria-label="Service details">
			<?php foreach ( $panels as $anchor => $p ) : ?>
				<?php if ( $p[2] ) : ?><a href="#<?php echo esc_attr( $anchor ); ?>"><?php echo esc_html( $p[0] ); ?></a><?php endif; ?>
			<?php endforeach; ?>
			<?php if ( $faqs ) : ?><a href="#service-faqs">FAQs</a><?php endif; ?>
		</nav>
		<?php sol_section_head( array( 'eyebrow' => 'Service Details', 'heading' => $title, 'intro' => 'A clear breakdown of who this service is for, what you receive, how delivery works, and what to expect before booking or paying.' ) ); ?>
		<div class="sol-grid sol-grid--2">
			<?php foreach ( $panels as $anchor => $p ) : ?>
				<?php if ( ! $p[2] ) { continue; } ?>
				<div class="sol-panel" id="<?php echo esc_attr( $anchor ); ?>">
					<h3><span class="sol-card__icon"><?php echo sol_icon( $p[1] ); // phpcs:ignore ?></span><?php echo esc_html( $p[0] ); ?></h3>
					<?php echo sol_checks( $p[2], 'sol-checks--sm' ); // phpcs:ignore ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="sol-grid sol-grid--2 sol-service-details__pay">
			<div class="sol-panel sol-panel--dark">
				<h3>Payment clarity</h3>
				<p>Only make a service payment after SOL confirms your scope, service type, agreed amount, and next step. If you are unsure, book a consultation first.</p>
				<div class="sol-buttons">
					<?php
					$type = get_field( 'service_type', $id );
					echo sol_button( array( 'url' => sol_payment_url( $type ), 'title' => 'Make Service Payment' ), 'primary' ); // phpcs:ignore
					echo sol_button( array( 'url' => '/book-consultation', 'title' => 'Book Consultation First' ), 'light' ); // phpcs:ignore
					?>
				</div>
			</div>
			<div class="sol-panel">
				<h3>After payment tracking</h3>
				<p>After payment, clients can use the client portal to track service records, requests, documents, tickets, admin updates, and deliverables.</p>
				<div class="sol-buttons">
					<?php
					echo sol_button( array( 'url' => '/after-payment', 'title' => 'View After Payment Guide' ), 'outline' ); // phpcs:ignore
					echo sol_button( array( 'url' => apply_filters( 'sol_client_portal_url', sol_app_url( '/client-portal' ) ), 'title' => 'Open Client Portal' ), 'outline' ); // phpcs:ignore
					?>
				</div>
			</div>
		</div>
		<?php if ( $faqs ) : ?>
			<div id="service-faqs" class="sol-service-details__faqs">
				<h3>FAQs</h3>
				<div class="sol-accordion">
					<?php foreach ( $faqs as $f ) : ?>
						<details class="sol-accordion__item"><summary><?php echo esc_html( sol_get( $f, 'question' ) ); ?><?php echo sol_icon( 'chevron-down', 'sol-accordion__chev' ); // phpcs:ignore ?></summary><div class="sol-accordion__body"><?php echo sol_text( sol_get( $f, 'answer' ) ); // phpcs:ignore ?></div></details>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="sol-inline-cta">
			<div><h3>Ready to confirm the right package?</h3><p>Book a consultation before payment if you are unsure about scope, timeline, or inclusions.</p></div>
			<?php echo sol_button( array( 'url' => '/book-consultation', 'title' => 'Book Consultation' ), 'primary' ); // phpcs:ignore ?>
		</div>
	</div>
</section>
