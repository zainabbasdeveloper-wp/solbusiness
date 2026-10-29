<?php
/**
 * WooCommerce in the SOL Training Academy style:
 * course cards, product image fallback, Enrol buttons, header cart, branded cart/checkout
 * header, and the hand-off to the training site after payment.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/* ------------------------------------------------------------------ */
/* Course helpers                                                      */
/* ------------------------------------------------------------------ */

/** True when a product carries training-site Course Details (the LMS courses). */
function sol_is_course_product( $product_id ) {
	return function_exists( 'get_field' ) && (bool) get_field( 'level', $product_id );
}

/** True when the cart holds at least one course. */
function sol_cart_has_course() {
	if ( ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( sol_is_course_product( $item['product_id'] ) ) {
			return true;
		}
	}
	return false;
}

/** Published course products, ordered by level. */
function sol_course_product_ids( $exclude = array() ) {
	$ids = get_posts( array(
		'post_type'   => 'product',
		'post_status' => 'publish',
		'numberposts' => 12,
		'fields'      => 'ids',
		'meta_key'    => 'level', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'     => 'meta_value',
		'order'       => 'ASC',
		'exclude'     => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams
	) );
	return array_values( array_filter( $ids, 'sol_is_course_product' ) );
}

/** Where "All training courses" links go: the training page, else the shop. */
function sol_courses_url() {
	$page = get_page_by_path( 'training-2' );
	return $page ? get_permalink( $page ) : wc_get_page_permalink( 'shop' );
}

/** Course / product card used on the shop, categories and "other levels". */
function sol_course_card( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return;
	}
	$is_course = sol_is_course_product( $product_id );
	$color     = $is_course ? preg_replace( '/[^a-z]/', '', (string) get_field( 'color', $product_id ) ) : 'amber';
	$curr      = $is_course ? sol_rows( get_field( 'curriculum', $product_id ) ) : array();
	$topics    = 0;
	foreach ( $curr as $m ) {
		$topics += count( sol_list( sol_get( $m, 'topics', array() ) ) );
	}
	$level   = $is_course ? get_field( 'level', $product_id ) : '';
	$badge   = $is_course ? get_field( 'badge', $product_id ) : '';
	$desc    = $is_course && get_field( 'description', $product_id ) ? get_field( 'description', $product_id ) : wp_strip_all_tags( $product->get_short_description() );
	$url     = get_permalink( $product_id );
	$buy_url = $product->is_purchasable() && $product->is_in_stock() && $product->is_type( 'simple' ) ? add_query_arg( 'add-to-cart', $product_id, wc_get_checkout_url() ) : $url;
	?>
	<article class="sol-course-card sol-color-<?php echo esc_attr( $color ); ?>">
		<a class="sol-course-card__media" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo $product->get_image_id() ? sol_image( $product->get_image_id(), 'medium_large', $product->get_name() ) : ''; // phpcs:ignore ?>
			<?php if ( $level ) : ?><span class="sol-level-pill sol-course-card__pill"><?php echo esc_html( $level . ( $badge ? ' · ' . $badge : '' ) ); ?></span><?php endif; ?>
			<?php if ( $is_course && get_field( 'popular', $product_id ) ) : ?><span class="sol-badge sol-course-card__popular">Most Popular</span><?php endif; ?>
		</a>
		<div class="sol-course-card__body">
			<h3 class="sol-course-card__title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
			<?php if ( $desc ) : ?><p class="sol-course-card__text"><?php echo esc_html( wp_trim_words( $desc, 26 ) ); ?></p><?php endif; ?>
			<?php if ( $is_course ) : ?>
				<p class="sol-course__facts">
					<?php if ( get_field( 'duration', $product_id ) ) : ?><span><?php echo sol_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( get_field( 'duration', $product_id ) ); ?></span><?php endif; ?>
					<span><?php echo sol_icon( 'book-open' ); // phpcs:ignore ?> <?php echo (int) count( $curr ); ?> modules</span>
					<span><?php echo sol_icon( 'video' ); // phpcs:ignore ?> <?php echo (int) $topics; ?> topics</span>
				</p>
			<?php endif; ?>
			<div class="sol-course-card__foot">
				<span class="sol-course-card__price price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				<div class="sol-course-card__actions">
					<a class="sol-btn sol-btn--outline sol-btn--sm" href="<?php echo esc_url( $url ); ?>">Details</a>
					<a class="sol-btn sol-btn--primary sol-btn--sm" href="<?php echo esc_url( $buy_url ); ?>" rel="nofollow"><?php echo $is_course ? 'Enrol Now' : 'Buy Now'; ?></a>
				</div>
			</div>
		</div>
	</article>
	<?php
}

/* ------------------------------------------------------------------ */
/* Product behaviour                                                   */
/* ------------------------------------------------------------------ */

// Courses use their Course Details thumbnail when the product has no image (product page, cart, checkout, emails).
add_filter( 'woocommerce_product_get_image_id', function ( $image_id, $product ) {
	if ( $image_id || ! function_exists( 'get_field' ) ) {
		return $image_id;
	}
	$thumb = get_field( 'thumbnail', $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() );
	return $thumb ? (int) $thumb : $image_id;
}, 10, 2 );

// Button labels.
add_filter( 'woocommerce_product_single_add_to_cart_text', function ( $text, $product ) {
	return sol_is_course_product( $product->get_id() ) ? 'Enrol Now' : 'Buy Now';
}, 10, 2 );
add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	return $product->is_purchasable() && sol_is_course_product( $product->get_id() ) ? 'Enrol Now' : $text;
}, 10, 2 );

// "Enrol Now" goes straight to checkout, like the training site.
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url, $product = null ) {
	if ( $product instanceof WC_Product && sol_is_course_product( $product->get_id() ) ) {
		return wc_get_checkout_url();
	}
	return $url;
}, 10, 2 );

// Buttons that link to checkout?add-to-cart=ID shouldn't add the same item twice on refresh.
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id ) {
	if ( WC()->cart && isset( $_GET['add-to-cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( (int) $item['product_id'] === (int) $product_id ) {
				return false;
			}
		}
	}
	return $passed;
}, 10, 2 );

// The theme draws its own wrappers, title and breadcrumbs on shop pages.
add_action( 'init', function () {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	add_filter( 'woocommerce_show_page_title', '__return_false' );
} );

/* ------------------------------------------------------------------ */
/* Header cart                                                         */
/* ------------------------------------------------------------------ */

/** Cart link with item count for the header. */
function sol_header_cart( $class = '' ) {
	if ( ! WC()->cart ) {
		return '';
	}
	$count = WC()->cart->get_cart_contents_count();
	return sprintf(
		'<a class="sol-cart-link %1$s" href="%2$s" aria-label="%3$s">%4$s<span class="sol-cart-count%5$s">%6$d</span></a>',
		esc_attr( $class ),
		esc_url( wc_get_cart_url() ),
		/* translators: %d: number of items */
		esc_attr( sprintf( _n( 'Cart, %d item', 'Cart, %d items', $count, 'sol-business-consultant' ), $count ) ),
		sol_icon( 'shopping-cart' ),
		$count ? '' : ' is-empty',
		$count
	);
}

// Keep the count fresh after AJAX add-to-cart.
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$fragments['span.sol-cart-count'] = '<span class="sol-cart-count' . ( $count ? '' : ' is-empty' ) . '">' . (int) $count . '</span>';
	$fragments['span.sol-fcart__count'] = sol_fcart_count_html();
	$fragments['span.sol-fcart__total'] = sol_fcart_total_html();
	$fragments['span.sol-fcart__heading-count'] = sol_fcart_heading_count_html();
	return $fragments;
} );

/* ------------------------------------------------------------------ */
/* Floating cart (left edge) + slide-in cart drawer                    */
/* ------------------------------------------------------------------ */

function sol_fcart_count_html() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	return '<span class="sol-fcart__count' . ( $count ? '' : ' is-empty' ) . '" data-count="' . (int) $count . '">' . (int) $count . '</span>';
}
function sol_fcart_total_html() {
	return '<span class="sol-fcart__total">' . ( WC()->cart ? wp_kses_post( WC()->cart->get_cart_subtotal() ) : '' ) . '</span>';
}
function sol_fcart_heading_count_html() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	/* translators: %d: number of items */
	return '<span class="sol-fcart__heading-count">' . esc_html( sprintf( _n( '%d item', '%d items', $count, 'sol-business-consultant' ), $count ) ) . '</span>';
}

// The mini cart in the drawer is refreshed by WooCommerce's cart fragments script.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_cart() && ! is_checkout() ) {
		wp_enqueue_script( 'wc-cart-fragments' );
		wp_enqueue_script( 'wc-add-to-cart' );
	}
}, 30 );

add_action( 'wp_footer', function () {
	if ( ! WC()->cart || is_cart() || is_checkout() || ! apply_filters( 'sol_show_floating_cart', true ) ) {
		return;
	}
	$browse = apply_filters( 'sol_fcart_browse_url', home_url( '/pricing/' ) );
	?>
	<div class="sol-fcart" data-sol-fcart>
		<button type="button" class="sol-fcart__tab" aria-controls="sol-fcart-drawer" aria-expanded="false" data-sol-fcart-open>
			<span class="sol-fcart__ring" aria-hidden="true"></span>
			<span class="sol-fcart__icon"><?php echo sol_icon( 'shopping-bag' ); // phpcs:ignore ?><?php echo sol_fcart_count_html(); // phpcs:ignore ?></span>
			<span class="sol-fcart__label"><b>Your cart</b><?php echo sol_fcart_total_html(); // phpcs:ignore ?></span>
			<span class="screen-reader-text">Open cart</span>
		</button>
		<div class="sol-fcart__backdrop" data-sol-fcart-close></div>
		<aside class="sol-fcart__drawer" id="sol-fcart-drawer" role="dialog" aria-modal="true" aria-labelledby="sol-fcart-title" aria-hidden="true">
			<header class="sol-fcart__head">
				<span class="sol-fcart__head-icon" aria-hidden="true"><?php echo sol_icon( 'shopping-bag' ); // phpcs:ignore ?></span>
				<div>
					<p class="sol-fcart__title" id="sol-fcart-title">Your cart</p>
					<?php echo sol_fcart_heading_count_html(); // phpcs:ignore ?>
				</div>
				<button type="button" class="sol-fcart__close" aria-label="Close cart" data-sol-fcart-close><?php echo sol_icon( 'x' ); // phpcs:ignore ?></button>
			</header>
			<div class="sol-fcart__body">
				<div class="widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
			</div>
			<footer class="sol-fcart__foot">
				<a class="sol-fcart__browse" href="<?php echo esc_url( $browse ); ?>"><?php echo sol_icon( 'sparkles' ); // phpcs:ignore ?> Browse packages and courses</a>
				<p class="sol-fcart__secure"><?php echo sol_icon( 'lock' ); // phpcs:ignore ?> Secure checkout · prices in AUD</p>
			</footer>
		</aside>
	</div>
	<?php
}, 20 );

/* ------------------------------------------------------------------ */
/* Cart and checkout                                                   */
/* ------------------------------------------------------------------ */

/** Branded header with steps above the cart / checkout. */
function sol_checkout_header() {
	$step = is_checkout() ? 2 : 1;
	$has_course = sol_cart_has_course();
	?>
	<section class="sol-checkout-head">
		<div class="sol-container">
			<a class="sol-back" href="<?php echo esc_url( $has_course ? sol_courses_url() : home_url( '/services/' ) ); ?>"><?php echo sol_icon( 'arrow-left' ); // phpcs:ignore ?> <?php echo $has_course ? 'Back to courses' : 'Back to services'; ?></a>
			<h1 class="sol-checkout-head__title"><?php echo 2 === $step ? 'Complete Your Purchase' : 'Your Cart'; ?></h1>
			<p class="sol-checkout-head__text"><?php echo $has_course ? 'Enrol now and continue learning on the SOL Training Academy straight after payment.' : 'Secure checkout. Your tax invoice is emailed straight away.'; ?></p>
			<ol class="sol-steps-bar" aria-label="Checkout steps">
				<?php
				$steps = array( 'Cart', 'Checkout', $has_course ? 'Start learning' : 'Client portal' );
				foreach ( $steps as $i => $label ) :
					$n     = $i + 1;
					$state = $n < $step ? 'is-done' : ( $n === $step ? 'is-current' : '' );
					?>
					<li class="<?php echo esc_attr( $state ); ?>" <?php echo $n === $step ? 'aria-current="step"' : ''; ?>>
						<span><?php echo $n < $step ? sol_icon( 'check' ) : (int) $n; // phpcs:ignore ?></span><?php echo esc_html( $label ); ?>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php if ( $has_course && is_checkout() ) : ?>
				<div class="sol-notice sol-notice--warning sol-checkout-head__notice">
					<p>This course is non-accredited professional development unless expressly delivered by an authorised RTO under its own scope. Completion certificates are not nationally recognised AQF qualifications.</p>
				</div>
			<?php endif; ?>
			<ul class="sol-checkout-trust">
				<li><?php echo sol_icon( 'lock' ); // phpcs:ignore ?> Secure, encrypted payment</li>
				<li><?php echo sol_icon( 'receipt' ); // phpcs:ignore ?> Tax invoice by email</li>
				<li><?php echo sol_icon( $has_course ? 'graduation-cap' : 'layout-dashboard' ); // phpcs:ignore ?> <?php echo $has_course ? 'Instant course access on the training site' : 'Track your service in the client portal'; ?></li>
			</ul>
		</div>
	</section>
	<?php
}

/*
 * Thank-you page: send the customer on to the training site, where the LMS,
 * student dashboard and client portal live. The training site creates the
 * enrolment from the WooCommerce order webhook.
 */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = $order_id ? wc_get_order( $order_id ) : null;
	if ( ! $order || $order->has_status( array( 'failed', 'cancelled' ) ) ) {
		return;
	}
	$has_course = false;
	foreach ( $order->get_items() as $item ) {
		if ( sol_is_course_product( $item->get_product_id() ) ) {
			$has_course = true;
		}
	}
	$email = $order->get_billing_email();
	?>
	<section class="sol-handoff">
		<span class="sol-eyebrow">Next step</span>
		<h2><?php echo $has_course ? 'Start learning on the SOL Training Academy' : 'Track your service in the SOL client portal'; ?></h2>
		<p>
			<?php if ( $has_course ) : ?>
				Your course access is set up on the training site. Register or sign in with <strong><?php echo esc_html( $email ); ?></strong> to open your student dashboard, lessons, quizzes and certificates.
			<?php else : ?>
				Register or sign in with <strong><?php echo esc_html( $email ); ?></strong> to view your purchased service, upload documents, raise requests and download deliverables.
			<?php endif; ?>
		</p>
		<div class="sol-buttons">
			<?php
			if ( $has_course ) {
				echo sol_button( array( 'url' => sol_app_url( '/student-dashboard' ), 'title' => 'Go to Student Dashboard' ), 'primary' ); // phpcs:ignore
			} else {
				echo sol_button( array( 'url' => sol_app_url( '/client-portal' ), 'title' => 'Open Client Portal' ), 'primary' ); // phpcs:ignore
			}
			echo sol_button( array( 'url' => sol_app_url( '/register' ), 'title' => 'Create your account' ), 'outline', '', false ); // phpcs:ignore
			?>
		</div>
	</section>
	<?php
}, 5 );
