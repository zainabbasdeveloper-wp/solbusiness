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

/** Service page path => position, from the theme seed (cached until the seed file changes). */
function sol_service_order() {
	$file  = SOL_DIR . 'seed/site.json';
	$mtime = file_exists( $file ) ? (int) filemtime( $file ) : 0;
	$cache = get_option( 'sol_service_order' );
	if ( is_array( $cache ) && isset( $cache['mtime'] ) && $cache['mtime'] === $mtime ) {
		return $cache['order'];
	}
	$order = array();
	foreach ( (array) ( sol_seed()['services'] ?? array() ) as $s ) {
		$order[ $s['path'] ] = (int) $s['menu_order'];
	}
	update_option( 'sol_service_order', array( 'mtime' => $mtime, 'order' => $order ), true );
	return $order;
}

/** Cards for the service grid: published pages that use the SOL Service Page template (under /services/ or not). */
function sol_cards_from_posts( $source ) {
	if ( 'services' !== $source ) {
		return array();
	}
	$pages = get_pages( array(
		'hierarchical' => false, // flat list: include /services/ children and top-level service pages alike
		'sort_column'  => 'menu_order,post_title',
		'meta_key'     => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'   => SOL_TPL_SERVICE, // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	// Theme order first (pages that existed before setup keep their old menu order), then any extra service pages.
	$order = sol_service_order();
	$rank = function ( $p ) use ( $order ) {
		$uri = get_page_uri( $p );
		return isset( $order[ $uri ] ) ? $order[ $uri ] : 1000 + (int) $p->menu_order;
	};
	usort( $pages, function ( $a, $b ) use ( $rank ) {
		return $rank( $a ) <=> $rank( $b );
	} );
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
