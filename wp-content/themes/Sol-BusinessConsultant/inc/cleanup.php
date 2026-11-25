<?php
/**
 * Site clean-up (v2.25.0).
 *
 * 1. Old pages left over from the previous site (LearnPress, Elementor tests, an old checkout, duplicate
 *    cart and training pages, "Hello world!"). None is linked from the site. Appearance → SOL Setup →
 *    "Old pages" moves them to the Trash (restorable for 30 days from Pages → Trash) and their addresses
 *    redirect (301) to the page that replaces them.
 *    Kept on purpose: Business Disclosure and Delivery Instructions & Timeframes (payment-provider disclosures).
 *
 * 2. Search titles and descriptions on the six finance service pages always use the compliant wording from
 *    the theme (some still had hand-set Yoast titles like "CPA Advisory" or "ABN, TFN, GST").
 */

defined( 'ABSPATH' ) || exit;

/** Old page address => where it redirects. Each is checked by ID and slug before it is trashed. */
function sol_old_pages() {
	$training = 'https://training.solbusinessconsultant.com.au/student-dashboard';
	return array(
		// path                          => array( id, redirect )
		'hello-world'                    => array( 0, '/blog/' ),
		'courses'                        => array( 477, '/training-courses/' ),
		'lp-profile'                     => array( 476, $training ),
		'instructors'                    => array( 478, '/contact/' ),
		'instructor'                     => array( 479, '/contact/' ),
		'become_a_teacher'               => array( 480, '/contact/' ),
		'term_conditions'                => array( 481, '/terms-conditions/' ),
		'checkout__trashed/receipt'      => array( 483, '/my-account/' ),
		'checkout__trashed/transaction-failed' => array( 484, '/cart/' ),
		'checkout__trashed/order-history'      => array( 485, '/my-account/' ),
		'checkout__trashed/confirmation'       => array( 486, '/my-account/' ),
		'products'                       => array( 487, '/pricing/' ),
		'resources'                      => array( 14, '/blog/' ),
		'enrolment-payment'              => array( 1435, '/sol-service-payment-form/' ),
		'pay-now'                        => array( 1535, '/sol-service-payment-form/' ),
		'training'                       => array( 1609, '/training-courses/' ),
		'dashboard'                      => array( 1620, $training ),
		'student-registration'           => array( 1621, $training ),
		'instructor-registration'        => array( 1622, '/contact/' ),
		'cart-2'                         => array( 1623, '/cart/' ),
		'elementor-2509'                 => array( 2509, '/training-courses/' ),
		'how-it-works'                   => array( 3277, '/training-courses/' ),
		'checkout-test'                  => array( 3380, '/cart/' ),
	);
}

/** The old pages that still exist (not in the Trash): path => WP_Post. */
function sol_old_pages_found() {
	$found = array();
	foreach ( sol_old_pages() as $path => $row ) {
		$post = $row[0] ? get_post( $row[0] ) : null;
		if ( ! $post && ! $row[0] ) { // "Hello world!" is a post, found by its slug; pages only by their exact ID
			$hits = get_posts( array( 'name' => basename( $path ), 'post_type' => array( 'post', 'page' ), 'post_status' => array( 'publish', 'draft', 'private', 'pending' ), 'numberposts' => 1 ) );
			$post = $hits ? $hits[0] : null;
		}
		if ( $post && 'trash' !== $post->post_status && basename( $path ) === $post->post_name ) {
			$found[ $path ] = $post;
		}
	}
	return $found;
}

add_action( 'admin_post_sol_old_pages', function () {
	if ( ! current_user_can( 'delete_pages' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_old_pages' );
	$n = 0;
	foreach ( sol_old_pages_found() as $post ) {
		if ( wp_trash_post( $post->ID ) ) {
			++$n;
		}
	}
	wp_safe_redirect( admin_url( 'themes.php?page=sol-setup&sol_old_trashed=' . $n . '#sol-old-pages' ) );
	exit;
} );

// Removed pages: 301 to their replacement instead of a 404.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$old  = sol_old_pages();
	if ( isset( $old[ $path ] ) ) {
		$to = $old[ $path ][1];
		wp_redirect( 0 === strpos( $to, 'http' ) ? $to : home_url( $to ), 301 ); // phpcs:ignore WordPress.Security.SafeRedirect -- fixed list, incl. the training site
		exit;
	}
}, 2 );

/** The "Old pages" box on SOL Setup. */
function sol_old_pages_box() {
	$found = sol_old_pages_found();
	$done  = isset( $_GET['sol_old_trashed'] ) ? absint( $_GET['sol_old_trashed'] ) : -1; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div id="sol-old-pages" class="card" style="max-width:760px;padding:12px 20px;margin:16px 0">
		<h2 style="margin-top:8px">Old pages from the previous site</h2>
		<?php if ( $done >= 0 ) : ?><div class="notice notice-success inline"><p><?php echo esc_html( sprintf( '%d page(s) moved to the Trash. Their addresses now redirect to the pages that replace them. To undo, restore them from Pages → Trash within 30 days.', $done ) ); ?></p></div><?php endif; ?>
		<?php if ( $found ) : ?>
			<p>These pages are left over from the old site (LearnPress, Elementor tests, an old checkout and duplicate pages). None is linked from the site, but search engines still list them.</p>
			<ul style="list-style:disc;padding-left:20px;columns:2">
				<?php foreach ( $found as $path => $post ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $post ) ); ?>" target="_blank" rel="noopener">/<?php echo esc_html( $path ); ?>/</a> → <?php echo esc_html( sol_old_pages()[ $path ][1] ); ?></li>
				<?php endforeach; ?>
			</ul>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Move <?php echo (int) count( $found ); ?> old page(s) to the Trash? You can restore them from Pages → Trash for 30 days.');">
				<input type="hidden" name="action" value="sol_old_pages">
				<?php wp_nonce_field( 'sol_old_pages' ); ?>
				<?php submit_button( 'Move ' . count( $found ) . ' old pages to the Trash', 'primary', 'submit', false ); ?>
			</form>
			<p class="description">Kept on purpose: Business Disclosure and Delivery Instructions &amp; Timeframes (payment-provider disclosures), Cart, Checkout, My Account and the Service Payment form.</p>
		<?php else : ?>
			<p style="color:#008a20">● No old pages left. Their addresses redirect to the pages that replace them.</p>
		<?php endif; ?>
	</div>
	<?php
}

/* ------------------------------------------------------------------ */
/* Finance pages: compliant search titles and descriptions             */
/* ------------------------------------------------------------------ */

/** Seed SEO title/description for the current finance service page, or null. */
function sol_compliance_seo() {
	static $seo = false;
	if ( false !== $seo ) {
		return $seo;
	}
	$seo = null;
	if ( ! is_page() ) {
		return $seo;
	}
	$path  = get_page_uri( get_queried_object_id() );
	$pages = array( 'services/bookkeeping-support', 'services/payroll-services', 'services/finance-broker-referrals', 'services/accountancy-partnerships', 'services/accountancy', 'services/company-business-registration' );
	if ( ! in_array( $path, $pages, true ) || ! function_exists( 'sol_seed' ) ) {
		return $seo;
	}
	foreach ( (array) ( sol_seed()['services'] ?? array() ) as $s ) {
		if ( $s['path'] === $path && ! empty( $s['yoast']['title'] ) ) {
			$seo = array( 'title' => $s['yoast']['title'], 'desc' => $s['yoast']['description'] ?? '' );
		}
	}
	return $seo;
}

foreach ( array( 'wpseo_title', 'wpseo_opengraph_title', 'wpseo_twitter_title' ) as $sol_hook ) {
	add_filter( $sol_hook, function ( $title ) {
		$seo = sol_compliance_seo();
		return $seo ? $seo['title'] : $title;
	}, 99 );
}
foreach ( array( 'wpseo_metadesc', 'wpseo_opengraph_desc', 'wpseo_twitter_description' ) as $sol_hook ) {
	add_filter( $sol_hook, function ( $desc ) {
		$seo = sol_compliance_seo();
		return $seo && $seo['desc'] ? $seo['desc'] : $desc;
	}, 99 );
}
unset( $sol_hook );
// Without Yoast: the document title.
add_filter( 'pre_get_document_title', function ( $title ) {
	$seo = defined( 'WPSEO_VERSION' ) ? null : sol_compliance_seo();
	return $seo ? $seo['title'] : $title;
}, 99 );
