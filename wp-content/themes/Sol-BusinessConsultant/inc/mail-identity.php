<?php
/**
 * One email address for the whole site: info@solbusinessconsultant.com.au.
 *
 * - Every email WordPress or a plugin sends goes out From info@ (name "SOL Business Consultant").
 * - Any email addressed to a retired address (workessentialsconsult@gmail.com) goes to info@ instead.
 * - SOL Site → Email lists every saved setting that still holds a retired address and swaps them in one click.
 * - Comments are switched off: the theme never shows them, so the only comments arriving were spam
 *   (and each one sent a "Please moderate" email).
 *
 * Change the addresses with the filters 'sol_mail_main_address' and 'sol_mail_retired_addresses'.
 * Turn comments back on with: add_filter( 'sol_comments_enabled', '__return_true' );
 */

defined( 'ABSPATH' ) || exit;

/** The one address the site sends from and to. */
function sol_mail_main() {
	return (string) apply_filters( 'sol_mail_main_address', 'info@solbusinessconsultant.com.au' );
}

/** Addresses that must never be used (lower case). */
function sol_mail_retired() {
	return array_map( 'strtolower', (array) apply_filters( 'sol_mail_retired_addresses', array( 'workessentialsconsult@gmail.com' ) ) );
}

/** $text with every retired address replaced by the main one (any letter case). */
function sol_mail_swap( $text ) {
	if ( ! is_string( $text ) || '' === $text ) {
		return $text;
	}
	foreach ( sol_mail_retired() as $old ) {
		$text = str_ireplace( $old, sol_mail_main(), $text );
	}
	return $text;
}

/** True when $text contains a retired address. */
function sol_mail_has_retired( $text ) {
	foreach ( sol_mail_retired() as $old ) {
		if ( is_string( $text ) && false !== stripos( $text, $old ) ) {
			return true;
		}
	}
	return false;
}

/**
 * sol_mail_swap() through arrays and objects (saved settings). Login details of a connected mail
 * account (keys like "gmail", "oauth", "token") are left alone so the connection isn't broken.
 */
function sol_mail_swap_deep( $value ) {
	if ( is_string( $value ) ) {
		if ( is_serialized( $value ) ) {
			$inner = @unserialize( trim( $value ), array( 'allowed_classes' => false ) ); // phpcs:ignore
			return false === $inner && 'b:0;' !== trim( $value ) ? $value : serialize( sol_mail_swap_deep( $inner ) ); // phpcs:ignore
		}
		return sol_mail_swap( $value );
	}
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			if ( is_string( $k ) && preg_match( '/gmail|oauth|token|auth|client_?(id|secret)|refresh/i', $k ) ) {
				continue;
			}
			$value[ $k ] = sol_mail_swap_deep( $v );
		}
	}
	return $value;
}

/* ------------------------------------------------------------------ */
/* Every email: From info@, nothing to the retired address             */
/* ------------------------------------------------------------------ */

// Runs last, after WooCommerce and SMTP plugins have set their own From.
add_filter( 'wp_mail_from', function () {
	return sol_mail_main();
}, PHP_INT_MAX );

add_filter( 'wp_mail_from_name', function ( $name ) {
	return ( '' === trim( (string) $name ) || 'WordPress' === $name || sol_mail_has_retired( $name ) ) ? sol_email_company_name() : $name;
}, PHP_INT_MAX );

/** Company name for the From line (inc/emails.php has the same default). */
function sol_email_company_name() {
	return function_exists( 'sol_email_company' ) ? sol_email_company() : 'SOL Business Consultant';
}

// Recipients, Reply-To, Cc/Bcc and the text of the email itself.
add_filter( 'wp_mail', function ( $args ) {
	foreach ( array( 'to', 'headers', 'message', 'subject' ) as $k ) {
		if ( ! isset( $args[ $k ] ) ) {
			continue;
		}
		if ( is_array( $args[ $k ] ) ) {
			$args[ $k ] = array_values( array_unique( array_map( 'sol_mail_swap', $args[ $k ] ) ) );
		} else {
			$args[ $k ] = sol_mail_swap( $args[ $k ] );
		}
	}
	// "a@x, info@…, info@…" → each address once.
	if ( is_string( $args['to'] ) && false !== strpos( $args['to'], ',' ) ) {
		$args['to'] = implode( ', ', array_unique( array_map( 'trim', explode( ',', $args['to'] ) ) ) );
	}
	return $args;
}, PHP_INT_MAX );

// Last check on the finished message, for plugins that set the From after the filters above.
add_action( 'phpmailer_init', function ( $pm ) {
	if ( strtolower( (string) $pm->From ) !== strtolower( sol_mail_main() ) ) {
		try {
			$pm->setFrom( sol_mail_main(), sol_mail_has_retired( $pm->FromName ) || 'WordPress' === $pm->FromName ? sol_email_company_name() : $pm->FromName, false );
		} catch ( Exception $e ) { // phpcs:ignore
			// Leave it; wp_mail reports any failure.
		}
	}
	if ( sol_mail_has_retired( (string) $pm->Sender ) ) {
		$pm->Sender = '';
	}
}, PHP_INT_MAX );

/* ------------------------------------------------------------------ */
/* Comments off (the theme doesn't show them; only spam was arriving)  */
/* ------------------------------------------------------------------ */

function sol_comments_enabled() {
	return (bool) apply_filters( 'sol_comments_enabled', false );
}

add_action( 'init', function () {
	if ( sol_comments_enabled() ) {
		return;
	}
	add_filter( 'comments_open', '__return_false', PHP_INT_MAX );
	add_filter( 'pings_open', '__return_false', PHP_INT_MAX );
	add_filter( 'comments_array', '__return_empty_array', PHP_INT_MAX );
	add_filter( 'feed_links_show_comments_feed', '__return_false' );
	// Bots post straight to wp-comments-post.php; refuse before anything is saved or emailed.
	add_action( 'pre_comment_on_post', function () {
		wp_die( 'Comments are closed on this site. Please use the contact form instead.', 'Comments closed', array( 'response' => 403 ) );
	} );
	// No pingbacks or trackbacks either (another spam route).
	add_filter( 'xmlrpc_methods', function ( $methods ) {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
		return $methods;
	} );
	add_filter( 'wp_headers', function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	} );
	// Nothing left to moderate, so no "Please moderate" emails.
	add_filter( 'notify_moderator', '__return_false', PHP_INT_MAX );
	add_filter( 'notify_post_author', '__return_false', PHP_INT_MAX );
} );

/* ------------------------------------------------------------------ */
/* Saved settings that still hold a retired address                    */
/* ------------------------------------------------------------------ */

/** Where a saved option is changed in the admin, for the list on SOL Site → Email. */
function sol_mail_option_label( $name ) {
	$known = array(
		'admin_email'                         => 'Settings → General → Administration Email Address',
		'new_admin_email'                     => 'Settings → General (an address change waiting for confirmation)',
		'woocommerce_email_from_address'      => 'WooCommerce → Settings → Emails → "From" address',
		'woocommerce_stock_email_recipient'   => 'WooCommerce → Settings → Products → Inventory → Notification recipient',
		'woocommerce_new_order_settings'      => 'WooCommerce → Settings → Emails → New order → Recipient',
		'woocommerce_cancelled_order_settings' => 'WooCommerce → Settings → Emails → Cancelled order → Recipient',
		'woocommerce_failed_order_settings'   => 'WooCommerce → Settings → Emails → Failed order → Recipient',
		'wp_mail_smtp'                        => 'WP Mail SMTP → Settings → From Email',
		'options_notification_email'          => 'SOL Site Settings → Notification email',
		'options_email'                       => 'SOL Site Settings → Email',
	);
	return isset( $known[ $name ] ) ? $known[ $name ] : 'Saved setting "' . $name . '"';
}

/** Saved options and user accounts that contain a retired address. */
function sol_mail_retired_places() {
	global $wpdb;
	$options = array();
	$users   = array();
	foreach ( sol_mail_retired() as $addr ) {
		$like = '%' . $wpdb->esc_like( $addr ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_col( $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_value LIKE %s AND option_name NOT LIKE %s AND option_name NOT LIKE %s AND option_name NOT LIKE %s",
			$like,
			$wpdb->esc_like( '_transient_' ) . '%',
			$wpdb->esc_like( '_site_transient_' ) . '%',
			$wpdb->esc_like( 'sol_mail_last_error' )
		) );
		$options = array_merge( $options, $rows );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$found = $wpdb->get_results( $wpdb->prepare( "SELECT ID, user_login FROM {$wpdb->users} WHERE user_email LIKE %s", $like ) );
		foreach ( $found as $u ) {
			$users[ (int) $u->ID ] = $u->user_login;
		}
	}
	// Only settings the swap would change: a connected mail account's login details are left as they are
	// (that case gets its own warning on the page).
	$options = array_filter( array_unique( $options ), function ( $name ) {
		if ( in_array( $name, array( 'new_admin_email', 'adminhash' ), true ) ) {
			return true;
		}
		$v = get_option( $name );
		return sol_mail_swap_deep( $v ) !== $v;
	} );
	return array( 'options' => array_values( $options ), 'users' => $users );
}

/** True when WP Mail SMTP sends through a connected Gmail account (then Google decides the real sender). */
function sol_mail_uses_gmail_mailer() {
	$o = get_option( 'wp_mail_smtp' );
	return is_array( $o ) && isset( $o['mail']['mailer'] ) && in_array( $o['mail']['mailer'], array( 'gmail', 'google' ), true );
}

add_action( 'admin_post_sol_mail_retire', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_mail_retire' );
	$places  = sol_mail_retired_places();
	$changed = 0;
	$skipped = array();
	foreach ( $places['options'] as $name ) {
		if ( in_array( $name, array( 'new_admin_email', 'adminhash' ), true ) ) {
			delete_option( 'new_admin_email' ); // a pending change to the old address: cancel it
			delete_option( 'adminhash' );
			$changed++;
			continue;
		}
		$old = get_option( $name );
		$new = sol_mail_swap_deep( $old );
		if ( $new !== $old && update_option( $name, $new ) ) {
			$changed++;
		}
	}
	add_filter( 'send_email_change_email', '__return_false' ); // don't email the old address about the change
	foreach ( $places['users'] as $id => $login ) {
		$owner = get_user_by( 'email', sol_mail_main() );
		if ( $owner && (int) $owner->ID !== (int) $id ) {
			$skipped[] = $login; // info@ already belongs to another account
			continue;
		}
		$r = wp_update_user( array( 'ID' => $id, 'user_email' => sol_mail_main() ) );
		if ( is_wp_error( $r ) ) {
			$skipped[] = $login;
		} else {
			$changed++;
		}
	}
	set_transient( 'sol_mail_retire_note', array( 'changed' => $changed, 'skipped' => $skipped ), 300 );
	wp_safe_redirect( admin_url( 'admin.php?page=' . SOL_MAIL_PAGE . '&sol_retired=1#sol-mail-identity' ) );
	exit;
} );

/** The "One email address" section on SOL Site → Email. */
function sol_mail_identity_section() {
	$places = sol_mail_retired_places();
	$note   = get_transient( 'sol_mail_retire_note' );
	$total  = count( $places['options'] ) + count( $places['users'] );
	?>
	<h2 id="sol-mail-identity" style="margin-top:32px">One email address: <?php echo esc_html( sol_mail_main() ); ?></h2>
	<p>Every email the site sends goes out from <code><?php echo esc_html( sol_mail_main() ); ?></code>, and anything addressed to <code><?php echo esc_html( implode( ', ', sol_mail_retired() ) ); ?></code> is delivered to <code><?php echo esc_html( sol_mail_main() ); ?></code> instead. Blog comments are switched off, so there are no more "Please moderate" emails.</p>
	<?php if ( ! empty( $_GET['sol_retired'] ) && is_array( $note ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success inline"><p><?php echo esc_html( sprintf( '%d setting(s) updated to %s.', (int) $note['changed'], sol_mail_main() ) ); ?>
		<?php if ( $note['skipped'] ) : ?><br><strong>Not changed:</strong> user account(s) <?php echo esc_html( implode( ', ', $note['skipped'] ) ); ?>, because <?php echo esc_html( sol_mail_main() ); ?> already belongs to another account. Change or delete those accounts in Users.<?php endif; ?></p></div>
		<?php delete_transient( 'sol_mail_retire_note' ); ?>
	<?php endif; ?>
	<?php if ( sol_mail_uses_gmail_mailer() ) : ?>
		<div class="notice notice-warning inline"><p><strong>WP Mail SMTP is connected to a Gmail account.</strong> Google sends every email as that Gmail account, whatever From address is set. To send as <?php echo esc_html( sol_mail_main() ); ?>, switch the mailer to Brevo in WP Mail SMTP (see "Make emails arrive reliably" above).</p></div>
	<?php endif; ?>
	<?php if ( $total ) : ?>
		<p><strong>Still saved with the old address</strong> (emails are already redirected, but these settings should be updated):</p>
		<ul style="list-style:disc;padding-left:20px">
			<?php foreach ( $places['options'] as $name ) : ?><li><?php echo esc_html( sol_mail_option_label( $name ) ); ?></li><?php endforeach; ?>
			<?php foreach ( $places['users'] as $id => $login ) : ?><li>User account <a href="<?php echo esc_url( get_edit_user_link( $id ) ); ?>"><?php echo esc_html( $login ); ?></a> (login email)</li><?php endforeach; ?>
		</ul>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Replace the old address with <?php echo esc_js( sol_mail_main() ); ?> in all <?php echo (int) $total; ?> place(s)? User accounts listed will log in with <?php echo esc_js( sol_mail_main() ); ?> (or their username) from now on.');">
			<input type="hidden" name="action" value="sol_mail_retire">
			<?php wp_nonce_field( 'sol_mail_retire' ); ?>
			<?php submit_button( 'Replace with ' . sol_mail_main() . ' everywhere', 'primary', 'submit', false ); ?>
		</form>
	<?php else : ?>
		<p style="color:#008a20">● No saved setting uses the old address.</p>
	<?php endif; ?>
	<?php
}
