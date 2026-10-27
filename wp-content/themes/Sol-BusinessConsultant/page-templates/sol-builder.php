<?php
/**
 * Template Name: SOL Page Builder
 * Template Post Type: page
 *
 * Page built from SCF "Page Sections" (training-site design). No Elementor.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) {
	the_post();
	sol_context_id( get_the_ID() );
	sol_render_sections( get_the_ID() );
}
get_footer();
