<?php
/**
 * Home page design options for review (v2.19).
 *
 * Four complete home page concepts plus an overview page, served at:
 *   /home-options/            overview for reviewers
 *   /home-options/executive/  A: dark, cinematic
 *   /home-options/editorial/  B: light, magazine
 *   /home-options/bento/      C: modern tiles, interactive
 *   /home-options/human/      D: warm, NDIS-first
 *
 * They need no WordPress page: the URLs are answered here, so they appear right after a git pull.
 * They are kept out of search results. Content comes from the theme's own content file
 * (seed/site.json), so every option shows the same, accurate SOL information.
 * Templates: template-parts/home-options/. Styles: assets/css/home-options/. Script: assets/js/sol-home-options.js.
 */

defined( 'ABSPATH' ) || exit;

/** Option slug => name, one-line idea, palette (for the overview page). */
function sol_ho_options() {
	return array(
		'executive' => array( 'letter' => 'A', 'name' => 'Executive', 'idea' => 'Dark, cinematic and confident. A full-photo hero, glass panels, a moving platform strip and bold numbers.', 'palette' => array( '#0b1424', '#13233a', '#f59e0b', '#ffffff' ) ),
		'editorial' => array( 'letter' => 'B', 'name' => 'Editorial', 'idea' => 'Light and spacious, like a premium magazine. Big type, a photo collage, numbered service stories and calm colour.', 'palette' => array( '#fbf8f2', '#efe8db', '#0f1b2d', '#d97706' ) ),
		'bento'     => array( 'letter' => 'C', 'name' => 'Bento', 'idea' => 'Modern and interactive. Tile layouts, a service explorer with tabs, a client portal preview and the quiz up front.', 'palette' => array( '#f5f7fb', '#0f172a', '#f59e0b', '#6366f1' ) ),
		'human'     => array( 'letter' => 'D', 'name' => 'Human', 'idea' => 'Warm and personal, NDIS-first. Soft shapes, "where are you today?" pathways, a journey path and an enquiry form on the page.', 'palette' => array( '#fff8f0', '#fde7cf', '#1e3a5f', '#e07a2f' ) ),
	);
}

/** The option being viewed: '' for the overview, a slug, or null when this isn't an option URL. */
function sol_ho_current( $set = false ) {
	static $current = null;
	if ( false !== $set ) {
		$current = $set;
	}
	return $current;
}

/** Answer the option URLs before WordPress shows "page not found". */
add_action( 'template_redirect', function () {
	$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$base = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $base && 0 === strpos( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) + 1 );
	}
	if ( ! preg_match( '~^home-options(?:/([a-z]+))?$~', $path, $m ) ) {
		return;
	}
	$slug = isset( $m[1] ) ? $m[1] : '';
	if ( '' !== $slug && ! isset( sol_ho_options()[ $slug ] ) ) {
		return;
	}
	global $wp_query;
	$wp_query->is_404  = false;
	$wp_query->is_page = true;
	status_header( 200 );
	nocache_headers();
	sol_ho_current( $slug );
	include locate_template( 'template-parts/home-options/' . ( '' === $slug ? 'index' : $slug ) . '.php' );
	exit;
}, 0 );

add_filter( 'body_class', function ( $classes ) {
	$c = sol_ho_current();
	if ( null === $c ) {
		return $classes;
	}
	// Drawn fully by the theme: use the SOL base styles, without Elementor's global kit styles.
	$classes   = array_values( array_filter( array_diff( $classes, array( 'error404' ) ), function ( $c ) {
		return 0 !== strpos( $c, 'elementor-kit-' );
	} ) );
	$classes[] = 'sol-site';
	$classes[] = 'sol-ho';
	$classes[] = 'sol-ho--' . ( '' === $c ? 'index' : $c );
	return $classes;
} );

add_filter( 'pre_get_document_title', function ( $title ) {
	$c = sol_ho_current();
	if ( null === $c ) {
		return $title;
	}
	$o = sol_ho_options();
	return '' === $c ? 'Home Page Options | SOL Business Consultant' : 'Option ' . $o[ $c ]['letter'] . ': ' . $o[ $c ]['name'] . ' | SOL Business Consultant';
}, 99 );

add_filter( 'wp_robots', function ( $robots ) {
	if ( null !== sol_ho_current() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}, 99 );
add_filter( 'wpseo_robots', function ( $r ) {
	return null !== sol_ho_current() ? 'noindex, nofollow' : $r;
} );

add_action( 'wp_enqueue_scripts', function () {
	$c = sol_ho_current();
	if ( null === $c ) {
		return;
	}
	$ver = SOL_THEME_VERSION;
	wp_enqueue_style( 'sol-ho', SOL_URI . 'assets/css/home-options/base.css', array( 'sol', 'sol-sections', 'sol-footer' ), $ver );
	wp_enqueue_style( 'sol-ho-' . ( '' === $c ? 'index' : $c ), SOL_URI . 'assets/css/home-options/' . ( '' === $c ? 'index' : $c ) . '.css', array( 'sol-ho' ), $ver );
	wp_enqueue_script( 'sol-ho', SOL_URI . 'assets/js/sol-home-options.js', array(), $ver, true );
}, 20 );

/* ------------------------------------------------------------------ */
/* Content                                                            */
/* ------------------------------------------------------------------ */

/** Page sections of a seed item ('pages' or 'blocks'). */
function sol_ho_rows( $group, $path ) {
	foreach ( (array) ( sol_seed()[ $group ] ?? array() ) as $it ) {
		if ( $it['path'] === $path ) {
			return (array) ( $it['fields']['page_sections'] ?? array() );
		}
	}
	return array();
}

/** First row in $rows matching every key => value in $where. */
function sol_ho_find( $rows, $where ) {
	foreach ( $rows as $r ) {
		$ok = true;
		foreach ( $where as $k => $v ) {
			if ( ! isset( $r[ $k ] ) || $r[ $k ] !== $v ) {
				$ok = false;
				break;
			}
		}
		if ( $ok ) {
			return $r;
		}
	}
	return array();
}

/** Everything the option pages show. */
function sol_ho_data() {
	static $d = null;
	if ( null !== $d ) {
		return $d;
	}
	$home       = sol_ho_rows( 'pages', 'home' );
	$preview    = sol_ho_rows( 'pages', 'home-new' );
	$compliance = sol_ho_rows( 'blocks', 'compliance' );
	$roadmap    = sol_ho_rows( 'blocks', 'ndis-roadmap' );
	$logos      = sol_ho_find( $home, array( 'acf_fc_layout' => 'logo_strip' ) );
	$examples   = sol_ho_find( $home, array( 'acf_fc_layout' => 'card_grid', 'card_style' => 'tagged' ) );
	$portal     = sol_ho_find( $preview, array( 'acf_fc_layout' => 'card_grid', 'card_style' => 'bordered' ) );
	$faq        = sol_ho_find( $preview, array( 'acf_fc_layout' => 'faq' ) );
	$life       = sol_ho_find( $compliance, array( 'acf_fc_layout' => 'steps' ) );
	$road       = sol_ho_find( $roadmap, array( 'acf_fc_layout' => 'steps' ) );

	$phone = sol_opt( 'phone', '+61 460 003 494' );
	$d     = array(
		'phone'     => $phone,
		'tel'       => 'tel:' . preg_replace( '/[^0-9+]/', '', sol_opt( 'phone_link', $phone ) ),
		'email'     => sol_opt( 'email', 'info@solbusinessconsultant.com.au' ),
		'address'   => sol_opt( 'address', 'Glenroy VIC 3046, Australia' ),
		'whatsapp'  => 'https://wa.me/61460003494',
		'headline'  => array( 'Business Support That', 'Moves You Forward.' ),
		'intro'     => 'Practical business consulting, NDIS support and digital solutions designed to help Australian businesses and providers operate, grow and succeed.',
		'badges'    => array( array( 'map-pin', 'Australian-owned' ), array( 'shield-check', 'NDIS-aligned processes' ), array( 'badge-check', 'Easy Compliance support' ) ),
		'stats'     => array( array( '12', '+', 'Business services' ), array( '2', '', 'Dedicated portals' ), array( '24', 'h', 'Response target' ), array( '1', '', 'Team for everything' ) ),
		'audiences' => array( array( 'heart-handshake', 'NDIS providers' ), array( 'store', 'Small businesses' ), array( 'rocket', 'Start-ups' ), array( 'trending-up', 'Growing organisations' ), array( 'building-2', 'Australian service providers' ) ),
		'areas'     => array(
			array(
				'key' => 'business', 'icon' => 'briefcase-business', 'title' => 'Business Consulting & Support', 'short' => 'Business',
				'img' => 'about-consultant-conversation', 'alt' => 'SOL consultant in a planning conversation with a client', 'url' => '/services/',
				'text' => 'Company setup, bookkeeping and BAS, payroll, accounting and admin support, so the day-to-day runs smoothly.',
				'links' => array(
					array( 'Company & Business Registration', '/services/company-business-registration/' ),
					array( 'Bookkeeping & BAS Lodgement', '/services/bookkeeping-bas-lodgement/' ),
					array( 'Payroll Services', '/services/payroll-services/' ),
					array( 'Accounting Consultancy', '/services/accountancy/' ),
					array( 'Business & Personal Loan Support', '/services/business-personal-loan-support/' ),
					array( 'Admin & Back-Office Support', '/services/admin-back-office-support/' ),
				),
			),
			array(
				'key' => 'ndis', 'icon' => 'shield-check', 'title' => 'NDIS Registration & Compliance', 'short' => 'NDIS',
				'img' => 'ndis-registration-support-worker', 'alt' => 'Support worker assisting an NDIS participant with an exercise session', 'url' => '/services/ndis-registration/',
				'text' => 'Registration guidance, policies and audit preparation for new and growing NDIS providers, plus training for support coordinators.',
				'links' => array(
					array( 'NDIS Registration', '/services/ndis-registration/' ),
					array( 'NDIS Packages & Pricing', '/ndis-packages/' ),
					array( 'NDIS Readiness Quiz', '/readiness-quiz/' ),
					array( 'Support Coordination Training', '/services/ndis-support-coordination-training/' ),
					array( 'Easy Compliance', '/about/#compliance' ),
				),
			),
			array(
				'key' => 'digital', 'icon' => 'monitor-smartphone', 'title' => 'Digital Solutions & Growth', 'short' => 'Digital',
				'img' => 'website-development-wireframes', 'alt' => 'Designer sketching website wireframes beside a laptop', 'url' => '/services/website-development/',
				'text' => 'Websites, search, ads, social media and automation that help customers find you and save your team time.',
				'links' => array(
					array( 'Website Development', '/services/website-development/' ),
					array( 'Search Engine Optimisation', '/services/search-engine-optimization/' ),
					array( 'Google Ads Management', '/services/google-adwords/' ),
					array( 'Social Media Marketing', '/services/social-media-marketing/' ),
					array( 'Software & NDIS Automation', '/services/software-automation/' ),
					array( 'AI & Machine Learning', '/ai-machine-learning/' ),
				),
			),
		),
		'why'       => array(
			array( 'compass', 'Practical guidance', 'Advice you can act on straight away, explained in plain English.' ),
			array( 'user-round-check', 'Personalised support', 'One team that learns how your business works and shapes the plan around it.' ),
			array( 'file-check', 'Transparent process', 'Scope and price are agreed in writing before any paid work begins.' ),
			array( 'life-buoy', 'Ongoing assistance', 'We stay with you after setup, with updates and documents in your own client portal.' ),
		),
		'steps'     => array(
			array( 'message-circle', 'Tell us what you need', 'Book a free 30-minute call or send us a message. We listen first.' ),
			array( 'clipboard-list', 'Get a clear plan', 'We confirm the scope, timeline and price in writing before any work starts.' ),
			array( 'rocket', 'Move forward with confidence', "We do the work alongside you and keep you updated until it's done." ),
		),
		'roadmap'   => sol_rows( $road['groups'][0]['steps'] ?? array() ),
		'lifecycle' => sol_rows( $life['groups'][0]['steps'] ?? array() ),
		'portal'    => sol_rows( $portal['cards'] ?? array() ),
		'examples'  => sol_rows( $examples['cards'] ?? array() ),
		'faqs'      => sol_rows( $faq['items'] ?? array() ),
		'platforms' => array_map( function ( $l ) {
			return str_replace( '|', ' ', (string) ( $l['name'] ?? '' ) );
		}, (array) ( $logos['logos'] ?? array() ) ),
		'quiz'      => sol_ho_find( $preview, array( 'acf_fc_layout' => 'quiz' ) ),
	);
	return $d;
}

/** A theme photo (assets/images/photos/{file}.webp). */
function sol_ho_img( $file, $alt = '', $class = '', $eager = false, $sizes = '' ) {
	return sprintf(
		'<img src="%s" alt="%s" class="%s" width="1600" height="1067" loading="%s" decoding="async"%s>',
		esc_url( SOL_URI . 'assets/images/photos/' . $file . '.webp' ),
		esc_attr( $alt ),
		esc_attr( $class ),
		$eager ? 'eager' : 'lazy',
		$eager ? ' fetchpriority="high"' : ''
	);
}

/** Shared pricing tabs (the same section as on the Pricing page). */
function sol_ho_pricing() {
	$b = get_page_by_path( 'pricing-tabs', OBJECT, 'sol_block' );
	if ( $b && function_exists( 'get_field' ) ) {
		sol_render_sections( $b->ID );
		return;
	}
	foreach ( sol_ho_rows( 'blocks', 'pricing-tabs' ) as $i => $row ) {
		sol_render_row( $row, $i );
	}
}

/** The service finder quiz. */
function sol_ho_quiz( $overrides = array() ) {
	$q = sol_ho_data()['quiz'];
	if ( $q ) {
		sol_render_row( array_merge( $q, $overrides ) );
	}
}

/** FAQ accordion markup (also adds FAQPage schema once). */
function sol_ho_faqs( $class = '' ) {
	$faqs = sol_ho_data()['faqs'];
	echo '<div class="sol-accordion ' . esc_attr( $class ) . '">';
	foreach ( $faqs as $f ) {
		echo '<details class="sol-accordion__item"><summary>' . esc_html( $f['question'] ) . sol_icon( 'chevron-down', 'sol-accordion__chev' ) . '</summary><div class="sol-accordion__body">' . sol_text( $f['answer'] ) . '</div></details>'; // phpcs:ignore
	}
	echo '</div>';
}

/** Floating bar to move between options. */
function sol_ho_switcher() {
	$c = sol_ho_current();
	echo '<nav class="sol-ho-switch" aria-label="Home page options"><a class="sol-ho-switch__all" href="' . esc_url( home_url( '/home-options/' ) ) . '">' . sol_icon( 'layout-grid' ) . '<span>All options</span></a>'; // phpcs:ignore
	foreach ( sol_ho_options() as $slug => $o ) {
		printf(
			'<a href="%s"%s title="%s"><b>%s</b><span>%s</span></a>',
			esc_url( home_url( '/home-options/' . $slug . '/' ) ),
			$slug === $c ? ' aria-current="page"' : '',
			esc_attr( 'Option ' . $o['letter'] . ': ' . $o['name'] ),
			esc_html( $o['letter'] ),
			esc_html( $o['name'] )
		);
	}
	echo '</nav>';
}

/** Section heading used by every option: eyebrow, title, optional intro. */
function sol_ho_head( $eyebrow, $title, $intro = '', $class = '' ) {
	echo '<header class="ho-head ' . esc_attr( $class ) . '" data-reveal>';
	if ( $eyebrow ) {
		echo '<span class="ho-eyebrow">' . esc_html( $eyebrow ) . '</span>';
	}
	echo '<h2 class="ho-title">' . wp_kses( $title, array( 'br' => array(), 'em' => array(), 'span' => array( 'class' => true ) ) ) . '</h2>';
	if ( $intro ) {
		echo '<p class="ho-intro">' . esc_html( $intro ) . '</p>';
	}
	echo '</header>';
}
