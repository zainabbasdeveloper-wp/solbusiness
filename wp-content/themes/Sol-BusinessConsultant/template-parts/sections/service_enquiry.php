<?php
/** Layout: service_enquiry — enquiry form pre-set to the current service. */
defined( 'ABSPATH' ) || exit;

$id = sol_context_id();
if ( SOL_TPL_SERVICE === get_page_template_slug( $id ) && ! get_field( 'show_enquiry', $id ) ) {
	return;
}
$title   = get_the_title( $id );
$heading = get_field( 'enquiry_heading', $id ) ? get_field( 'enquiry_heading', $id ) : 'Start Your ' . $title . ' Enquiry';
$text    = get_field( 'enquiry_text', $id ) ? get_field( 'enquiry_text', $id ) : 'Share your details and the SOL team will confirm the right scope, package, and next step for your situation.';
$anchor  = sanitize_title( sol_get( $s, 'section_id', 'intake-form' ) );
$variant = 'training' === sol_get( $s, 'form_variant' ) ? 'training' : 'contact';
?>
<section id="<?php echo esc_attr( $anchor ); ?>" class="sol-section sol-bg-chalk sol-service-enquiry">
	<div class="sol-container">
		<div class="sol-split sol-split--form">
			<div class="sol-split__content">
				<?php sol_section_head( array( 'eyebrow' => 'Get Started', 'heading' => $heading, 'intro' => $text ), 'h2', 'left' ); ?>
				<?php
				$type = get_field( 'service_type', $id );
				if ( $type ) {
					echo sol_button( array( 'url' => sol_payment_url( $type ), 'title' => 'Make a Service Payment' ), 'outline' ); // phpcs:ignore
				}
				echo sol_contact_details(); // phpcs:ignore
				?>
			</div>
			<div class="sol-split__form">
				<?php
				sol_render_form( $variant, array(
					'service' => $title,
					'success' => 'Thanks. The SOL team will contact you within one business day.',
				) );
				?>
			</div>
		</div>
	</div>
</section>
