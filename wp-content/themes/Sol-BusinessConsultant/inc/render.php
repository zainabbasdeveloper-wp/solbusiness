<?php
/**
 * Page-section renderer and data helpers used by the section templates.
 */

defined( 'ABSPATH' ) || exit;

/** Render all page sections of a post (pages, reusable sections). */
function sol_render_sections( $post_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		echo '<div class="sol-container sol-section"><p>Activate Secure Custom Fields to display this page.</p></div>';
		return;
	}
	foreach ( sol_rows( get_field( 'page_sections', $post_id ) ) as $i => $row ) {
		sol_render_row( $row, $i );
	}
}

/** Render one flexible-content row with template-parts/sections/{layout}.php. */
function sol_render_row( $row, $index = 0 ) {
	$layout = isset( $row['acf_fc_layout'] ) ? (string) $row['acf_fc_layout'] : '';
	if ( ! preg_match( '/^[a-z_]+$/', $layout ) ) {
		return;
	}
	$file = locate_template( 'template-parts/sections/' . $layout . '.php' );
	$file = apply_filters( 'sol_section_template', $file, $layout, $row );
	if ( ! $file || ! file_exists( $file ) ) {
		return;
	}
	$s   = $row;
	$uid = 'sol-' . $layout . '-' . wp_unique_id();
	include $file;
}

/** Collect FAQ items for one FAQPage JSON-LD block in the footer. */
function sol_faq_schema( $items = null ) {
	static $all = array();
	if ( null !== $items ) {
		$all = array_merge( $all, $items );
	}
	return $all;
}
add_action( 'wp_footer', function () {
	$items = sol_faq_schema();
	if ( ! $items ) {
		return;
	}
	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map( function ( $f ) {
			return array( '@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f['answer'] ) );
		}, $items ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}, 50 );

/** The /services/ parent page. */
function sol_services_parent_id() {
	$page = get_page_by_path( 'services' );
	return $page ? (int) $page->ID : 0;
}

/** Cards for the service grid: child pages of /services/ that use the SOL Service Page template. */
function sol_cards_from_posts( $source ) {
	if ( 'services' !== $source || ! sol_services_parent_id() ) {
		return array();
	}
	$pages = get_pages( array(
		'parent'      => sol_services_parent_id(),
		'sort_column' => 'menu_order,post_title',
		'meta_key'    => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'  => SOL_TPL_SERVICE, // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	$cards = array();
	foreach ( $pages as $p ) {
		if ( get_field( 'hide_in_grid', $p->ID ) ) {
			continue;
		}
		$url     = get_field( 'card_url', $p->ID );
		$cards[] = array(
			'icon'  => get_field( 'card_icon', $p->ID ),
			'title' => get_the_title( $p ),
			'text'  => get_field( 'card_text', $p->ID ),
			'link'  => array( 'url' => $url ? $url : get_permalink( $p ), 'title' => 'Learn more', 'target' => '' ),
		);
	}
	return $cards;
}
