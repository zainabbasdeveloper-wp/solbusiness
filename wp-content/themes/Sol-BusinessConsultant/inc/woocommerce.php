<?php
/**
 * WooCommerce: hand-off to the training site after checkout, and course details on product pages.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/** True when a product carries training-site Course Details (the LMS courses). */
function sol_is_course_product( $product_id ) {
	return function_exists( 'get_field' ) && (bool) get_field( 'level', $product_id );
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

/* Product page: course outcomes and curriculum for training products. */
add_action( 'woocommerce_after_single_product_summary', function () {
	$id = get_the_ID();
	if ( ! sol_is_course_product( $id ) ) {
		return;
	}
	$outcomes = sol_list( get_field( 'outcomes', $id ) );
	$curr     = sol_rows( get_field( 'curriculum', $id ) );
	?>
	<section class="sol-product-course">
		<p class="sol-course__facts">
			<span><?php echo sol_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( get_field( 'duration', $id ) ); ?></span>
			<span><?php echo sol_icon( 'layers' ); // phpcs:ignore ?> <?php echo (int) count( $curr ); ?> modules</span>
			<span><?php echo sol_icon( 'award' ); // phpcs:ignore ?> Certificate of completion</span>
		</p>
		<?php if ( $outcomes ) : ?>
			<h2>What you'll learn</h2>
			<?php echo sol_checks( $outcomes, 'sol-checks--cols-2 sol-checks--sm' ); // phpcs:ignore ?>
		<?php endif; ?>
		<?php if ( $curr ) : ?>
			<h2>Course curriculum</h2>
			<div class="sol-accordion">
				<?php foreach ( $curr as $n => $m ) : ?>
					<?php $tps = sol_list( sol_get( $m, 'topics', array() ) ); ?>
					<details class="sol-accordion__item" <?php echo 0 === $n ? 'open' : ''; ?>>
						<summary><span class="sol-course__num"><?php echo (int) $n + 1; ?></span> <?php echo esc_html( sol_get( $m, 'title' ) ); ?> <small><?php echo (int) count( $tps ); ?> topics</small><?php echo sol_icon( 'chevron-down', 'sol-accordion__chev' ); // phpcs:ignore ?></summary>
						<ul class="sol-course__topics"><?php foreach ( $tps as $t ) : ?><li><?php echo sol_icon( 'play-circle' ); // phpcs:ignore ?> <span><?php echo esc_html( $t ); ?></span></li><?php endforeach; ?></ul>
					</details>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<p class="sol-note">SOL training certificates record completion of non-accredited professional development. Course access, lessons and certificates are delivered on the SOL Training Academy after checkout.</p>
	</section>
	<?php
}, 8 );

// Package buttons add the product and go straight to checkout; a second click shouldn't add it twice.
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id ) {
	if ( WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( (int) $item['product_id'] === (int) $product_id && isset( $_GET['add-to-cart'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				return false;
			}
		}
	}
	return $passed;
}, 10, 2 );
