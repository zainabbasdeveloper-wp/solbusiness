<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * SOL Training Academy design for WordPress, built with Secure Custom Fields (no Elementor).
 * Setup: Appearance → SOL Setup. Field reference: _dev/FIELD-REFERENCE.md
 */
define( 'SOL_THEME_VERSION', '2.10.2' );
define( 'SOL_DIR', trailingslashit( get_stylesheet_directory() ) );
define( 'SOL_URI', trailingslashit( get_stylesheet_directory_uri() ) );
define( 'SOL_OPTIONS_SLUG', 'sol-site-settings' );
define( 'SOL_TPL_BUILDER', 'page-templates/sol-builder.php' );
define( 'SOL_TPL_SERVICE', 'page-templates/sol-service.php' );

require_once SOL_DIR . 'inc/helpers.php';
require_once SOL_DIR . 'inc/menu.php';
require_once SOL_DIR . 'inc/setup.php';
require_once SOL_DIR . 'inc/render.php';
require_once SOL_DIR . 'inc/seo.php';
require_once SOL_DIR . 'inc/chat.php';
require_once SOL_DIR . 'inc/a11y.php';
require_once SOL_DIR . 'inc/components.php';
require_once SOL_DIR . 'inc/forms.php';
require_once SOL_DIR . 'inc/setup-tool.php';
require_once SOL_DIR . 'inc/updates.php';
add_action( 'after_setup_theme', function () {
	require_once SOL_DIR . 'inc/woocommerce.php';
} );
