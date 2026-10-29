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
		$data = sol_menu_from_options();
	}
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
			'url'     => '#' === $it->url ? '' : $it->url,
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
		foreach ( sol_menu_from_options() as $top ) {
			$top_id = $add( $top['label'], $top['url'] );
			if ( 'mega' === $top['type'] && $top_id ) {
				foreach ( $top['columns'] as $col ) {
					$col_id = $add( $col['label'], $col['url'], $top_id, $col );
					foreach ( $col['items'] as $l ) {
						$add( $l['label'], $l['url'], $col_id, $l );
					}
				}
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
