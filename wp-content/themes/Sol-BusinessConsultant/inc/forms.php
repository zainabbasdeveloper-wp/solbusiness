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

/**
 * Every service the site offers, as dropdown options. Values match the ones the
 * forms already used, so older enquiries and links keep working.
 */
function sol_service_options() {
	return apply_filters( 'sol_service_options', array(
		'ndis_registration'             => 'NDIS Registration Support',
		'support_coordination_training' => 'NDIS Support Coordination Training',
		'software_automation'           => 'Software & NDIS Automation',
		'company_registration'          => 'Company & Business Registration',
		'bookkeeping_bas'               => 'Bookkeeping & BAS Lodgement',
		'payroll'                       => 'Payroll Services',
		'accountancy'                   => 'Accounting Consultancy',
		'accountancy_partnerships'      => 'Accountancy Partnerships',
		'loan_support'                  => 'Business & Personal Loan Support',
		'website_development'           => 'Website Development',
		'seo'                           => 'Search Engine Optimization (SEO)',
		'google_ads'                    => 'Google Ads Management',
		'social_media'                  => 'Social Media Marketing',
		'admin_support'                 => 'Admin & Back-Office Support',
		'offshore_staff'                => 'Offshore Outsourcing & Staff Solutions',
		'ai_machine_learning'           => 'AI & Machine Learning',
	) );
}

/** The dropdown value for a service page, from its slug ('' if it isn't a service page). */
function sol_service_value_for_slug( $slug ) {
	$map = array(
		'ndis-registration'                   => 'ndis_registration',
		'ndis-support-coordination-training'  => 'support_coordination_training',
		'software-automation'                 => 'software_automation',
		'company-business-registration'       => 'company_registration',
		'bookkeeping-bas-lodgement'           => 'bookkeeping_bas',
		'payroll-services'                    => 'payroll',
		'accountancy'                         => 'accountancy',
		'accountancy-partnerships'            => 'accountancy_partnerships',
		'business-personal-loan-support'      => 'loan_support',
		'website-development'                 => 'website_development',
		'search-engine-optimization'          => 'seo',
		'google-adwords'                      => 'google_ads',
		'social-media-marketing'              => 'social_media',
		'admin-back-office-support'           => 'admin_support',
		'offshore-outsourcing-staff-solutions' => 'offshore_staff',
		'ai-machine-learning'                 => 'ai_machine_learning',
	);
	return isset( $map[ $slug ] ) ? $map[ $slug ] : '';
}

/**
 * Turn a saved "Service of interest" list into the full service list. Only applies when the
 * saved list is a service list (it uses at least one service value), so a custom dropdown is
 * left alone. Saved options that aren't services (like "Other / Multiple Services") go last.
 */
function sol_service_dropdown( $saved ) {
	$services = sol_service_options();
	$extra    = array();
	foreach ( $saved as $o ) {
		if ( ! isset( $services[ $o['value'] ] ) ) {
			$extra[] = $o;
		}
	}
	if ( count( $extra ) === count( $saved ) ) {
		return $saved;
	}
	$out = array();
	foreach ( $services as $value => $label ) {
		$out[] = array( 'value' => $value, 'label' => $label );
	}
	return array_merge( $out, $extra );
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
		'selected'       => '',
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
	if ( in_array( $variant, array( 'contact', 'consultation' ), true ) ) {
		$opts = sol_service_dropdown( $opts );
		// Service page forms: offer every service, with this page's service picked.
		if ( ! $opts && $args['service'] ) {
			foreach ( sol_service_options() as $value => $label ) {
				$opts[] = array( 'value' => $value, 'label' => $label );
			}
			$opts[] = array( 'value' => 'general_enquiry', 'label' => 'Other / Multiple Services' );
		}
	}
	if ( 'training' === $variant && ! $opts ) {
		$opts = array(
			array( 'value' => 'training_individual', 'label' => 'Individual Learner' ),
			array( 'value' => 'training_team', 'label' => 'Team / Organisation (up to 10 staff)' ),
			array( 'value' => 'training_enterprise', 'label' => 'Enterprise / RTO' ),
		);
		$args['select_label'] = 'Training package';
	}

	$field = function ( $name, $label, $type = 'text', $required = false, $auto = '', $extra = '' ) use ( $uid ) {
		$id = $uid . '-' . $name;
		return sprintf(
			'<label class="sol-field" for="%1$s"><span>%2$s%3$s</span><input id="%1$s" type="%4$s" name="%5$s"%6$s%7$s%8$s></label>',
			esc_attr( $id ),
			esc_html( $label ),
			$required ? ' *' : '',
			esc_attr( $type ),
			esc_attr( $name ),
			$required ? ' required' : '',
			$auto ? ' autocomplete="' . esc_attr( $auto ) . '"' : '',
			$extra ? ' ' . $extra : ''
		);
	};

	$sent = isset( $_GET['sol_sent'] ) && $variant === sanitize_key( wp_unslash( $_GET['sol_sent'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<form id="<?php echo esc_attr( $uid ); ?>" class="sol-form sol-form-card sol-form--<?php echo esc_attr( $variant ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sol-form data-success="<?php echo esc_attr( $success ); ?>">
		<?php sol_form_hidden( $variant, $args['service'] ? array( 'service' => $args['service'] ) : array() ); ?>
		<?php if ( 'quiz' === $variant ) : ?><input type="hidden" name="summary" value="" data-sol-quiz-summary><?php endif; ?>
		<?php if ( $sent ) : ?><p class="sol-form__status is-success"><?php echo esc_html( $success ); ?></p><?php endif; ?>
		<?php if ( ! $sent && ! empty( $_GET['sol_error'] ) && 0 === $GLOBALS['sol_form_error_shown']++ ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<p class="sol-form__status is-error"><?php echo esc_html( sol_form_error_text( sanitize_key( wp_unslash( $_GET['sol_error'] ) ) ) ); // phpcs:ignore ?></p>
		<?php endif; ?>

		<div class="sol-form__row">
			<?php echo $field( 'name', 'complaint' === $variant ? 'Your name' : 'Full name', 'text', true, 'name', 'minlength="2" maxlength="100"' ); // phpcs:ignore ?>
			<?php echo $field( 'email', 'Email address', 'email', true, 'email', 'maxlength="150"' ); // phpcs:ignore ?>
		</div>
		<div class="sol-form__row">
			<?php echo $field( 'phone', 'Phone number', 'tel', false, 'tel', 'pattern="[0-9 +\(\)\-]{8,20}" maxlength="20" title="Digits, spaces, + ( ) and - only, at least 8 digits"' ); // phpcs:ignore ?>
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
					<?php foreach ( $opts as $o ) : ?><option value="<?php echo esc_attr( $o['value'] ); ?>"<?php selected( $args['selected'], $o['value'] ); ?>><?php echo esc_html( $o['label'] ); ?></option><?php endforeach; ?>
				</select>
			</label>
		<?php endif; ?>

		<?php if ( 'consultation' === $variant ) : ?>
			<div class="sol-form__row">
				<?php echo $field( 'preferred_date', 'Preferred date', 'date', false, '', 'min="' . esc_attr( wp_date( 'Y-m-d' ) ) . '" max="' . esc_attr( wp_date( 'Y-m-d', time() + YEAR_IN_SECONDS ) ) . '"' ); // phpcs:ignore ?>
				<label class="sol-field" for="<?php echo esc_attr( $uid ); ?>-time"><span>Preferred time (Melbourne)</span>
					<select id="<?php echo esc_attr( $uid ); ?>-time" name="preferred_time">
						<option value="">Any time</option><option>Morning (9am–12pm)</option><option>Afternoon (12pm–3pm)</option><option>Late afternoon (3pm–5pm)</option>
					</select>
				</label>
			</div>
		<?php endif; ?>
		<?php if ( 'training' === $variant ) : ?>
			<?php echo $field( 'learners', 'Number of learners', 'number', false, '', 'min="1" max="1000" step="1" inputmode="numeric"' ); // phpcs:ignore ?>
		<?php endif; ?>

		<?php if ( 'quiz' !== $variant ) : ?>
			<label class="sol-field" for="<?php echo esc_attr( $uid ); ?>-message"><span><?php echo esc_html( 'complaint' === $variant ? 'Describe your complaint or feedback' : ( 'consultation' === $variant ? 'What would you like to discuss?' : 'What do you need help with?' ) ); ?><?php echo in_array( $variant, array( 'contact', 'complaint' ), true ) ? ' *' : ''; ?></span>
				<textarea id="<?php echo esc_attr( $uid ); ?>-message" name="message" rows="5" maxlength="5000" <?php echo in_array( $variant, array( 'contact', 'complaint' ), true ) ? 'required' : ''; ?>></textarea>
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

	$fail = function ( $key, $code = 400 ) use ( $ajax, $back ) {
		if ( $ajax ) {
			wp_send_json_error( array( 'message' => sol_form_error_text( $key ), 'field' => $key ), $code );
		}
		wp_safe_redirect( add_query_arg( 'sol_error', $key, $back ) );
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
		$fail( 'unknown' );
	}

	// Bots: honeypot filled or submitted faster than a person can type. Pretend it worked.
	$ts = isset( $_POST['_sol_ts'] ) ? (int) $_POST['_sol_ts'] : 0;
	if ( ! empty( $_POST['website'] ) || ! $ts || time() - $ts < 2 ) {
		$done();
	}

	// Basic rate limit per IP.
	$ip   = sol_visitor_ip();
	$rkey = 'sol_rl_' . md5( $ip );
	$hits = (int) get_transient( $rkey );
	if ( $hits >= 8 ) {
		$fail( 'rate', 429 );
	}
	set_transient( $rkey, $hits + 1, 10 * MINUTE_IN_SECONDS );

	// Google reCAPTCHA v3 (inc/recaptcha.php), when it's set up under SOL Site → Spam protection.
	if ( function_exists( 'sol_rc_active' ) && sol_rc_active( 'forms' ) ) {
		$rc = sol_rc_verify( 'forms', 'form' );
		if ( ! $rc['ok'] ) {
			$fail( 'missing' === $rc['reason'] ? 'captcha_missing' : 'captcha', 403 );
		}
	}

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
		$fail( 'email' );
	}
	if ( 'newsletter' !== $variant && mb_strlen( $data['name'] ) < 2 ) {
		$fail( 'name' );
	}
	if ( '' !== $data['phone'] && ( ! preg_match( '/^[0-9 +()\-]+$/', $data['phone'] ) || strlen( preg_replace( '/\D/', '', $data['phone'] ) ) < 8 ) ) {
		$fail( 'phone' );
	}
	// Spam usually carries links: none in name or business name, at most 2 in the message.
	$link = '#https?://|www\.|\[url|<a\s#i';
	if ( preg_match( $link, $data['name'] . ' ' . $data['company'] ) ) {
		$fail( 'links_name' );
	}
	if ( preg_match_all( $link, $data['message'] ) > 2 ) {
		$fail( 'links' );
	}
	if ( in_array( $variant, array( 'contact', 'complaint' ), true ) && '' === $data['message'] ) {
		$fail( 'message' );
	}
	if ( 'complaint' === $variant && '' === $data['selection'] ) {
		$fail( 'selection' );
	}
	if ( '' !== $data['preferred_date'] ) {
		$day = DateTime::createFromFormat( '!Y-m-d', $data['preferred_date'], wp_timezone() );
		if ( ! $day || $day->format( 'Y-m-d' ) !== $data['preferred_date'] || $data['preferred_date'] < wp_date( 'Y-m-d' ) ) {
			$fail( 'date' );
		}
	}
	if ( '' !== $data['learners'] && ( ! ctype_digit( $data['learners'] ) || (int) $data['learners'] < 1 || (int) $data['learners'] > 1000 ) ) {
		$fail( 'learners' );
	}
	// Newsletter: one entry per email address.
	if ( 'newsletter' === $variant ) {
		$dupe = get_posts( array( 'post_type' => 'sol_enquiry', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_query' => array( 'relation' => 'AND', array( 'key' => 'email', 'value' => $data['email'] ), array( 'key' => 'form_variant', 'value' => 'newsletter' ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $dupe ) {
			$done();
		}
	}

	sol_save_enquiry( $variant, $data, $back );
	$done();
}

/**
 * Save a validated submission as an Enquiry, email the team and the visitor, and call the webhook.
 * Used by the website forms and by chat bookings (inc/live-chat.php). Returns the enquiry ID.
 */
function sol_save_enquiry( $variant, $data, $back ) {
	$data = wp_parse_args( $data, array(
		'name' => '', 'email' => '', 'phone' => '', 'company' => '', 'service' => '', 'selection' => '', 'reference' => '',
		'preferred_date' => '', 'preferred_time' => '', 'learners' => '', 'message' => '', 'summary' => '', 'marketing_consent' => 'No',
	) );
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
	// Quote the name so commas or quotes in it can't break the address (newlines are already stripped).
	$reply   = trim( str_replace( array( '"', chr( 92 ) ), '', $data['name'] ) );
	$headers = array( 'Reply-To: ' . ( $reply ? '"' . $reply . '" ' : '' ) . '<' . $data['email'] . '>' );
	$mailed  = sol_email_send_template( 'enquiry_notification', $to, array( 'variant' => $variant, 'fields' => $rows ), $headers ); // inc/emails.php
	if ( $post_id && ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_sol_mail', $mailed ? 'sent' : 'failed' );
	}
	if ( $mailed ) {
		update_option( 'sol_mail_last_ok', time(), false );
	}

	// Confirmation to the person who submitted the form.
	$confirmed = sol_send_confirmation( $variant, $data, $to );
	if ( $post_id && ! is_wp_error( $post_id ) && null !== $confirmed ) {
		update_post_meta( $post_id, '_sol_confirm', $confirmed ? 'sent' : 'failed' );
	}

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
	return ( $post_id && ! is_wp_error( $post_id ) ) ? (int) $post_id : 0;
}

/** Visitor message for each failure code. */
function sol_form_error_text( $key ) {
	$msgs = array(
		'email'     => 'Please enter a valid email address.',
		'name'      => 'Please enter your full name.',
		'phone'     => 'Please enter a valid phone number (at least 8 digits).',
		'message'   => 'Please tell us a little about your enquiry.',
		'selection' => 'Please choose a category.',
		'date'      => 'Please choose a preferred date from today onwards.',
		'learners'  => 'Please enter a number of learners between 1 and 1000.',
		'rate'      => 'Too many submissions. Please try again in a few minutes or call us.',
		'links'     => 'Please remove some of the web links from your message (2 at most).',
		'links_name' => 'Please enter your name and business name without web links.',
		'captcha'   => function_exists( 'sol_rc_message' ) ? sol_rc_message( 'low' ) : 'Please refresh the page and try again.',
		'captcha_missing' => function_exists( 'sol_rc_message' ) ? sol_rc_message( 'missing' ) : 'Please refresh the page and try again.',
		'unknown'   => 'Something went wrong. Please refresh the page and try again.',
	);
	return isset( $msgs[ $key ] ) ? $msgs[ $key ] : $msgs['unknown'];
}

/**
 * The visitor's IP address. Behind a reverse proxy (as on this server) REMOTE_ADDR is the proxy,
 * so the forwarded address is used when the request comes from a private or local network.
 */
function sol_visitor_ip() {
	$remote = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$public = filter_var( $remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	if ( $remote && ! $public ) {
		foreach ( array( 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR' ) as $h ) {
			if ( ! empty( $_SERVER[ $h ] ) ) {
				$first = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $h ] ) ) )[0] );
				if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
					return $first;
				}
			}
		}
	}
	return $remote;
}

/**
 * Emails the visitor a short confirmation. It only repeats choices they picked (never their free text),
 * so the form can't be used to send someone else a message. Replies go to the team.
 * Returns null when confirmations are turned off, otherwise whether the email was handed over for sending.
 */
function sol_send_confirmation( $variant, $data, $team ) {
	if ( function_exists( 'get_field' ) && get_field( 'confirm_emails_off', 'option' ) ) {
		return null;
	}
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	if ( ! isset( sol_email_form_copy()[ $variant ] ) ) {
		return null;
	}

	// Greet by first name only when it looks like a name.
	$first = strtok( trim( $data['name'] ), ' ' );
	$first = $first && preg_match( '/^[\p{L}\'.-]{2,30}$/u', $first ) ? $first : '';

	$pretty  = function ( $v ) {
		$services = sol_service_options();
		if ( isset( $services[ $v ] ) ) {
			return $services[ $v ];
		}
		return preg_replace_callback( '/\b(ndis|bas|seo|gst|abn|ai)\b/i', function ( $m ) { return strtoupper( $m[1] ); }, ucfirst( str_replace( array( '_', '-' ), ' ', $v ) ) );
	};
	$details = array();
	if ( '' !== $data['selection'] ) {
		$what = array( 'complaint' => 'Category', 'resource' => 'Resource', 'training' => 'Training option' );
		$details[ isset( $what[ $variant ] ) ? $what[ $variant ] : 'Selection' ] = $pretty( $data['selection'] );
	}
	if ( '' !== $data['preferred_date'] ) {
		$day = DateTime::createFromFormat( '!Y-m-d', $data['preferred_date'], wp_timezone() );
		$details['Preferred date'] = $day ? wp_date( 'l j F Y', $day->getTimestamp() ) : $data['preferred_date'];
	}
	if ( '' !== $data['preferred_time'] ) {
		$details['Preferred time'] = $data['preferred_time'];
	}
	if ( '' !== $data['learners'] ) {
		$details['Learners'] = (string) (int) $data['learners'];
	}

	// The branded templates are in inc/emails.php.
	if ( 'consultation' === $variant ) {
		$key  = 'consultation_confirmation';
		$args = array(
			'when'    => $data['preferred_date'],
			'time'    => $data['preferred_time'],
			'service' => '' !== $data['selection'] ? $pretty( $data['selection'] ) : ( '' !== $data['service'] ? $pretty( $data['service'] ) : 'Consultation' ),
		);
	} elseif ( 'resource' === $variant ) {
		$key  = 'resource_checklist';
		$args = array( 'resource' => $data['selection'] );
	} else {
		$key  = 'form_confirmation';
		$args = array( 'variant' => $variant, 'details' => $details );
	}
	$args['name']  = $first;
	$args['extra'] = trim( (string) sol_opt( 'confirm_note' ) );

	$headers = is_email( $team ) ? array( 'Reply-To: "' . str_replace( '"', '', $site ) . '" <' . $team . '>' ) : array();
	return sol_email_send_template( $key, $data['email'], $args, $headers );
}
