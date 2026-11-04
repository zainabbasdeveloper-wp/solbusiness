<?php
/**
 * Google reCAPTCHA v3 (invisible) for the site's forms: the SOL website forms, the service payment form,
 * log in, create account, lost password and blog comments. Each submission gets a score from Google and
 * low scores are blocked before anything is saved or emailed.
 *
 * Set up under SOL Site → Spam protection. Protection only switches on after "Check keys" passes there,
 * so wrong keys can never lock anyone out of the login page.
 * Emergency switch-off: define( 'SOL_RECAPTCHA_OFF', true ); in wp-config.php.
 * Keys can also live in wp-config.php: SOL_RECAPTCHA_SITE_KEY and SOL_RECAPTCHA_SECRET_KEY.
 */

defined( 'ABSPATH' ) || exit;

const SOL_RC_PAGE   = 'sol-spam-protection';
const SOL_RC_OPTION = 'sol_recaptcha';
const SOL_RC_LOG    = 'sol_recaptcha_log';

/** Places that can be protected, as key => label. */
function sol_rc_areas() {
	return array(
		'forms'        => 'Website forms (contact, consultation, complaint, training, checklist, newsletter, quiz)',
		'payment'      => 'Service payment form',
		'login'        => 'Log in (wp-login.php and My Account)',
		'register'     => 'Create account (wp-login.php and My Account)',
		'lostpassword' => 'Lost password (wp-login.php and My Account)',
		'comments'     => 'Blog comments from visitors who are not logged in',
	);
}

/** Saved settings with defaults; wp-config.php constants win over saved keys. */
function sol_rc_settings() {
	$s = get_option( SOL_RC_OPTION, array() );
	$s = wp_parse_args( is_array( $s ) ? $s : array(), array(
		'site_key'    => '',
		'secret_key'  => '',
		'min_score'   => 0.5,
		'areas'       => array_keys( sol_rc_areas() ),
		'checked'     => '', // fingerprint of the key pair that passed "Check keys"
	) );
	if ( defined( 'SOL_RECAPTCHA_SITE_KEY' ) && SOL_RECAPTCHA_SITE_KEY ) {
		$s['site_key'] = (string) SOL_RECAPTCHA_SITE_KEY;
	}
	if ( defined( 'SOL_RECAPTCHA_SECRET_KEY' ) && SOL_RECAPTCHA_SECRET_KEY ) {
		$s['secret_key'] = (string) SOL_RECAPTCHA_SECRET_KEY;
	}
	$s['site_key']   = trim( $s['site_key'] );
	$s['secret_key'] = trim( $s['secret_key'] );
	$s['min_score']  = min( 0.9, max( 0.1, (float) $s['min_score'] ) );
	$s['areas']      = array_values( array_intersect( (array) $s['areas'], array_keys( sol_rc_areas() ) ) );
	return $s;
}

/** Fingerprint of a key pair, so changing either key needs a fresh "Check keys". */
function sol_rc_fingerprint( $s ) {
	return $s['site_key'] && $s['secret_key'] ? wp_hash( $s['site_key'] . '|' . $s['secret_key'] ) : '';
}

/** 'off' (no keys), 'unchecked' (keys saved, check not passed yet), 'disabled' (wp-config switch) or 'on'. */
function sol_rc_status() {
	if ( defined( 'SOL_RECAPTCHA_OFF' ) && SOL_RECAPTCHA_OFF ) {
		return 'disabled';
	}
	$s = sol_rc_settings();
	if ( ! $s['site_key'] || ! $s['secret_key'] ) {
		return 'off';
	}
	return hash_equals( sol_rc_fingerprint( $s ), (string) $s['checked'] ) ? 'on' : 'unchecked';
}

/** True when reCAPTCHA is working and switched on for $area ('' = any area). */
function sol_rc_active( $area = '' ) {
	$on = 'on' === sol_rc_status() && ( '' === $area || in_array( $area, sol_rc_settings()['areas'], true ) );
	return (bool) apply_filters( 'sol_recaptcha_active', $on, $area );
}

/** Asks Google about a token. Returns the decoded reply, or WP_Error when Google can't be reached. */
function sol_rc_siteverify( $token, $secret ) {
	$r = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
		'timeout' => 8,
		'body'    => array( 'secret' => $secret, 'response' => $token, 'remoteip' => function_exists( 'sol_visitor_ip' ) ? sol_visitor_ip() : '' ),
	) );
	if ( is_wp_error( $r ) ) {
		return $r;
	}
	$body = json_decode( (string) wp_remote_retrieve_body( $r ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $r ) || ! is_array( $body ) ) {
		return new WP_Error( 'sol_rc_http', 'Unexpected reply from Google (HTTP ' . (int) wp_remote_retrieve_response_code( $r ) . ').' );
	}
	return $body;
}

/**
 * Checks the reCAPTCHA token sent with this request.
 * $action is the expected action name (or its start, e.g. 'form' matches 'form_contact').
 * Returns array( 'ok' => bool, 'reason' => string, 'score' => float|null ).
 * If Google can't be reached, or the secret key stops working, the submission is let through
 * (and recorded) so real enquiries and logins are never lost because of an outage.
 */
function sol_rc_verify( $area, $action ) {
	static $seen = array(); // a token can be checked once; several hooks in one request share the answer
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- public forms; this IS the bot check.
	$token = isset( $_POST['sol_rc_token'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) wp_unslash( $_POST['sol_rc_token'] ) ) : '';
	if ( '' === $token ) {
		return sol_rc_record( $area, $action, false, 'missing', null );
	}
	if ( isset( $seen[ $token ] ) ) {
		return $seen[ $token ];
	}
	$s     = sol_rc_settings();
	$reply = sol_rc_siteverify( $token, $s['secret_key'] );
	$score = is_array( $reply ) && isset( $reply['score'] ) ? (float) $reply['score'] : null;
	$codes = is_array( $reply ) && isset( $reply['error-codes'] ) ? (array) $reply['error-codes'] : array();

	if ( is_wp_error( $reply ) ) {
		$out = sol_rc_record( $area, $action, true, 'unreachable', null );
	} elseif ( array_intersect( $codes, array( 'invalid-input-secret', 'missing-input-secret' ) ) ) {
		update_option( 'sol_recaptcha_problem', array( 'time' => time(), 'codes' => $codes ), false );
		$out = sol_rc_record( $area, $action, true, 'bad-secret', null );
	} elseif ( empty( $reply['success'] ) ) {
		$out = sol_rc_record( $area, $action, false, 'invalid', null ); // expired, reused or forged
	} elseif ( isset( $reply['action'] ) && 0 !== strpos( (string) $reply['action'], $action ) ) {
		$out = sol_rc_record( $area, $action, false, 'action', $score );
	} elseif ( null !== $score && $score < (float) apply_filters( 'sol_recaptcha_min_score', $s['min_score'], $area ) ) {
		$out = sol_rc_record( $area, $action, false, 'low', $score );
	} else {
		$out = sol_rc_record( $area, $action, true, 'ok', $score );
	}
	$seen[ $token ] = $out;
	return $out;
}

/** Counts the result and keeps the last 30 blocked or unusual checks for the admin page. */
function sol_rc_record( $area, $action, $ok, $reason, $score ) {
	$log = get_option( SOL_RC_LOG, array() );
	$log = wp_parse_args( is_array( $log ) ? $log : array(), array( 'since' => time(), 'passed' => 0, 'blocked' => 0, 'recent' => array() ) );
	$log[ $ok ? 'passed' : 'blocked' ]++;
	if ( ! $ok || 'ok' !== $reason ) {
		array_unshift( $log['recent'], array(
			'time'   => time(),
			'area'   => $area,
			'reason' => $reason,
			'score'  => $score,
			'page'   => isset( $_SERVER['REQUEST_URI'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 0, 120 ) : '',
		) );
		$log['recent'] = array_slice( $log['recent'], 0, 30 );
	}
	update_option( SOL_RC_LOG, $log, false );
	do_action( 'sol_recaptcha_checked', $area, $ok, $reason, $score );
	return array( 'ok' => (bool) $ok, 'reason' => $reason, 'score' => $score );
}

/** What a visitor sees when their submission is blocked. */
function sol_rc_message( $reason ) {
	if ( 'missing' === $reason ) {
		return 'We could not run our spam check. Please refresh the page and try again. If you use an ad or script blocker, please allow Google reCAPTCHA on this site.';
	}
	return 'We could not confirm this was sent by a person, so it was not sent. Please refresh the page and try again, or contact us by phone or email.';
}

/* ------------------------------------------------------------------ */
/* Front end and login page script                                     */
/* ------------------------------------------------------------------ */

/** Forms the script protects, as CSS selector => action name sent to Google. */
function sol_rc_client_forms() {
	$map = array(
		'payment'      => array( 'form.sol-service-payment-form' => 'service_payment' ),
		'login'        => array( '#loginform' => 'login', 'form.woocommerce-form-login' => 'login' ),
		'register'     => array( '#registerform' => 'register', 'form.woocommerce-form-register' => 'register' ),
		'lostpassword' => array( '#lostpasswordform' => 'lostpassword', 'form.woocommerce-ResetPassword' => 'lostpassword' ),
		'comments'     => is_user_logged_in() ? array() : array( '#commentform' => 'comment' ),
	);
	$out = array();
	foreach ( $map as $area => $forms ) {
		if ( sol_rc_active( $area ) ) {
			foreach ( $forms as $sel => $action ) {
				$out[] = array( 'sel' => $sel, 'action' => $action );
			}
		}
	}
	return $out;
}

function sol_rc_enqueue() {
	if ( ! sol_rc_active() ) {
		return;
	}
	$ver = defined( 'SOL_THEME_VERSION' ) ? SOL_THEME_VERSION : null;
	wp_enqueue_script( 'sol-recaptcha', SOL_URI . 'assets/js/sol-recaptcha.js', array(), $ver, true );
	wp_localize_script( 'sol-recaptcha', 'SOL_RC', array(
		'key'   => sol_rc_settings()['site_key'],
		'sol'   => sol_rc_active( 'forms' ) ? 1 : 0, // SOL forms ask for a token themselves (assets/js/sol.js)
		'forms' => sol_rc_client_forms(),
	) );
	// Google allows hiding the floating badge when this notice is shown with the form instead.
	$css = '.grecaptcha-badge{visibility:hidden!important}.sol-rc-note{margin:.6rem 0 0;font-size:.72rem;line-height:1.45;opacity:.75}.sol-rc-note a{color:inherit;text-decoration:underline}';
	wp_register_style( 'sol-recaptcha', false, array(), $ver );
	wp_enqueue_style( 'sol-recaptcha' );
	wp_add_inline_style( 'sol-recaptcha', $css );
}
add_action( 'wp_enqueue_scripts', 'sol_rc_enqueue', 30 );
add_action( 'login_enqueue_scripts', 'sol_rc_enqueue' );

/* ------------------------------------------------------------------ */
/* Server-side checks                                                  */
/* ------------------------------------------------------------------ */
// The SOL website forms are checked inside sol_handle_form() in inc/forms.php.

// Service payment form: it posts back to its own page and is handled outside the theme,
// so the check runs at the very start of the request, before that handler.
add_action( 'init', function () {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['sol_service_payment_submit'] ) || ! sol_rc_active( 'payment' ) ) {
		return;
	}
	$rc = sol_rc_verify( 'payment', 'service_payment' );
	if ( ! $rc['ok'] ) {
		wp_die( esc_html( sol_rc_message( $rc['reason'] ) ), 'Payment form not sent', array( 'response' => 403, 'back_link' => true ) );
	}
}, PHP_INT_MIN );

// Log in: wp-login.php and the WooCommerce My Account / checkout login form (both use wp_signon).
add_filter( 'authenticate', function ( $user ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$form = 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) && ( isset( $_POST['log'] ) || isset( $_POST['woocommerce-login-nonce'] ) );
	// phpcs:enable
	if ( ! $form || is_wp_error( $user ) || ! sol_rc_active( 'login' ) ) {
		return $user; // wrong passwords are already refused; app passwords, XML-RPC and REST aren't browser forms
	}
	$rc = sol_rc_verify( 'login', 'login' );
	return $rc['ok'] ? $user : new WP_Error( 'sol_recaptcha', '<strong>Error:</strong> ' . esc_html( sol_rc_message( $rc['reason'] ) ) );
}, 99 );

// Create account on wp-login.php.
add_filter( 'registration_errors', function ( $errors ) {
	if ( 'wp-login.php' === ( isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '' ) && sol_rc_active( 'register' ) ) {
		$rc = sol_rc_verify( 'register', 'register' );
		if ( ! $rc['ok'] ) {
			$errors->add( 'sol_recaptcha', '<strong>Error:</strong> ' . esc_html( sol_rc_message( $rc['reason'] ) ) );
		}
	}
	return $errors;
}, 20 );

// Create account on My Account (not the "create an account" box at checkout, which isn't a separate form).
add_filter( 'woocommerce_registration_errors', function ( $errors ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( isset( $_POST['woocommerce-register-nonce'] ) && sol_rc_active( 'register' ) ) {
		$rc = sol_rc_verify( 'register', 'register' );
		if ( ! $rc['ok'] ) {
			$errors->add( 'sol_recaptcha', esc_html( sol_rc_message( $rc['reason'] ) ) );
		}
	}
	return $errors;
}, 20 );

// Lost password on wp-login.php and My Account. Admins sending a reset link from Users aren't affected.
add_action( 'lostpassword_post', function ( $errors ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$form = isset( $_POST['woocommerce-lost-password-nonce'] ) || ( 'wp-login.php' === ( isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '' ) && isset( $_POST['user_login'] ) );
	if ( $form && is_wp_error( $errors ) && sol_rc_active( 'lostpassword' ) ) {
		$rc = sol_rc_verify( 'lostpassword', 'lostpassword' );
		if ( ! $rc['ok'] ) {
			$errors->add( 'sol_recaptcha', esc_html( sol_rc_message( $rc['reason'] ) ) );
		}
	}
}, 20 );

// Blog comments from visitors who aren't logged in.
add_filter( 'preprocess_comment', function ( $data ) {
	$type = isset( $data['comment_type'] ) ? (string) $data['comment_type'] : '';
	if ( is_user_logged_in() || ! in_array( $type, array( '', 'comment' ), true ) || ! sol_rc_active( 'comments' ) ) {
		return $data;
	}
	$rc = sol_rc_verify( 'comments', 'comment' );
	if ( ! $rc['ok'] ) {
		wp_die( esc_html( sol_rc_message( $rc['reason'] ) ), 'Comment not sent', array( 'response' => 403, 'back_link' => true ) );
	}
	return $data;
} );

/* ------------------------------------------------------------------ */
/* Admin: SOL Site → Spam protection                                   */
/* ------------------------------------------------------------------ */

add_action( 'admin_menu', function () {
	add_submenu_page( SOL_OPTIONS_SLUG, 'Spam protection', 'Spam protection', 'manage_options', SOL_RC_PAGE, 'sol_rc_admin_page', 4 );
}, 100 );

add_action( 'admin_post_sol_rc_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_rc_save' );
	$old = get_option( SOL_RC_OPTION, array() );
	$old = is_array( $old ) ? $old : array();
	$new = $old;
	if ( ! empty( $_POST['remove_keys'] ) ) {
		$new['site_key']   = '';
		$new['secret_key'] = '';
		$new['checked']    = '';
	} else {
		$new['site_key'] = isset( $_POST['site_key'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', wp_unslash( $_POST['site_key'] ) ) : '';
		$secret          = isset( $_POST['secret_key'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', wp_unslash( $_POST['secret_key'] ) ) : '';
		if ( '' !== $secret ) {
			$new['secret_key'] = $secret; // blank keeps the saved secret
		}
		$new['min_score'] = isset( $_POST['min_score'] ) ? (float) $_POST['min_score'] : 0.5;
		$areas            = isset( $_POST['areas'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['areas'] ) ) : array();
		$new['areas']     = array_values( array_intersect( $areas, array_keys( sol_rc_areas() ) ) );
	}
	update_option( SOL_RC_OPTION, $new, false );
	delete_option( 'sol_recaptcha_problem' );
	wp_safe_redirect( admin_url( 'admin.php?page=' . SOL_RC_PAGE . '&sol_saved=1' ) );
	exit;
} );

// "Check keys": the admin page gets a real token in the browser and the server verifies it with the secret.
add_action( 'wp_ajax_sol_rc_check', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'sol_rc_check', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed. Refresh the page and try again.' ), 403 );
	}
	$s     = sol_rc_settings();
	$token = isset( $_POST['token'] ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) wp_unslash( $_POST['token'] ) ) : '';
	if ( ! $s['site_key'] || ! $s['secret_key'] ) {
		wp_send_json_error( array( 'message' => 'Save both keys first.' ) );
	}
	if ( '' === $token ) {
		wp_send_json_error( array( 'message' => 'Google did not accept the site key on this domain. Make sure the keys are reCAPTCHA v3 (score based) and that ' . wp_parse_url( home_url(), PHP_URL_HOST ) . ' is in the key\'s domain list.' ) );
	}
	$reply = sol_rc_siteverify( $token, $s['secret_key'] );
	if ( is_wp_error( $reply ) ) {
		wp_send_json_error( array( 'message' => 'This server could not reach Google: ' . $reply->get_error_message() ) );
	}
	$codes = isset( $reply['error-codes'] ) ? implode( ', ', (array) $reply['error-codes'] ) : '';
	if ( empty( $reply['success'] ) ) {
		$hint = false !== strpos( $codes, 'secret' ) ? 'The secret key is wrong. Copy it again from Google (it is the second key).' : 'Google rejected the check (' . $codes . '). Check that both keys come from the same reCAPTCHA v3 key.';
		wp_send_json_error( array( 'message' => $hint ) );
	}
	$opt            = get_option( SOL_RC_OPTION, array() );
	$opt            = is_array( $opt ) ? $opt : array();
	$opt['checked'] = sol_rc_fingerprint( $s );
	update_option( SOL_RC_OPTION, $opt, false );
	delete_option( 'sol_recaptcha_problem' );
	wp_send_json_success( array(
		'message' => sprintf( 'Keys work. Protection is now on. Google gave this check a score of %s for %s.', isset( $reply['score'] ) ? number_format( (float) $reply['score'], 1 ) : '?', isset( $reply['hostname'] ) ? $reply['hostname'] : 'this site' ),
	) );
} );

// Warn admins when the secret key stopped working (submissions are let through until it's fixed).
add_action( 'admin_notices', function () {
	$p = get_option( 'sol_recaptcha_problem' );
	if ( ! is_array( $p ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-error"><p><strong>Spam protection:</strong> Google says the reCAPTCHA secret key is not valid, so forms are not being checked. <a href="%s">Fix the keys</a>.</p></div>',
		esc_url( admin_url( 'admin.php?page=' . SOL_RC_PAGE ) )
	);
} );

function sol_rc_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = sol_rc_settings();
	$status = sol_rc_status();
	$log    = get_option( SOL_RC_LOG, array() );
	$log    = wp_parse_args( is_array( $log ) ? $log : array(), array( 'since' => 0, 'passed' => 0, 'blocked' => 0, 'recent' => array() ) );
	$const  = defined( 'SOL_RECAPTCHA_SITE_KEY' ) || defined( 'SOL_RECAPTCHA_SECRET_KEY' );
	$host   = wp_parse_url( home_url(), PHP_URL_HOST );
	$labels = array(
		'on'        => array( 'success', 'On. Forms are checked by Google reCAPTCHA v3.' ),
		'unchecked' => array( 'warning', 'Keys saved but not checked yet. Click "Check keys" below to switch protection on.' ),
		'off'       => array( 'info', 'Off. Add your reCAPTCHA v3 keys below.' ),
		'disabled'  => array( 'warning', 'Switched off in wp-config.php (SOL_RECAPTCHA_OFF).' ),
	);
	$reasons = array(
		'missing'     => 'No spam check sent (bot posting directly, or script blocked)',
		'invalid'     => 'Expired, reused or fake token',
		'action'      => 'Token made for a different form',
		'low'         => 'Score too low (likely a bot)',
		'unreachable' => 'Let through: Google could not be reached',
		'bad-secret'  => 'Let through: secret key not valid',
	);
	?>
	<div class="wrap">
		<h1>Spam protection</h1>
		<?php if ( ! empty( $_GET['sol_saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p>Saved.<?php echo 'unchecked' === $status ? ' Now click "Check keys" to switch protection on.' : ''; ?></p></div>
		<?php endif; ?>
		<div class="notice notice-<?php echo esc_attr( $labels[ $status ][0] ); ?> inline"><p><strong>Status:</strong> <?php echo esc_html( $labels[ $status ][1] ); ?></p></div>

		<?php if ( get_option( 'users_can_register' ) ) : ?>
			<div class="notice notice-warning inline"><p><strong>Anyone can create an account on this site</strong> (Settings → General → Membership). Bots often use this to sign up and trigger "new user" emails. If customers don't need to sign up on wp-login.php, <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">turn it off</a>. WooCommerce My Account sign-up has its own setting.</p></div>
		<?php endif; ?>

		<h2>1. Get keys from Google</h2>
		<ol>
			<li>Open <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">google.com/recaptcha/admin/create</a> and sign in with the business Google account.</li>
			<li>Label: <code>SOL website</code>. Type: <strong>Score based (v3)</strong>. Domain: <code><?php echo esc_html( $host ); ?></code>.</li>
			<li>Submit, then copy the <strong>site key</strong> and the <strong>secret key</strong> into the boxes below. Keep the secret key private.</li>
		</ol>

		<h2>2. Save and check</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sol_rc_save">
			<?php wp_nonce_field( 'sol_rc_save' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sol-rc-site">Site key</label></th>
					<td><input id="sol-rc-site" class="regular-text code" name="site_key" value="<?php echo esc_attr( $s['site_key'] ); ?>" autocomplete="off" <?php disabled( defined( 'SOL_RECAPTCHA_SITE_KEY' ) ); ?>></td>
				</tr>
				<tr>
					<th scope="row"><label for="sol-rc-secret">Secret key</label></th>
					<td>
						<input id="sol-rc-secret" class="regular-text code" type="password" name="secret_key" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $s['secret_key'] ? 'Saved (leave blank to keep it)' : '' ); ?>" <?php disabled( defined( 'SOL_RECAPTCHA_SECRET_KEY' ) ); ?>>
						<?php if ( $const ) : ?><p class="description">Some keys are set in wp-config.php and can't be changed here.</p><?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sol-rc-score">Block when the score is below</label></th>
					<td>
						<select id="sol-rc-score" name="min_score">
							<?php foreach ( array( '0.3' => '0.3 (lenient: blocks only obvious bots)', '0.5' => '0.5 (recommended)', '0.7' => '0.7 (strict: may block some real people)' ) as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( abs( (float) $v - $s['min_score'] ) < 0.01 ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">Google scores each submission from 0.0 (bot) to 1.0 (person).</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Protect</th>
					<td><fieldset>
						<?php foreach ( sol_rc_areas() as $k => $l ) : ?>
							<label><input type="checkbox" name="areas[]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $s['areas'], true ) ); ?>> <?php echo esc_html( $l ); ?></label><br>
						<?php endforeach; ?>
					</fieldset></td>
				</tr>
			</table>
			<p class="submit">
				<?php submit_button( 'Save keys', 'primary', 'submit', false ); ?>
				<?php if ( $s['site_key'] || $s['secret_key'] ) : ?>
					<button type="submit" class="button" name="remove_keys" value="1" onclick="return confirm('Remove both keys and switch spam protection off?');">Remove keys</button>
				<?php endif; ?>
			</p>
		</form>

		<?php if ( $s['site_key'] && $s['secret_key'] ) : ?>
			<p>
				<button type="button" class="button button-primary" id="sol-rc-check">Check keys</button>
				<span id="sol-rc-result" style="margin-left:8px;"></span>
			</p>
			<script>
			( function () {
				var btn = document.getElementById( 'sol-rc-check' ), out = document.getElementById( 'sol-rc-result' );
				var key = <?php echo wp_json_encode( $s['site_key'] ); ?>;
				var say = function ( msg, ok ) { out.textContent = msg; out.style.color = ok ? '#008a20' : '#b32d2e'; out.style.fontWeight = '600'; };
				var send = function ( token ) {
					var d = new FormData();
					d.append( 'action', 'sol_rc_check' );
					d.append( 'nonce', <?php echo wp_json_encode( wp_create_nonce( 'sol_rc_check' ) ); ?> );
					d.append( 'token', token || '' );
					fetch( ajaxurl, { method: 'POST', body: d, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
						say( r && r.data && r.data.message ? r.data.message : 'Unexpected reply.', r && r.success );
						btn.disabled = false;
						if ( r && r.success ) { setTimeout( function () { location.reload(); }, 2500 ); }
					} ).catch( function () { say( 'Could not reach WordPress. Try again.', false ); btn.disabled = false; } );
				};
				btn.addEventListener( 'click', function () {
					btn.disabled = true; say( 'Checking…', true ); out.style.color = '';
					var run = function () {
						var timer = setTimeout( function () { send( '' ); }, 10000 );
						grecaptcha.ready( function () {
							try {
								grecaptcha.execute( key, { action: 'admin_check' } ).then( function ( t ) { clearTimeout( timer ); send( t ); }, function () { clearTimeout( timer ); send( '' ); } );
							} catch ( e ) { clearTimeout( timer ); send( '' ); }
						} );
					};
					if ( window.grecaptcha && grecaptcha.execute ) { return run(); }
					var sc = document.createElement( 'script' );
					sc.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent( key );
					sc.onload = run;
					sc.onerror = function () { say( 'Could not load Google reCAPTCHA in this browser (blocked by an extension?).', false ); btn.disabled = false; };
					document.head.appendChild( sc );
				} );
			} )();
			</script>
		<?php endif; ?>

		<h2>3. Results</h2>
		<?php if ( $log['since'] ) : ?>
			<p>Since <?php echo esc_html( wp_date( 'j M Y', (int) $log['since'] ) ); ?>: <strong><?php echo esc_html( number_format_i18n( (int) $log['passed'] ) ); ?></strong> passed, <strong><?php echo esc_html( number_format_i18n( (int) $log['blocked'] ) ); ?></strong> blocked.</p>
			<?php if ( $log['recent'] ) : ?>
				<table class="widefat striped" style="max-width:900px">
					<thead><tr><th>When</th><th>Where</th><th>Result</th><th>Score</th><th>Address</th></tr></thead>
					<tbody>
					<?php foreach ( $log['recent'] as $r ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( 'j M Y, g:i a', (int) $r['time'] ) ); ?></td>
							<td><?php echo esc_html( ucfirst( (string) $r['area'] ) ); ?></td>
							<td><?php echo esc_html( isset( $reasons[ $r['reason'] ] ) ? $reasons[ $r['reason'] ] : $r['reason'] ); ?></td>
							<td><?php echo null === $r['score'] ? '–' : esc_html( number_format( (float) $r['score'], 1 ) ); ?></td>
							<td><code><?php echo esc_html( $r['page'] ); ?></code></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		<?php else : ?>
			<p>No submissions checked yet.</p>
		<?php endif; ?>

		<h2>Good to know</h2>
		<ul style="list-style:disc;padding-left:20px">
			<li>Visitors never see a puzzle. The check runs when they press Send, and a small "protected by reCAPTCHA" note appears under each protected form.</li>
			<li>The website forms also keep their hidden bot trap and limit of 8 submissions per 10 minutes per visitor.</li>
			<li>If Google is unreachable, submissions are let through, so real enquiries and logins are never lost.</li>
			<li>Locked out? Add <code>define( 'SOL_RECAPTCHA_OFF', true );</code> to wp-config.php to switch it off.</li>
			<li>Google's free allowance is 10,000 checks a month, far more than this site needs.</li>
		</ul>
	</div>
	<?php
}
