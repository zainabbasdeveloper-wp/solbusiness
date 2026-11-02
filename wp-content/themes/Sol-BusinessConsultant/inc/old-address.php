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
