<?php
/**
 * SOL Site → Email: shows where enquiry emails go, whether sending works, and sends a test email.
 * WordPress hides mail failures by default, so failures are recorded here and shown to admins.
 */

defined( 'ABSPATH' ) || exit;

define( 'SOL_MAIL_PAGE', 'sol-email' );

/** The address enquiry emails are sent to, and where that setting comes from. */
function sol_mail_recipient() {
	$set = sol_opt( 'notification_email' );
	return $set ? array( $set, 'SOL Site Settings → Notification email' ) : array( get_option( 'admin_email' ), 'Settings → General → Administration Email Address (SOL Site Notification email is empty)' );
}

/** Name of the SMTP plugin handling mail, or ''. */
function sol_mail_smtp_plugin() {
	$known = array(
		'WPMS_PLUGIN_VER'   => 'WP Mail SMTP',
		'FLUENTMAIL'        => 'FluentSMTP',
		'POST_SMTP_VER'     => 'Post SMTP',
		'EASY_WP_SMTP_PATH' => 'Easy WP SMTP',
	);
	foreach ( $known as $const => $name ) {
		if ( defined( $const ) ) {
			return $name;
		}
	}
	if ( class_exists( 'SIB_Manager' ) ) {
		return 'Brevo';
	}
	return '';
}

// Record every failed send so it can be shown in the admin.
add_action( 'wp_mail_failed', function ( $error ) {
	update_option( 'sol_mail_last_error', array( 'time' => time(), 'message' => $error->get_error_message() ), false );
} );

/** True when the most recent send failed. */
function sol_mail_is_failing() {
	$err = get_option( 'sol_mail_last_error' );
	return is_array( $err ) && (int) $err['time'] > (int) get_option( 'sol_mail_last_ok', 0 );
}

add_action( 'admin_menu', function () {
	add_submenu_page( SOL_OPTIONS_SLUG, 'Email', 'Email', 'manage_options', SOL_MAIL_PAGE, 'sol_mail_admin_page', 2 );
}, 100 );

add_action( 'admin_post_sol_mail_test', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_mail_test' );
	list( $to ) = sol_mail_recipient();
	$ok = wp_mail( $to, '[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '] Test email', "This is a test from SOL Site → Email.\n\nIf you can read this, enquiry emails from the website forms will reach this inbox.\n\nSent " . wp_date( 'j M Y, g:i a' ) . '.' );
	if ( $ok ) {
		update_option( 'sol_mail_last_ok', time(), false );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=' . SOL_MAIL_PAGE . '&sol_test=' . ( $ok ? 'sent' : 'failed' ) ) );
	exit;
} );

function sol_mail_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	list( $to, $source ) = sol_mail_recipient();
	$smtp   = sol_mail_smtp_plugin();
	$err    = get_option( 'sol_mail_last_error' );
	$ok_at  = (int) get_option( 'sol_mail_last_ok', 0 );
	$test   = isset( $_GET['sol_test'] ) ? sanitize_key( $_GET['sol_test'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$from   = apply_filters( 'wp_mail_from', 'wordpress@' . preg_replace( '/^www\./', '', (string) wp_parse_url( network_home_url(), PHP_URL_HOST ) ) );
	$when   = function ( $t ) {
		return $t ? wp_date( 'j M Y, g:i a', $t ) : 'Never';
	};
	?>
	<div class="wrap">
		<h1>Email</h1>
		<p>Every website form saves an entry in <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=sol_enquiry' ) ); ?>">Enquiries</a> and emails the team. Use this page to check the email part works.</p>

		<?php if ( 'sent' === $test ) : ?>
			<div class="notice notice-success"><p><strong>Test email handed over for sending to <?php echo esc_html( $to ); ?>.</strong> Check that inbox, including spam, in the next few minutes. If it never arrives, set up SMTP as described below.</p></div>
		<?php elseif ( 'failed' === $test ) : ?>
			<div class="notice notice-error"><p><strong>The test email could not be sent.</strong> <?php echo esc_html( is_array( $err ) ? $err['message'] : '' ); ?> Set up SMTP as described below.</p></div>
		<?php endif; ?>

		<table class="widefat striped" style="max-width:820px;margin:16px 0 24px">
			<tbody>
				<tr><td style="width:200px"><strong>Enquiries are sent to</strong></td><td><code><?php echo esc_html( $to ); ?></code><br><span class="description">From: <?php echo esc_html( $source ); ?></span></td></tr>
				<tr><td><strong>Sent from</strong></td><td><code><?php echo esc_html( $from ); ?></code></td></tr>
				<tr><td><strong>Sending service</strong></td><td><?php echo $smtp ? '<span style="color:#008a20">● ' . esc_html( $smtp ) . '</span>' : '<span style="color:#b32d2e">● None. The server sends mail by itself, which usually fails or lands in spam.</span>'; ?></td></tr>
				<tr><td><strong>Last successful send</strong></td><td><?php echo esc_html( $when( $ok_at ) ); ?></td></tr>
				<tr><td><strong>Last failure</strong></td><td><?php echo is_array( $err ) ? esc_html( $when( (int) $err['time'] ) . ': ' . $err['message'] ) : 'None recorded'; ?></td></tr>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="sol_mail_test">
			<?php wp_nonce_field( 'sol_mail_test' ); ?>
			<?php submit_button( 'Send test email to ' . $to, 'primary', 'submit', false ); ?>
		</form>

		<h2 style="margin-top:32px">Make emails arrive reliably</h2>
		<?php if ( $smtp ) : ?>
			<p><?php echo esc_html( $smtp ); ?> is active. If the test email doesn't arrive, open its settings and run its own test to see the exact error.</p>
		<?php else : ?>
			<p>Your domain already has a verified Brevo account, so sending through Brevo is the quickest fix:</p>
			<ol>
				<li>Install and activate the free <a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=wp+mail+smtp&tab=search&type=term' ) ); ?>">WP Mail SMTP</a> plugin.</li>
				<li>In Brevo, go to <em>SMTP &amp; API → API Keys</em> and create a key.</li>
				<li>In WP Mail SMTP, choose <strong>Brevo</strong> as the mailer, paste the key, and set the From Email to an address on your domain, such as <code>info@solbusinessconsultant.com.au</code> (it must be a verified sender in Brevo).</li>
				<li>Come back here and press <strong>Send test email</strong>.</li>
			</ol>
		<?php endif; ?>
		<p>To change where enquiries go, edit <strong>Notification email</strong> in <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . SOL_OPTIONS_SLUG ) ); ?>">SOL Site Settings</a>.</p>
	</div>
	<?php
}

// Warn admins while sending is failing.
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || ! sol_mail_is_failing() ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && false !== strpos( (string) $screen->id, SOL_MAIL_PAGE ) ) {
		return;
	}
	$err = get_option( 'sol_mail_last_error' );
	echo '<div class="notice notice-error"><p><strong>Website enquiry emails are not being sent.</strong> ' . esc_html( $err['message'] ) . ' Enquiries are still saved. <a href="' . esc_url( admin_url( 'admin.php?page=' . SOL_MAIL_PAGE ) ) . '">Fix it in SOL Site → Email</a>.</p></div>';
} );

// Enquiries list: show whether each notification email went out.
add_filter( 'manage_sol_enquiry_posts_columns', function ( $cols ) {
	$date = isset( $cols['date'] ) ? $cols['date'] : null;
	unset( $cols['date'] );
	$cols['sol_mail'] = 'Team notified';
	$cols['sol_confirm'] = 'Visitor confirmation';
	if ( $date ) {
		$cols['date'] = $date;
	}
	return $cols;
}, 20 );
add_action( 'manage_sol_enquiry_posts_custom_column', function ( $col, $post_id ) {
	if ( ! in_array( $col, array( 'sol_mail', 'sol_confirm' ), true ) ) {
		return;
	}
	$s = get_post_meta( $post_id, '_' . $col, true );
	echo 'sent' === $s ? '<span style="color:#008a20">Sent</span>' : ( 'failed' === $s ? '<span style="color:#b32d2e">Failed</span>' : '<span class="description">Not recorded</span>' );
}, 10, 2 );
