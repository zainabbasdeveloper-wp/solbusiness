<?php
/**
 * Single product in the SOL Training Academy course layout.
 * Courses (products with Course Details) get the full course page; other products
 * (packages, service payment) get the same frame with their description.
 *
 * Overrides woocommerce/templates/single-product.php
 *
 * @package Sol-BusinessConsultant
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

while ( have_posts() ) :
	the_post();
	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	$id        = $product->get_id();
	$is_course = sol_is_course_product( $id );
	$color     = $is_course ? preg_replace( '/[^a-z]/', '', (string) get_field( 'color', $id ) ) : 'amber';
	$curr      = $is_course ? sol_rows( get_field( 'curriculum', $id ) ) : array();
	$topics    = 0;
	foreach ( $curr as $m ) {
		$topics += count( sol_list( sol_get( $m, 'topics', array() ) ) );
	}
	$level     = $is_course ? get_field( 'level', $id ) : '';
	$badge     = $is_course ? get_field( 'badge', $id ) : '';
	$popular   = $is_course && get_field( 'popular', $id );
	$duration  = $is_course ? get_field( 'duration', $id ) : '';
	$desc      = $is_course && get_field( 'description', $id ) ? get_field( 'description', $id ) : wp_strip_all_tags( $product->get_short_description() );
	$cats      = wc_get_product_category_list( $id, ', ' );
	$shop_url  = sol_courses_url();

	do_action( 'woocommerce_before_single_product' );
	if ( post_password_required() ) {
		echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput
		continue;
	}
	?>
	<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'sol-product sol-color-' . $color, $product ); ?>>

		<section class="sol-product-hero">
			<div class="sol-container">
				<a class="sol-back sol-back--light" href="<?php echo esc_url( $shop_url ); ?>"><?php echo sol_icon( 'arrow-left' ); // phpcs:ignore ?> <?php echo $is_course ? 'All training courses' : 'Back'; ?></a>
				<p class="sol-product-hero__badges">
					<?php if ( $level ) : ?><span class="sol-level-pill"><?php echo esc_html( $level ); ?></span><?php endif; ?>
					<?php if ( $badge ) : ?><span class="sol-product-hero__badge"><?php echo esc_html( $badge ); ?></span><?php endif; ?>
					<?php if ( $popular ) : ?><span class="sol-badge">Most Popular</span><?php endif; ?>
					<?php if ( ! $is_course && $cats ) : ?><span class="sol-product-hero__badge"><?php echo wp_kses_post( wp_strip_all_tags( $cats ) ); ?></span><?php endif; ?>
				</p>
				<h1 class="sol-product-hero__title product_title entry-title"><?php the_title(); ?></h1>
				<?php if ( $desc ) : ?><p class="sol-product-hero__text"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
				<?php if ( $is_course ) : ?>
					<p class="sol-course__facts sol-course__facts--light">
						<?php if ( $duration ) : ?><span><?php echo sol_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( $duration ); ?></span><?php endif; ?>
						<span><?php echo sol_icon( 'book-open' ); // phpcs:ignore ?> <?php echo (int) count( $curr ); ?> Modules</span>
						<span><?php echo sol_icon( 'video' ); // phpcs:ignore ?> <?php echo (int) $topics; ?> Video Topics</span>
						<span><?php echo sol_icon( 'star' ); // phpcs:ignore ?> Certificate Included</span>
					</p>
				<?php endif; ?>
			</div>
		</section>

		<section class="sol-section sol-bg-white sol-product-body">
			<div class="sol-container sol-product-grid">

				<div class="sol-product-main">
					<?php if ( $is_course ) : ?>
						<?php $outcomes = sol_list( get_field( 'outcomes', $id ) ); ?>
						<?php if ( $outcomes ) : ?>
							<div class="sol-product-block">
								<h2>What You'll Learn</h2>
								<?php echo sol_checks( $outcomes, 'sol-checks--cols-2 sol-checks--sm sol-checks--course' ); // phpcs:ignore ?>
							</div>
						<?php endif; ?>
						<?php if ( $curr ) : ?>
							<div class="sol-product-block">
								<h2>Course Curriculum</h2>
								<p class="sol-muted"><?php echo (int) count( $curr ); ?> modules · <?php echo (int) $topics; ?> video topics · 10 min per topic</p>
								<div class="sol-accordion sol-curriculum">
									<?php foreach ( $curr as $n => $m ) : ?>
										<?php $tps = sol_list( sol_get( $m, 'topics', array() ) ); ?>
										<details class="sol-accordion__item" <?php echo 0 === $n ? 'open' : ''; ?>>
											<summary>
												<span class="sol-course__num"><?php echo (int) $n + 1; ?></span>
												<span class="sol-curriculum__title"><?php echo esc_html( sol_get( $m, 'title' ) ); ?><small><?php echo (int) count( $tps ); ?> topics</small></span>
												<?php echo sol_icon( 'chevron-down', 'sol-accordion__chev' ); // phpcs:ignore ?>
											</summary>
											<ul class="sol-course__topics"><?php foreach ( $tps as $t ) : ?><li><?php echo sol_icon( 'play' ); // phpcs:ignore ?> <span><?php echo esc_html( $t ); ?></span> <small>10 min</small></li><?php endforeach; ?></ul>
										</details>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
						<div class="sol-notice sol-notice--warning">
							<p>This course is non-accredited professional development unless expressly delivered by an authorised RTO under its own scope. Completion certificates are not nationally recognised AQF qualifications.</p>
						</div>
					<?php endif; ?>

					<?php if ( trim( (string) $product->get_description() ) ) : ?>
						<div class="sol-product-block sol-prose">
							<?php if ( $is_course ) : ?><h2>About this course</h2><?php endif; ?>
							<?php echo wp_kses_post( apply_filters( 'the_content', $product->get_description() ) ); ?>
						</div>
					<?php endif; ?>
				</div>

				<aside class="sol-product-aside">
					<div class="sol-enrol-card">
						<div class="sol-enrol-card__media">
							<?php
							$img = $product->get_image_id();
							echo $img ? sol_image( $img, 'medium_large', get_the_title(), 'sol-enrol-card__img', true ) : ''; // phpcs:ignore
							?>
							<?php if ( $level ) : ?><span class="sol-level-pill sol-enrol-card__pill"><?php echo esc_html( $level . ( $badge ? ' · ' . $badge : '' ) ); ?></span><?php endif; ?>
							<?php if ( $popular ) : ?><span class="sol-badge sol-enrol-card__popular">Most Popular</span><?php endif; ?>
							<?php if ( $is_course ) : ?>
								<div class="sol-enrol-card__stats">
									<div><strong><?php echo (int) count( $curr ); ?></strong><span>Modules</span></div>
									<div><strong><?php echo (int) $topics; ?></strong><span>Topics</span></div>
									<div><strong><?php echo esc_html( $duration ? preg_replace( '/[–-].*$/u', '', $duration ) . 'h+' : '—' ); ?></strong><span>Duration</span></div>
								</div>
							<?php endif; ?>
						</div>
						<div class="sol-enrol-card__body">
							<p class="sol-enrol-card__price price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
							<p class="sol-muted sol-enrol-card__terms"><?php echo $is_course ? 'One-time payment · Lifetime access · No hidden fees' : 'Secure checkout · Tax invoice emailed'; ?></p>
							<div class="sol-enrol-card__cart">
								<?php woocommerce_template_single_add_to_cart(); ?>
							</div>
							<a class="sol-btn sol-btn--outline sol-btn--block" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Ask a Question</a>
							<?php if ( $is_course ) : ?>
								<ul class="sol-enrol-card__list">
									<?php foreach ( array( '100% online, self-paced learning', 'Certificate of completion', 'Lifetime access to all content', 'Mobile-friendly LMS platform', 'NDIS-aligned curriculum', 'Knowledge assessments per module' ) as $inc ) : ?>
										<li><?php echo sol_icon( 'check-circle' ); // phpcs:ignore ?> <?php echo esc_html( $inc ); ?></li>
									<?php endforeach; ?>
								</ul>
								<p class="sol-enrol-card__after"><?php echo sol_icon( 'graduation-cap' ); // phpcs:ignore ?> After checkout you'll learn on the SOL Training Academy.</p>
							<?php endif; ?>
						</div>
					</div>
				</aside>
			</div>
		</section>

		<?php
		// Other course levels.
		if ( $is_course ) {
			$others = sol_course_product_ids( array( $id ) );
			if ( $others ) {
				echo '<section class="sol-section sol-bg-chalk"><div class="sol-container">';
				sol_section_head( array( 'eyebrow' => 'Keep Learning', 'heading' => 'Other Training Levels', 'align' => 'center' ) );
				echo '<div class="sol-grid sol-grid--' . (int) min( 3, count( $others ) ) . ' sol-course-cards">';
				foreach ( $others as $oid ) {
					sol_course_card( $oid );
				}
				echo '</div></div></section>';
			}
		}
		?>
	</div>
	<?php
	if ( isset( WC()->structured_data ) ) {
		WC()->structured_data->generate_product_data( $product );
	}
	do_action( 'woocommerce_after_single_product' );
endwhile;

get_footer( 'shop' );
