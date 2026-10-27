<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

// END ENQUEUE PARENT ACTION

/*
 * SOL Training Academy design for WordPress, built with Secure Custom Fields (no Elementor).
 * Setup: Appearance → SOL Setup. Field reference: _dev/FIELD-REFERENCE.md
 */
define( 'SOL_THEME_VERSION', '2.0.0' );
define( 'SOL_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'SOL_URI', trailingslashit( get_stylesheet_directory_uri() ) );
define( 'SOL_OPTIONS_SLUG', 'sol-site-settings' );
define( 'SOL_TPL_BUILDER', 'page-templates/sol-builder.php' );
define( 'SOL_TPL_SERVICE', 'page-templates/sol-service.php' );

require_once SOL_DIR . 'inc/helpers.php';
require_once SOL_DIR . 'inc/setup.php';
require_once SOL_DIR . 'inc/render.php';
require_once SOL_DIR . 'inc/components.php';
require_once SOL_DIR . 'inc/forms.php';
require_once SOL_DIR . 'inc/setup-tool.php';
add_action( 'after_setup_theme', function () {
	require_once SOL_DIR . 'inc/woocommerce.php';
} );
