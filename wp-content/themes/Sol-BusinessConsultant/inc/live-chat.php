<?php
/**
 * Live chat with the SOL team, plus booking a consultation from the chat.
 *
 * The AI answers first. When a visitor asks for a person (or the AI can't help), the widget
 * starts a live chat: the conversation so far is saved, the team is emailed, and team members
 * reply from WordPress admin → Live Chat. Both sides poll for new messages every few seconds.
 *
 * Storage: one private `sol_chat` post per conversation (status in meta) and its messages in
 * the {prefix}sol_chat_msgs table, so two people writing at once never overwrite each other.
 * Team members are users who can edit others' posts (editors and admins; filter
 * `sol_live_agent_cap`). They count as online while the inbox or any admin page is open.
 */

defined( 'ABSPATH' ) || exit;

define( 'SOL_LIVE_PAGE', 'sol-live-chat' );

/** Live chat is on when the AI chat is showing and it isn't switched off under SOL Site → AI Chat. */
function sol_live_enabled() {
	return sol_chat_enabled() && '0' !== (string) get_option( 'sol_live_on', '1' );
}

function sol_live_agent_cap() {
	return apply_filters( 'sol_live_agent_cap', 'edit_others_posts' );
}

function sol_live_is_agent() {
	return is_user_logged_in() && current_user_can( sol_live_agent_cap() );
}

/* ---------- Storage ---------- */

function sol_live_table() {
	global $wpdb;
	return $wpdb->prefix . 'sol_chat_msgs';
}

add_action( 'init', function () {
	register_post_type( 'sol_chat', array(
		'labels'              => array( 'name' => 'Live Chats', 'singular_name' => 'Live Chat' ),
		'public'              => false,
		'show_ui'             => false,
		'exclude_from_search' => true,
		'supports'            => array( 'title' ),
	) );

	if ( '1' !== get_option( 'sol_live_db' ) ) {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( 'CREATE TABLE ' . sol_live_table() . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			chat_id bigint(20) unsigned NOT NULL,
			role varchar(10) NOT NULL,
			name varchar(100) NOT NULL DEFAULT '',
			body text NOT NULL,
			created int(10) unsigned NOT NULL,
			PRIMARY KEY  (id),
			KEY chat_id (chat_id,id)
		) " . $wpdb->get_charset_collate() . ';' );
		update_option( 'sol_live_db', '1', true );
	}
} );

/** Add a message. Roles: visitor, agent, bot (AI, before the handover), note (system line). */
function sol_live_add( $chat_id, $role, $body, $name = '' ) {
	global $wpdb;
	$body = trim( (string) $body );
	if ( '' === $body ) {
		return 0;
	}
	$wpdb->insert( sol_live_table(), array( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		'chat_id' => (int) $chat_id,
		'role'    => $role,
		'name'    => mb_substr( (string) $name, 0, 100 ),
		'body'    => mb_substr( $body, 0, 4000 ),
		'created' => time(),
	) );
	$id = (int) $wpdb->insert_id;
	update_post_meta( $chat_id, '_sc_last_at', time() );
	return $id;
}

/** Messages after a given ID, oldest first. */
function sol_live_msgs( $chat_id, $after = 0, $limit = 300 ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, role, name, body, created FROM ' . sol_live_table() . ' WHERE chat_id = %d AND id > %d ORDER BY id ASC LIMIT %d', $chat_id, $after, $limit ), ARRAY_A ); // phpcs:ignore
	return array_map( function ( $r ) {
		return array( 'id' => (int) $r['id'], 'role' => $r['role'], 'name' => $r['name'], 'text' => $r['body'], 'at' => (int) $r['created'] );
	}, (array) $rows );
}

function sol_live_status( $chat_id ) {
	$s = get_post_meta( $chat_id, '_sc_status', true );
	return $s ? $s : 'closed';
}

/** What the visitor sees of a team member: first name and avatar. */
function sol_live_agent_public( $user_id ) {
	$u = $user_id ? get_userdata( $user_id ) : null;
	if ( ! $u ) {
		return null;
	}
	$name = $u->first_name ? $u->first_name : $u->display_name;
	return array( 'name' => $name, 'avatar' => get_avatar_url( $u->ID, array( 'size' => 72 ) ) );
}

/** Team members seen in admin in the last 2 minutes who haven't set themselves as away. */
function sol_live_agents_online() {
	$cached = get_transient( 'sol_live_online' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$users = get_users( array(
		'meta_key'     => '_sc_seen', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'   => time() - 120, // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_compare' => '>',
		'meta_type'    => 'NUMERIC',
		'number'       => 20,
	) );
	$out = array();
	foreach ( $users as $u ) {
		if ( user_can( $u, sol_live_agent_cap() ) && '1' !== get_user_meta( $u->ID, '_sc_away', true ) ) {
			$out[] = sol_live_agent_public( $u->ID );
		}
	}
	set_transient( 'sol_live_online', $out, 15 );
	return $out;
}

/** Mark the current team member as active. */
function sol_live_seen() {
	$uid = get_current_user_id();
	if ( $uid && time() - (int) get_user_meta( $uid, '_sc_seen', true ) > 20 ) {
		update_user_meta( $uid, '_sc_seen', time() );
		delete_transient( 'sol_live_online' );
	}
}

function sol_live_waiting_count() {
	$q = new WP_Query( array(
		'post_type'      => 'sol_chat',
		'post_status'    => 'private',
		'fields'         => 'ids',
		'posts_per_page' => 50,
		'no_found_rows'  => true,
		'meta_query'     => array( array( 'key' => '_sc_status', 'value' => 'waiting' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	return count( $q->posts );
}

/* ---------- Shared request checks ---------- */

function sol_live_same_origin( WP_REST_Request $req ) {
	$origin = $req->get_header( 'origin' );
	return ! $origin || wp_parse_url( $origin, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST );
}

/** Count a hit against a limit. Returns false when the limit is already reached. */
function sol_live_limit( $key, $max, $seconds ) {
	$k    = 'sol_lc_' . md5( $key );
	$hits = (int) get_transient( $k );
	if ( $hits >= $max ) {
		return false;
	}
	set_transient( $k, $hits + 1, $seconds );
	return true;
}

function sol_live_error( $message, $status ) {
	return new WP_REST_Response( array( 'message' => $message ), $status );
}

/** The visitor's chat, if the id and secret token match. */
function sol_live_visitor_chat( WP_REST_Request $req ) {
	$id    = (int) $req->get_param( 'id' );
	$token = (string) $req->get_param( 'token' );
	$post  = $id ? get_post( $id ) : null;
	if ( ! $post || 'sol_chat' !== $post->post_type || strlen( $token ) < 20 ) {
		return null;
	}
	$hash = (string) get_post_meta( $id, '_sc_token', true );
	return ( $hash && hash_equals( $hash, hash( 'sha256', $token ) ) ) ? $post : null;
}

/** A page URL on this site, or ''. */
function sol_live_page_url( $url ) {
	$url = esc_url_raw( (string) $url );
	return ( $url && wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) ? $url : '';
}

/* ---------- REST routes ---------- */

add_action( 'rest_api_init', function () {
	$public = function () {
		return true; // public chat; checked by origin, secret token and rate limits
	};
	$agent = function () {
		return sol_live_is_agent();
	};
	$routes = array(
		'/live/status'             => array( 'sol_live_r_status', $public ),
		'/live/start'              => array( 'sol_live_r_start', $public ),
		'/live/poll'               => array( 'sol_live_r_poll', $public ),
		'/live/send'               => array( 'sol_live_r_send', $public ),
		'/live/end'                => array( 'sol_live_r_end', $public ),
		'/chat/book'               => array( 'sol_live_r_book', $public ),
		'/live/agent/chats'        => array( 'sol_live_r_agent_chats', $agent ),
		'/live/agent/chat'         => array( 'sol_live_r_agent_chat', $agent ),
		'/live/agent/send'         => array( 'sol_live_r_agent_send', $agent ),
		'/live/agent/join'         => array( 'sol_live_r_agent_join', $agent ),
		'/live/agent/close'        => array( 'sol_live_r_agent_close', $agent ),
		'/live/agent/away'         => array( 'sol_live_r_agent_away', $agent ),
	);
	foreach ( $routes as $path => $r ) {
		register_rest_route( 'sol/v1', $path, array(
			'methods'             => 'POST',
			'callback'            => $r[0],
			'permission_callback' => $r[1],
		) );
	}
} );

// Never cache chat replies.
add_filter( 'rest_post_dispatch', function ( $res, $server, $req ) {
	if ( 0 === strpos( $req->get_route(), '/sol/v1/live' ) || '/sol/v1/chat/book' === $req->get_route() ) {
		$res->header( 'Cache-Control', 'no-store, private' );
	}
	return $res;
}, 10, 3 );

/* ---------- Visitor side ---------- */

function sol_live_r_status( WP_REST_Request $req ) {
	if ( ! sol_live_enabled() ) {
		return sol_live_error( 'Live chat is not available.', 503 );
	}
	$agents = sol_live_agents_online();
	return new WP_REST_Response( array(
		'online' => (bool) $agents,
		'agents' => array_slice( $agents, 0, 3 ),
		'hours'  => sol_opt( 'business_hours', 'Monday-Friday: 9:00 AM - 5:00 PM AEST' ),
	), 200 );
}

function sol_live_r_start( WP_REST_Request $req ) {
	if ( ! sol_live_enabled() ) {
		return sol_live_error( 'Live chat is not available right now. Please call us on ' . sol_opt( 'phone', '+61 460 003 494' ) . '.', 503 );
	}
	if ( ! sol_live_same_origin( $req ) ) {
		return sol_live_error( 'Not allowed.', 403 );
	}
	$name    = mb_substr( sanitize_text_field( (string) $req->get_param( 'name' ) ), 0, 80 );
	$email   = sanitize_email( (string) $req->get_param( 'email' ) );
	$phone   = mb_substr( sanitize_text_field( (string) $req->get_param( 'phone' ) ), 0, 30 );
	$message = mb_substr( sanitize_textarea_field( (string) $req->get_param( 'message' ) ), 0, 2000 );
	if ( mb_strlen( $name ) < 2 || preg_match( '#https?://|www\.#i', $name ) ) {
		return sol_live_error( 'Please enter your name.', 400 );
	}
	if ( ! is_email( $email ) ) {
		return sol_live_error( 'Please enter a valid email address.', 400 );
	}
	if ( '' !== $phone && ( ! preg_match( '/^[0-9 +()\-]+$/', $phone ) || strlen( preg_replace( '/\D/', '', $phone ) ) < 8 ) ) {
		return sol_live_error( 'Please enter a valid phone number, or leave it empty.', 400 );
	}
	if ( ! sol_live_limit( 'start' . sol_visitor_ip(), 4, HOUR_IN_SECONDS ) || ! sol_live_limit( 'start_site' . gmdate( 'Ymd' ), 300, DAY_IN_SECONDS ) ) {
		return sol_live_error( 'Too many chat requests. Please try again later or call us on ' . sol_opt( 'phone', '+61 460 003 494' ) . '.', 429 );
	}

	$offline = ! sol_live_agents_online();
	$token   = wp_generate_password( 40, false, false );
	$id      = wp_insert_post( array(
		'post_type'   => 'sol_chat',
		'post_status' => 'private',
		'post_title'  => $name,
	) );
	if ( ! $id || is_wp_error( $id ) ) {
		return sol_live_error( 'Sorry, we could not start the chat. Please call us on ' . sol_opt( 'phone', '+61 460 003 494' ) . '.', 500 );
	}
	$page = sol_live_page_url( $req->get_param( 'page' ) );
	update_post_meta( $id, '_sc_token', hash( 'sha256', $token ) );
	update_post_meta( $id, '_sc_status', 'waiting' );
	update_post_meta( $id, '_sc_email', $email );
	update_post_meta( $id, '_sc_phone', $phone );
	update_post_meta( $id, '_sc_page', $page );
	update_post_meta( $id, '_sc_offline', $offline ? '1' : '0' );
	update_post_meta( $id, '_sc_vseen', time() );

	// The AI conversation so far, so the team has the context.
	$history = $req->get_param( 'transcript' );
	foreach ( array_slice( is_array( $history ) ? $history : array(), -16 ) as $m ) {
		$role = isset( $m['role'] ) && 'user' === $m['role'] ? 'visitor' : 'bot';
		$text = isset( $m['content'] ) ? mb_substr( trim( wp_strip_all_tags( (string) $m['content'] ) ), 0, 2000 ) : '';
		sol_live_add( $id, $role, $text, 'visitor' === $role ? $name : sol_opt( 'chat_name', 'SOL Assistant' ) );
	}
	sol_live_add( $id, 'note', $offline ? $name . ' left a message while the team was offline.' : $name . ' asked to talk to the team.' );
	if ( '' !== $message ) {
		sol_live_add( $id, 'visitor', $message, $name );
	}
	delete_transient( 'sol_live_waiting' );

	$to = sol_opt( 'notification_email', get_option( 'admin_email' ) );
	sol_email_send_template( 'live_chat_request', $to, array(
		'name'    => $name,
		'email'   => $email,
		'phone'   => $phone,
		'message' => $message,
		'page'    => $page,
		'offline' => $offline,
		'url'     => admin_url( 'admin.php?page=' . SOL_LIVE_PAGE . '&chat=' . $id ),
	), array( 'Reply-To: "' . str_replace( array( '"', chr( 92 ) ), '', $name ) . '" <' . $email . '>' ) );

	return new WP_REST_Response( array(
		'id'       => (int) $id,
		'token'    => $token,
		'status'   => 'waiting',
		'offline'  => $offline,
		'messages' => sol_live_msgs( $id ),
	), 200 );
}

function sol_live_r_poll( WP_REST_Request $req ) {
	$chat = sol_live_visitor_chat( $req );
	if ( ! $chat ) {
		return sol_live_error( 'This chat has ended.', 404 );
	}
	$id = $chat->ID;
	update_post_meta( $id, '_sc_vseen', time() );
	if ( $req->get_param( 'typing' ) ) {
		update_post_meta( $id, '_sc_vtyping', time() );
	}
	$agent = (int) get_post_meta( $id, '_sc_agent', true );
	return new WP_REST_Response( array(
		'status'   => sol_live_status( $id ),
		'offline'  => '1' === get_post_meta( $id, '_sc_offline', true ),
		'agent'    => sol_live_agent_public( $agent ),
		'typing'   => time() - (int) get_post_meta( $id, '_sc_atyping', true ) < 6,
		'messages' => sol_live_msgs( $id, max( 0, (int) $req->get_param( 'after' ) ) ),
	), 200 );
}

function sol_live_r_send( WP_REST_Request $req ) {
	$chat = sol_live_visitor_chat( $req );
	if ( ! $chat ) {
		return sol_live_error( 'This chat has ended.', 404 );
	}
	if ( 'closed' === sol_live_status( $chat->ID ) ) {
		return sol_live_error( 'This chat has ended.', 409 );
	}
	$text = mb_substr( sanitize_textarea_field( (string) $req->get_param( 'text' ) ), 0, 2000 );
	if ( '' === trim( $text ) ) {
		return sol_live_error( 'Please type a message.', 400 );
	}
	if ( ! sol_live_limit( 'send' . $chat->ID, 40, 10 * MINUTE_IN_SECONDS ) ) {
		return sol_live_error( 'You are sending messages too quickly. Please wait a moment.', 429 );
	}
	$mid = sol_live_add( $chat->ID, 'visitor', $text, $chat->post_title );
	delete_post_meta( $chat->ID, '_sc_vtyping' );
	update_post_meta( $chat->ID, '_sc_vseen', time() );
	return new WP_REST_Response( array( 'id' => $mid ), 200 );
}

function sol_live_r_end( WP_REST_Request $req ) {
	$chat = sol_live_visitor_chat( $req );
	if ( $chat && 'closed' !== sol_live_status( $chat->ID ) ) {
		update_post_meta( $chat->ID, '_sc_status', 'closed' );
		sol_live_add( $chat->ID, 'note', $chat->post_title . ' ended the chat.' );
		delete_transient( 'sol_live_waiting' );
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/** Book a consultation from the chat's booking card. Saved and emailed like the Book Consultation form. */
function sol_live_r_book( WP_REST_Request $req ) {
	if ( ! sol_chat_enabled() ) {
		return sol_live_error( 'Booking from the chat is not available right now.', 503 );
	}
	if ( ! sol_live_same_origin( $req ) ) {
		return sol_live_error( 'Not allowed.', 403 );
	}
	$p = function ( $k, $max = 200 ) use ( $req ) {
		return mb_substr( sanitize_text_field( (string) $req->get_param( $k ) ), 0, $max );
	};
	$data = array(
		'name'           => $p( 'name', 100 ),
		'email'          => sanitize_email( (string) $req->get_param( 'email' ) ),
		'phone'          => $p( 'phone', 30 ),
		'preferred_date' => $p( 'date', 10 ),
		'preferred_time' => $p( 'time', 60 ),
		'message'        => mb_substr( sanitize_textarea_field( (string) $req->get_param( 'notes' ) ), 0, 1000 ),
	);
	$services = sol_service_options();
	$service  = $p( 'service', 60 );
	if ( mb_strlen( $data['name'] ) < 2 || preg_match( '#https?://|www\.#i', $data['name'] ) ) {
		return sol_live_error( 'Please enter your full name.', 400 );
	}
	if ( ! is_email( $data['email'] ) ) {
		return sol_live_error( 'Please enter a valid email address.', 400 );
	}
	if ( '' !== $data['phone'] && ( ! preg_match( '/^[0-9 +()\-]+$/', $data['phone'] ) || strlen( preg_replace( '/\D/', '', $data['phone'] ) ) < 8 ) ) {
		return sol_live_error( 'Please enter a valid phone number (at least 8 digits).', 400 );
	}
	if ( ! isset( $services[ $service ] ) && 'general_enquiry' !== $service ) {
		return sol_live_error( 'Please choose a service.', 400 );
	}
	if ( '' !== $data['preferred_date'] ) {
		$day = DateTime::createFromFormat( '!Y-m-d', $data['preferred_date'], wp_timezone() );
		if ( ! $day || $day->format( 'Y-m-d' ) !== $data['preferred_date'] || $data['preferred_date'] < wp_date( 'Y-m-d' ) ) {
			return sol_live_error( 'Please choose a date from today onwards.', 400 );
		}
	}
	if ( ! in_array( $data['preferred_time'], sol_live_times(), true ) ) {
		$data['preferred_time'] = '';
	}
	if ( preg_match_all( '#https?://|www\.#i', $data['message'] ) > 2 ) {
		return sol_live_error( 'Please remove some of the web links from your notes.', 400 );
	}
	if ( ! sol_live_limit( 'book' . sol_visitor_ip(), 3, HOUR_IN_SECONDS ) ) {
		return sol_live_error( 'Too many booking requests. Please try again later or call us.', 429 );
	}
	$data['selection'] = isset( $services[ $service ] ) ? $services[ $service ] : 'Other / Multiple Services';
	$data['message']   = trim( $data['message'] . "\n\n(Booked through the website chat.)" );

	$page = sol_live_page_url( $req->get_param( 'page' ) );
	sol_save_enquiry( 'consultation', $data, $page ? $page : home_url( '/' ) );

	$first = strtok( $data['name'], ' ' );
	return new WP_REST_Response( array(
		'message' => sprintf( 'Thanks %s, your consultation request for **%s** is in. We\'ve emailed you a confirmation and the team will confirm a time within one business day.', $first, $data['selection'] ),
	), 200 );
}

/** Preferred time choices, the same as the Book Consultation form. */
function sol_live_times() {
	return array( 'Morning (9am–12pm)', 'Afternoon (12pm–3pm)', 'Late afternoon (3pm–5pm)' );
}

/* ---------- Team side ---------- */

function sol_live_agent_chat_or_error( WP_REST_Request $req ) {
	$post = get_post( (int) $req->get_param( 'id' ) );
	return ( $post && 'sol_chat' === $post->post_type ) ? $post : null;
}

/** One chat as the inbox lists it. */
function sol_live_summary( $post ) {
	global $wpdb;
	$id    = $post->ID;
	$read  = (int) get_post_meta( $id, '_sc_read', true );
	$last  = $wpdb->get_row( $wpdb->prepare( 'SELECT role, body, created FROM ' . sol_live_table() . " WHERE chat_id = %d AND role <> 'note' ORDER BY id DESC LIMIT 1", $id ), ARRAY_A ); // phpcs:ignore
	$new   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . sol_live_table() . " WHERE chat_id = %d AND role = 'visitor' AND id > %d", $id, $read ) ); // phpcs:ignore
	$agent = (int) get_post_meta( $id, '_sc_agent', true );
	$au    = $agent ? get_userdata( $agent ) : null;
	return array(
		'id'      => $id,
		'name'    => $post->post_title,
		'email'   => (string) get_post_meta( $id, '_sc_email', true ),
		'phone'   => (string) get_post_meta( $id, '_sc_phone', true ),
		'page'    => (string) get_post_meta( $id, '_sc_page', true ),
		'status'  => sol_live_status( $id ),
		'offline' => '1' === get_post_meta( $id, '_sc_offline', true ),
		'agent'   => $au ? array( 'id' => $agent, 'name' => $au->display_name ) : null,
		'here'    => time() - (int) get_post_meta( $id, '_sc_vseen', true ) < 30,
		'unread'  => $new,
		'started' => get_post_time( 'U', true, $post ),
		'last'    => $last ? array( 'role' => $last['role'], 'text' => mb_substr( $last['body'], 0, 90 ), 'at' => (int) $last['created'] ) : null,
	);
}

function sol_live_r_agent_chats( WP_REST_Request $req ) {
	sol_live_seen();
	$posts = get_posts( array(
		'post_type'      => 'sol_chat',
		'post_status'    => 'private',
		'posts_per_page' => 60,
		'meta_key'       => '_sc_last_at', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
	) );
	$uid = get_current_user_id();
	return new WP_REST_Response( array(
		'chats'  => array_map( 'sol_live_summary', $posts ),
		'away'   => '1' === get_user_meta( $uid, '_sc_away', true ),
		'online' => count( sol_live_agents_online() ),
	), 200 );
}

function sol_live_r_agent_chat( WP_REST_Request $req ) {
	$chat = sol_live_agent_chat_or_error( $req );
	if ( ! $chat ) {
		return sol_live_error( 'Chat not found.', 404 );
	}
	sol_live_seen();
	if ( $req->get_param( 'typing' ) ) {
		update_post_meta( $chat->ID, '_sc_atyping', time() );
	}
	$msgs = sol_live_msgs( $chat->ID, max( 0, (int) $req->get_param( 'after' ) ) );
	if ( $msgs ) {
		update_post_meta( $chat->ID, '_sc_read', end( $msgs )['id'] );
	}
	return new WP_REST_Response( array(
		'chat'     => sol_live_summary( $chat ),
		'typing'   => time() - (int) get_post_meta( $chat->ID, '_sc_vtyping', true ) < 6,
		'messages' => $msgs,
	), 200 );
}

/** Assign the chat to the current team member, if no one has it yet. */
function sol_live_join( $chat ) {
	$uid = get_current_user_id();
	if ( (int) get_post_meta( $chat->ID, '_sc_agent', true ) === $uid && 'live' === sol_live_status( $chat->ID ) ) {
		return;
	}
	$pub = sol_live_agent_public( $uid );
	update_post_meta( $chat->ID, '_sc_agent', $uid );
	update_post_meta( $chat->ID, '_sc_status', 'live' );
	sol_live_add( $chat->ID, 'note', $pub['name'] . ' from the SOL team joined the chat.' );
	delete_transient( 'sol_live_waiting' );
}

function sol_live_r_agent_join( WP_REST_Request $req ) {
	$chat = sol_live_agent_chat_or_error( $req );
	if ( ! $chat ) {
		return sol_live_error( 'Chat not found.', 404 );
	}
	if ( 'closed' === sol_live_status( $chat->ID ) ) {
		return sol_live_error( 'This chat has ended.', 409 );
	}
	sol_live_join( $chat );
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

function sol_live_r_agent_send( WP_REST_Request $req ) {
	$chat = sol_live_agent_chat_or_error( $req );
	if ( ! $chat ) {
		return sol_live_error( 'Chat not found.', 404 );
	}
	if ( 'closed' === sol_live_status( $chat->ID ) ) {
		return sol_live_error( 'This chat has ended. Reply by email instead.', 409 );
	}
	$text = mb_substr( sanitize_textarea_field( (string) $req->get_param( 'text' ) ), 0, 4000 );
	if ( '' === trim( $text ) ) {
		return sol_live_error( 'Please type a message.', 400 );
	}
	if ( 'waiting' === sol_live_status( $chat->ID ) || ! get_post_meta( $chat->ID, '_sc_agent', true ) ) {
		sol_live_join( $chat );
	}
	$pub = sol_live_agent_public( get_current_user_id() );
	$mid = sol_live_add( $chat->ID, 'agent', $text, $pub['name'] );
	delete_post_meta( $chat->ID, '_sc_atyping' );
	return new WP_REST_Response( array( 'id' => $mid ), 200 );
}

function sol_live_r_agent_close( WP_REST_Request $req ) {
	$chat = sol_live_agent_chat_or_error( $req );
	if ( ! $chat ) {
		return sol_live_error( 'Chat not found.', 404 );
	}
	if ( 'closed' !== sol_live_status( $chat->ID ) ) {
		$pub = sol_live_agent_public( get_current_user_id() );
		update_post_meta( $chat->ID, '_sc_status', 'closed' );
		sol_live_add( $chat->ID, 'note', $pub['name'] . ' ended the chat.' );
		delete_transient( 'sol_live_waiting' );
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

function sol_live_r_agent_away( WP_REST_Request $req ) {
	$uid = get_current_user_id();
	if ( $req->get_param( 'away' ) ) {
		update_user_meta( $uid, '_sc_away', '1' );
	} else {
		delete_user_meta( $uid, '_sc_away' );
		update_user_meta( $uid, '_sc_seen', time() );
	}
	delete_transient( 'sol_live_online' );
	return new WP_REST_Response( array( 'away' => (bool) $req->get_param( 'away' ) ), 200 );
}

/* ---------- Admin: inbox page, menu badge, admin bar alert ---------- */

function sol_live_waiting_cached() {
	$n = get_transient( 'sol_live_waiting' );
	if ( false === $n ) {
		$n = sol_live_waiting_count();
		set_transient( 'sol_live_waiting', $n, 30 );
	}
	return (int) $n;
}

add_action( 'admin_menu', function () {
	if ( ! sol_live_enabled() ) {
		return;
	}
	$n     = sol_live_waiting_cached();
	$title = 'Live Chat' . ( $n ? ' <span class="awaiting-mod count-' . $n . '"><span class="pending-count">' . $n . '</span></span>' : '' );
	add_menu_page( 'Live Chat', $title, sol_live_agent_cap(), SOL_LIVE_PAGE, 'sol_live_admin_page', 'dashicons-format-chat', 3 );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_' . SOL_LIVE_PAGE !== $hook || ! sol_live_is_agent() ) {
		return;
	}
	wp_enqueue_style( 'sol-live-admin', SOL_URI . 'assets/css/sol-live-admin.css', array(), SOL_THEME_VERSION );
	wp_enqueue_script( 'sol-live-admin', SOL_URI . 'assets/js/sol-live-admin.js', array(), SOL_THEME_VERSION, true );
	$me = sol_live_agent_public( get_current_user_id() );
	wp_localize_script( 'sol-live-admin', 'SOL_LIVE', array(
		'api'   => esc_url_raw( rest_url( 'sol/v1/live/agent/' ) ),
		'nonce' => wp_create_nonce( 'wp_rest' ),
		'me'    => $me['name'],
		'open'  => isset( $_GET['chat'] ) ? (int) $_GET['chat'] : 0, // phpcs:ignore WordPress.Security.NonceVerification
	) );
} );

function sol_live_admin_page() {
	if ( ! sol_live_is_agent() ) {
		return;
	}
	?>
	<div class="wrap sol-live">
		<div class="sol-live__top">
			<h1>Live Chat</h1>
			<label class="sol-live__avail"><input type="checkbox" data-live-avail checked> <span>I'm available to chat</span></label>
			<span class="sol-live__online" data-live-online></span>
			<button type="button" class="button" data-live-notify hidden>Turn on desktop alerts</button>
		</div>
		<p class="sol-live__help">Visitors reach you here when they ask the SOL Assistant to talk to a person. Keep this page open (in any tab) to show as online; you'll hear a sound when someone is waiting. When no one is online, visitors leave their details and the request is emailed to <?php echo esc_html( sol_opt( 'notification_email', get_option( 'admin_email' ) ) ); ?>.</p>
		<div class="sol-live__app">
			<aside class="sol-live__list" data-live-list aria-label="Chats"><p class="sol-live__empty">Loading…</p></aside>
			<section class="sol-live__conv" data-live-conv>
				<div class="sol-live__placeholder"><span class="dashicons dashicons-format-chat"></span><p>Select a chat to read and reply.</p></div>
			</section>
		</div>
	</div>
	<?php
}

// Heartbeat: keeps team members "online" on any admin page and shows waiting chats in the admin bar.
add_filter( 'heartbeat_received', function ( $response, $data ) {
	if ( ! empty( $data['sol_live'] ) && sol_live_enabled() && sol_live_is_agent() ) {
		sol_live_seen();
		$response['sol_live'] = sol_live_waiting_cached();
	}
	return $response;
}, 10, 2 );

add_action( 'admin_bar_menu', function ( $bar ) {
	if ( ! is_admin() || ! sol_live_enabled() || ! sol_live_is_agent() ) {
		return;
	}
	$n = sol_live_waiting_cached();
	$bar->add_node( array(
		'id'    => 'sol-live',
		'title' => '<span class="ab-icon dashicons dashicons-format-chat" style="margin-top:2px"></span><span class="ab-label" data-sol-live-count>' . ( $n ? $n . ' waiting' : 'Live Chat' ) . '</span>',
		'href'  => admin_url( 'admin.php?page=' . SOL_LIVE_PAGE ),
		'meta'  => array( 'class' => $n ? 'sol-live-waiting' : '' ),
	) );
}, 80 );

add_action( 'admin_footer', function () {
	if ( ! sol_live_enabled() || ! sol_live_is_agent() ) {
		return;
	}
	wp_enqueue_script( 'heartbeat' );
	?>
	<style>#wpadminbar .sol-live-waiting > .ab-item { background: #d97706; color: #fff; }</style>
	<script>
	(function ($) {
		if (!$ || !window.wp || !wp.heartbeat) return;
		var last = <?php echo (int) sol_live_waiting_cached(); ?>;
		var onInbox = /page=<?php echo esc_js( SOL_LIVE_PAGE ); ?>/.test(location.search);
		$(document).on('heartbeat-send', function (e, data) { data.sol_live = 1; });
		$(document).on('heartbeat-tick', function (e, data) {
			if (typeof data.sol_live === 'undefined') return;
			var n = +data.sol_live, node = $('#wp-admin-bar-sol-live');
			node.toggleClass('sol-live-waiting', n > 0).find('[data-sol-live-count]').text(n ? n + ' waiting' : 'Live Chat');
			if (n > last && !onInbox) {
				try { var a = new AudioContext(), o = a.createOscillator(), g = a.createGain(); o.connect(g); g.connect(a.destination); o.frequency.value = 880; g.gain.value = .08; o.start(); o.stop(a.currentTime + .25); } catch (err) {}
			}
			last = n;
		});
	})(window.jQuery);
	</script>
	<?php
}, 99 );

/* ---------- Clean-up: closed chats are deleted after 180 days ---------- */

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'sol_live_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'sol_live_cleanup' );
	}
} );

add_action( 'sol_live_cleanup', function () {
	global $wpdb;
	$days = (int) apply_filters( 'sol_live_keep_days', 180 );
	$old  = get_posts( array(
		'post_type'      => 'sol_chat',
		'post_status'    => 'private',
		'fields'         => 'ids',
		'posts_per_page' => 200,
		'meta_query'     => array( array( 'key' => '_sc_last_at', 'value' => time() - $days * DAY_IN_SECONDS, 'compare' => '<', 'type' => 'NUMERIC' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	foreach ( $old as $id ) {
		$wpdb->delete( sol_live_table(), array( 'chat_id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_delete_post( $id, true );
	}
} );
