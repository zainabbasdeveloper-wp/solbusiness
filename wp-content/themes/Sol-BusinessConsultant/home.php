<?php
/**
 * Blog index (the "Posts page" set in Settings → Reading), in the training-site design.
 */

defined( 'ABSPATH' ) || exit;

get_header();
sol_render_row( array(
	'acf_fc_layout' => 'page_header',
	'eyebrow'       => 'Insights',
	'heading'       => 'Business, NDIS & Automation Blog',
	'text'          => 'Practical guides for NDIS registration, client portals, automation, compliance workflows, websites, marketing, and business operations.',
) );
sol_render_row( array(
	'acf_fc_layout'  => 'post_feed',
	'post_type'      => 'post',
	'count'          => 24,
	'featured_first' => true,
	'show_filters'   => true,
) );
sol_render_row( array(
	'acf_fc_layout' => 'cta_banner',
	'style'         => 'light',
	'heading'       => 'Need advice for your own workflow?',
	'text'          => 'Book a consultation and we can map the right NDIS, automation, website, or client portal pathway for your business.',
	'buttons'       => array( array( 'link' => array( 'url' => '/book-consultation/', 'title' => 'Book Consultation', 'target' => '' ), 'style' => 'primary' ) ),
) );
get_footer();
