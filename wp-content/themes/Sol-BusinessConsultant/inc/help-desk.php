<?php
/**
 * Help desk (v2.26.0).
 *
 * 1. Support line: 0414 395 319, shown in the header top bar, the mobile menu and the footer.
 *    Change it with the `sol_support_phone` option (or the filter of the same name).
 * 2. Help Desk for the support team: solbusinessconsultant.com.au/help-desk/ opens the Live Chat inbox
 *    (after signing in). A "Support Agent" role can use the help desk and nothing else in WordPress admin.
 *    The inbox shows a summary: chats waiting, active chats, chats today, team online and the latest
 *    website enquiries.
 * 3. Email replies: team replies are emailed to the visitor when they have left the chat, and the team can
 *    send an email reply from the inbox at any time (also after the chat has ended). Each email is recorded
 *    in the chat. Visitors reply to the email and it lands in the team inbox.
 * 4. Easy Compliance has its own address, /easy-compliance/, instead of "/#compliance".
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ */
/* Support line                                                        */
/* ------------------------------------------------------------------ */

function sol_support_phone() {
	return (string) apply_filters( 'sol_support_phone', get_option( 'sol_support_phone', '0414 395 319' ) );
}

/** tel: link for an Australian number ("0414 395 319" → "+61414395319"). */
function sol_tel( $number ) {
	$n = preg_replace( '/[^0-9+]/', '', (string) $number );
	return preg_match( '/^0\d{9}$/', $n ) ? '+61' . substr( $n, 1 ) : $n;
}

/* ------------------------------------------------------------------ */
/* Who can use the help desk                                           */
/* ------------------------------------------------------------------ */

const SOL_HELPDESK_CAP = 'sol_help_desk';

// Admins and editors keep access; the new Support Agent role gets the help desk only.
add_action( 'init', function () {
	if ( '1' === get_option( 'sol_help_desk_roles' ) ) {
		return;
	}
	foreach ( array( 'administrator', 'editor' ) as $r ) {
		$role = get_role( $r );
		if ( $role ) {
			$role->add_cap( SOL_HELPDESK_CAP );
		}
	}
	if ( ! get_role( 'sol_support_agent' ) ) {
		add_role( 'sol_support_agent', 'Support Agent', array( 'read' => true, SOL_HELPDESK_CAP => true ) );
	}
	update_option( 'sol_help_desk_roles', '1', true );
}, 5 );

add_filter( 'sol_live_agent_cap', function () {
	return SOL_HELPDESK_CAP;
} );

function sol_help_desk_url() {
	return admin_url( 'admin.php?page=' . SOL_LIVE_PAGE );
}

// solbusinessconsultant.com.au/help-desk/ → sign in → the inbox.
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( 'help-desk' !== $path || ! is_404() ) {
		return;
	}
	nocache_headers();
	wp_safe_redirect( is_user_logged_in() ? sol_help_desk_url() : wp_login_url( sol_help_desk_url() ) );
	exit;
}, 1 );

// Support agents land on the help desk after signing in, and don't see the rest of admin.
add_filter( 'login_redirect', function ( $to, $requested, $user ) {
	if ( $user instanceof WP_User && in_array( 'sol_support_agent', (array) $user->roles, true ) ) {
		return sol_help_desk_url();
	}
	return $to;
}, 10, 3 );

add_action( 'admin_menu', function () {
	if ( ! current_user_can( SOL_HELPDESK_CAP ) || current_user_can( 'edit_posts' ) ) {
		return;
	}
	remove_menu_page( 'index.php' );
}, 999 );

add_action( 'admin_init', function () {
	global $pagenow;
	if ( wp_doing_ajax() || ! current_user_can( SOL_HELPDESK_CAP ) || current_user_can( 'edit_posts' ) ) {
		return;
	}
	if ( 'index.php' === $pagenow ) {
		wp_safe_redirect( sol_help_desk_url() );
		exit;
	}
} );

/* ------------------------------------------------------------------ */
/* Help desk summary (top of the inbox)                                */
/* ------------------------------------------------------------------ */

function sol_help_desk_count( $meta = array(), $after = 0 ) {
	$args = array(
		'post_type'      => 'sol_chat',
		'post_status'    => 'private',
		'fields'         => 'ids',
		'posts_per_page' => 1,
	);
	if ( $meta ) {
		$args['meta_query'] = array( $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	if ( $after ) {
		$args['date_query'] = array( array( 'after' => gmdate( 'Y-m-d H:i:s', $after ), 'column' => 'post_date_gmt' ) );
	}
	$q = new WP_Query( $args );
	return (int) $q->found_posts;
}

function sol_help_desk_summary() {
	$today = strtotime( wp_date( 'Y-m-d 00:00:00' ) . ' ' . wp_timezone_string() );
	$cards = array(
		array( 'Waiting for a reply', sol_help_desk_count( array( 'key' => '_sc_status', 'value' => 'waiting' ) ), 'is-warn' ),
		array( 'Active chats', sol_help_desk_count( array( 'key' => '_sc_status', 'value' => 'live' ) ), '' ),
		array( 'Chats today', sol_help_desk_count( array(), $today ), '' ),
		array( 'Last 7 days', sol_help_desk_count( array(), time() - 7 * DAY_IN_SECONDS ), '' ),
		array( 'Team online', count( sol_live_agents_online() ), '' ),
	);
	$enq = get_posts( array( 'post_type' => 'sol_enquiry', 'post_status' => 'any', 'posts_per_page' => 5 ) );
	?>
	<div class="sol-hd">
		<div class="sol-hd__cards">
			<?php foreach ( $cards as $c ) : ?>
				<div class="sol-hd__card <?php echo esc_attr( $c[2] ); ?>"><strong><?php echo (int) $c[1]; ?></strong><span><?php echo esc_html( $c[0] ); ?></span></div>
			<?php endforeach; ?>
			<div class="sol-hd__card sol-hd__card--info">
				<span>Support line</span><strong class="sol-hd__phone"><?php echo esc_html( sol_support_phone() ); ?></strong>
				<span>Team inbox: <?php echo esc_html( sol_opt( 'notification_email', get_option( 'admin_email' ) ) ); ?></span>
			</div>
		</div>
		<?php if ( $enq ) : ?>
			<details class="sol-hd__enq">
				<summary>Latest website enquiries</summary>
				<table class="widefat striped">
					<thead><tr><th>Received</th><th>Enquiry</th><th>About</th></tr></thead>
					<tbody>
						<?php foreach ( $enq as $e ) : ?>
							<tr>
								<td><?php echo esc_html( get_the_date( 'j M, g:i a', $e ) ); ?></td>
								<td><?php echo current_user_can( 'edit_post', $e->ID ) ? '<a href="' . esc_url( get_edit_post_link( $e->ID ) ) . '">' . esc_html( $e->post_title ) . '</a>' : esc_html( $e->post_title ); ?></td>
								<td><?php echo esc_html( (string) get_post_meta( $e->ID, 'selection', true ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</details>
		<?php endif; ?>
	</div>
	<?php
}

/* ------------------------------------------------------------------ */
/* Email replies                                                       */
/* ------------------------------------------------------------------ */

/**
 * Email the visitor the team's replies they haven't seen in the chat.
 * $force: send the given message IDs even if the visitor is still on the page.
 */
function sol_live_email_replies( $chat_id, $force = array() ) {
	$chat  = get_post( $chat_id );
	$email = (string) get_post_meta( $chat_id, '_sc_email', true );
	if ( ! $chat || ! is_email( $email ) ) {
		return false;
	}
	$sent  = (int) get_post_meta( $chat_id, '_sc_emailed', true );
	$seen  = (int) get_post_meta( $chat_id, '_sc_vseen', true );
	$msgs  = array();
	$last  = $sent;
	foreach ( sol_live_msgs( $chat_id, $sent ) as $m ) {
		if ( 'agent' === $m['role'] && ( in_array( $m['id'], $force, true ) || $m['at'] > $seen ) ) {
			$msgs[] = $m;
			$last   = max( $last, $m['id'] );
		}
	}
	if ( ! $msgs ) {
		return false;
	}
	$team = sol_opt( 'notification_email', get_option( 'admin_email' ) );
	$ok   = sol_email_send_template( 'live_chat_reply', $email, array(
		'name'     => $chat->post_title,
		'messages' => wp_list_pluck( $msgs, 'text' ),
		'agent'    => end( $msgs )['name'],
	), array( 'Reply-To: ' . $team ) );
	update_post_meta( $chat_id, '_sc_emailed', $last );
	sol_live_add( $chat_id, 'note', $ok ? sprintf( '%s emailed to %s.', 1 === count( $msgs ) ? 'Reply' : count( $msgs ) . ' replies', $email ) : 'The email to ' . $email . ' could not be sent.' );
	return $ok;
}

// A reply the visitor doesn't read within 2 minutes is emailed to them (several replies go in one email).
add_action( 'sol_live_email_check', function ( $chat_id ) {
	sol_live_email_replies( (int) $chat_id );
} );

function sol_live_schedule_email( $chat_id ) {
	if ( ! wp_next_scheduled( 'sol_live_email_check', array( (int) $chat_id ) ) ) {
		wp_schedule_single_event( time() + 2 * MINUTE_IN_SECONDS, 'sol_live_email_check', array( (int) $chat_id ) );
	}
}

/* ------------------------------------------------------------------ */
/* Easy Compliance address                                             */
/* ------------------------------------------------------------------ */

/** "/#compliance", "/about/#compliance" or "/#compliance-…" → the Easy Compliance page, once it exists. */
function sol_compliance_link( $url ) {
	static $page = null;
	$home = untrailingslashit( home_url() );
	$path = 0 === strpos( (string) $url, $home ) ? substr( $url, strlen( $home ) ) : (string) $url;
	if ( ! preg_match( '~^(?:/about)?/?#(compliance(?:-[a-z0-9-]+)?)$~', $path, $m ) ) {
		return $url;
	}
	if ( null === $page ) {
		$p    = get_page_by_path( 'easy-compliance' );
		$page = $p && 'publish' === $p->post_status ? get_permalink( $p ) : '';
	}
	if ( ! $page ) {
		return $url;
	}
	return 'compliance' === $m[1] ? $page : $page . '#' . $m[1];
}
