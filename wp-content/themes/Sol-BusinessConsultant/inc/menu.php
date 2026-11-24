<?php
/**
 * Main menu data.
 *
 * The header menu can be edited in two places:
 *  1. Appearance → Menus → "SOL Main Menu" (used whenever a menu is assigned to the "Main menu" location).
 *     Top-level item with children that have their own children = mega menu:
 *       Services                ← top-level item
 *         NDIS Services         ← category (left-hand tab)
 *           NDIS Registration   ← link inside that tab
 *     Top-level item with plain children = small dropdown.
 *     Each item has extra SOL fields: icon, short description, badge.
 *  2. SOL Site → Header (fallback when no WordPress menu is assigned).
 *
 * On first load after this theme version the WordPress menu is created from the SOL Site settings,
 * so both start out identical. SOL Setup → "Rebuild main menu" resets it to the theme defaults.
 */

defined( 'ABSPATH' ) || exit;

const SOL_MENU_LOCATION = 'sol-primary';

add_action( 'after_setup_theme', function () {
	register_nav_menus( array( SOL_MENU_LOCATION => 'Main menu (SOL header)' ) );
} );

/* ------------------------------------------------------------------ */
/* Normalised data used by header.php                                  */
/* ------------------------------------------------------------------ */

/**
 * List of top-level items:
 *   label, url, type (link|mega|dropdown), columns (mega) or children (dropdown).
 * Column: label, url, icon, desc, badge, items[]. Item: label, url, icon, desc, badge.
 */
function sol_menu_data() {
	static $data = null;
	if ( null !== $data ) {
		return $data;
	}
	$data = sol_menu_from_wp();
	if ( null === $data ) {
		$data = sol_menu_from_layout();
	}
	$data = sol_menu_add_directory( $data );
	$data = sol_menu_compliance_labels( $data );
	return $data;
}

/**
 * Old service names and menu descriptions that described SOL as a BAS/tax agent, accountant or broker,
 * mapped to the compliant wording (same list as _dev/build/content/compliance.mjs). Applied when the menu and
 * footer are shown, so links saved in Appearance → Menus or SOL Site settings are corrected too.
 */
function sol_compliance_labels() {
	return (array) apply_filters( 'sol_compliance_labels', array(
		'Bookkeeping & BAS Lodgement'      => 'Bookkeeping Support',
		'Bookkeeping & BAS'                => 'Bookkeeping Support',
		'Business & Personal Loan Support' => 'Finance Broker Referrals',
		'Business & Personal Loans'        => 'Finance Broker Referrals',
		'Accountancy Partnerships'         => 'Accounting Partners',
		'Payroll Services'                 => 'Payroll Admin Support',
		'Company & Business Registration'  => 'Business Setup Support',
		'Accounting Consultancy'           => 'Business Finance Guidance',
		'Accountancy'                      => 'Business Finance Guidance',
		'ASIC, ABN, TFN & GST setup'       => 'ASIC and ABN applications',
		'Xero, QuickBooks & BAS'           => 'Xero and QuickBooks support',
		'Xero, MYOB, QuickBooks & BAS'     => 'Xero and QuickBooks support',
		'PAYG, super & STP support'        => 'Payroll software and records',
		'Loan packs & broker links'        => 'Funding document packs',
		'CPA network & growth planning'    => 'Accountant referrals',
	) );
}

/** The compliant wording for a menu or footer label (unchanged when it isn't on the list). */
function sol_compliance_label( $text ) {
	$map  = sol_compliance_labels();
	$text = (string) $text;
	$key  = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	return isset( $map[ $key ] ) ? $map[ $key ] : $text;
}

function sol_menu_compliance_labels( $data ) {
	$fix = function ( $node ) {
		foreach ( array( 'label', 'desc' ) as $k ) {
			if ( isset( $node[ $k ] ) ) {
				$node[ $k ] = sol_compliance_label( $node[ $k ] );
			}
		}
		return $node;
	};
	foreach ( (array) $data as $i => $top ) {
		$data[ $i ] = $fix( $top );
		foreach ( array( 'children', 'columns' ) as $list ) {
			foreach ( (array) ( $top[ $list ] ?? array() ) as $j => $c ) {
				$data[ $i ][ $list ][ $j ] = $fix( $c );
				foreach ( (array) ( $c['items'] ?? array() ) as $k => $it ) {
					$data[ $i ][ $list ][ $j ]['items'][ $k ] = $fix( $it );
				}
			}
		}
	}
	return $data;
}

/* ------------------------------------------------------------------ */
/* Provider directory link (directory.solbusinessconsultant.com.au)    */
/* ------------------------------------------------------------------ */

/** The provider directory site. Change with the 'sol_directory_url' filter; return '' to hide the links. */
function sol_directory_url() {
	return (string) apply_filters( 'sol_directory_url', 'https://directory.solbusinessconsultant.com.au/' );
}

/** Descriptive link text (not "click here"), used in the menu and footer. */
function sol_directory_label() {
	return (string) apply_filters( 'sol_directory_label', 'NDIS and aged care provider directory' );
}

/** True when $url points at the directory site. */
function sol_is_directory_url( $url ) {
	$host = wp_parse_url( sol_directory_url(), PHP_URL_HOST );
	return $host && wp_parse_url( (string) $url, PHP_URL_HOST ) === $host;
}

/**
 * Adds the directory to the Services mega menu, in the NDIS column (or the first column), unless a menu item
 * already links to it. Works for both menu sources (Appearance → Menus and SOL Site → Header).
 */
function sol_menu_add_directory( $data ) {
	if ( '' === sol_directory_url() || ! is_array( $data ) ) {
		return $data;
	}
	$mega = null;
	foreach ( $data as $i => $top ) {
		if ( sol_is_directory_url( $top['url'] ?? '' ) ) {
			return $data;
		}
		foreach ( (array) ( $top['children'] ?? array() ) as $c ) {
			if ( sol_is_directory_url( $c['url'] ?? '' ) ) {
				return $data;
			}
		}
		foreach ( (array) ( $top['columns'] ?? array() ) as $col ) {
			if ( sol_is_directory_url( $col['url'] ?? '' ) ) {
				return $data;
			}
			foreach ( (array) ( $col['items'] ?? array() ) as $it ) {
				if ( sol_is_directory_url( $it['url'] ?? '' ) ) {
					return $data;
				}
			}
		}
		if ( null === $mega && 'mega' === ( $top['type'] ?? '' ) && ! empty( $top['columns'] ) ) {
			$mega = $i;
		}
	}
	if ( null === $mega ) {
		return $data; // no mega menu: the footer link still shows
	}
	$col = 0;
	foreach ( $data[ $mega ]['columns'] as $j => $c ) {
		if ( false !== stripos( (string) ( $c['label'] ?? '' ), 'ndis' ) ) {
			$col = $j;
			break;
		}
	}
	$data[ $mega ]['columns'][ $col ]['items'][] = array(
		'label'   => 'NDIS & Aged Care Provider Directory',
		'url'     => sol_directory_url(),
		'icon'    => 'search',
		'desc'    => 'Find registered NDIS and aged care providers near you',
		'badge'   => '',
		'target'  => '',
		'current' => false,
	);
	return $data;
}

/** Menu built from Appearance → Menus, or null when no menu is assigned. */
function sol_menu_from_wp() {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ SOL_MENU_LOCATION ] ) ) {
		return null;
	}
	$items = wp_get_nav_menu_items( $locations[ SOL_MENU_LOCATION ] );
	if ( ! $items ) {
		return null;
	}
	if ( ! is_admin() ) {
		_wp_menu_item_classes_by_context( $items );
	}
	$by_parent = array();
	foreach ( $items as $it ) {
		$by_parent[ (int) $it->menu_item_parent ][] = $it;
	}
	$node = function ( $it ) {
		return array(
			'label'   => $it->title,
			'url'     => '#' === $it->url ? '' : sol_url( $it->url ),
			'icon'    => (string) get_post_meta( $it->ID, '_sol_icon', true ),
			'desc'    => (string) get_post_meta( $it->ID, '_sol_desc', true ),
			'badge'   => (string) get_post_meta( $it->ID, '_sol_badge', true ),
			'target'  => $it->target,
			// Links to a section of a page ("/#compliance") are never marked as the current page.
			'current' => ( ! empty( $it->current ) || ! empty( $it->current_item_ancestor ) ) && false === strpos( $it->url, '#' ),
		);
	};
	$out = array();
	foreach ( isset( $by_parent[0] ) ? $by_parent[0] : array() as $top ) {
		$entry    = $node( $top );
		$children = isset( $by_parent[ $top->ID ] ) ? $by_parent[ $top->ID ] : array();
		$is_mega  = false;
		foreach ( $children as $c ) {
			if ( ! empty( $by_parent[ $c->ID ] ) ) {
				$is_mega = true;
				break;
			}
		}
		if ( $is_mega ) {
			$entry['type']    = 'mega';
			$entry['columns'] = array();
			foreach ( $children as $c ) {
				$col          = $node( $c );
				$col['items'] = array_map( $node, isset( $by_parent[ $c->ID ] ) ? $by_parent[ $c->ID ] : array() );
				$entry['columns'][] = $col;
			}
		} elseif ( $children ) {
			$entry['type']     = 'dropdown';
			$entry['children'] = array_map( $node, $children );
		} else {
			$entry['type'] = 'link';
		}
		$out[] = $entry;
	}
	return $out;
}

/** Menu built from SOL Site → Header. */
function sol_menu_from_options() {
	$out  = array();
	$mega = sol_rows( sol_opt( 'mega_menu', array() ) );
	foreach ( sol_rows( sol_opt( 'nav_items', array() ) ) as $item ) {
		$entry = array(
			'label'   => (string) sol_get( $item, 'label' ),
			'url'     => sol_url( sol_get( $item, 'url' ) ),
			'icon'    => '',
			'desc'    => '',
			'badge'   => '',
			'target'  => '',
			'current' => false,
			'type'    => 'link',
		);
		if ( 'mega' === sol_get( $item, 'type' ) && $mega ) {
			$entry['type']    = 'mega';
			$entry['columns'] = array();
			foreach ( $mega as $col ) {
				$links = array();
				foreach ( sol_rows( sol_get( $col, 'items', array() ) ) as $l ) {
					$links[] = array(
						'label'   => (string) sol_get( $l, 'label' ),
						'url'     => sol_url( sol_get( $l, 'url' ) ),
						'icon'    => (string) sol_get( $l, 'icon' ),
						'desc'    => (string) sol_get( $l, 'desc' ),
						'badge'   => '',
						'target'  => '',
						'current' => false,
					);
				}
				$entry['columns'][] = array(
					'label' => (string) sol_get( $col, 'label' ),
					'url'   => sol_url( sol_get( $col, 'url' ) ),
					'icon'  => (string) sol_get( $col, 'icon' ),
					'desc'  => (string) sol_get( $col, 'desc' ),
					'badge' => (string) sol_get( $col, 'badge' ),
					'items' => $links,
				);
			}
		}
		$out[] = $entry;
	}
	return $out;
}

/** True when the link points at the page being viewed. */
function sol_menu_is_current( $url ) {
	if ( ! $url || '#' === $url[0] ) {
		return false;
	}
	$path = wp_parse_url( $url, PHP_URL_PATH );
	$here = wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH );
	return $path && trailingslashit( $path ) === trailingslashit( (string) $here ) && '/' !== $path;
}

/* ------------------------------------------------------------------ */
/* Build the WordPress menu from the SOL Site settings                 */
/* ------------------------------------------------------------------ */

/**
 * Create (or with $rebuild, refill) "SOL Main Menu" from the SOL Site header settings and
 * assign it to the Main menu location. Returns the menu ID or 0.
 */
function sol_build_wp_menu( $rebuild = false ) {
	ignore_user_abort( true ); // Finish the rebuild even if the browser leaves the page.
	$name = 'SOL Main Menu';
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu && ! $rebuild ) {
		$menu_id = (int) $menu->term_id;
	} else {
		if ( $menu ) {
			foreach ( (array) wp_get_nav_menu_items( $menu->term_id, array( 'post_status' => 'any' ) ) as $old ) {
				wp_delete_post( $old->ID, true );
			}
			$menu_id = (int) $menu->term_id;
		} else {
			$menu_id = wp_create_nav_menu( $name );
			if ( is_wp_error( $menu_id ) ) {
				return 0;
			}
		}
		$add = function ( $label, $url, $parent = 0, $meta = array() ) use ( $menu_id ) {
			$args = array(
				'menu-item-title'     => $label,
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
			);
			$page = sol_menu_page_for_url( $url );
			if ( $page ) {
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = $page;
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = $url ? $url : '#';
			}
			$id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( is_wp_error( $id ) ) {
				return 0;
			}
			foreach ( array( 'icon', 'desc', 'badge' ) as $k ) {
				if ( ! empty( $meta[ $k ] ) ) {
					update_post_meta( $id, '_sol_' . $k, sanitize_text_field( $meta[ $k ] ) );
				}
			}
			return $id;
		};
		foreach ( sol_menu_layout() as $top ) {
			$top_id = $add( $top['label'], $top['url'] );
			foreach ( $top_id ? (array) ( $top['children'] ?? array() ) : array() as $c ) {
				$add( $c['label'], $c['url'], $top_id, $c );
			}
		}
	}
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( $rebuild || empty( $locations[ SOL_MENU_LOCATION ] ) ) {
		$locations[ SOL_MENU_LOCATION ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}
	update_option( 'sol_menu_built', SOL_THEME_VERSION, false );
	return $menu_id;
}

/**
 * The main menu (v2.23.0): one small dropdown per area, as approved on 24 Nov 2026.
 * Home | AI Automation | NDIS Services | Training Academy | Business Services | Digital Marketing | About | Contact
 * A dropdown's own link is shown first as "<label> overview". Edit later in Appearance → Menus.
 */
function sol_menu_layout() {
	$c = function ( $label, $url, $icon, $desc ) {
		return array( 'label' => $label, 'url' => $url, 'icon' => $icon, 'desc' => $desc );
	};
	$training = 'https://training.solbusinessconsultant.com.au';
	$dir      = function_exists( 'sol_directory_url' ) ? sol_directory_url() : '';
	$ndis     = array(
		$c( 'NDIS Registration', '/services/ndis-registration/', 'shield-check', 'Registration preparation and audit readiness' ),
		$c( 'Readiness Quiz', '/readiness-quiz/', 'list-checks', '8 questions, personalised result' ),
		$c( 'Readiness Calculator', '/ndis-readiness-calculator/', 'calculator', 'See how ready you are for registration' ),
	);
	if ( $dir ) {
		$ndis[] = $c( 'NDIS and aged care provider directory', $dir, 'search', 'Find registered providers near you' );
	}
	return (array) apply_filters( 'sol_menu_layout', array(
		array( 'label' => 'Home', 'url' => '/' ),
		array( 'label' => 'AI Automation', 'url' => '/services/software-automation/', 'children' => array(
			$c( 'AI & Machine Learning', '/ai-machine-learning/', 'brain-circuit', 'Data annotation and AI projects' ),
			$c( 'Easy Compliance platform', '/#compliance', 'clipboard-check', 'Compliance software for NDIS providers' ),
		) ),
		array( 'label' => 'NDIS Services', 'url' => '/ndis-packages/', 'children' => $ndis ),
		array( 'label' => 'Training Academy', 'url' => '/training-2/', 'children' => array(
			$c( 'Support Coordination Training', '/services/ndis-support-coordination-training/', 'graduation-cap', 'Online NDIS Support Coordinator course' ),
			$c( 'Verify Certificate', $training . '/verify-certificate', 'badge-check', 'Check a SOL completion certificate' ),
			$c( 'Student Dashboard', $training . '/student-dashboard', 'layout-dashboard', 'Continue your course' ),
		) ),
		array( 'label' => 'Business Services', 'url' => '/services/', 'children' => array(
			$c( 'Business Setup Support', '/services/company-business-registration/', 'building-2', 'ASIC and ABN applications' ),
			$c( 'Bookkeeping Support', '/services/bookkeeping-support/', 'receipt-text', 'Xero and QuickBooks support' ),
			$c( 'Payroll Admin Support', '/services/payroll-services/', 'users', 'Payroll software and records' ),
			$c( 'Business Finance Guidance', '/services/accountancy/', 'calculator', 'Finance admin and record keeping' ),
			$c( 'Accounting Partners', '/services/accountancy-partnerships/', 'handshake', 'Accountant referrals' ),
			$c( 'Finance Broker Referrals', '/services/finance-broker-referrals/', 'briefcase-business', 'Funding document packs' ),
			$c( 'Admin & Back-Office Support', '/services/admin-back-office-support/', 'folder-kanban', 'Day-to-day admin handled' ),
			$c( 'Offshore Staffing', '/services/offshore-outsourcing-staff-solutions/', 'earth', 'Remote professionals by the hour' ),
		) ),
		array( 'label' => 'Digital Marketing', 'url' => '/marketing-packages/', 'children' => array(
			$c( 'Website Development', '/services/website-development/', 'globe', 'Custom business websites' ),
			$c( 'Social Media Marketing', '/services/social-media-marketing/', 'megaphone', 'Content and campaigns' ),
			$c( 'Search Engine Optimization', '/services/search-engine-optimization/', 'search', 'Rank higher on Google' ),
			$c( 'Google Ads', '/services/google-adwords/', 'mouse-pointer-click', 'Paid search campaigns' ),
		) ),
		array( 'label' => 'About', 'url' => '/about/', 'children' => array(
			$c( 'Pricing', '/pricing/', 'tag', 'Packages and prices' ),
			$c( 'Blog', '/blog/', 'newspaper', 'Guides and updates' ),
			$c( 'Case Studies', '/case-studies/', 'briefcase', 'Engagement examples' ),
			$c( 'FAQ', '/faq/', 'circle-help', 'Common questions' ),
			$c( 'Trust & Security', '/data-privacy-and-security/', 'lock', 'How we protect your data' ),
		) ),
		array( 'label' => 'Contact', 'url' => '/contact/' ),
	) );
}

/** Menu in the sol_menu_data() shape, used when no WordPress menu is assigned. */
function sol_menu_from_layout() {
	$node = function ( $it ) {
		return array( 'label' => $it['label'], 'url' => sol_url( $it['url'] ), 'icon' => $it['icon'] ?? '', 'desc' => $it['desc'] ?? '', 'badge' => '', 'target' => '', 'current' => false );
	};
	$out = array();
	foreach ( sol_menu_layout() as $top ) {
		$entry = $node( $top );
		if ( ! empty( $top['children'] ) ) {
			$entry['type']     = 'dropdown';
			$entry['children'] = array_map( $node, $top['children'] );
		} else {
			$entry['type'] = 'link';
		}
		$out[] = $entry;
	}
	return $out;
}

/*
 * Switch to the new menu once: the current "SOL Main Menu" is kept, renamed "SOL Main Menu (backup before
 * v2.23.0)", and a new one is built from sol_menu_layout() and assigned to the header. To go back, assign the
 * backup to "Main menu (SOL header)" in Appearance → Menus → Manage Locations.
 */
add_action( 'init', function () {
	if ( 'v2' === get_option( 'sol_menu_layout_version' ) || get_transient( 'sol_menu_v2_lock' ) ) {
		return;
	}
	set_transient( 'sol_menu_v2_lock', 1, MINUTE_IN_SECONDS );
	$old = wp_get_nav_menu_object( 'SOL Main Menu' );
	if ( $old ) {
		wp_update_nav_menu_object( $old->term_id, array( 'menu-name' => 'SOL Main Menu (backup before v2.23.0)' ) );
	}
	if ( sol_build_wp_menu( true ) ) {
		update_option( 'sol_menu_layout_version', 'v2', false );
	}
	delete_transient( 'sol_menu_v2_lock' );
}, 20 );

/** Page ID for an internal link, so menu items follow the page if its slug changes. */
function sol_menu_page_for_url( $url ) {
	if ( ! $url ) {
		return 0;
	}
	$home = untrailingslashit( home_url() );
	if ( 0 === strpos( $url, $home ) ) {
		$url = substr( $url, strlen( $home ) );
	}
	if ( '' === $url || '/' !== $url[0] || false !== strpos( $url, '#' ) || false !== strpos( $url, '?' ) ) {
		return 0;
	}
	$path = trim( $url, '/' );
	if ( '' === $path ) {
		return 0;
	}
	$page = get_page_by_path( $path );
	return $page && 'publish' === $page->post_status ? (int) $page->ID : 0;
}

// Create the WordPress menu once, the first time an admin opens the dashboard after the update
// (after the theme auto-update at priority 10 has refreshed the SOL Site settings).
add_action( 'admin_init', function () {
	if ( wp_doing_ajax() || ! current_user_can( 'edit_theme_options' ) || get_option( 'sol_menu_built' ) ) {
		return;
	}
	if ( ! get_option( 'sol_applied_build' ) && ! sol_opt( 'nav_items' ) ) {
		return; // Site not set up yet; SOL Setup builds the menu.
	}
	sol_build_wp_menu();
}, 20 );

/* ------------------------------------------------------------------ */
/* Extra fields on each menu item (Appearance → Menus)                 */
/* ------------------------------------------------------------------ */

add_action( 'wp_nav_menu_item_custom_fields', function ( $item_id ) {
	$fields = array(
		'icon'  => array( 'Icon', 'Lucide icon name, e.g. shield-check, briefcase, megaphone. See lucide.dev/icons' ),
		'desc'  => array( 'Short description', 'One short line shown under the label in the mega menu.' ),
		'badge' => array( 'Badge', 'Optional tag such as "Most popular" or "New".' ),
	);
	echo '<div class="sol-menu-fields" style="clear:both;padding:8px 0 4px;border-top:1px dashed #dcdcde;margin-top:8px"><strong style="display:block;margin-bottom:4px">SOL mega menu</strong>';
	wp_nonce_field( 'sol_menu_item', 'sol_menu_item_nonce' );
	foreach ( $fields as $k => $f ) {
		$val = get_post_meta( $item_id, '_sol_' . $k, true );
		printf(
			'<p class="description description-wide"><label>%1$s<br><input type="text" class="widefat" name="sol_menu_%2$s[%3$d]" value="%4$s"></label><span style="color:#646970;font-size:12px">%5$s</span></p>',
			esc_html( $f[0] ),
			esc_attr( $k ),
			(int) $item_id,
			esc_attr( $val ),
			esc_html( $f[1] )
		);
	}
	echo '</div>';
} );

add_action( 'wp_update_nav_menu_item', function ( $menu_id, $item_id ) {
	if ( ! isset( $_POST['sol_menu_item_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sol_menu_item_nonce'] ), 'sol_menu_item' ) ) {
		return;
	}
	foreach ( array( 'icon', 'desc', 'badge' ) as $k ) {
		$key = 'sol_menu_' . $k;
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) || ! array_key_exists( $item_id, $_POST[ $key ] ) ) {
			continue;
		}
		$val = sanitize_text_field( wp_unslash( $_POST[ $key ][ $item_id ] ) );
		if ( 'icon' === $k ) {
			$val = preg_replace( '/[^a-z0-9-]/', '', strtolower( $val ) );
		}
		if ( '' === $val ) {
			delete_post_meta( $item_id, '_sol_' . $k );
		} else {
			update_post_meta( $item_id, '_sol_' . $k, $val );
		}
	}
}, 10, 2 );

// Short how-to above the menu editor.
add_action( 'admin_notices', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'nav-menus' !== $screen->id ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>SOL header menu:</strong> edit "SOL Main Menu" (location "Main menu (SOL header)"). '
		. 'Drag items <em>under</em> a top-level item to make a dropdown. For the Services mega menu, use three levels: '
		. 'Services → category (e.g. NDIS Services) → links. Each item has icon, short description and badge fields at the bottom of its box. '
		. 'The dark "Get started" box in the mega menu is set in SOL Site → Header.</p></div>';
} );

/**
 * v2.18: the home page no longer holds the contact, compliance and NDIS sections.
 * Where an old link to one of them (/#contact, /#compliance-…, /#ndis-…) should go now.
 * Returns the URL unchanged when it isn't one of those links.
 */
function sol_home_anchor_url( $url ) {
	$home = untrailingslashit( home_url() );
	$path = 0 === strpos( (string) $url, $home ) ? substr( $url, strlen( $home ) ) : (string) $url;
	if ( ! preg_match( '~^/?#([a-z0-9-]+)$~', $path, $m ) ) {
		return $url;
	}
	$h   = $m[1];
	$map = array( 'contact' => '/contact/', 'pricing' => '/pricing/', 'faq' => '/faq/', 'about' => '/about/' );
	if ( isset( $map[ $h ] ) ) {
		return home_url( $map[ $h ] );
	}
	if ( 'compliance' === $h || 0 === strpos( $h, 'compliance-' ) ) {
		return home_url( '/about/#' . $h );
	}
	if ( 'ndis' === $h || 0 === strpos( $h, 'ndis-' ) ) {
		return home_url( '/services/ndis-registration/' );
	}
	return $url;
}

/**
 * v2.18.1: the original home page is back, so undo the one-time menu change from v2.18.0.
 * Each changed link gets its original "/#section" address back, matched by its label against
 * the theme's menu settings. Runs once, and only on sites where v2.18.0 changed the menu.
 */
add_action( 'admin_init', function () {
	if ( ! get_option( 'sol_menu_anchors_218' ) || get_option( 'sol_menu_anchors_218_undone' ) ) {
		return;
	}
	update_option( 'sol_menu_anchors_218_undone', 1, false );
	$seed = function_exists( 'sol_seed' ) ? sol_seed() : array();
	$orig = array(); // label => "/#section"
	$walk = function ( $v ) use ( &$walk, &$orig ) {
		if ( ! is_array( $v ) ) {
			return;
		}
		if ( isset( $v['label'], $v['url'] ) && is_string( $v['url'] ) && preg_match( '~^/#[a-z0-9-]+$~', $v['url'] ) ) {
			$orig[ trim( wp_strip_all_tags( $v['label'] ) ) ] = $v['url'];
		}
		foreach ( $v as $c ) {
			$walk( $c );
		}
	};
	$walk( isset( $seed['options'] ) ? $seed['options'] : array() );
	$ids = get_posts( array( 'post_type' => 'nav_menu_item', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_menu_item_type', 'meta_value' => 'custom' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $ids as $mid ) {
		$label = trim( wp_strip_all_tags( get_the_title( $mid ) ) );
		if ( ! isset( $orig[ $label ] ) ) {
			continue;
		}
		$now = (string) get_post_meta( $mid, '_menu_item_url', true );
		// Only links v2.18.0 rewrote: their old address maps to what is stored now.
		if ( $now !== $orig[ $label ] && untrailingslashit( sol_home_anchor_url( home_url( $orig[ $label ] ) ) ) === untrailingslashit( $now ) ) {
			update_post_meta( $mid, '_menu_item_url', $orig[ $label ] );
		}
	}
} );
