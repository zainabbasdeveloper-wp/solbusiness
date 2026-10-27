<?php
/**
 * Page not found.
 */

defined( 'ABSPATH' ) || exit;

get_header();
sol_render_row( array(
	'acf_fc_layout' => 'cta_banner',
	'style'         => 'light',
	'eyebrow'       => 'Error 404',
	'heading'       => 'We couldn’t find that page',
	'text'          => 'The page may have moved. Try one of these instead.',
	'buttons'       => array(
		array( 'link' => array( 'url' => '/', 'title' => 'Go to Home', 'target' => '' ), 'style' => 'primary' ),
		array( 'link' => array( 'url' => '/services/', 'title' => 'Our Services', 'target' => '' ), 'style' => 'outline' ),
		array( 'link' => array( 'url' => '/contact/', 'title' => 'Contact us', 'target' => '' ), 'style' => 'outline' ),
	),
) );
get_footer();
