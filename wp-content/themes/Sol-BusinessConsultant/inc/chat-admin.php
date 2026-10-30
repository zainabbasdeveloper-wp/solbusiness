<?php
/**
 * SOL Site → AI Chat: one simple screen for the Groq API key, the on/off switch and a connection test.
 * It works on its own, so it doesn't depend on the SOL Site Settings tabs being up to date.
 */

defined( 'ABSPATH' ) || exit;

define( 'SOL_CHAT_PAGE', 'sol-ai-chat' );

/** Where the key currently comes from: 'config', 'env', 'page', 'settings' or ''. */
function sol_chat_key_source() {
	if ( defined( 'SOL_GROQ_API_KEY' ) && SOL_GROQ_API_KEY ) {
		return 'config';
	}
	if ( getenv( 'GROQ_API_KEY' ) ) {
		return 'env';
	}
	if ( '' !== trim( (string) get_option( 'sol_groq_api_key', '' ) ) ) {
		return 'page';
	}
	return '' !== trim( (string) sol_opt( 'chat_api_key' ) ) ? 'settings' : '';
}

/** Ask Groq whether a key works. Returns true or an error message. */
function sol_chat_test_key( $key ) {
	$res = wp_remote_get( 'https://api.groq.com/openai/v1/models', array(
		'timeout' => 15,
		'headers' => array( 'Authorization' => 'Bearer ' . $key ),
	) );
	if ( is_wp_error( $res ) ) {
		return 'Your server could not reach Groq: ' . $res->get_error_message();
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	if ( 200 === $code ) {
		return true;
	}
	if ( 401 === $code || 403 === $code ) {
		return 'Groq rejected this key. Check that you copied the whole key from console.groq.com → API Keys (it starts with gsk_).';
	}
	return 'Groq answered with an error (HTTP ' . $code . '). Please try again in a minute.';
}

// Priority 100: SCF adds the SOL Site menu at 99, and a submenu needs its parent to exist first.
add_action( 'admin_menu', function () {
	add_submenu_page( SOL_OPTIONS_SLUG, 'AI Chat', 'AI Chat', 'manage_options', SOL_CHAT_PAGE, 'sol_chat_admin_page', 1 );
}, 100 );

// Settings link straight to this page from the AI Assistant tab's old home.
add_filter( 'acf/prepare_field/name=chat_api_key', function ( $field ) {
	$field['instructions'] = 'Easier: use <a href="' . esc_url( admin_url( 'admin.php?page=' . SOL_CHAT_PAGE ) ) . '">SOL Site → AI Chat</a>, which also tests the key. ' . $field['instructions'];
	return $field;
} );

add_action( 'admin_post_sol_chat_save', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_chat_save' );

	update_option( 'sol_chat_on', empty( $_POST['sol_chat_on'] ) ? '0' : '1', false );

	$msg  = 'saved';
	$note = '';
	if ( ! empty( $_POST['sol_chat_remove'] ) ) {
		delete_option( 'sol_groq_api_key' );
		$msg = 'removed';
	} else {
		$key = isset( $_POST['sol_groq_api_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['sol_groq_api_key'] ) ) ) : '';
		if ( '' !== $key ) {
			if ( 0 === strpos( $key, 'xai-' ) ) {
				$msg  = 'error';
				$note = 'That is an xAI (Grok) key. This chat uses Groq, a different service. Get a free key at console.groq.com → API Keys; it starts with gsk_.';
			} else {
				$ok = sol_chat_test_key( $key );
				if ( true === $ok ) {
					update_option( 'sol_groq_api_key', $key, false );
					$msg = 'connected';
				} else {
					$msg  = 'error';
					$note = $ok;
				}
			}
		} elseif ( ! empty( $_POST['sol_chat_test'] ) ) {
			$cur = sol_chat_api_key();
			$ok  = '' === $cur ? 'There is no key to test yet.' : sol_chat_test_key( $cur );
			$msg  = true === $ok ? 'connected' : 'error';
			$note = true === $ok ? '' : $ok;
		}
	}
	if ( $note ) {
		set_transient( 'sol_chat_note_' . get_current_user_id(), $note, 120 );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=' . SOL_CHAT_PAGE . '&sol_msg=' . $msg ) );
	exit;
} );

function sol_chat_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$source = sol_chat_key_source();
	$on     = sol_chat_switched_on();
	$live   = sol_chat_enabled();
	$msg    = isset( $_GET['sol_msg'] ) ? sanitize_key( $_GET['sol_msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$note   = get_transient( 'sol_chat_note_' . get_current_user_id() );
	delete_transient( 'sol_chat_note_' . get_current_user_id() );
	$key    = sol_chat_api_key();
	$masked = $key ? substr( $key, 0, 4 ) . str_repeat( '•', 8 ) . substr( $key, -4 ) : '';
	$labels = array(
		'config'   => 'wp-config.php (SOL_GROQ_API_KEY)',
		'env'      => 'the server environment (GROQ_API_KEY)',
		'page'     => 'this page',
		'settings' => 'SOL Site Settings → AI Assistant',
	);
	?>
	<div class="wrap">
		<h1>AI Chat</h1>
		<p>The SOL Assistant chat button appears in the bottom-right corner of the site once a Groq API key is added and the chat is switched on.</p>

		<?php if ( 'connected' === $msg ) : ?>
			<div class="notice notice-success"><p><strong>Connected.</strong> Groq accepted the key<?php echo $on ? ' and the chat is now live on your site. Clear your page cache if you use one.' : '. Tick “Show the chat on the site” to switch it on.'; ?></p></div>
		<?php elseif ( 'saved' === $msg ) : ?>
			<div class="notice notice-success"><p>Settings saved.</p></div>
		<?php elseif ( 'removed' === $msg ) : ?>
			<div class="notice notice-warning"><p>The key saved on this page was removed.</p></div>
		<?php elseif ( 'error' === $msg ) : ?>
			<div class="notice notice-error"><p><strong>Not saved.</strong> <?php echo esc_html( $note ? $note : 'Something went wrong.' ); ?></p></div>
		<?php endif; ?>

		<table class="widefat striped" style="max-width:720px;margin:16px 0 24px">
			<tbody>
				<tr><td style="width:180px"><strong>Status</strong></td><td><?php echo $live ? '<span style="color:#008a20">● Live on the site</span>' : '<span style="color:#b32d2e">● Not showing</span>'; ?></td></tr>
				<tr><td><strong>API key</strong></td><td><?php echo $source ? '<code>' . esc_html( $masked ) . '</code> from ' . esc_html( $labels[ $source ] ) : 'None yet'; ?></td></tr>
			</tbody>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:720px">
			<input type="hidden" name="action" value="sol_chat_save">
			<?php wp_nonce_field( 'sol_chat_save' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sol_groq_api_key">Groq API key</label></th>
					<td>
						<input type="password" id="sol_groq_api_key" name="sol_groq_api_key" class="regular-text" autocomplete="off" spellcheck="false" placeholder="<?php echo $key ? 'Leave empty to keep the current key' : 'gsk_…'; ?>">
						<p class="description">Get a free key at <a href="https://console.groq.com/keys" target="_blank" rel="noopener">console.groq.com → API Keys</a>. It starts with <code>gsk_</code>. The key is tested before it's saved and is never shown to visitors.</p>
						<?php if ( 'config' === $source || 'env' === $source ) : ?>
							<p class="description"><strong>Note:</strong> a key in <?php echo esc_html( $labels[ $source ] ); ?> is in use and takes priority over this field.</p>
						<?php endif; ?>
						<?php if ( 'page' === $source ) : ?>
							<p><label><input type="checkbox" name="sol_chat_remove" value="1"> Remove the saved key</label></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">Chat</th>
					<td><label><input type="checkbox" name="sol_chat_on" value="1" <?php checked( $on ); ?>> Show the chat on the site</label></td>
				</tr>
			</table>
			<p class="submit">
				<?php submit_button( 'Save and test', 'primary', 'submit', false ); ?>
				<?php if ( $key ) : ?>&nbsp;<button type="submit" class="button" name="sol_chat_test" value="1">Test current key</button><?php endif; ?>
			</p>
		</form>
		<p class="description" style="max-width:720px">Assistant name, greeting, quick questions and extra knowledge are in <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . SOL_OPTIONS_SLUG ) ); ?>">SOL Site Settings</a> → AI Assistant tab.</p>
	</div>
	<?php
}

// Point to the new page when the chat has no key yet.
add_action( 'admin_notices', function () {
	$screen = get_current_screen();
	if ( ! $screen || ! current_user_can( 'manage_options' ) || false === strpos( (string) $screen->id, SOL_OPTIONS_SLUG ) || '' !== sol_chat_api_key() ) {
		return;
	}
	echo '<div class="notice notice-info"><p>The AI chat needs a Groq API key. <a href="' . esc_url( admin_url( 'admin.php?page=' . SOL_CHAT_PAGE ) ) . '">Add it in SOL Site → AI Chat</a>.</p></div>';
} );

// Keep both switches in step: saving SOL Site Settings updates the AI Chat switch too.
add_action( 'acf/save_post', function ( $post_id ) {
	if ( 'options' === $post_id && function_exists( 'get_field' ) ) {
		update_option( 'sol_chat_on', get_field( 'chat_enabled', 'option' ) ? '1' : '0', false );
	}
}, 20 );
