<?php
/**
 * Forwards website enquiries to the SOL Marketing CRM through the n8n "Lead Intake" webhook.
 *
 * OFF unless switched on in wp-config.php (the key never lives in the database, the admin screens or the page):
 *
 *   define( 'SOL_CRM_MODE', 'off' );          // 'off' (default), 'dry_run' (build and store the payload, send nothing) or 'live'
 *   define( 'SOL_CRM_WEBHOOK_URL', 'https://…' ); // full production webhook URL from n8n
 *   define( 'SOL_CRM_WEBHOOK_KEY', '…' );     // shared secret, sent as the x-sol-webhook-key header
 *   define( 'SOL_CRM_FORMS', 'contact,consultation,training' ); // optional: which forms count as leads
 *
 * How it works:
 * - Runs on the existing sol_form_submitted action, after the enquiry is saved and the team email has gone,
 *   so the form, its validation, storage and emails are unchanged.
 * - Each enquiry gets a stable submission ID once (_sol_submission_id). The payload is built once and stored,
 *   so every retry and every "Resend to CRM" sends exactly the same submission.
 * - Sending happens in the background (Action Scheduler, or WP-Cron as a fallback); the visitor never waits on n8n.
 * - Timeouts, 5xx and 429 are retried after 1 min, 5 min, 30 min, 2 h and 12 h; other 4xx are marked "rejected".
 * - Status is shown in SOL Site → Enquiries (CRM column) and on each enquiry.
 * - While this is dry_run or live, the older "Forward form submissions to (webhook)" setting is skipped
 *   (inc/forms.php), so there is only one path into the CRM.
 */

defined( 'ABSPATH' ) || exit;

const SOL_CRM_HOOK    = 'sol_crm_send';
const SOL_CRM_RETRIES = array( 60, 300, 1800, 7200, 43200 );

/** 'off', 'dry_run' or 'live'. */
function sol_crm_mode() {
	$m = defined( 'SOL_CRM_MODE' ) ? strtolower( trim( (string) SOL_CRM_MODE ) ) : 'off';
	return in_array( $m, array( 'dry_run', 'live' ), true ) ? $m : 'off';
}

function sol_crm_url() {
	$u = defined( 'SOL_CRM_WEBHOOK_URL' ) ? trim( (string) SOL_CRM_WEBHOOK_URL ) : '';
	return $u && wp_http_validate_url( $u ) && 0 === strpos( $u, 'https://' ) ? $u : '';
}

function sol_crm_key() {
	return defined( 'SOL_CRM_WEBHOOK_KEY' ) ? trim( (string) SOL_CRM_WEBHOOK_KEY ) : '';
}

/** Forms whose submissions are leads. Newsletter sign-ups and complaints are not sent unless listed here. */
function sol_crm_forms() {
	$list = defined( 'SOL_CRM_FORMS' ) ? (string) SOL_CRM_FORMS : 'contact,consultation,training';
	return array_values( array_filter( array_map( 'sanitize_key', explode( ',', $list ) ) ) );
}

/** True when the integration is on (dry run or live) — used by inc/forms.php to skip the older webhook. */
function sol_crm_enabled() {
	return 'off' !== sol_crm_mode();
}

/** The enquiry's stable submission ID, created the first time it is asked for. */
function sol_crm_submission_id( $post_id ) {
	$id = (string) get_post_meta( $post_id, '_sol_submission_id', true );
	if ( '' === $id ) {
		$id = 'sol_wp_' . wp_generate_uuid4();
		add_post_meta( $post_id, '_sol_submission_id', $id, true );
		$id = (string) get_post_meta( $post_id, '_sol_submission_id', true ); // the stored value wins a race
	}
	return $id;
}

/** A service ID from the submitted data: the dropdown value, the chat booking's ID, or a service page's title. */
function sol_crm_service_id( $data ) {
	$services = sol_service_options();
	foreach ( array( 'service_id', 'selection', 'service' ) as $k ) {
		$v = isset( $data[ $k ] ) ? trim( (string) $data[ $k ] ) : '';
		if ( '' === $v ) {
			continue;
		}
		if ( isset( $services[ $v ] ) || 'general_enquiry' === $v ) {
			return $v;
		}
		$by_label = array_search( strtolower( $v ), array_map( 'strtolower', $services ), true );
		if ( false !== $by_label ) {
			return $by_label;
		}
	}
	return 'general_enquiry';
}

/** The lead.submitted payload agreed with the n8n workflow. */
function sol_crm_payload( $variant, $data, $post_id ) {
	$notice = ! empty( $data['privacy_notice_shown'] );
	return array(
		'event'         => 'lead.submitted',
		'event_version' => '1.0',
		'submission_id' => sol_crm_submission_id( $post_id ),
		'source'        => 'sol_website',
		'lead'          => array(
			'full_name'  => (string) $data['name'],
			'email'      => (string) $data['email'],
			'phone'      => (string) $data['phone'],
			'company'    => (string) $data['company'],
			'service_id' => sol_crm_service_id( $data ),
			'message'    => (string) $data['message'],
		),
		'consent'       => array(
			// Contact: the person sent an enquiry asking to be contacted, with the privacy notice shown.
			// Without the notice this is false, so n8n sends no automated email (the team still replies personally).
			'contact'              => $notice,
			'marketing'            => 'Yes' === ( isset( $data['marketing_consent'] ) ? $data['marketing_consent'] : 'No' ),
			'contact_basis'        => $notice ? 'enquiry_submission' : 'none',
			'privacy_notice_shown' => $notice,
		),
		'meta'          => array(
			'form'           => $variant,
			'page_url'       => (string) get_post_meta( $post_id, '_sol_page', true ),
			'wp_enquiry_id'  => (int) $post_id,
			'submitted_at'   => get_post_time( 'c', true, $post_id ),
			'preferred_date' => isset( $data['preferred_date'] ) ? (string) $data['preferred_date'] : '',
			'preferred_time' => isset( $data['preferred_time'] ) ? (string) $data['preferred_time'] : '',
		),
	);
}

function sol_crm_set( $post_id, $status, $code = '', $error = '' ) {
	update_post_meta( $post_id, '_sol_crm', $status );
	update_post_meta( $post_id, '_sol_crm_code', (string) $code );
	update_post_meta( $post_id, '_sol_crm_error', mb_substr( (string) $error, 0, 300 ) );
	update_post_meta( $post_id, '_sol_crm_time', time() );
}

/** Queue a send attempt in the background. */
function sol_crm_queue( $post_id, $attempt = 1, $delay = 0 ) {
	$args = array( (int) $post_id, (int) $attempt );
	if ( function_exists( 'as_schedule_single_action' ) ) {
		as_schedule_single_action( time() + $delay, SOL_CRM_HOOK, $args, 'sol-crm' );
	} else {
		wp_schedule_single_event( time() + $delay, SOL_CRM_HOOK, $args );
	}
}

// New enquiry: build and store the payload once, then queue it (or just store it in dry-run mode).
add_action( 'sol_form_submitted', function ( $variant, $data, $post_id ) {
	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return;
	}
	sol_crm_submission_id( $post_id ); // every enquiry gets its stable ID, even while the integration is off
	$mode = sol_crm_mode();
	if ( 'off' === $mode ) {
		return;
	}
	if ( ! in_array( $variant, sol_crm_forms(), true ) ) {
		sol_crm_set( $post_id, 'skipped', '', 'Form "' . $variant . '" is not sent to the CRM.' );
		return;
	}
	update_post_meta( $post_id, '_sol_crm_payload', wp_slash( wp_json_encode( sol_crm_payload( $variant, $data, $post_id ) ) ) );
	update_post_meta( $post_id, '_sol_crm_attempts', 0 );
	if ( 'dry_run' === $mode ) {
		sol_crm_set( $post_id, 'dry_run', '', 'Dry run: payload stored, nothing sent.' );
		return;
	}
	sol_crm_set( $post_id, 'queued' );
	sol_crm_queue( $post_id );
}, 10, 3 );

// Background send.
add_action( SOL_CRM_HOOK, 'sol_crm_send', 10, 2 );
function sol_crm_send( $post_id, $attempt = 1 ) {
	$post_id = (int) $post_id;
	$attempt = max( 1, (int) $attempt );
	if ( 'live' !== sol_crm_mode() || 'sent' === get_post_meta( $post_id, '_sol_crm', true ) ) {
		return;
	}
	$body = (string) get_post_meta( $post_id, '_sol_crm_payload', true );
	if ( '' === $body ) {
		return;
	}
	$url = sol_crm_url();
	$key = sol_crm_key();
	if ( ! $url || ! $key ) {
		sol_crm_set( $post_id, 'failed', '', 'SOL_CRM_WEBHOOK_URL (https) or SOL_CRM_WEBHOOK_KEY is missing in wp-config.php.' );
		return;
	}
	update_post_meta( $post_id, '_sol_crm_attempts', $attempt );
	$r = wp_remote_post( $url, array(
		'timeout'     => 10,
		'redirection' => 0,
		'headers'     => array( 'Content-Type' => 'application/json', 'x-sol-webhook-key' => $key ),
		'body'        => $body,
	) );
	$code = is_wp_error( $r ) ? 0 : (int) wp_remote_retrieve_response_code( $r );
	if ( $code >= 200 && $code < 300 ) {
		sol_crm_set( $post_id, 'sent', $code );
		return;
	}
	$error = is_wp_error( $r ) ? $r->get_error_message() : 'HTTP ' . $code . ': ' . wp_strip_all_tags( (string) wp_remote_retrieve_body( $r ) );
	$retry = 0 === $code || 408 === $code || 429 === $code || $code >= 500;
	if ( $retry && $attempt <= count( SOL_CRM_RETRIES ) ) {
		sol_crm_set( $post_id, 'retrying', $code, $error );
		sol_crm_queue( $post_id, $attempt + 1, SOL_CRM_RETRIES[ $attempt - 1 ] );
		return;
	}
	sol_crm_set( $post_id, $retry ? 'failed' : 'rejected', $code, $error );
}

/* ------------------------------------------------------------------ */
/* Admin: Enquiries list column, detail box and "Resend to CRM"        */
/* ------------------------------------------------------------------ */

function sol_crm_label( $status ) {
	$labels = array(
		'sent'     => array( '#008a20', 'Sent' ),
		'queued'   => array( '#996800', 'Queued' ),
		'retrying' => array( '#996800', 'Retrying' ),
		'failed'   => array( '#b32d2e', 'Failed' ),
		'rejected' => array( '#b32d2e', 'Rejected' ),
		'dry_run'  => array( '#2271b1', 'Dry run' ),
		'skipped'  => array( '#646970', 'Not a lead form' ),
	);
	return isset( $labels[ $status ] ) ? '<span style="color:' . $labels[ $status ][0] . '">' . esc_html( $labels[ $status ][1] ) . '</span>' : '<span class="description">Not sent</span>';
}

add_filter( 'manage_sol_enquiry_posts_columns', function ( $cols ) {
	$date = isset( $cols['date'] ) ? $cols['date'] : null;
	unset( $cols['date'] );
	$cols['sol_crm'] = 'CRM';
	if ( $date ) {
		$cols['date'] = $date;
	}
	return $cols;
}, 30 );
add_action( 'manage_sol_enquiry_posts_custom_column', function ( $col, $post_id ) {
	if ( 'sol_crm' === $col ) {
		echo sol_crm_label( (string) get_post_meta( $post_id, '_sol_crm', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from fixed strings
	}
}, 10, 2 );

function sol_crm_resend_url( $post_id ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=sol_crm_resend&enquiry=' . (int) $post_id ), 'sol_crm_resend_' . (int) $post_id );
}

add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'sol_enquiry' === $post->post_type && 'live' === sol_crm_mode() && current_user_can( 'manage_options' )
		&& in_array( get_post_meta( $post->ID, '_sol_crm', true ), array( 'failed', 'rejected', 'dry_run' ), true ) ) {
		$actions['sol_crm_resend'] = '<a href="' . esc_url( sol_crm_resend_url( $post->ID ) ) . '">Resend to CRM</a>';
	}
	return $actions;
}, 10, 2 );

// Resend reuses the stored payload and submission ID, so n8n can recognise it as the same submission.
add_action( 'admin_post_sol_crm_resend', function () {
	$post_id = isset( $_GET['enquiry'] ) ? (int) $_GET['enquiry'] : 0;
	if ( ! current_user_can( 'manage_options' ) || 'sol_enquiry' !== get_post_type( $post_id ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_crm_resend_' . $post_id );
	if ( 'live' === sol_crm_mode() && '' !== (string) get_post_meta( $post_id, '_sol_crm_payload', true ) ) {
		sol_crm_set( $post_id, 'queued' );
		sol_crm_queue( $post_id );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=sol_enquiry' ) );
	exit;
} );

add_action( 'add_meta_boxes_sol_enquiry', function () {
	add_meta_box( 'sol_enquiry_crm', 'CRM', function ( $post ) {
		$status = (string) get_post_meta( $post->ID, '_sol_crm', true );
		$sid    = (string) get_post_meta( $post->ID, '_sol_submission_id', true );
		echo '<p>' . sol_crm_label( $status ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( $sid ) {
			echo '<p>Submission ID<br><code style="word-break:break-all">' . esc_html( $sid ) . '</code></p>';
		}
		$bits = array_filter( array(
			'Attempts'  => get_post_meta( $post->ID, '_sol_crm_attempts', true ),
			'HTTP code' => get_post_meta( $post->ID, '_sol_crm_code', true ),
			'Note'      => get_post_meta( $post->ID, '_sol_crm_error', true ),
			'Updated'   => ( $t = (int) get_post_meta( $post->ID, '_sol_crm_time', true ) ) ? wp_date( 'j M Y, g:i a', $t ) : '',
		), 'strlen' );
		foreach ( $bits as $k => $v ) {
			echo '<p><strong>' . esc_html( $k ) . ':</strong> ' . esc_html( (string) $v ) . '</p>';
		}
		$payload = (string) get_post_meta( $post->ID, '_sol_crm_payload', true );
		if ( $payload ) {
			echo '<details><summary>Payload</summary><pre style="white-space:pre-wrap;font-size:11px">' . esc_html( (string) wp_json_encode( json_decode( $payload ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) . '</pre></details>';
		}
		echo '<p class="description">Mode: ' . esc_html( sol_crm_mode() ) . ' (set in wp-config.php)</p>';
	}, 'sol_enquiry', 'side' );
} );
