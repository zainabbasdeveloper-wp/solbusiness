<?php
/**
 * Branded HTML emails: one SOL layout plus the same templates the training app sends.
 * Used by the website forms, WordPress account emails and WooCommerce order emails.
 * Preview each one and send a test in SOL Site → Email templates.
 *
 * Send one from anywhere (a Code Snippet, a plugin):
 *   sol_email_send_template( 'ndis_documents', 'client@example.com', array( 'name' => 'Jane', ... ) );
 *   do_action( 'sol_send_email', 'proposal_sent', 'client@example.com', array( ... ) );
 *
 * A template is a list of blocks, so the HTML and the plain-text copy always say the same thing:
 *   array( 'p', 'Text with **bold** and [a link](https://…)' )
 *   array( 'rows', array( array( 'Label', 'Value' ), array( 'Total', '$10', true ) ) ) // true = amber value
 *   array( 'button', 'Label', 'https://…' )
 *   array( 'list', array( 'one', 'two' ) )
 *   array( 'code', '123456' )
 *   array( 'heading', 'Sub heading' )
 *   array( 'small', 'Muted text' )
 * Values that come from people or other systems go through sol_email_value(), so they can't add markup.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Layout and rendering
 * ---------------------------------------------------------------------- */

/** Brand colours (the same as the training app). */
function sol_email_brand() {
	return apply_filters( 'sol_email_brand', array(
		'ink'     => '#0F172A',
		'harvest' => '#D97706',
		'slate'   => '#64748B',
		'border'  => '#E2E8F0',
		'bg'      => '#F8FAFC',
	) );
}

function sol_email_company() {
	return apply_filters( 'sol_email_company', 'SOL Business Consultant' );
}

/** Logo shown at the top of every email (a JPG, which every email app displays). */
function sol_email_logo_url() {
	return apply_filters( 'sol_email_logo_url', SOL_URI . 'assets/images/sol-logo.jpg' );
}

function sol_email_contact() {
	return array(
		'phone' => (string) sol_opt( 'phone', '+61 460 003 494' ),
		'email' => (string) sol_opt( 'email', 'info@solbusinessconsultant.com.au' ),
	);
}

/**
 * Marks text as a plain value: it is shown exactly as written, never read as **bold** or [link](url).
 * Use it for names, course titles, links and anything else that isn't fixed wording.
 */
function sol_email_value( $value ) {
	return "\x01" . base64_encode( (string) $value ) . "\x02"; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/** Turns marked values back into text. $esc escapes them for HTML. */
function sol_email_unmark( $text, $esc = false ) {
	return preg_replace_callback( '/\x01([A-Za-z0-9+\/=]*)\x02/', function ( $m ) use ( $esc ) {
		$v = (string) base64_decode( $m[1] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		return $esc ? esc_html( $v ) : $v;
	}, (string) $text );
}

/** Wording with **bold** and [links](url), as HTML or as plain text. */
function sol_email_inline( $text, $html = true ) {
	$text = (string) $text;
	if ( ! $html ) {
		$text = preg_replace( '/\*\*(.+?)\*\*/s', '$1', $text );
		$text = preg_replace_callback( '/\[([^\]]+)\]\(([^)\s]+)\)/', function ( $m ) {
			$label = sol_email_unmark( $m[1] );
			$url   = sol_email_unmark( $m[2] );
			return $label === $url ? $url : $label . ' (' . $url . ')';
		}, $text );
		return sol_email_unmark( $text );
	}
	$c   = sol_email_brand();
	$out = esc_html( $text );
	$out = preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $out );
	$out = preg_replace_callback( '/\[([^\]]+)\]\(([^)\s]+)\)/', function ( $m ) use ( $c ) {
		$url = esc_url( sol_email_unmark( html_entity_decode( $m[2], ENT_QUOTES ) ) );
		return '<a href="' . $url . '" target="_blank" style="color:' . $c['harvest'] . ';font-weight:600;text-decoration:underline;word-break:break-word;">' . $m[1] . '</a>';
	}, $out );
	return nl2br( sol_email_unmark( $out, true ), false );
}

/** The blocks of a message as HTML (no outer layout; WooCommerce emails use this). */
function sol_email_blocks_html( $blocks ) {
	$c    = sol_email_brand();
	$html = '';
	foreach ( (array) $blocks as $b ) {
		$type = isset( $b[0] ) ? $b[0] : '';
		switch ( $type ) {
			case 'p':
				$html .= '<p style="margin:0 0 16px;">' . sol_email_inline( $b[1] ) . '</p>';
				break;
			case 'small':
				$html .= '<p style="margin:0 0 12px;color:' . $c['slate'] . ';font-size:13px;line-height:1.6;">' . sol_email_inline( $b[1] ) . '</p>';
				break;
			case 'heading':
				$html .= '<h2 style="margin:24px 0 10px;font-size:16px;line-height:1.4;color:' . $c['ink'] . ';">' . sol_email_inline( $b[1] ) . '</h2>';
				break;
			case 'list':
				$items = '';
				foreach ( (array) $b[1] as $item ) {
					$items .= '<li style="margin:0 0 6px;">' . sol_email_inline( $item ) . '</li>';
				}
				$html .= '<ul style="margin:0 0 18px;padding-left:20px;">' . $items . '</ul>';
				break;
			case 'rows':
				$rows = '';
				foreach ( array_values( (array) $b[1] ) as $i => $r ) {
					$top   = $i ? 'border-top:1px solid ' . $c['border'] . ';' : '';
					$rows .= '<tr>'
						. '<td style="padding:12px 14px;background:' . $c['bg'] . ';color:' . $c['slate'] . ';font-size:13px;width:38%;vertical-align:top;' . $top . '">' . sol_email_inline( $r[0] ) . '</td>'
						. '<td style="padding:12px 14px;font-weight:700;vertical-align:top;color:' . ( ! empty( $r[2] ) ? $c['harvest'] : $c['ink'] ) . ';' . $top . '">' . sol_email_inline( $r[1] ) . '</td>'
						. '</tr>';
				}
				$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 22px;border:1px solid ' . $c['border'] . ';border-radius:12px;border-collapse:separate;overflow:hidden;font-size:14px;">' . $rows . '</table>';
				break;
			case 'button':
				if ( empty( $b[2] ) ) {
					break;
				}
				$html .= '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:6px 0 24px;"><tr><td style="padding:0;background:' . $c['harvest'] . ';border-radius:10px;">'
					. '<a href="' . esc_url( sol_email_unmark( $b[2] ) ) . '" target="_blank" style="display:inline-block;padding:14px 26px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;border-radius:10px;">' . esc_html( sol_email_unmark( $b[1] ) ) . '</a>'
					. '</td></tr></table>';
				break;
			case 'code':
				$code  = preg_replace( '/\s+/', '', sol_email_unmark( $b[1] ) );
				$cells = array();
				foreach ( preg_split( '//u', $code, -1, PREG_SPLIT_NO_EMPTY ) as $d ) {
					$cells[] = '<td style="width:44px;height:54px;background:#ffffff;border:2px solid ' . $c['harvest'] . ';border-radius:10px;text-align:center;vertical-align:middle;font-size:28px;font-weight:700;color:' . $c['harvest'] . ';font-family:\'Courier New\',Courier,monospace;">' . esc_html( $d ) . '</td>';
				}
				$html .= '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 16px;"><tr>' . implode( '<td style="width:8px;"></td>', $cells ) . '</tr></table>'
					. '<p style="margin:0 0 20px;text-align:center;font-size:13px;color:' . $c['slate'] . ';">Can\'t see the boxes? Your code is <strong style="color:' . $c['ink'] . ';font-size:16px;letter-spacing:2px;">' . esc_html( $code ) . '</strong></p>';
				break;
		}
	}
	return $html;
}

/** The blocks of a message as plain text. */
function sol_email_blocks_text( $blocks ) {
	$parts = array();
	foreach ( (array) $blocks as $b ) {
		$type = isset( $b[0] ) ? $b[0] : '';
		if ( in_array( $type, array( 'p', 'small' ), true ) ) {
			$parts[] = sol_email_inline( $b[1], false );
		} elseif ( 'heading' === $type ) {
			$parts[] = strtoupper( sol_email_inline( $b[1], false ) );
		} elseif ( 'list' === $type ) {
			$parts[] = implode( "\n", array_map( function ( $i ) {
				return '- ' . sol_email_inline( $i, false );
			}, (array) $b[1] ) );
		} elseif ( 'rows' === $type ) {
			$parts[] = implode( "\n", array_map( function ( $r ) {
				return sol_email_inline( $r[0], false ) . ': ' . sol_email_inline( $r[1], false );
			}, (array) $b[1] ) );
		} elseif ( 'button' === $type && ! empty( $b[2] ) ) {
			$parts[] = sol_email_unmark( $b[1] ) . ': ' . sol_email_unmark( $b[2] );
		} elseif ( 'code' === $type ) {
			$parts[] = 'Your code: ' . preg_replace( '/\s+/', '', sol_email_unmark( $b[1] ) );
		}
	}
	return implode( "\n\n", array_filter( $parts, 'strlen' ) );
}

/**
 * Builds the finished email from a message array:
 *   subject, title, blocks, preheader (inbox preview line), unsubscribe (URL, marketing emails only),
 *   note (footer line; false hides it).
 * Returns array( subject, html, text ).
 */
function sol_email_render( $msg ) {
	$msg  = wp_parse_args( $msg, array(
		'subject'     => '',
		'title'       => '',
		'preheader'   => '',
		'blocks'      => array(),
		'unsubscribe' => '',
		'note'        => 'This is an automated message from ' . sol_email_company() . '. If you weren’t expecting it, you can ignore this email.',
	) );
	$c       = sol_email_brand();
	$co      = sol_email_company();
	$contact = sol_email_contact();
	$home    = home_url( '/' );
	$title   = sol_email_inline( $msg['title'] );
	$pre     = $msg['preheader'] ? sol_email_inline( $msg['preheader'], false ) : '';
	$font    = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

	$foot  = '<p style="margin:0 0 6px;color:' . $c['ink'] . ';font-size:13px;font-weight:700;">' . esc_html( $co ) . '</p>';
	$foot .= '<p style="margin:0 0 10px;color:' . $c['slate'] . ';font-size:12px;line-height:1.6;">'
		. ( $contact['phone'] ? '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $contact['phone'] ) ) . '" style="color:' . $c['slate'] . ';text-decoration:none;">' . esc_html( $contact['phone'] ) . '</a> &nbsp;·&nbsp; ' : '' )
		. ( is_email( $contact['email'] ) ? '<a href="mailto:' . esc_attr( $contact['email'] ) . '" style="color:' . $c['slate'] . ';text-decoration:none;">' . esc_html( $contact['email'] ) . '</a> &nbsp;·&nbsp; ' : '' )
		. '<a href="' . esc_url( $home ) . '" style="color:' . $c['harvest'] . ';text-decoration:none;">' . esc_html( preg_replace( '#^https?://(www\.)?|/$#', '', $home ) ) . '</a></p>';
	if ( $msg['unsubscribe'] ) {
		$foot .= '<p style="margin:0 0 8px;color:' . $c['slate'] . ';font-size:12px;line-height:1.5;">You’re receiving this because you opted in to ' . esc_html( $co ) . ' updates. <a href="' . esc_url( sol_email_unmark( $msg['unsubscribe'] ) ) . '" target="_blank" style="color:' . $c['harvest'] . ';">Unsubscribe</a> from marketing emails at any time.</p>';
	}
	if ( $msg['note'] ) {
		$foot .= '<p style="margin:0;color:' . $c['slate'] . ';font-size:12px;line-height:1.5;">' . sol_email_inline( $msg['note'] ) . '</p>';
	}

	$html = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light only"><meta name="supported-color-schemes" content="light"><title>' . esc_html( sol_email_inline( $msg['subject'], false ) ) . '</title></head>'
		. '<body style="margin:0;padding:0;background:' . $c['bg'] . ';-webkit-text-size-adjust:100%;">'
		. ( $pre ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . esc_html( $pre ) . str_repeat( '&#8203;&nbsp;', 40 ) . '</div>' : '' )
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . $c['bg'] . ';"><tr><td align="center" style="padding:32px 16px;">'
		. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border:1px solid ' . $c['border'] . ';border-radius:16px;border-collapse:separate;overflow:hidden;font-family:' . $font . ';color:' . $c['ink'] . ';">'
		. '<tr><td style="height:4px;background:' . $c['harvest'] . ';font-size:0;line-height:0;">&nbsp;</td></tr>'
		. '<tr><td align="center" style="padding:26px 32px 22px;border-bottom:1px solid ' . $c['border'] . ';">'
		. '<a href="' . esc_url( $home ) . '" target="_blank" style="text-decoration:none;"><img src="' . esc_url( sol_email_logo_url() ) . '" alt="' . esc_attr( $co ) . '" height="48" style="display:block;height:48px;width:auto;max-width:220px;border:0;outline:none;text-decoration:none;"></a>'
		. ( $title ? '<h1 style="margin:16px 0 0;font-size:22px;line-height:1.3;font-weight:700;color:' . $c['ink'] . ';">' . $title . '</h1>' : '' )
		. '</td></tr>'
		. '<tr><td style="padding:30px 32px 14px;font-size:15px;line-height:1.65;color:' . $c['ink'] . ';">' . sol_email_blocks_html( $msg['blocks'] ) . '</td></tr>'
		. '<tr><td style="padding:20px 32px 24px;border-top:1px solid ' . $c['border'] . ';background:' . $c['bg'] . ';">' . $foot . '</td></tr>'
		. '</table></td></tr></table></body></html>';

	$text = trim( sol_email_inline( $msg['title'], false ) . "\n\n" . sol_email_blocks_text( $msg['blocks'] ) )
		. "\n\n--\n" . $co . "\n" . implode( ' · ', array_filter( array( $contact['phone'], $contact['email'], $home ) ) )
		. ( $msg['unsubscribe'] ? "\n\nYou're receiving this because you opted in to " . $co . ' updates. Unsubscribe: ' . sol_email_unmark( $msg['unsubscribe'] ) : '' )
		. ( $msg['note'] ? "\n\n" . sol_email_inline( $msg['note'], false ) : '' );

	return array(
		'subject' => sol_email_inline( $msg['subject'], false ),
		'html'    => $html,
		'text'    => $text,
	);
}

/* -------------------------------------------------------------------------
 * Sending
 * ---------------------------------------------------------------------- */

/** Plain-text copies waiting to be attached to the next email with that subject. */
function sol_email_alt_text( $subject, $text = null, $take = false ) {
	static $queue = array();
	if ( $take ) {
		$t = isset( $queue[ $subject ] ) ? $queue[ $subject ] : null;
		unset( $queue[ $subject ] );
		return $t;
	}
	if ( null === $text ) {
		unset( $queue[ $subject ] );
	} else {
		$queue[ $subject ] = $text;
	}
	return null;
}

// Attach the plain-text copy (better delivery, and readable in text-only email apps).
add_action( 'phpmailer_init', function ( $phpmailer ) {
	$text = sol_email_alt_text( $phpmailer->Subject, null, true );
	if ( null !== $text && 'text/html' === $phpmailer->ContentType ) {
		$phpmailer->AltBody = $text;
	}
}, 5 );

/**
 * Sends a message (a sol_email_render() result, or a message array to render).
 * $headers and $attachments are as for wp_mail(). Returns whether it was handed over for sending.
 */
function sol_email_send( $to, $msg, $headers = array(), $attachments = array() ) {
	$m = isset( $msg['html'] ) ? $msg : sol_email_render( $msg );
	$headers   = is_array( $headers ) ? $headers : array_filter( explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) ) );
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	sol_email_alt_text( $m['subject'], $m['text'] );
	$ok = wp_mail( $to, $m['subject'], $m['html'], $headers, $attachments );
	sol_email_alt_text( $m['subject'] );
	return $ok;
}

/** A finished email from a template: array( subject, html, text ), or null for an unknown template. */
function sol_email_build( $key, $data = array() ) {
	$all = sol_email_templates();
	if ( ! isset( $all[ $key ] ) ) {
		return null;
	}
	$msg = call_user_func( $all[ $key ]['build'], wp_parse_args( (array) $data, array( 'name' => '' ) ) );
	return sol_email_render( apply_filters( 'sol_email_message', $msg, $key, $data ) );
}

/** Builds and sends a template. Returns false for an unknown template or a failed send. */
function sol_email_send_template( $key, $to, $data = array(), $headers = array(), $attachments = array() ) {
	$m = sol_email_build( $key, $data );
	return $m ? sol_email_send( $to, $m, $headers, $attachments ) : false;
}

add_action( 'sol_send_email', function ( $key, $to, $data = array() ) {
	sol_email_send_template( $key, $to, $data );
}, 10, 3 );

/* -------------------------------------------------------------------------
 * Template helpers
 * ---------------------------------------------------------------------- */

function sol_email_hello( $name ) {
	$name = trim( (string) $name );
	return 'Hi ' . ( '' !== $name ? sol_email_value( $name ) : 'there' ) . ',';
}

function sol_email_money( $amount, $currency = 'AUD' ) {
	return strtoupper( $currency ? $currency : 'AUD' ) . ' $' . number_format( (float) $amount, 2 );
}

/** A date (timestamp or date string) as "3 November 2026", or with $time "Tuesday 3 November 2026, 2:30 pm". */
function sol_email_date( $value, $time = false ) {
	if ( '' === $value || null === $value ) {
		return $time ? 'To be confirmed' : '';
	}
	$ts = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );
	if ( ! $ts ) {
		return (string) $value;
	}
	return wp_date( $time ? 'l j F Y, g:i a' : 'j F Y', $ts );
}

function sol_email_label( $value ) {
	return ucwords( str_replace( array( '_', '-' ), ' ', (string) $value ) );
}

function sol_email_dashboard_url() {
	return sol_app_url( '/student-dashboard' );
}

function sol_email_portal_url() {
	return sol_app_url( '/client-portal/services' );
}

/* -------------------------------------------------------------------------
 * Templates
 * ---------------------------------------------------------------------- */

/**
 * Every template: key => label, group, where it is used, sample data for the preview, and its builder.
 * Add or replace templates with the 'sol_email_templates' filter.
 */
function sol_email_templates() {
	$soon = time() + 3 * DAY_IN_SECONDS;
	$t    = array(
		// Account.
		'otp'                       => array( 'Account', 'Verification code', 'Training app sign-up. Send from code with sol_email_send_template().', array( 'name' => 'Jane Citizen', 'code' => '482913' ) ),
		'reset_password'            => array( 'Account', 'Password reset', 'WordPress “Lost your password?” emails.', array( 'name' => 'Jane Citizen', 'username' => 'jane', 'url' => home_url( '/wp-login.php?action=rp&key=sample' ), 'expires' => '24 hours' ) ),
		'new_account'               => array( 'Account', 'New account', 'New WordPress user accounts (set your password).', array( 'name' => 'Jane Citizen', 'username' => 'jane', 'url' => home_url( '/wp-login.php?action=rp&key=sample' ) ) ),
		// Training.
		'course_welcome'            => array( 'Training', 'Course welcome', 'Training app, when course access is confirmed.', array( 'name' => 'Jane Citizen', 'course' => 'NDIS Support Coordination Training', 'source' => 'payment', 'expiry' => time() + YEAR_IN_SECONDS ) ),
		'course_expiry'             => array( 'Training', 'Course access reminder', 'Training app, before course access ends.', array( 'name' => 'Jane Citizen', 'course' => 'NDIS Support Coordination Training', 'days' => 7, 'expiry' => time() + 7 * DAY_IN_SECONDS ) ),
		'course_completion'         => array( 'Training', 'Course completed', 'Training app, when a course reaches 100%.', array( 'name' => 'Jane Citizen', 'course' => 'NDIS Support Coordination Training', 'completed_topics' => 12, 'total_topics' => 12, 'completed' => time(), 'certificate_url' => sol_app_url( '/certificates/sample' ), 'certificate_number' => 'SOL-CERT-2026-0042', 'verification_code' => 'VX7K-29QF' ) ),
		'waitlist_available'        => array( 'Training', 'Waitlist: course open', 'Training app, when a waitlisted course opens.', array( 'name' => 'Jane Citizen', 'course' => 'Level 3 Behaviour Support', 'unsubscribe' => sol_app_url( '/unsubscribe/sample' ) ) ),
		'waitlist_invite'           => array( 'Training', 'Waitlist: invitation', 'Training app, a personal invitation to enrol.', array( 'name' => 'Jane Citizen', 'course' => 'Level 3 Behaviour Support', 'price' => 625, 'expires' => $soon, 'unsubscribe' => sol_app_url( '/unsubscribe/sample' ) ) ),
		'invoice'                   => array( 'Training', 'Tax invoice', 'Training app, with the invoice PDF attached.', array( 'name' => 'Jane Citizen', 'invoice_number' => 'INV-2026-0187', 'course' => 'NDIS Support Coordination Training', 'subtotal' => 568.18, 'gst' => 56.82, 'total' => 625, 'payment_method' => 'card', 'date' => time(), 'pdf_url' => sol_app_url( '/invoices/sample.pdf' ) ) ),
		// Services.
		'service_payment'           => array( 'Services', 'Service payment confirmed', 'Service payments taken outside WooCommerce. WooCommerce service payments get the same wording inside the order email.', array( 'name' => 'Jane Citizen', 'service' => 'NDIS Registration – Core Package', 'amount' => 1500, 'receipt' => 'SOL-RC-2026-0310' ) ),
		'service_status'            => array( 'Services', 'Service status updated', 'Client portal, when the team changes a project status.', array( 'name' => 'Jane Citizen', 'service' => 'NDIS Registration – Core Package', 'status' => 'documents_in_review' ) ),
		'ndis_documents'            => array( 'Services', 'NDIS documents ready', 'Client portal, when policy documents are sent for review.', array( 'name' => 'Jane Citizen', 'company' => 'Bright Path Care Pty Ltd', 'documents' => array( 'Incident Management Policy', 'Complaints Management Procedure', 'Risk Management Framework' ), 'link' => sol_app_url( '/client-portal/documents' ) ) ),
		'proposal_sent'             => array( 'Services', 'Proposal ready', 'Sales: a proposal is sent.', array( 'name' => 'Jane Citizen', 'number' => 'SOL-P-2026-014', 'total' => 3850, 'url' => sol_app_url( '/proposals/sample' ) ) ),
		'proposal_follow_up'        => array( 'Services', 'Proposal follow-up', 'Sales: a reminder about an open proposal.', array( 'name' => 'Jane Citizen', 'number' => 'SOL-P-2026-014', 'total' => 3850, 'url' => sol_app_url( '/proposals/sample' ) ) ),
		'proposal_accepted'         => array( 'Services', 'Proposal accepted', 'Sales: the client accepted.', array( 'name' => 'Jane Citizen', 'number' => 'SOL-P-2026-014', 'total' => 3850, 'url' => sol_app_url( '/proposals/sample' ) ) ),
		'proposal_rejected'         => array( 'Services', 'Proposal response', 'Sales: the client declined.', array( 'name' => 'Jane Citizen', 'number' => 'SOL-P-2026-014', 'total' => 3850, 'url' => sol_app_url( '/proposals/sample' ) ) ),
		// Website forms.
		'consultation_confirmation' => array( 'Website forms', 'Consultation request received', 'Book a consultation form (sent to the visitor).', array( 'name' => 'Jane', 'when' => wp_date( 'Y-m-d', $soon ), 'time' => 'Morning', 'service' => 'NDIS registration' ) ),
		'consultation_reminder'     => array( 'Website forms', 'Consultation reminder', 'Before a booked consultation. Send from code.', array( 'name' => 'Jane', 'when' => $soon, 'service' => 'NDIS registration' ) ),
		'resource_checklist'        => array( 'Website forms', 'Free checklist', 'Free checklist form (sent to the visitor).', array( 'name' => 'Jane', 'resource' => 'ndis_checklist' ) ),
		'form_confirmation'         => array( 'Website forms', 'Form received', 'Contact, training, feedback, newsletter and readiness-check forms (sent to the visitor).', array( 'name' => 'Jane', 'variant' => 'contact' ) ),
		'enquiry_notification'      => array( 'Website forms', 'New enquiry (team)', 'Every website form (sent to the SOL team).', array( 'variant' => 'contact', 'fields' => array( 'Form' => 'Contact', 'Page' => home_url( '/contact/' ), 'Name' => 'Jane Citizen', 'Email' => 'jane@example.com', 'Phone' => '0400 000 000', 'Message' => "Hi, I'd like help with NDIS registration.\nWhat documents do I need?" ) ) ),
		'live_chat_request'         => array( 'Website forms', 'Live chat request (team)', 'When a visitor asks the chat to talk to a person (sent to the SOL team).', array( 'name' => 'Jane Citizen', 'email' => 'jane@example.com', 'phone' => '0400 000 000', 'message' => 'Can someone check my NDIS registration quote?', 'page' => home_url( '/services/' ), 'offline' => false, 'url' => admin_url( 'admin.php?page=sol-live-chat' ) ) ),
		'test'                      => array( 'Website forms', 'Test email', 'SOL Site → Email test button.', array() ),
	);
	$out = array();
	foreach ( $t as $key => $row ) {
		$out[ $key ] = array(
			'group'  => $row[0],
			'label'  => $row[1],
			'used'   => $row[2],
			'sample' => $row[3],
			'build'  => 'sol_email_tpl_' . $key,
		);
	}
	return apply_filters( 'sol_email_templates', $out );
}

function sol_email_tpl_otp( $d ) {
	$code = preg_replace( '/\s+/', '', (string) sol_get( $d, 'code' ) );
	$mins = (int) sol_get( $d, 'minutes', 10 );
	return array(
		'subject'   => 'Your verification code: ' . sol_email_value( $code ),
		'title'     => 'Verify your email',
		'preheader' => 'Your code expires in ' . $mins . ' minutes.',
		'blocks'    => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', 'Use the code below to finish setting up your account. It expires in **' . $mins . ' minutes**.' ),
			array( 'code', sol_email_value( $code ) ),
			array( 'small', 'Never share this code. The SOL team will never ask you for it.' ),
		),
		'note'      => 'If you didn’t create an account, you can safely ignore this email.',
	);
}

function sol_email_tpl_reset_password( $d ) {
	$url  = (string) sol_get( $d, 'url' );
	$rows = sol_get( $d, 'username' ) ? array( array( 'Username', sol_email_value( $d['username'] ) ) ) : array();
	return array(
		'subject'   => 'Reset your password',
		'title'     => 'Reset your password',
		'preheader' => 'Choose a new password for your SOL account.',
		'blocks'    => array_merge(
			array(
				array( 'p', sol_email_hello( $d['name'] ) ),
				array( 'p', 'We received a request to reset your password. Click the button below to choose a new one. This link expires in **' . sol_email_value( sol_get( $d, 'expires', '30 minutes' ) ) . '**.' ),
			),
			$rows ? array( array( 'rows', $rows ) ) : array(),
			array(
				array( 'button', 'Reset password', sol_email_value( $url ) ),
				array( 'small', 'Or paste this link into your browser: [' . sol_email_value( $url ) . '](' . sol_email_value( $url ) . ')' ),
			)
		),
		'note'      => 'If you didn’t ask to reset your password, you can ignore this email. Your password won’t change.',
	);
}

function sol_email_tpl_new_account( $d ) {
	$url = (string) sol_get( $d, 'url' );
	return array(
		'subject'   => 'Your ' . sol_email_company() . ' account',
		'title'     => 'Welcome to ' . sol_email_company(),
		'preheader' => 'Set your password to finish setting up your account.',
		'blocks'    => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', 'An account has been created for you. Set your password to sign in.' ),
			array( 'rows', array( array( 'Username', sol_email_value( sol_get( $d, 'username' ) ) ) ) ),
			array( 'button', 'Set your password', sol_email_value( $url ) ),
			array( 'small', 'This link can only be used once. If it has expired, use “Lost your password?” on the sign-in page.' ),
		),
	);
}

function sol_email_tpl_course_welcome( $d ) {
	$course  = sol_email_value( sol_get( $d, 'course', 'your course' ) );
	$source  = (string) sol_get( $d, 'source' );
	$expiry  = sol_email_date( sol_get( $d, 'expiry' ) );
	$lead    = array(
		'woocommerce_approval' => 'Your payment has been approved by the SOL team.',
		'payment'              => 'Your payment has been confirmed.',
	);
	$lead    = isset( $lead[ $source ] ) ? $lead[ $source ] : ( 0 === strpos( $source, 'admin' ) ? 'Your course access has been approved by the SOL team.' : 'Your course access has been confirmed.' );
	$rows    = array( array( 'Course', $course ) );
	if ( $expiry ) {
		$rows[] = array( 'Access expires', $expiry );
	}
	return array(
		'subject'   => ( in_array( $source, array( 'payment', 'woocommerce_approval' ), true ) ? 'Payment confirmed! Welcome to ' : 'Welcome to ' ) . $course,
		'title'     => 'Welcome to ' . $course,
		'preheader' => 'Your course is ready in the SOL Training Academy.',
		'blocks'    => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', '**' . $lead . '** You now have access to ' . $course . ' in your SOL Training Academy student dashboard.' ),
			array( 'rows', $rows ),
			array( 'button', 'Open student dashboard', sol_email_dashboard_url() ),
			array( 'heading', 'Getting started' ),
			array( 'list', array(
				'Log in with the email address you registered with.',
				'Forgotten your password? Use the “Forgot password” link on the login page.',
				'Start with the first lesson and work through each topic. Your progress and certificate are saved in your dashboard.',
			) ),
		),
	);
}

function sol_email_tpl_course_expiry( $d ) {
	$course = sol_email_value( sol_get( $d, 'course', 'your course' ) );
	$days   = max( 0, (int) sol_get( $d, 'days', 0 ) );
	$label  = $days . ' day' . ( 1 === $days ? '' : 's' );
	return array(
		'subject'   => 'Course access expires in ' . $label,
		'title'     => 'Course access reminder',
		'preheader' => 'Finish your training before your access ends.',
		'blocks'    => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', 'Your access to **' . $course . '** expires in **' . $label . '**.' ),
			array( 'rows', array(
				array( 'Course', $course ),
				array( 'Access expires', sol_email_date( sol_get( $d, 'expiry' ) ) ),
				array( 'Time remaining', $label, true ),
			) ),
			array( 'p', 'Please complete any remaining lessons before your access ends.' ),
			array( 'button', 'Open student dashboard', sol_email_dashboard_url() ),
			array( 'small', 'Need more time or help with your access? Reply to this email and the SOL team will help.' ),
		),
	);
}

function sol_email_tpl_course_completion( $d ) {
	$title = (string) sol_get( $d, 'course', 'your course' );
	$course = sol_email_value( $title );
	$cert   = (string) sol_get( $d, 'certificate_url' );
	$num    = (string) sol_get( $d, 'certificate_number' );
	$done   = sol_get( $d, 'completed_topics' );
	$total  = sol_get( $d, 'total_topics' );
	$rows   = array(
		array( 'Course', $course ),
		array( 'Completed', sol_email_date( sol_get( $d, 'completed', time() ) ) ),
		array( 'Progress', (int) round( (float) sol_get( $d, 'progress', 100 ) ) . '%', true ),
		array( 'Summary', is_numeric( $done ) && is_numeric( $total ) ? (int) $done . ' of ' . (int) $total . ' topics completed' : 'All required topics completed' ),
	);
	if ( $num ) {
		$rows[] = array( 'Certificate number', sol_email_value( $num ) );
	}
	if ( sol_get( $d, 'verification_code' ) ) {
		$rows[] = array( 'Verification code', sol_email_value( $d['verification_code'] ) );
	}
	$linkedin = add_query_arg( array_filter( array(
		'startTask'        => 'CERTIFICATION_NAME',
		'name'             => rawurlencode( $title ),
		'organizationName' => rawurlencode( sol_email_company() ),
		'certId'           => rawurlencode( $num ),
		'certUrl'          => rawurlencode( $cert ? $cert : sol_email_dashboard_url() ),
	) ), 'https://www.linkedin.com/profile/add' );
	return array(
		'subject'   => 'Congratulations! You completed ' . $course,
		'title'     => 'Congratulations, you completed ' . $course,
		'preheader' => 'Your completion record and certificate are ready.',
		'blocks'    => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', 'Excellent work. You have completed **' . $course . '**, and your completion record is now in your student dashboard.' ),
			array( 'rows', $rows ),
			array( 'button', $cert ? 'Download certificate' : 'Open student dashboard', sol_email_value( $cert ? $cert : sol_email_dashboard_url() ) ),
			array( 'p', 'Share your achievement: [Add it to your LinkedIn profile](' . sol_email_value( $linkedin ) . ')' ),
			array( 'small', 'This certificate records completion of non-accredited professional development, unless the course was delivered by an authorised RTO under its own scope.' ),
		),
	);
}

function sol_email_tpl_waitlist_available( $d ) {
	$course = sol_email_value( sol_get( $d, 'course', 'your waitlisted course' ) );
	return array(
		'subject'     => 'Course available: ' . $course,
		'title'       => 'You’re off the waitlist',
		'preheader'   => 'Enrolments are now open.',
		'unsubscribe' => sol_get( $d, 'unsubscribe' ) ? sol_email_value( $d['unsubscribe'] ) : '',
		'note'        => false,
		'blocks'      => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', 'Good news: **' . $course . '** is now open for enrolment.' ),
			array( 'button', 'Open student dashboard', sol_email_dashboard_url() ),
			array( 'small', 'Places are limited, so log in and enrol while they are available.' ),
		),
	);
}

function sol_email_tpl_waitlist_invite( $d ) {
	$course  = sol_email_value( sol_get( $d, 'course', 'your waitlisted course' ) );
	$expires = sol_email_date( sol_get( $d, 'expires' ) );
	$rows    = array( array( 'Course', $course ) );
	if ( (float) sol_get( $d, 'price', 0 ) > 0 ) {
		$rows[] = array( 'Price', sol_email_money( $d['price'], sol_get( $d, 'currency', 'AUD' ) ) );
	}
	if ( $expires ) {
		$rows[] = array( 'Invitation valid until', $expires, true );
	}
	return array(
		'subject'     => 'You’re invited to enrol: ' . $course,
		'title'       => 'You’re invited to enrol',
		'preheader'   => 'A place has opened for you.',
		'unsubscribe' => sol_get( $d, 'unsubscribe' ) ? sol_email_value( $d['unsubscribe'] ) : '',
		'note'        => false,
		'blocks'      => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', 'A place has opened for you on **' . $course . '**. This invitation is for you only. Open your dashboard to complete your enrolment.' ),
			array( 'rows', $rows ),
			array( 'button', 'Enrol now', sol_email_dashboard_url() ),
			array( 'small', $expires ? 'Please enrol by **' . $expires . '**. After that the invitation lapses and your place may be offered to the next person on the waitlist.' : 'Enrol at a time that suits you from your student dashboard.' ),
		),
	);
}

function sol_email_tpl_invoice( $d ) {
	$cur    = sol_get( $d, 'currency', 'AUD' );
	$course = sol_email_value( sol_get( $d, 'course', 'your course' ) );
	$num    = sol_email_value( sol_get( $d, 'invoice_number' ) );
	$rows   = array(
		array( 'Invoice number', $num ),
		array( 'Date', sol_email_date( sol_get( $d, 'date', time() ) ) ),
		array( 'Course', $course ),
	);
	if ( sol_get( $d, 'payment_method' ) ) {
		$rows[] = array( 'Payment method', sol_email_value( sol_email_label( $d['payment_method'] ) ) );
	}
	$rows[] = array( 'Subtotal (ex GST)', sol_email_money( sol_get( $d, 'subtotal', 0 ), $cur ) );
	$rows[] = array( 'GST', sol_email_money( sol_get( $d, 'gst', 0 ), $cur ) );
	$rows[] = array( 'Total paid', sol_email_money( sol_get( $d, 'total', 0 ), $cur ), true );
	$pdf    = (string) sol_get( $d, 'pdf_url' );
	return array(
		'subject'   => 'Tax invoice ' . $num . ' – ' . sol_email_company(),
		'title'     => 'Your tax invoice',
		'preheader' => 'Thank you for your payment. Your invoice is attached.',
		'blocks'    => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', 'Thank you for your payment. Your enrolment in **' . $course . '** is confirmed, and your tax invoice is attached as a PDF.' ),
			array( 'rows', $rows ),
			array( 'button', 'Download invoice PDF', $pdf ? sol_email_value( $pdf ) : '' ),
			array( 'small', 'You can view this invoice any time in your [student dashboard](' . sol_email_dashboard_url() . '). Please keep it for your records. If anything looks wrong, reply to this email.' ),
		),
	);
}

function sol_email_tpl_service_payment( $d ) {
	$service = sol_email_value( sol_get( $d, 'service', 'SOL business service' ) );
	$rows    = array();
	if ( sol_get( $d, 'receipt' ) ) {
		$rows[] = array( 'Receipt', sol_email_value( $d['receipt'] ) );
	}
	$rows[] = array( 'Service', $service );
	$rows[] = array( 'Amount paid', sol_email_money( sol_get( $d, 'amount', 0 ), sol_get( $d, 'currency', 'AUD' ) ), true );
	return array(
		'subject'   => 'Payment confirmed – ' . $service,
		'title'     => 'Service payment confirmed',
		'preheader' => 'Thank you. We’ve received your payment.',
		'blocks'    => array_merge(
			array(
				array( 'p', sol_email_hello( $d['name'] ) ),
				array( 'p', 'Thank you. We’ve received your payment for **' . $service . '**.' . ( sol_get( $d, 'invoice_attached', true ) ? ' Your tax invoice and receipt are attached.' : '' ) ),
				array( 'rows', $rows ),
			),
			sol_email_service_next_blocks( sol_get( $d, 'email' ) )
		),
	);
}

/** "What happens next" for a service payment (also used inside WooCommerce order emails). */
function sol_email_service_next_blocks( $email = '' ) {
	return array(
		array( 'heading', 'What happens next' ),
		array( 'p', 'The SOL team will contact you within one business day about onboarding and any documents we need from you.' ),
		array( 'p', 'Your client portal shows your package, receipt and onboarding status. Sign in or create your account with ' . ( $email ? '**' . sol_email_value( $email ) . '**' : 'the email address you paid with' ) . '.' ),
		array( 'button', 'Open client portal', sol_email_portal_url() ),
		array( 'small', 'No account yet? [Create your secure account](' . sol_app_url( '/register' ) . ') using the same email address.' ),
	);
}

function sol_email_tpl_service_status( $d ) {
	$service = sol_email_value( sol_get( $d, 'service', 'your SOL service' ) );
	$status  = sol_email_value( sol_email_label( sol_get( $d, 'status', 'updated' ) ) );
	$blocks  = array(
		array( 'p', sol_email_hello( $d['name'] ) ),
		array( 'p', 'The SOL team has updated your service **' . $service . '**.' ),
		array( 'rows', array( array( 'Current status', $status, true ), array( 'Service', $service ) ) ),
	);
	if ( sol_get( $d, 'message' ) ) {
		$blocks[] = array( 'p', sol_email_value( $d['message'] ) );
	}
	$blocks[] = array( 'button', 'Open client portal', sol_email_portal_url() );
	$blocks[] = array( 'small', 'Your client portal shows your service progress, receipt and documents.' );
	return array(
		'subject'   => 'Service status updated – ' . $service,
		'title'     => 'Service status updated',
		'preheader' => 'New status: ' . $status,
		'blocks'    => $blocks,
	);
}

function sol_email_tpl_ndis_documents( $d ) {
	$company = sol_email_value( sol_get( $d, 'company', 'your organisation' ) );
	$docs    = array();
	foreach ( (array) sol_get( $d, 'documents', array() ) as $doc ) {
		$title  = is_array( $doc ) ? sol_get( $doc, 'title', 'NDIS policy document' ) : $doc;
		$docs[] = sol_email_value( $title );
	}
	$count   = $docs ? count( $docs ) : max( 1, (int) sol_get( $d, 'count', 1 ) );
	$contact = sol_email_contact();
	$blocks  = array(
		array( 'p', sol_email_hello( $d['name'] ) ),
		array( 'p', 'The NDIS registration documents for **' . $company . '** are ready for your review and approval.' ),
		array( 'heading', 'Documents included (' . $count . ')' ),
	);
	if ( $docs ) {
		$blocks[] = array( 'list', $docs );
	}
	$blocks[] = array( 'p', 'Please review each document carefully. You can approve them or request changes in your secure client portal.' );
	$blocks[] = array( 'button', 'View documents', sol_get( $d, 'link' ) ? sol_email_value( $d['link'] ) : sol_email_portal_url() );
	$blocks[] = array( 'small', 'Questions about a document? Reply to this email or contact us at [' . sol_email_value( $contact['email'] ) . '](mailto:' . sol_email_value( $contact['email'] ) . ').' );
	return array(
		'subject'   => 'NDIS registration documents ready for review – ' . $company,
		'title'     => 'Your NDIS documents are ready for review',
		'preheader' => $count . ' document' . ( 1 === $count ? '' : 's' ) . ' ready for your approval.',
		'blocks'    => $blocks,
	);
}

/** Shared builder for the four proposal emails. */
function sol_email_proposal( $d, $mode ) {
	$titles = array(
		'sent'      => array( 'Your SOL proposal is ready', 'Your proposal is ready to review using the secure link below.' ),
		'follow_up' => array( 'Following up on your SOL proposal', 'Just checking in on the proposal we sent. You can review it any time using the secure link below, and reply to this email if you have any questions.' ),
		'accepted'  => array( 'Proposal accepted', 'Thank you for accepting the proposal. Our team has been notified and will be in touch with the next steps.' ),
		'rejected'  => array( 'Proposal response received', 'Thank you for reviewing the proposal. Our team has received your response and will follow up if needed.' ),
	);
	$url = (string) sol_get( $d, 'url' );
	if ( ! $url && sol_get( $d, 'token' ) ) {
		$url = sol_app_url( '/proposals/' . rawurlencode( $d['token'] ) );
	}
	return array(
		'subject'   => $titles[ $mode ][0],
		'title'     => $titles[ $mode ][0],
		'preheader' => 'Proposal ' . sol_email_value( sol_get( $d, 'number' ) ),
		'note'      => 'This is a service email about your enquiry, consultation or proposal with ' . sol_email_company() . '.',
		'blocks'    => array(
			array( 'p', sol_email_hello( $d['name'] ) ),
			array( 'p', $titles[ $mode ][1] ),
			array( 'rows', array(
				array( 'Proposal', sol_email_value( sol_get( $d, 'number' ) ) ),
				array( 'Total', sol_email_money( sol_get( $d, 'total', 0 ), sol_get( $d, 'currency', 'AUD' ) ), true ),
			) ),
			array( 'button', in_array( $mode, array( 'sent', 'follow_up' ), true ) ? 'Review proposal' : 'View proposal', $url ? sol_email_value( $url ) : '' ),
		),
	);
}
function sol_email_tpl_proposal_sent( $d ) {
	return sol_email_proposal( $d, 'sent' );
}
function sol_email_tpl_proposal_follow_up( $d ) {
	return sol_email_proposal( $d, 'follow_up' );
}
function sol_email_tpl_proposal_accepted( $d ) {
	return sol_email_proposal( $d, 'accepted' );
}
function sol_email_tpl_proposal_rejected( $d ) {
	return sol_email_proposal( $d, 'rejected' );
}

/** Consultation request (from the website form) or reminder (from code). */
function sol_email_consultation( $d, $reminder ) {
	$when = sol_get( $d, 'when' );
	$time = (string) sol_get( $d, 'time' );
	// A date alone (from the form) is shown as a date, plus the preferred time of day if one was chosen.
	$date_only = is_string( $when ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $when );
	$label     = $when ? ( $date_only ? sol_email_date( strtotime( $when . ' 12:00' ) ) : sol_email_date( $when, true ) ) : '';
	$rows      = array();
	if ( $label ) {
		$rows[] = array( $date_only ? 'Preferred date' : 'Date and time', sol_email_value( $label ) );
	}
	if ( $time ) {
		$rows[] = array( 'Preferred time', sol_email_value( $time ) );
	}
	$rows[] = array( 'Service', sol_email_value( sol_get( $d, 'service' ) ? $d['service'] : 'Consultation' ) );
	return array(
		'subject'   => $reminder ? 'Reminder: your SOL consultation is coming up' : 'Your SOL consultation request has been received',
		'title'     => $reminder ? 'Consultation reminder' : 'Consultation request received',
		'preheader' => $reminder ? 'Your consultation with SOL is coming up.' : 'We’ll contact you within one business day to confirm a time.',
		'note'      => 'This is a service email about your enquiry or consultation with ' . sol_email_company() . '.',
		'blocks'    => array_merge(
			array(
				array( 'p', sol_email_hello( $d['name'] ) ),
				array( 'p', $reminder ? 'This is a friendly reminder about your upcoming consultation with ' . sol_email_company() . '.' : 'Thanks for booking a consultation. We’ve received your request and the SOL team will contact you within one business day to confirm a time.' ),
				array( 'rows', $rows ),
			),
			sol_get( $d, 'extra' ) ? array( array( 'p', sol_email_value( $d['extra'] ) ) ) : array(),
			array( array( 'p', 'If anything changes, just reply to this email and our team will help.' ) )
		),
	);
}
function sol_email_tpl_consultation_confirmation( $d ) {
	return sol_email_consultation( $d, false );
}
function sol_email_tpl_consultation_reminder( $d ) {
	return sol_email_consultation( $d, true );
}

/** Free checklists offered on the site (resource keys from the "Free Tools" section). */
function sol_email_checklists() {
	return apply_filters( 'sol_email_checklists', array(
		'ndis_checklist' => array( 'NDIS Registration Checklist', array(
			'Confirm your registration groups and the services you will deliver.',
			'Prepare your policies, procedures, risk register and key worker documents.',
			'Check audit readiness: complaints, incident management and client intake records.',
		) ),
		'website_launch' => array( 'Website Launch Checklist', array(
			'Write your website goal, core pages, preferred style and must-have features.',
			'Collect your logo, service descriptions, testimonials, and domain and hosting access.',
			'List your enquiry forms, payment needs, client portal requirements and launch date.',
		) ),
		'seo_audit'      => array( 'SEO Readiness Checklist', array(
			'Review your Google Business Profile, website SEO basics and tracking setup.',
			'List your target keywords, service locations and main competitors.',
			'Set monthly reporting goals for traffic, leads and conversions.',
		) ),
		'payroll_bas'    => array( 'Payroll & BAS Prep Checklist', array(
			'Organise employee records, pay cycles and Single Touch Payroll setup.',
			'Check super payments and your accounting software access.',
			'Gather your BAS documents, receipts and GST records for the period.',
		) ),
	) );
}

function sol_email_tpl_resource_checklist( $d ) {
	$all = sol_email_checklists();
	$key = (string) sol_get( $d, 'resource' );
	list( $title, $items ) = isset( $all[ $key ] ) ? $all[ $key ] : array( 'SOL Business Checklist', array(
		'Write down your business goals, budget and preferred timeline.',
		'Gather the documents and access details our team will need.',
		'Book a consultation if you’d like SOL to review your situation.',
	) );
	return array(
		'subject'   => 'Your ' . $title . ' – ' . sol_email_company(),
		'title'     => 'Your ' . $title,
		'preheader' => 'A quick checklist to start with, and your next step.',
		'note'      => 'You’re receiving this because this email address requested a resource on our website. If that wasn’t you, you can ignore this email.',
		'blocks'    => array_merge(
			array(
				array( 'p', sol_email_hello( $d['name'] ) ),
				array( 'p', 'Thanks for requesting this SOL resource. Here’s a quick checklist to help you prepare:' ),
				array( 'list', $items ),
				array( 'p', 'The SOL team will also email you the full checklist and a suggested next step within one business day.' ),
			),
			sol_get( $d, 'extra' ) ? array( array( 'p', sol_email_value( $d['extra'] ) ) ) : array(),
			array(
				array( 'p', 'Want us to review your situation sooner? Book a consultation with our team.' ),
				array( 'button', 'Book a consultation', sol_url( '/book-consultation/' ) ),
				array( 'small', 'Questions? Just reply to this email.' ),
			)
		),
	);
}

/** Wording for each website form confirmation. */
function sol_email_form_copy() {
	return array(
		'contact'      => array( 'We’ve received your message', 'Thanks for getting in touch. We’ve received your message and the SOL team will reply within one business day.' ),
		'consultation' => array( 'Your consultation request has been received', 'Thanks for booking a consultation. We’ve received your request and the SOL team will contact you within one business day to confirm a time.' ),
		'complaint'    => array( 'We’ve received your feedback', 'Thank you for your feedback. It has been recorded, and the SOL team will review it and contact you within one business day.' ),
		'training'     => array( 'We’ve received your training enquiry', 'Thanks for your interest in our training. We’ve received your enquiry and the SOL team will contact you within one business day.' ),
		'resource'     => array( 'Your resource request has been received', 'Thanks for requesting our free resource. The SOL team will email it to you within one business day.' ),
		'newsletter'   => array( 'You’re subscribed to SOL updates', 'Thanks for subscribing. You’ll receive our latest NDIS and business updates by email. To unsubscribe at any time, just reply to this email.' ),
		'quiz'         => array( 'We’ve received your readiness results', 'Thanks for completing the NDIS readiness check. We’ve received your results and the SOL team will contact you within one business day to talk through your next steps.' ),
	);
}

function sol_email_tpl_form_confirmation( $d ) {
	$copy    = sol_email_form_copy();
	$variant = isset( $copy[ sol_get( $d, 'variant' ) ] ) ? $d['variant'] : 'contact';
	$rows    = array();
	foreach ( (array) sol_get( $d, 'details', array() ) as $label => $value ) {
		$rows[] = array( sol_email_value( $label ), sol_email_value( $value ) );
	}
	$blocks = array(
		array( 'p', sol_email_hello( $d['name'] ) ),
		array( 'p', $copy[ $variant ][1] ),
	);
	if ( $rows ) {
		$blocks[] = array( 'heading', 'Your request' );
		$blocks[] = array( 'rows', $rows );
	}
	if ( sol_get( $d, 'extra' ) ) {
		$blocks[] = array( 'p', sol_email_value( $d['extra'] ) );
	}
	if ( 'newsletter' !== $variant ) {
		$blocks[] = array( 'p', 'If you have anything to add, just reply to this email.' );
	}
	$blocks[] = array( 'p', 'Kind regards,' . "\n" . 'The SOL team' );
	return array(
		'subject'   => $copy[ $variant ][0],
		'title'     => $copy[ $variant ][0],
		'preheader' => 'newsletter' === $variant ? 'Welcome to SOL updates.' : 'We’ll be in touch within one business day.',
		'note'      => 'You’re receiving this because this email address was entered on our website. If that wasn’t you, you can ignore this email.',
		'blocks'    => $blocks,
	);
}

function sol_email_tpl_enquiry_notification( $d ) {
	$rows   = array();
	$fields = (array) sol_get( $d, 'fields', array() );
	foreach ( $fields as $label => $value ) {
		$v      = (string) $value;
		$v      = ( 'Email' === $label && is_email( $v ) ) ? '[' . sol_email_value( $v ) . '](mailto:' . sol_email_value( $v ) . ')' : ( wp_http_validate_url( $v ) ? '[' . sol_email_value( $v ) . '](' . sol_email_value( $v ) . ')' : sol_email_value( $v ) );
		$rows[] = array( sol_email_value( $label ), $v );
	}
	$who     = sol_get( $fields, 'Name', sol_get( $fields, 'Email', 'a visitor' ) );
	$variant = sol_email_label( sol_get( $d, 'variant', 'enquiry' ) );
	return array(
		'subject'   => '[' . sol_email_company() . '] New ' . strtolower( $variant ) . ' from ' . sol_email_value( $who ),
		'title'     => 'New ' . strtolower( $variant ) . ' enquiry',
		'preheader' => sol_email_value( $who ) . ' sent the ' . strtolower( $variant ) . ' form.',
		'note'      => 'Reply to this email to answer ' . sol_email_value( $who ) . ' directly. Sent by the website form.',
		'blocks'    => array(
			array( 'p', '**' . sol_email_value( $who ) . '** sent the ' . strtolower( $variant ) . ' form on the website.' ),
			array( 'rows', $rows ),
			array( 'button', 'View all enquiries', admin_url( 'edit.php?post_type=sol_enquiry' ) ),
		),
	);
}

function sol_email_tpl_live_chat_request( $d ) {
	$who     = sol_email_value( sol_get( $d, 'name', 'A visitor' ) );
	$offline = ! empty( $d['offline'] );
	$rows    = array();
	foreach ( array( 'Name' => 'name', 'Email' => 'email', 'Phone' => 'phone', 'Page' => 'page', 'Message' => 'message' ) as $label => $k ) {
		$v = (string) sol_get( $d, $k, '' );
		if ( '' === $v ) {
			continue;
		}
		$v      = 'email' === $k && is_email( $v ) ? '[' . sol_email_value( $v ) . '](mailto:' . sol_email_value( $v ) . ')' : sol_email_value( $v );
		$rows[] = array( $label, $v );
	}
	return array(
		'subject'   => '[' . sol_email_company() . '] ' . ( $offline ? 'Chat message' : 'Live chat request' ) . ' from ' . $who,
		'title'     => $offline ? 'New chat message (team offline)' : 'A visitor wants to talk to the team',
		'preheader' => $offline ? $who . ' left a message in the website chat.' : $who . ' is waiting in the website chat.',
		'note'      => 'Reply to this email to answer ' . $who . ' directly. Sent by the website chat.',
		'blocks'    => array(
			array( 'p', $offline
				? '**' . $who . '** asked to talk to the team while no one was online. Please reply by email or phone.'
				: '**' . $who . '** is waiting in the website chat. Open the inbox to reply in the chat window.' ),
			array( 'rows', $rows ),
			array( 'button', 'Open Live Chat inbox', sol_get( $d, 'url', admin_url( 'admin.php?page=sol-live-chat' ) ) ),
		),
	);
}

function sol_email_tpl_test( $d ) {
	return array(
		'subject' => '[' . sol_email_company() . '] Test email',
		'title'   => 'Test email',
		'blocks'  => array(
			array( 'p', 'This is a test from **SOL Site → Email**.' ),
			array( 'p', 'If you can read this, website emails will reach this inbox.' ),
			array( 'rows', array( array( 'Sent', wp_date( 'j M Y, g:i a' ) ) ) ),
		),
	);
}

/* -------------------------------------------------------------------------
 * WordPress account emails
 * ---------------------------------------------------------------------- */

// "Lost your password?" from wp-login.php.
add_filter( 'retrieve_password_notification_email', function ( $email, $key, $user_login, $user ) {
	if ( ! apply_filters( 'sol_email_brand_wp', true ) || ! $user instanceof WP_User ) {
		return $email;
	}
	$url = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user_login ), 'login' ) . '&wp_lang=' . get_user_locale( $user );
	$exp = (int) apply_filters( 'password_reset_expiration', DAY_IN_SECONDS );
	$m   = sol_email_build( 'reset_password', array(
		'name'     => $user->first_name ? $user->first_name : $user->display_name,
		'username' => $user_login,
		'url'      => $url,
		'expires'  => human_time_diff( 0, $exp ),
	) );
	sol_email_alt_text( $m['subject'], $m['text'] );
	$email['subject'] = $m['subject'];
	$email['message'] = $m['html'];
	$email['headers'] = array_merge( is_array( $email['headers'] ) ? $email['headers'] : array_filter( array( $email['headers'] ) ), array( 'Content-Type: text/html; charset=UTF-8' ) );
	return $email;
}, 20, 4 );

// New user account (when an admin adds a user or someone registers on wp-login.php).
add_filter( 'wp_new_user_notification_email', function ( $email, $user ) {
	if ( ! apply_filters( 'sol_email_brand_wp', true ) || ! preg_match( '#(https?://\S*action=rp\S*)#', (string) $email['message'], $m ) ) {
		return $email;
	}
	$msg = sol_email_build( 'new_account', array(
		'name'     => $user->first_name ? $user->first_name : $user->display_name,
		'username' => $user->user_login,
		'url'      => trim( $m[1], '<>' ),
	) );
	sol_email_alt_text( $msg['subject'], $msg['text'] );
	$email['subject'] = $msg['subject'];
	$email['message'] = $msg['html'];
	$email['headers'] = array_merge( is_array( $email['headers'] ) ? $email['headers'] : array_filter( array( $email['headers'] ) ), array( 'Content-Type: text/html; charset=UTF-8' ) );
	return $email;
}, 20, 2 );

/* -------------------------------------------------------------------------
 * WooCommerce order emails
 * ---------------------------------------------------------------------- */

/**
 * WooCommerce email colours, logo and footer in the SOL style. Settings that were changed in
 * WooCommerce → Settings → Emails are kept; only WooCommerce's own defaults are replaced.
 */
function sol_email_woo_defaults() {
	$c       = sol_email_brand();
	$contact = sol_email_contact();
	return array(
		'woocommerce_email_base_color'            => array( $c['harvest'], array( '', '#720eec', '#7f54b3', '#96588a', '#8a4fff' ) ),
		'woocommerce_email_background_color'      => array( $c['bg'], array( '', '#f7f7f7', '#f8f8f8', '#f5f5f5' ) ),
		'woocommerce_email_body_background_color' => array( '#ffffff', array( '', '#ffffff', '#fdfdfd' ) ),
		'woocommerce_email_text_color'            => array( $c['ink'], array( '', '#3c3c3c', '#1e1e1e', '#000000' ) ),
		'woocommerce_email_footer_text_color'     => array( $c['slate'], array( '', '#3c3c3c', '#787c82', '#1e1e1e' ) ),
		'woocommerce_email_header_image'          => array( sol_email_logo_url(), array( '' ) ),
		'woocommerce_email_footer_text'           => array( '{site_title} · ' . $contact['phone'] . ' · ' . $contact['email'], array( '', '{site_title} &mdash; Built with {WooCommerce}', '{site_title} — Built with {WooCommerce}', '{site_title} &mdash; Powered by {WooCommerce}', '{site_title}<br/>{store_address}' ) ),
	);
}
foreach ( array_keys( sol_email_woo_defaults_keys() ) as $sol_opt_name ) {
	$sol_woo_swap = function ( $value ) use ( $sol_opt_name ) {
		if ( ! apply_filters( 'sol_email_brand_woo', true ) ) {
			return $value;
		}
		$all = sol_email_woo_defaults();
		list( $ours, $defaults ) = $all[ $sol_opt_name ];
		return in_array( sol_email_woo_norm( $value ), array_map( 'sol_email_woo_norm', $defaults ), true ) ? $ours : $value;
	};
	add_filter( 'option_' . $sol_opt_name, $sol_woo_swap );
	add_filter( 'default_option_' . $sol_opt_name, $sol_woo_swap );
}
unset( $sol_opt_name, $sol_woo_swap );

/** A setting value in a comparable form (case, spaces, dashes and <br> written differently still match). */
function sol_email_woo_norm( $v ) {
	$v = strtolower( html_entity_decode( (string) $v, ENT_QUOTES, 'UTF-8' ) );
	$v = str_replace( array( '—', '–', '<br/>', '<br />', '<br>' ), array( '-', '-', '|', '|', '|' ), $v );
	return preg_replace( '/\s+/', '', $v );
}

/** Option names handled above (kept separate so the loop doesn't need sol_opt() before ACF loads). */
function sol_email_woo_defaults_keys() {
	return array_flip( array( 'woocommerce_email_base_color', 'woocommerce_email_background_color', 'woocommerce_email_body_background_color', 'woocommerce_email_text_color', 'woocommerce_email_footer_text_color', 'woocommerce_email_header_image', 'woocommerce_email_footer_text' ) );
}

// Same font and rounded corners as the SOL emails.
add_filter( 'woocommerce_email_styles', function ( $css ) {
	if ( ! apply_filters( 'sol_email_brand_woo', true ) ) {
		return $css;
	}
	$c = sol_email_brand();
	return $css . "\n#template_container{border-radius:16px!important;overflow:hidden;border:1px solid {$c['border']}!important;box-shadow:none!important}"
		. "\n#template_header{border-radius:0!important}"
		. "\n#template_header_image img{max-height:56px;width:auto}"
		. "\nbody,#body_content_inner,#template_container,h1,h2,h3,td,th{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif!important}"
		. "\n.sol-wc-next{margin:0 0 28px;padding:20px 22px;border:1px solid {$c['border']};border-radius:12px;background:{$c['bg']}}"
		. "\n.sol-wc-next h2{margin-top:0!important}";
} );

/** What an order is: 'service' (Service Payment only), 'course', 'mixed' or ''. */
function sol_email_order_kind( $order ) {
	if ( ! $order instanceof WC_Order || ! function_exists( 'sol_is_service_payment' ) ) {
		return '';
	}
	$service = 0;
	$other   = 0;
	foreach ( $order->get_items() as $item ) {
		$p = $item->get_product();
		if ( $p && sol_is_service_payment( $p ) ) {
			$service++;
		} else {
			$other++;
		}
	}
	return $service && ! $other ? 'service' : ( $other && ! $service ? 'course' : ( $service ? 'mixed' : '' ) );
}

// Service payment and course orders: say what happens next, above the order table.
add_action( 'woocommerce_email_before_order_table', function ( $order, $sent_to_admin, $plain_text, $email = null ) {
	if ( $sent_to_admin || ! $email || ! in_array( $email->id, array( 'customer_processing_order', 'customer_completed_order' ), true ) || ! apply_filters( 'sol_email_brand_woo', true ) ) {
		return;
	}
	$kind = sol_email_order_kind( $order );
	if ( ! $kind ) {
		return;
	}
	$blocks = array();
	if ( in_array( $kind, array( 'service', 'mixed' ), true ) ) {
		$blocks = sol_email_service_next_blocks( $order->get_billing_email() );
	}
	if ( in_array( $kind, array( 'course', 'mixed' ), true ) ) {
		$blocks = array_merge( $blocks, array(
			array( 'heading', 'Your course access' ),
			array( 'p', 'The SOL team will activate your enrolment in the SOL Training Academy and send you a welcome email when your course is ready.' ),
			array( 'p', 'Log in with **' . sol_email_value( $order->get_billing_email() ) . '**, the email address on this order.' ),
			array( 'button', 'Open student dashboard', sol_email_dashboard_url() ),
		) );
	}
	if ( $plain_text ) {
		echo "\n" . sol_email_blocks_text( $blocks ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- plain-text email
	} else {
		echo '<div class="sol-wc-next">' . sol_email_blocks_html( $blocks ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by the block renderer
	}
}, 5, 4 );

// Service payment orders: clearer subjects and headings than "Your order has been received".
foreach ( array(
	'customer_processing_order' => array( 'Payment received – SOL service payment #%s', 'Service payment received' ),
	'customer_completed_order'  => array( 'Service payment confirmed – order #%s', 'Service payment confirmed' ),
	'customer_on_hold_order'    => array( 'Service payment order #%s received', 'Thanks for your service payment order' ),
	'new_order'                 => array( '[SOL] New service payment #%s', 'New service payment' ),
) as $sol_email_id => $sol_words ) {
	add_filter( 'woocommerce_email_subject_' . $sol_email_id, function ( $subject, $order ) use ( $sol_words, $sol_email_id ) {
		if ( 'service' !== sol_email_order_kind( $order ) || ! apply_filters( 'sol_email_brand_woo', true ) ) {
			return $subject;
		}
		$s = sprintf( $sol_words[0], $order->get_order_number() );
		if ( 'new_order' === $sol_email_id ) {
			$s .= ' – ' . wp_strip_all_tags( html_entity_decode( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ) ) . ' from ' . $order->get_formatted_billing_full_name();
		}
		return $s;
	}, 20, 2 );
	add_filter( 'woocommerce_email_heading_' . $sol_email_id, function ( $heading, $order ) use ( $sol_words ) {
		return 'service' === sol_email_order_kind( $order ) && apply_filters( 'sol_email_brand_woo', true ) ? $sol_words[1] : $heading;
	}, 20, 2 );
}
unset( $sol_email_id, $sol_words );

/* -------------------------------------------------------------------------
 * Admin: preview and test
 * ---------------------------------------------------------------------- */

define( 'SOL_EMAIL_TPL_PAGE', 'sol-email-templates' );

add_action( 'admin_menu', function () {
	add_submenu_page( SOL_OPTIONS_SLUG, 'Email templates', 'Email templates', 'manage_options', SOL_EMAIL_TPL_PAGE, 'sol_email_admin_page', 3 );
}, 100 );

// The preview itself (shown in a frame on the page).
add_action( 'admin_post_sol_email_preview', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_email_preview' );
	$all = sol_email_templates();
	$key = isset( $_GET['tpl'] ) ? sanitize_key( wp_unslash( $_GET['tpl'] ) ) : '';
	if ( ! isset( $all[ $key ] ) ) {
		wp_die( 'Unknown template.' );
	}
	$m = sol_email_build( $key, $all[ $key ]['sample'] );
	nocache_headers();
	if ( ! empty( $_GET['text'] ) ) {
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo $m['text']; // phpcs:ignore WordPress.Security.EscapeOutput -- plain text
	} else {
		header( 'Content-Type: text/html; charset=utf-8' );
		echo $m['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped by sol_email_render()
	}
	exit;
} );

add_action( 'admin_post_sol_email_test_tpl', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_email_test_tpl' );
	$all = sol_email_templates();
	$key = isset( $_POST['tpl'] ) ? sanitize_key( wp_unslash( $_POST['tpl'] ) ) : '';
	$to  = wp_get_current_user()->user_email;
	$ok  = isset( $all[ $key ] ) && sol_email_send_template( $key, $to, $all[ $key ]['sample'] );
	wp_safe_redirect( admin_url( 'admin.php?page=' . SOL_EMAIL_TPL_PAGE . '&tpl=' . $key . '&sol_test=' . ( $ok ? 'sent' : 'failed' ) ) );
	exit;
} );

function sol_email_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$all  = sol_email_templates();
	$key  = isset( $_GET['tpl'] ) ? sanitize_key( wp_unslash( $_GET['tpl'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$key  = isset( $all[ $key ] ) ? $key : 'form_confirmation';
	$test = isset( $_GET['sol_test'] ) ? sanitize_key( wp_unslash( $_GET['sol_test'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$src  = wp_nonce_url( admin_url( 'admin-post.php?action=sol_email_preview&tpl=' . $key ), 'sol_email_preview' );
	$tpl  = $all[ $key ];
	$m    = sol_email_build( $key, $tpl['sample'] );
	$groups = array();
	foreach ( $all as $k => $row ) {
		$groups[ $row['group'] ][ $k ] = $row;
	}
	?>
	<div class="wrap">
		<h1>Email templates</h1>
		<p style="max-width:860px">Every email the website sends uses this SOL design: website form replies, team enquiry alerts, WordPress password emails, and the extra “what happens next” section in WooCommerce order emails. The training app’s emails are here too, so any of them can be sent from WordPress. The preview uses sample details.</p>
		<?php if ( 'sent' === $test ) : ?>
			<div class="notice notice-success"><p><strong>Test sent to <?php echo esc_html( wp_get_current_user()->user_email ); ?>.</strong> Check that inbox, including spam.</p></div>
		<?php elseif ( 'failed' === $test ) : ?>
			<div class="notice notice-error"><p><strong>The test could not be sent.</strong> Check <a href="<?php echo esc_url( admin_url( 'admin.php?page=sol-email' ) ); ?>">SOL Site → Email</a>.</p></div>
		<?php endif; ?>
		<div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;margin-top:16px">
			<div style="flex:0 0 260px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:8px 0">
				<?php foreach ( $groups as $group => $rows ) : ?>
					<p style="margin:10px 16px 4px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#646970"><?php echo esc_html( $group ); ?></p>
					<?php foreach ( $rows as $k => $row ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . SOL_EMAIL_TPL_PAGE . '&tpl=' . $k ) ); ?>" style="display:block;padding:6px 16px;text-decoration:none;<?php echo $k === $key ? 'background:#fff7ed;color:#b45309;font-weight:600;border-left:3px solid #d97706' : 'color:#1d2327;border-left:3px solid transparent'; ?>"><?php echo esc_html( $row['label'] ); ?></a>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</div>
			<div style="flex:1 1 640px;min-width:320px">
				<table class="widefat striped" style="margin-bottom:12px">
					<tbody>
						<tr><td style="width:120px"><strong>Subject</strong></td><td><?php echo esc_html( $m['subject'] ); ?></td></tr>
						<tr><td><strong>Used for</strong></td><td><?php echo esc_html( $tpl['used'] ); ?></td></tr>
						<tr><td><strong>Template key</strong></td><td><code><?php echo esc_html( $key ); ?></code></td></tr>
					</tbody>
				</table>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0 0 12px">
					<input type="hidden" name="action" value="sol_email_test_tpl">
					<input type="hidden" name="tpl" value="<?php echo esc_attr( $key ); ?>">
					<?php wp_nonce_field( 'sol_email_test_tpl' ); ?>
					<?php submit_button( 'Send this test to ' . wp_get_current_user()->user_email, 'secondary', 'submit', false ); ?>
				</form>
				<iframe title="Email preview" src="<?php echo esc_url( $src ); ?>" style="width:100%;height:900px;border:1px solid #dcdcde;border-radius:8px;background:#f8fafc"></iframe>
				<details style="margin-top:12px"><summary>Plain-text version</summary><pre style="white-space:pre-wrap;background:#fff;border:1px solid #dcdcde;padding:12px;border-radius:6px"><?php echo esc_html( $m['text'] ); ?></pre></details>
				<details style="margin-top:8px"><summary>Send it from code (Code Snippets)</summary>
					<pre style="white-space:pre-wrap;background:#fff;border:1px solid #dcdcde;padding:12px;border-radius:6px"><?php echo esc_html( "sol_email_send_template( '" . $key . "', \$to_email, " . ( var_export( array_map( function ( $v ) { return is_int( $v ) && $v > 1000000000 ? '(timestamp)' : $v; }, $tpl['sample'] ), true ) ) . ' );' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions ?></pre>
				</details>
			</div>
		</div>
	</div>
	<?php
}
