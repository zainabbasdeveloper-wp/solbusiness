<?php
/**
 * SOL Assistant: the AI chat in the bottom-right corner, the same assistant as the training site.
 *
 * The browser sends the conversation to /wp-json/sol/v1/chat. WordPress adds the assistant's
 * instructions and forwards it to Groq, so the API key never reaches the visitor.
 * Settings: SOL Site → AI Assistant. The key can also be set in wp-config.php:
 *   define( 'SOL_GROQ_API_KEY', 'gsk_…' );
 */

defined( 'ABSPATH' ) || exit;

/** Groq API key: wp-config constant, then server environment, then SOL Site Settings. */
function sol_chat_api_key() {
	if ( defined( 'SOL_GROQ_API_KEY' ) && SOL_GROQ_API_KEY ) {
		return (string) SOL_GROQ_API_KEY;
	}
	$env = getenv( 'GROQ_API_KEY' );
	if ( $env ) {
		return (string) $env;
	}
	return trim( (string) sol_opt( 'chat_api_key' ) );
}

/** The chat shows only when it's switched on and has a key. */
function sol_chat_enabled() {
	return (bool) apply_filters( 'sol_chat_enabled', sol_opt( 'chat_enabled', false ) && '' !== sol_chat_api_key() );
}

/** Link to a page by path, or '' when the page doesn't exist. */
function sol_chat_page( $path ) {
	$p = get_page_by_path( $path );
	return ( $p && 'publish' === $p->post_status ) ? get_permalink( $p ) : '';
}

/** Service pages as "- Title: URL" lines (cached for 12 hours). */
function sol_chat_service_lines() {
	$lines = get_transient( 'sol_chat_services_v2' );
	if ( false !== $lines ) {
		return $lines;
	}
	$pages = get_pages( array(
		'hierarchical' => false,
		'sort_column'  => 'menu_order,post_title',
		'meta_key'     => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'   => SOL_TPL_SERVICE, // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	$out = array();
	foreach ( $pages as $p ) {
		$out[] = '- ' . html_entity_decode( wp_strip_all_tags( get_the_title( $p ) ), ENT_QUOTES, 'UTF-8' ) . ': ' . get_permalink( $p );
	}
	$lines = implode( "\n", $out );
	set_transient( 'sol_chat_services_v2', $lines, 12 * HOUR_IN_SECONDS );
	return $lines;
}
add_action( 'save_post_page', function () {
	delete_transient( 'sol_chat_services_v2' );
} );

/** The assistant's instructions, built from the site's own pages and contact details. */
function sol_chat_system_prompt() {
	$name    = sol_opt( 'chat_name', 'SOL Assistant' );
	$phone   = sol_opt( 'phone', '+61 460 003 494' );
	$email   = sol_opt( 'email', 'info@solbusinessconsultant.com.au' );
	$hours   = sol_opt( 'business_hours', 'Monday-Friday: 9:00 AM - 5:00 PM AEST' );
	$address = sol_opt( 'address', 'Glenroy VIC 3046, Australia' );

	$links = array_filter( array(
		'All business services'                        => home_url( '/services/' ),
		'Book a free consultation'                     => sol_chat_page( 'book-consultation' ),
		'Contact the SOL team (form, phone, email)'    => sol_chat_page( 'contact' ),
		'NDIS registration packages and pricing'       => sol_chat_page( 'ndis-packages' ),
		'Pricing and packages'                         => sol_chat_page( 'pricing' ),
		'Free NDIS readiness quiz'                     => sol_chat_page( 'readiness-quiz' ),
		'NDIS readiness calculator'                    => sol_chat_page( 'ndis-readiness-calculator' ),
		'Package finder (find my plan)'                => sol_chat_page( 'get-started' ),
		'Support Coordinator training courses'         => sol_chat_page( 'training-2' ),
		'Marketing packages'                           => sol_chat_page( 'marketing-packages' ),
		'FAQs'                                         => sol_chat_page( 'faq' ),
		'Pay for an agreed service (service payment form)' => sol_url( sol_opt( 'payment_page_url', '/sol-service-payment-form/' ) ),
		'Refunds and cancellation policy'              => sol_chat_page( 'refunds-cancellation-policy' ),
		'Complaints and feedback'                      => sol_chat_page( 'complaints-feedback' ),
		'Student Learning Portal'                      => function_exists( 'sol_app_url' ) ? sol_app_url( '/' ) : '',
		'Create a student account'                     => function_exists( 'sol_app_url' ) ? sol_app_url( '/register' ) : '',
		'Student login'                                => function_exists( 'sol_app_url' ) ? sol_app_url( '/login' ) : '',
		'Client portal (track a purchased service)'    => function_exists( 'sol_app_url' ) ? sol_app_url( '/client-portal' ) : '',
	) );
	$link_lines = '';
	foreach ( $links as $label => $url ) {
		$link_lines .= "- {$label}: {$url}\n";
	}

	$prompt = <<<TXT
You are "{$name}", the professional online concierge on the website of SOL Business Consultant Pty Ltd, an Australian-owned business and NDIS consultancy based in {$address}.

SOL services:
- NDIS registration, compliance preparation, policy support, audit readiness and NDIS administration
- NDIS Support Coordination training (Levels 1-3, online)
- Company and business registration
- Admin and back-office support
- Bookkeeping, BAS lodgement, payroll and accountancy partnerships
- Website and app development
- Business and personal loan application support
- Offshore staffing and outsourcing
- AI data annotation and machine learning services
- SEO, Google Ads, social media and digital marketing
- Software, workflow and NDIS automation

Service pages on this website (link the relevant one when you recommend a service):
{SERVICES}

Other approved links:
{$link_lines}
Contact: phone {$phone}, email {$email}. Hours: {$hours}.

Training course purchase and access:
1. Open the training courses page, choose the course level and select Enrol Now. The course is added to the cart and checkout opens.
2. Pay at checkout with the learner's correct email address and keep the order confirmation.
3. After payment, follow the link on the order confirmation page to the Student Learning Portal.
4. Register with the exact same email used at checkout, verify the email and sign in. Existing students sign in with that same email.
5. Course access is set up once the payment is confirmed; the course then appears in My Courses.

Business-service payment guidance:
- First help the visitor identify the right service and encourage them to book a consultation or request a quote when the price has not been agreed.
- The service payment form is only for amounts already agreed with SOL (a quote or invoice). The visitor selects the service and enters the agreed amount.
- Never tell anyone to choose an arbitrary amount for work whose scope or price has not been confirmed. Never collect card or bank details in chat.

Guidelines:
- Be warm, concise and professional. Use plain Australian English.
- Start by understanding whether the visitor needs a business service, NDIS support, a training course, payment help or portal access, then give the most relevant link as a Markdown link with a descriptive label.
- Recommend only options that fit what the visitor said. Link only the URLs listed above; never make up URLs.
- If someone has already paid for training, remind them to register or sign in with the same checkout email.
- For payment, account, enrolment or access problems, don't claim to see their account or payment. Ask them to contact the SOL team with their order or reference number.
- For anything outside SOL's services or that needs a person (quotes, account issues, legal, financial or medical advice), say so politely and point them to {$phone}, the contact page or a free consultation.
- Never invent prices, dates, timeframes or guarantees. If you're not sure, say so and suggest contacting the team.
- Never ask for passwords, card details, bank logins, one-time codes or identity documents.
- Keep replies short (2-5 sentences) unless the visitor asks for detail. Use short bullet points and Markdown links when helpful. No tables or headings.
TXT;
	$prompt = str_replace( '{SERVICES}', sol_chat_service_lines(), $prompt );

	$extra = trim( (string) sol_opt( 'chat_instructions' ) );
	if ( $extra ) {
		$prompt .= "\n\nAdditional information from SOL (trust this over the general guidance above):\n" . $extra;
	}
	return apply_filters( 'sol_chat_system_prompt', $prompt );
}

/* ---------- REST endpoint ---------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'sol/v1', '/chat', array(
		'methods'             => 'POST',
		'callback'            => 'sol_chat_reply',
		'permission_callback' => '__return_true', // public chat; protected by origin check and rate limits below
	) );
} );

/** Error reply in the shape the widget expects. */
function sol_chat_error( $message, $status ) {
	return new WP_REST_Response( array( 'message' => $message ), $status );
}

function sol_chat_reply( WP_REST_Request $req ) {
	$phone = sol_opt( 'phone', '+61 460 003 494' );
	if ( ! sol_chat_enabled() ) {
		return sol_chat_error( 'The chat assistant is not available right now. Please call us on ' . $phone . '.', 503 );
	}

	// Only this website may use the endpoint.
	$origin = $req->get_header( 'origin' );
	if ( $origin && wp_parse_url( $origin, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		return sol_chat_error( 'Not allowed.', 403 );
	}

	// Rate limits: per visitor (short and daily) and for the whole site per day, to protect the Groq quota.
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$limits  = apply_filters( 'sol_chat_limits', array( 'burst' => 12, 'daily' => 80, 'site_daily' => 2000 ) );
	$buckets = array(
		array( 'sol_chat_b_' . md5( $ip ), $limits['burst'], 5 * MINUTE_IN_SECONDS ),
		array( 'sol_chat_d_' . md5( $ip ), $limits['daily'], DAY_IN_SECONDS ),
		array( 'sol_chat_site_' . gmdate( 'Ymd' ), $limits['site_daily'], DAY_IN_SECONDS ),
	);
	foreach ( $buckets as $b ) {
		if ( (int) get_transient( $b[0] ) >= $b[1] ) {
			return sol_chat_error( "You've reached the chat limit for now. Please try again later, or call us on {$phone}.", 429 );
		}
	}

	// The conversation: last 12 turns, user/assistant only, each up to 2,000 characters.
	$history = $req->get_param( 'messages' );
	$clean   = array();
	foreach ( is_array( $history ) ? $history : array() as $m ) {
		$role    = isset( $m['role'] ) ? (string) $m['role'] : '';
		$content = isset( $m['content'] ) ? trim( wp_strip_all_tags( (string) $m['content'] ) ) : '';
		if ( in_array( $role, array( 'user', 'assistant' ), true ) && '' !== $content ) {
			$clean[] = array( 'role' => $role, 'content' => mb_substr( $content, 0, 2000 ) );
		}
	}
	$clean = array_slice( $clean, -12 );
	if ( ! $clean || 'user' !== end( $clean )['role'] ) {
		return sol_chat_error( 'Please type a message.', 400 );
	}

	foreach ( $buckets as $b ) {
		set_transient( $b[0], (int) get_transient( $b[0] ) + 1, $b[2] );
	}

	$model = trim( (string) sol_opt( 'chat_model', 'openai/gpt-oss-120b' ) );
	$model = $model ? $model : 'openai/gpt-oss-120b';
	$body  = array(
		'model'       => $model,
		'messages'    => array_merge( array( array( 'role' => 'system', 'content' => sol_chat_system_prompt() ) ), $clean ),
		'temperature' => 0.6,
		'max_tokens'  => 1024,
	);
	if ( 0 === strpos( $model, 'openai/gpt-oss' ) ) {
		$body['reasoning_effort'] = 'low'; // quicker replies; this is a concierge, not a research task
	}

	$res = wp_remote_post( 'https://api.groq.com/openai/v1/chat/completions', array(
		'timeout' => 30,
		'headers' => array(
			'Authorization' => 'Bearer ' . sol_chat_api_key(),
			'Content-Type'  => 'application/json',
		),
		'body'    => wp_json_encode( $body ),
	) );

	$fallback = "Sorry, I couldn't respond just now. Please try again, or call us on {$phone}.";
	if ( is_wp_error( $res ) ) {
		error_log( '[SOL chat] ' . $res->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		return sol_chat_error( $fallback, 502 );
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 !== $code ) {
		error_log( '[SOL chat] Groq HTTP ' . $code . ': ' . substr( wp_remote_retrieve_body( $res ), 0, 300 ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		$msg = 429 === $code ? 'The assistant is busy right now. Please try again in a moment.' : $fallback;
		return sol_chat_error( $msg, 429 === $code ? 429 : 502 );
	}
	$reply = isset( $data['choices'][0]['message']['content'] ) ? (string) $data['choices'][0]['message']['content'] : '';
	$reply = trim( preg_replace( '#<think>.*?</think>#s', '', $reply ) );
	if ( '' === $reply ) {
		return sol_chat_error( $fallback, 502 );
	}
	return new WP_REST_Response( array( 'reply' => $reply ), 200 );
}

/* ---------- Widget ---------- */

add_action( 'wp_enqueue_scripts', function () {
	if ( ! sol_chat_enabled() ) {
		return;
	}
	$ver = SOL_THEME_VERSION;
	wp_enqueue_style( 'sol-chat', SOL_URI . 'assets/css/sol-chat.css', array( 'sol' ), $ver );
	wp_enqueue_script( 'sol-chat', SOL_URI . 'assets/js/sol-chat.js', array(), $ver, true );
	$questions = array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) sol_opt( 'chat_questions' ) ) ) ) );
	wp_localize_script( 'sol-chat', 'SOL_CHAT', array(
		'endpoint'  => esc_url_raw( rest_url( 'sol/v1/chat' ) ),
		'name'      => sol_opt( 'chat_name', 'SOL Assistant' ),
		'greeting'  => sol_opt( 'chat_greeting', "Hi, I'm online and ready to help. What can I help you with today?" ),
		'questions' => array_slice( $questions, 0, 6 ),
		'error'     => "Sorry, I couldn't respond just now. Please try again, or call us on " . sol_opt( 'phone', '+61 460 003 494' ) . '.',
	) );
}, 30 );

add_filter( 'body_class', function ( $classes ) {
	if ( ! is_admin() && sol_chat_enabled() ) {
		$classes[] = 'sol-has-chat';
	}
	return $classes;
} );

add_action( 'wp_footer', function () {
	if ( ! sol_chat_enabled() ) {
		return;
	}
	$name     = sol_opt( 'chat_name', 'SOL Assistant' );
	$phone    = sol_opt( 'phone', '+61 460 003 494' );
	$phone_l  = preg_replace( '/[^0-9+]/', '', sol_opt( 'phone_link', $phone ) );
	$pay      = sol_url( sol_opt( 'payment_page_url', '/sol-service-payment-form/' ) );
	$book     = sol_chat_page( 'book-consultation' );
	$bot      = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2M20 14h2M15 13v2M9 13v2"/></svg>';
	?>
	<div class="sol-chat" data-sol-chat>
		<button type="button" class="sol-chat__hello" data-chat-open hidden>
			<strong>Hi, I'm online</strong>
			<span>How can I help you today?</span>
		</button>
		<button type="button" class="sol-chat__launch" data-chat-toggle aria-expanded="false" aria-controls="sol-chat-panel" aria-label="<?php echo esc_attr( 'Chat with ' . $name ); ?>">
			<span class="sol-chat__launch-icon sol-chat__launch-icon--bot"><?php echo $bot; // phpcs:ignore ?></span>
			<span class="sol-chat__launch-icon sol-chat__launch-icon--x"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></span>
			<span class="sol-chat__badge" data-chat-badge hidden>1</span>
		</button>

		<section class="sol-chat__panel" id="sol-chat-panel" role="dialog" aria-label="<?php echo esc_attr( $name ); ?>" hidden>
			<header class="sol-chat__head">
				<div class="sol-chat__who">
					<span class="sol-chat__avatar"><?php echo $bot; // phpcs:ignore ?></span>
					<div>
						<p class="sol-chat__name"><?php echo esc_html( $name ); ?></p>
						<p class="sol-chat__status"><i></i>Online and ready to help</p>
					</div>
				</div>
				<div class="sol-chat__tools">
					<button type="button" data-chat-reset aria-label="Start a new chat" title="New chat"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 2v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/></svg></button>
					<button type="button" data-chat-close aria-label="Close chat" title="Close"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
				</div>
			</header>
			<nav class="sol-chat__links" aria-label="Quick links">
				<?php if ( $pay ) : ?><a href="<?php echo esc_url( $pay ); ?>">Service &amp; Training Payment</a><?php endif; ?>
				<?php if ( $book ) : ?><a href="<?php echo esc_url( $book ); ?>">Book Consultation</a><?php endif; ?>
			</nav>
			<div class="sol-chat__log" data-chat-log aria-live="polite"></div>
			<form class="sol-chat__form" data-chat-form>
				<label class="screen-reader-text" for="sol-chat-input">Your message</label>
				<textarea id="sol-chat-input" rows="1" maxlength="2000" placeholder="Type your message…" data-chat-input></textarea>
				<button type="submit" aria-label="Send message" data-chat-send disabled><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 21.7a.5.5 0 0 0 .9 0L22 2.6a.5.5 0 0 0-.6-.6L2.3 8.6a.5.5 0 0 0 0 .9l8 3.2a2 2 0 0 1 1.1 1.1z"/><path d="m21.9 2.1-10.9 10.9"/></svg></button>
			</form>
			<p class="sol-chat__foot">AI replies can be wrong. For anything urgent call <a href="tel:<?php echo esc_attr( $phone_l ); ?>"><?php echo esc_html( $phone ); ?></a></p>
		</section>
	</div>
	<?php
}, 5 ); // before the footer scripts (priority 20) so sol-chat.js finds the markup
