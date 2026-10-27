<?php
/**
 * Built-in forms: contact, consultation, complaint, training, resource, newsletter and quiz leads.
 * Submissions are saved as "Enquiries", emailed to the team and optionally sent to a webhook.
 */

defined( 'ABSPATH' ) || exit;

$GLOBALS['sol_form_error_shown'] = 0;

function sol_form_variants() {
	return array( 'contact', 'consultation', 'complaint', 'training', 'resource', 'newsletter', 'quiz' );
}

/** Hidden fields every SOL form needs. */
function sol_form_hidden( $variant, $extra = array() ) {
	echo '<input type="hidden" name="action" value="sol_form">';
	echo '<input type="hidden" name="form_variant" value="' . esc_attr( $variant ) . '">';
	echo '<input type="hidden" name="_sol_ts" value="' . esc_attr( (string) time() ) . '">';
	echo '<input type="hidden" name="_sol_page" value="' . esc_attr( get_permalink() ? get_permalink() : home_url( '/' ) ) . '">';
	foreach ( $extra as $k => $v ) {
		echo '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">';
	}
	// Honeypot: real visitors never see or fill this.
	echo '<div class="sol-hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>';
}

/** Render a built-in form. */
function sol_render_form( $variant, $args = array() ) {
	$variant = in_array( $variant, sol_form_variants(), true ) ? $variant : 'contact';
	$args    = wp_parse_args( $args, array(
		'select_label'   => 'Service of interest',
		'select_options' => array(),
		'consent_text'   => '',
		'privacy_text'   => '',
		'success'        => '',
		'service'        => '',
		'submit'         => '',
	) );
	$uid     = 'sol-f-' . wp_unique_id();
	$success = $args['success'] ? $args['success'] : sol_opt( 'default_success', 'Thanks. The SOL team will contact you within one business day.' );
	$submit  = $args['submit'] ? $args['submit'] : array(
		'contact'      => 'Send Enquiry',
		'consultation' => 'Request Consultation',
		'complaint'    => 'Submit Complaint',
		'training'     => 'Send Training Enquiry',
		'quiz'         => 'View My Results',
	)[ $variant ] ?? 'Send';

	$opts = array();
	foreach ( sol_rows( $args['select_options'] ) as $o ) {
		$opts[] = array( 'value' => sol_get( $o, 'value', sol_get( $o, 'label' ) ), 'label' => sol_get( $o, 'label', sol_get( $o, 'value' ) ) );
	}
	if ( 'training' === $variant && ! $opts ) {
		$opts = array(
			array( 'value' => 'training_individual', 'label' => 'Individual Learner' ),
			array( 'value' => 'training_team', 'label' => 'Team / Organisation (up to 10 staff)' ),
			array( 'value' => 'training_enterprise', 'label' => 'Enterprise / RTO' ),
		);
		$args['select_label'] = 'Training package';
	}

	$field = function ( $name, $label, $type = 'text', $required = false, $auto = '' ) use ( $uid ) {
		$id = $uid . '-' . $name;
		return sprintf(
			'<label class="sol-field" for="%1$s"><span>%2$s%3$s</span><input id="%1$s" type="%4$s" name="%5$s"%6$s%7$s></label>',
			esc_attr( $id ),
			esc_html( $label ),
			$required ? ' *' : '',
			esc_attr( $type ),
			esc_attr( $name ),
			$required ? ' required' : '',
			$auto ? ' autocomplete="' . esc_attr( $auto ) . '"' : ''
		);
	};

	$sent = isset( $_GET['sol_sent'] ) && $variant === sanitize_key( wp_unslash( $_GET['sol_sent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<form id="<?php echo esc_attr( $uid ); ?>" class="sol-form sol-form-card sol-form--<?php echo esc_attr( $variant ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sol-form data-success="<?php echo esc_attr( $success ); ?>">
		<?php sol_form_hidden( $variant, $args['service'] ? array( 'service' => $args['service'] ) : array() ); ?>
		<?php if ( 'quiz' === $variant ) : ?><input type="hidden" name="summary" value="" data-sol-quiz-summary><?php endif; ?>
		<?php if ( $sent ) : ?><p class="sol-form__status is-success"><?php echo esc_html( $success ); ?></p><?php endif; ?>
		<?php if ( ! $sent && ! empty( $_GET['sol_error'] ) && 0 === $GLOBALS['sol_form_error_shown']++ ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<p class="sol-form__status is-error"><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['sol_error'] ) ) ); // phpcs:ignore ?></p>
		<?php endif; ?>

		<div class="sol-form__row">
			<?php echo $field( 'name', 'complaint' === $variant ? 'Your name' : 'Full name', 'text', true, 'name' ); // phpcs:ignore ?>
			<?php echo $field( 'email', 'Email address', 'email', true, 'email' ); // phpcs:ignore ?>
		</div>
		<div class="sol-form__row">
			<?php echo $field( 'phone', 'Phone number', 'tel', false, 'tel' ); // phpcs:ignore ?>
			<?php
			if ( 'complaint' === $variant ) {
				echo $field( 'reference', 'Order or reference number (optional)' ); // phpcs:ignore
			} elseif ( 'quiz' !== $variant ) {
				echo $field( 'company', 'training' === $variant ? 'Organisation' : 'Business name', 'text', false, 'organization' ); // phpcs:ignore
			}
			?>
		</div>

		<?php if ( $opts && 'quiz' !== $variant ) : ?>
			<label class="sol-field" for="<?php echo esc_attr( $uid ); ?>-selection"><span><?php echo esc_html( $args['select_label'] ); ?><?php echo 'complaint' === $variant ? ' *' : ''; ?></span>
				<select id="<?php echo esc_attr( $uid ); ?>-selection" name="selection" <?php echo 'complaint' === $variant ? 'required' : ''; ?>>
					<option value="">Select…</option>
					<?php foreach ( $opts as $o ) : ?><option value="<?php echo esc_attr( $o['value'] ); ?>"><?php echo esc_html( $o['label'] ); ?></option><?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<?php if ( 'consultation' === $variant ) : ?>
			<div class="sol-form__row">
				<?php echo $field( 'preferred_date', 'Preferred date', 'date' ); // phpcs:ignore ?>
				<label class="sol-field" for="<?php echo esc_attr( $uid ); ?>-time"><span>Preferred time (Melbourne)</span>
					<select id="<?php echo esc_attr( $uid ); ?>-time" name="preferred_time">
						<option value="">Any time</option><option>Morning (9am–12pm)</option><option>Afternoon (12pm–3pm)</option><option>Late afternoon (3pm–5pm)</option>
					</select>
				</label>
			</div>
		<?php endif; ?>
		<?php if ( 'training' === $variant ) : ?>
			<?php echo $field( 'learners', 'Number of learners', 'number' ); // phpcs:ignore ?>
		<?php endif; ?>

		<?php if ( 'quiz' !== $variant ) : ?>
			<label class="sol-field" for="<?php echo esc_attr( $uid ); ?>-message"><span><?php echo esc_html( 'complaint' === $variant ? 'Describe your complaint or feedback' : ( 'consultation' === $variant ? 'What would you like to discuss?' : 'What do you need help with?' ) ); ?><?php echo in_array( $variant, array( 'contact', 'complaint' ), true ) ? ' *' : ''; ?></span>
				<textarea id="<?php echo esc_attr( $uid ); ?>-message" name="message" rows="5" <?php echo in_array( $variant, array( 'contact', 'complaint' ), true ) ? 'required' : ''; ?>></textarea>
			</label>
		<?php endif; ?>

		<?php if ( $args['consent_text'] ) : ?>
			<label class="sol-check"><input type="checkbox" name="marketing_consent" value="1"> <span><?php echo esc_html( $args['consent_text'] ); ?></span></label>
		<?php endif; ?>

		<button type="submit" class="sol-btn sol-btn--primary sol-btn--block"><?php echo esc_html( $submit ); ?> <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></button>
		<?php if ( $args['privacy_text'] ) : ?><p class="sol-form__privacy"><?php echo esc_html( $args['privacy_text'] ); ?></p><?php endif; ?>
		<p class="sol-form__status" role="status" aria-live="polite"></p>
	</form>
	<?php
}

/* ------------------------------------------------------------------ */
/* Handler                                                             */
/* ------------------------------------------------------------------ */

add_action( 'admin_post_nopriv_sol_form', 'sol_handle_form' );
add_action( 'admin_post_sol_form', 'sol_handle_form' );

function sol_handle_form() {
	// phpcs:disable WordPress.Security.NonceVerification -- public lead forms; protected by honeypot, time trap and rate limit.
	$ajax    = ! empty( $_POST['_sol_ajax'] );
	$variant = isset( $_POST['form_variant'] ) ? sanitize_key( wp_unslash( $_POST['form_variant'] ) ) : '';
	$back    = isset( $_POST['_sol_page'] ) ? esc_url_raw( wp_unslash( $_POST['_sol_page'] ) ) : home_url( '/' );
	if ( ! wp_validate_redirect( $back, false ) ) {
		$back = home_url( '/' );
	}

	$fail = function ( $msg, $code = 400 ) use ( $ajax, $back ) {
		if ( $ajax ) {
			wp_send_json_error( array( 'message' => $msg ), $code );
		}
		wp_safe_redirect( add_query_arg( 'sol_error', rawurlencode( $msg ), $back ) );
		exit;
	};
	$done = function () use ( $ajax, $back, $variant ) {
		if ( $ajax ) {
			wp_send_json_success( array( 'message' => sol_opt( 'default_success', 'Thanks. The SOL team will contact you within one business day.' ) ) );
		}
		wp_safe_redirect( add_query_arg( 'sol_sent', $variant, $back ) );
		exit;
	};

	if ( ! in_array( $variant, sol_form_variants(), true ) ) {
		$fail( 'Unknown form.' );
	}

	// Bots: honeypot filled or submitted faster than a person can type. Pretend it worked.
	$ts = isset( $_POST['_sol_ts'] ) ? (int) $_POST['_sol_ts'] : 0;
	if ( ! empty( $_POST['website'] ) || ( $ts && time() - $ts < 2 ) ) {
		$done();
	}

	// Basic rate limit per IP.
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rkey = 'sol_rl_' . md5( $ip );
	$hits = (int) get_transient( $rkey );
	if ( $hits >= 8 ) {
		$fail( 'Too many submissions. Please try again in a few minutes or call us.', 429 );
	}
	set_transient( $rkey, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$text = function ( $k, $max = 200 ) {
		return isset( $_POST[ $k ] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ), 0, $max ) : '';
	};
	$data = array(
		'name'              => $text( 'name' ),
		'email'             => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'phone'             => $text( 'phone', 40 ),
		'company'           => $text( 'company' ),
		'service'           => $text( 'service' ),
		'selection'         => $text( 'selection' ),
		'reference'         => $text( 'reference', 80 ),
		'preferred_date'    => $text( 'preferred_date', 20 ),
		'preferred_time'    => $text( 'preferred_time', 60 ),
		'learners'          => $text( 'learners', 10 ),
		'message'           => isset( $_POST['message'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['message'] ) ), 0, 5000 ) : '',
		'summary'           => isset( $_POST['summary'] ) ? mb_substr( sanitize_textarea_field( wp_unslash( $_POST['summary'] ) ), 0, 5000 ) : '',
		'marketing_consent' => ! empty( $_POST['marketing_consent'] ) ? 'Yes' : 'No',
	);
	// phpcs:enable

	if ( ! is_email( $data['email'] ) ) {
		$fail( 'Please enter a valid email address.' );
	}
	if ( 'newsletter' !== $variant && '' === $data['name'] ) {
		$fail( 'Please enter your name.' );
	}
	if ( in_array( $variant, array( 'contact', 'complaint' ), true ) && '' === $data['message'] ) {
		$fail( 'Please tell us a little about your enquiry.' );
	}

	$labels = array(
		'name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'company' => 'Business / organisation', 'service' => 'Service page',
		'selection' => 'Selection', 'reference' => 'Reference', 'preferred_date' => 'Preferred date', 'preferred_time' => 'Preferred time',
		'learners' => 'Learners', 'message' => 'Message', 'summary' => 'Quiz result', 'marketing_consent' => 'Marketing consent',
	);
	$rows = array( 'Form' => ucfirst( $variant ), 'Page' => $back );
	foreach ( $labels as $k => $label ) {
		if ( '' !== $data[ $k ] && ! ( 'marketing_consent' === $k && 'newsletter' === $variant ) ) {
			$rows[ $label ] = $data[ $k ];
		}
	}

	$post_id = wp_insert_post( array(
		'post_type'   => 'sol_enquiry',
		'post_status' => 'private',
		'post_title'  => ( $data['name'] ? $data['name'] : $data['email'] ) . ' — ' . ucfirst( $variant ),
	) );
	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, 'email', $data['email'] );
		update_post_meta( $post_id, 'form_variant', $variant );
		update_post_meta( $post_id, 'selection', $data['selection'] ? $data['selection'] : $data['service'] );
		update_post_meta( $post_id, '_sol_fields', $rows );
	}

	// Email the team.
	$to = sol_opt( 'notification_email', get_option( 'admin_email' ) );
	$body = '';
	foreach ( $rows as $label => $value ) {
		$body .= $label . ': ' . $value . "\n";
	}
	$body .= "\nView all enquiries: " . admin_url( 'edit.php?post_type=sol_enquiry' );
	$headers = array( 'Reply-To: ' . ( $data['name'] ? $data['name'] . ' ' : '' ) . '<' . $data['email'] . '>' );
	wp_mail( $to, sprintf( '[%s] New %s from %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $variant, $data['name'] ? $data['name'] : $data['email'] ), $body, $headers );

	// Optional webhook (CRM, n8n, Zapier).
	$hook = sol_opt( 'lead_webhook_url' );
	if ( $hook && wp_http_validate_url( $hook ) ) {
		wp_remote_post( $hook, array(
			'timeout'  => 5,
			'blocking' => false,
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'body'     => wp_json_encode( array_merge( $data, array( 'form' => $variant, 'page' => $back, 'source' => 'wordpress', 'submitted_at' => gmdate( 'c' ) ) ) ),
		) );
	}

	do_action( 'sol_form_submitted', $variant, $data, $post_id );
	$done();
}
