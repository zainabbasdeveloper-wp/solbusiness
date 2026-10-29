<?php
/**
 * Theme setup: SCF options page + Local JSON, helper post types, assets,
 * and the switch from Elementor headers/footers to the SOL ones.
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ */
/* SCF                                                                 */
/* ------------------------------------------------------------------ */

add_action( 'acf/init', function () {
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page( array(
			'page_title' => 'SOL Site Settings',
			'menu_title' => 'SOL Site',
			'menu_slug'  => SOL_OPTIONS_SLUG,
			'capability' => 'manage_options',
			'icon_url'   => 'dashicons-admin-site-alt3',
			'position'   => 59,
			'redirect'   => false,
		) );
	}
} );

// Field groups live in the theme's acf-json folder and stay editable in SCF → Field Groups.
add_filter( 'acf/settings/load_json', function ( $paths ) {
	$paths[] = SOL_DIR . 'acf-json';
	return $paths;
} );
add_filter( 'acf/json/save_paths', function ( $paths, $post ) {
	if ( isset( $post['key'] ) && 0 === strpos( $post['key'], 'group_sol_' ) ) {
		return array( SOL_DIR . 'acf-json' );
	}
	return $paths;
}, 10, 2 );

add_action( 'admin_notices', function () {
	if ( function_exists( 'acf_add_local_field_group' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>Sol-Business Consultant theme</strong> needs the <a href="' . esc_url( admin_url( 'plugin-install.php?s=secure+custom+fields&tab=search&type=term' ) ) . '">Secure Custom Fields</a> plugin for its page sections.</p></div>';
} );

/* ------------------------------------------------------------------ */
/* Post types                                                          */
/* ------------------------------------------------------------------ */

add_action( 'init', function () {
	register_post_type( 'sol_block', array(
		'labels'              => array( 'name' => 'Reusable Sections', 'singular_name' => 'Reusable Section', 'add_new_item' => 'Add Reusable Section', 'edit_item' => 'Edit Reusable Section' ),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => SOL_OPTIONS_SLUG,
		'supports'            => array( 'title', 'revisions' ),
		'exclude_from_search' => true,
	) );
	register_post_type( 'sol_enquiry', array(
		'labels'              => array( 'name' => 'Enquiries', 'singular_name' => 'Enquiry', 'edit_item' => 'View Enquiry' ),
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => SOL_OPTIONS_SLUG,
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'        => true,
		'exclude_from_search' => true,
	) );
} );

// Parent menu when SCF is missing, so the submenus still have a home.
add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		add_menu_page( 'SOL Site', 'SOL Site', 'manage_options', SOL_OPTIONS_SLUG, '__return_null', 'dashicons-admin-site-alt3', 59 );
	}
}, 9 );

// Classic editor for SOL templates and reusable sections; the sections replace page content.
add_filter( 'use_block_editor_for_post', function ( $use, $post ) {
	if ( $post && ( 'sol_block' === $post->post_type || sol_is_sol_template( $post->ID ) ) ) {
		return false;
	}
	return $use;
}, 10, 2 );

// Enquiry list columns + read-only detail box.
add_filter( 'manage_sol_enquiry_posts_columns', function ( $cols ) {
	return array( 'cb' => $cols['cb'], 'title' => 'Name', 'sol_email' => 'Email', 'sol_type' => 'Form', 'sol_service' => 'Service / topic', 'date' => 'Received' );
} );
add_action( 'manage_sol_enquiry_posts_custom_column', function ( $col, $post_id ) {
	$map = array( 'sol_email' => 'email', 'sol_type' => 'form_variant', 'sol_service' => 'selection' );
	if ( isset( $map[ $col ] ) ) {
		echo esc_html( (string) get_post_meta( $post_id, $map[ $col ], true ) );
	}
}, 10, 2 );
add_action( 'add_meta_boxes_sol_enquiry', function () {
	add_meta_box( 'sol_enquiry_details', 'Submission', function ( $post ) {
		$data = get_post_meta( $post->ID, '_sol_fields', true );
		if ( ! is_array( $data ) ) {
			return;
		}
		echo '<table class="widefat striped"><tbody>';
		foreach ( $data as $label => $value ) {
			echo '<tr><th style="width:200px">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}, 'sol_enquiry', 'normal', 'high' );
} );

/* ------------------------------------------------------------------ */
/* Front end                                                           */
/* ------------------------------------------------------------------ */

/** True when the current page uses one of the SOL page templates. */
function sol_is_sol_template( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_queried_object_id();
	$tpl     = $post_id ? get_page_template_slug( $post_id ) : '';
	return in_array( $tpl, array( SOL_TPL_BUILDER, SOL_TPL_SERVICE ), true );
}

/** Pages fully drawn by the theme (sections, blog), as opposed to Elementor or WooCommerce content. */
function sol_is_sol_view() {
	return ( is_page() && sol_is_sol_template() ) || is_singular( 'post' ) || is_home();
}

add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'sol-chrome';
	if ( sol_is_sol_view() ) {
		$classes[] = 'sol-site';
		// Elementor's global kit styles would override the SOL typography on these pages.
		$classes = array_values( array_filter( $classes, function ( $c ) {
			return 0 !== strpos( $c, 'elementor-kit-' );
		} ) );
	}
	return $classes;
} );

add_action( 'wp_enqueue_scripts', function () {
	$ver = SOL_THEME_VERSION;
	wp_enqueue_style( 'sol-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap', array(), null );
	wp_enqueue_style( 'sol', SOL_URI . 'assets/css/sol.css', array(), $ver );
	wp_enqueue_style( 'sol-hero', SOL_URI . 'assets/css/sol-hero.css', array( 'sol' ), $ver );
	wp_enqueue_style( 'sol-nav', SOL_URI . 'assets/css/sol-nav.css', array( 'sol' ), $ver );
	wp_enqueue_style( 'sol-sections', SOL_URI . 'assets/css/sol-sections.css', array( 'sol' ), $ver );
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 'sol-woo', SOL_URI . 'assets/css/sol-woo.css', array( 'sol' ), $ver );
		wp_enqueue_style( 'sol-shop', SOL_URI . 'assets/css/sol-shop.css', array( 'sol-woo' ), $ver );
	}
	wp_enqueue_script( 'lucide', 'https://unpkg.com/lucide@0.475.0/dist/umd/lucide.min.js', array(), '0.475.0', true );
	wp_enqueue_script( 'sol', SOL_URI . 'assets/js/sol.js', array( 'lucide' ), $ver, true );
	wp_localize_script( 'sol', 'SOL', array(
		'success' => sol_opt( 'default_success', 'Thanks. The SOL team will contact you within one business day.' ),
	) );
}, 20 );

/*
 * Elementor Pro theme-builder and "Elementor Header & Footer Builder" both hook into
 * get_header / get_footer to swap in their templates. The SOL header and footer replace
 * them on every page, so those hooks are removed on the front end.
 * To go back to Elementor headers: add_filter( 'sol_use_theme_header_footer', '__return_false' );
 */
add_action( 'wp', function () {
	if ( is_admin() || ! apply_filters( 'sol_use_theme_header_footer', true ) ) {
		return;
	}
	remove_all_actions( 'get_header' );
	remove_all_actions( 'get_footer' );
}, 999 );

/*
 * Switch on load animations before the page paints, only when JavaScript runs and the visitor
 * allows motion. Without this class every animated element simply shows in its final state.
 */
add_action( 'wp_head', function () {
	echo "<script>if(window.matchMedia&&!matchMedia('(prefers-reduced-motion: reduce)').matches){document.documentElement.classList.add('sol-anim');}</script>\n";
}, 1 );

add_action( 'wp_head', function () {
	$code = sol_opt( 'head_code' );
	if ( $code ) {
		echo $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- admin-only setting
	}
}, 5 );
add_filter( 'acf/update_value/name=head_code', function ( $value ) {
	return current_user_can( 'unfiltered_html' ) ? $value : '';
} );

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	// WooCommerce templates (shop, product, cart, checkout) render inside the SOL header/footer.
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

// Content width for embeds and images in the editor.
if ( ! isset( $content_width ) ) {
	$content_width = 1180; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}

// Elementor: let pages that haven't been rebuilt yet keep working without Hello Elementor.
add_action( 'elementor/theme/register_locations', function ( $manager ) {
	$manager->register_all_core_location();
} );
