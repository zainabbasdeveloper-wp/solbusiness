<?php
/** Layout: contact — details + built-in or shortcode form. */
defined( 'ABSPATH' ) || exit;

$dark = 'ink' === sol_get( $s, 'background' );
$type = sol_get( $s, 'form_type', 'builtin' );

sol_section_open( $s, 'contact' );
?>
<div class="sol-container">
	<div class="sol-split sol-split--form">
		<div class="sol-split__content">
			<?php sol_section_head( $s, 'h2', 'left' ); ?>
			<?php echo sol_buttons( sol_get( $s, 'buttons', array() ) ); // phpcs:ignore ?>
			<?php
			if ( ! empty( $s['show_details'] ) ) {
				echo sol_contact_details( $dark ); // phpcs:ignore
			}
			?>
		</div>
		<div class="sol-split__form">
			<?php
			if ( 'shortcode' === $type && sol_get( $s, 'form_shortcode' ) ) {
				echo '<div class="sol-form-card">' . do_shortcode( wp_kses_post( $s['form_shortcode'] ) ) . '</div>'; // phpcs:ignore
			} elseif ( 'builtin' === $type ) {
				sol_render_form( sol_get( $s, 'form_variant', 'contact' ), array(
					'select_label'   => sol_get( $s, 'select_label', 'Service of interest' ),
					'select_options' => sol_rows( sol_get( $s, 'select_options', array() ) ),
					'consent_text'   => sol_get( $s, 'consent_text' ),
					'privacy_text'   => sol_get( $s, 'privacy_text' ),
					'success'        => sol_get( $s, 'success_message' ),
				) );
			}
			?>
		</div>
	</div>
</div>
</section>
