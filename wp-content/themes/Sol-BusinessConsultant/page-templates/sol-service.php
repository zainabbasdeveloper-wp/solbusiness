<?php
/**
 * Template Name: SOL Service Page
 * Template Post Type: page
 *
 * Service page (child of /services/). The hero comes from the "Service Details"
 * fields; everything below from Page Sections. With no sections, a standard
 * layout is used: scope & FAQs, overview, enquiry form.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) {
	the_post();
	$id = get_the_ID();
	sol_context_id( $id );

	$heading = get_field( 'hero_heading', $id );
	$image   = get_field( 'hero_image', $id );
	sol_render_row( array(
		'acf_fc_layout' => 'hero',
		'style'         => 'dark',
		'eyebrow'       => get_field( 'eyebrow', $id ),
		'heading'       => $heading ? $heading : get_the_title(),
		'text'          => get_field( 'hero_intro', $id ),
		'buttons'       => get_field( 'hero_buttons', $id ),
		'checks'        => sol_list( get_field( 'hero_highlights', $id ) ),
		'stats'         => get_field( 'hero_stats', $id ),
		'image'         => $image ? $image : get_post_thumbnail_id( $id ),
		'image_alt'     => get_the_title() . ' consultation',
		'disclaimer'    => get_field( 'hero_disclaimer', $id ),
		'scroll_hint'   => get_field( 'show_enquiry', $id ) ? '#intake-form' : '',
	) );

	$rows = sol_rows( get_field( 'page_sections', $id ) );
	if ( $rows ) {
		foreach ( $rows as $i => $row ) {
			sol_render_row( $row, $i );
		}
	} else {
		sol_render_row( array( 'acf_fc_layout' => 'service_details' ) );
		sol_render_row( array( 'acf_fc_layout' => 'service_overview' ) );
		sol_render_row( array( 'acf_fc_layout' => 'service_enquiry', 'section_id' => 'intake-form' ) );
	}
}
get_footer();
