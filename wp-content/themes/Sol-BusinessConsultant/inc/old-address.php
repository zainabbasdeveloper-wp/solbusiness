<?php
/**
 * The site used to run at http://46.250.242.208:8086. Plugins and page builders saved that address
 * in their settings (Sticky Buttons, Elementor, Yoast and others), so links and images still point there.
 * This rewrites the old address to the live domain in every page before it is sent to the browser.
 */

defined( 'ABSPATH' ) || exit;

/** Old addresses to rewrite, e.g. 'http://46.250.242.208:8086'. */
function sol_old_origins() {
	return (array) apply_filters( 'sol_old_origins', array( 'http://46.250.242.208:8086', 'https://46.250.242.208:8086' ) );
}

/** Rewrites the old addresses in $html to this site's address. */
function sol_fix_old_address( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, '46.250.242.208' ) ) {
		return $html;
	}
	$home = untrailingslashit( home_url() );
	$pair = array();
	foreach ( sol_old_origins() as $old ) {
		$old = untrailingslashit( $old );
		if ( 0 === strpos( $home, $old ) ) {
			continue; // the site is running on this address, leave it alone
		}
		$pair[ $old ]                              = $home;
		$pair[ str_replace( '/', '\/', $old ) ]    = str_replace( '/', '\/', $home ); // inside JSON
		$pair[ preg_replace( '#^https?:#', '', $old ) ] = preg_replace( '#^https?:#', '', $home ); // protocol-relative
	}
	return $pair ? strtr( $html, $pair ) : $html;
}

// Front end only: pages, feeds and sitemaps. The admin keeps showing what is saved, so it can still be corrected there.
add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'sol_fix_old_address' );
}, 0 );

// Redirects saved with the old address (for example a button's link) go to the domain too.
add_filter( 'wp_redirect', 'sol_fix_old_address' );

/** Page addresses that changed since the old site, as old path => current page path. */
function sol_moved_pages() {
	return (array) apply_filters( 'sol_moved_pages', array( 'training-2' => 'training-courses' ) );
}

// Old page addresses (saved in menus and links) redirect to the page's current address instead of a 404.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path  = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$home  = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	$path  = $home && 0 === strpos( $path, $home . '/' ) ? substr( $path, strlen( $home ) + 1 ) : $path;
	$to = sol_moved_page_url( $path );
	if ( $to ) {
		wp_safe_redirect( $to, 301 );
		exit;
	}
}, 1 );

/** Current address for an old page path, or '' when it hasn't moved (or the old page still exists). */
function sol_moved_page_url( $path ) {
	$moved = sol_moved_pages();
	if ( ! isset( $moved[ $path ] ) || get_page_by_path( $path ) ) {
		return '';
	}
	$p = get_page_by_path( $moved[ $path ] );
	return $p && 'publish' === $p->post_status ? get_permalink( $p ) : '';
}

/** A link saved with an old page address, rewritten to the current one (used by sol_url for menus, footer and buttons). */
function sol_moved_link( $url ) {
	$hit = false;
	foreach ( array_keys( sol_moved_pages() ) as $old ) {
		$hit = $hit || false !== strpos( $url, (string) $old );
	}
	if ( ! $hit ) {
		return $url; // quick exit for the usual case
	}
	$u    = wp_parse_url( $url );
	$home = wp_parse_url( home_url() );
	if ( empty( $u['path'] ) || ( ! empty( $u['host'] ) && preg_replace( '/^www\./', '', $u['host'] ) !== preg_replace( '/^www\./', '', (string) $home['host'] ) ) ) {
		return $url;
	}
	$path = trim( $u['path'], '/' );
	$base = isset( $home['path'] ) ? trim( $home['path'], '/' ) : '';
	$path = $base && 0 === strpos( $path, $base . '/' ) ? substr( $path, strlen( $base ) + 1 ) : $path;
	$to   = sol_moved_page_url( $path );
	if ( ! $to ) {
		return $url;
	}
	return $to . ( isset( $u['query'] ) ? '?' . $u['query'] : '' ) . ( isset( $u['fragment'] ) ? '#' . $u['fragment'] : '' );
}
