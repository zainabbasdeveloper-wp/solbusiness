<?php
/** Layout: course_levels — tabbed course panels from WooCommerce products with Course Details. */
defined( 'ABSPATH' ) || exit;

$ids = array_filter( array_map( 'intval', (array) sol_get( $s, 'courses', array() ) ) );
if ( ! $ids ) {
	$ids = get_posts( array( 'post_type' => 'product', 'numberposts' => 10, 'meta_key' => 'level', 'orderby' => 'meta_value', 'order' => 'ASC', 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
}
$ids  = array_values( array_filter( $ids, function ( $id ) {
	return 'product' === get_post_type( $id ) && 'publish' === get_post_status( $id );
} ) );
$many = count( $ids ) > 1;
if ( ! $ids ) {
	return;
}

sol_section_open( $s, 'course_levels' );
?>
<div class="sol-container">
	<?php sol_section_head( $s ); ?>
	<div class="sol-tabs" <?php echo $many ? 'data-sol-tabs' : ''; ?>>
		<?php if ( $many ) : ?>
			<div class="sol-tabs__list sol-tabs__list--levels" role="tablist">
				<?php foreach ( $ids as $i => $id ) : ?>
					<button type="button" role="tab" class="sol-tabs__tab sol-color-<?php echo esc_attr( get_field( 'color', $id ) ); ?>" id="<?php echo esc_attr( $uid . '-tab-' . $i ); ?>" aria-controls="<?php echo esc_attr( $uid . '-panel-' . $i ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>">
						<strong><?php echo esc_html( get_field( 'level', $id ) ); ?></strong> <?php echo esc_html( get_field( 'badge', $id ) ); ?>
						<?php if ( get_field( 'popular', $id ) ) : ?><span class="sol-badge">Popular</span><?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php foreach ( $ids as $i => $id ) : ?>
			<?php
			$curr   = sol_rows( get_field( 'curriculum', $id ) );
			$topics = 0;
			foreach ( $curr as $m ) {
				$topics += count( sol_list( sol_get( $m, 'topics', array() ) ) );
			}
			$color = preg_replace( '/[^a-z]/', '', (string) get_field( 'color', $id ) );
			$wc    = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
			$price = $wc ? $wc->get_price() : get_field( 'price', $id );
			$thumb = get_field( 'thumbnail', $id ) ? get_field( 'thumbnail', $id ) : get_post_thumbnail_id( $id );
			?>
			<div class="sol-tabs__panel sol-course sol-color-<?php echo esc_attr( $color ); ?>" <?php echo $many ? 'role="tabpanel" aria-labelledby="' . esc_attr( $uid . '-tab-' . $i ) . '"' : ''; ?> id="<?php echo esc_attr( $uid . '-panel-' . $i ); ?>" <?php echo ( $many && $i > 0 ) ? 'hidden' : ''; ?>>
				<div class="sol-course__main">
					<p class="sol-course__badges"><span class="sol-tag"><?php echo esc_html( get_field( 'level', $id ) ); ?></span><span class="sol-tag"><?php echo esc_html( get_field( 'badge', $id ) ); ?></span><?php if ( get_field( 'popular', $id ) ) : ?><span class="sol-badge">Most Popular</span><?php endif; ?></p>
					<h2 class="sol-course__title"><?php echo esc_html( get_the_title( $id ) ); ?></h2>
					<p><?php echo esc_html( get_field( 'description', $id ) ); ?></p>
					<p class="sol-course__facts">
						<span><?php echo sol_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( get_field( 'duration', $id ) ); ?></span>
						<span><?php echo sol_icon( 'layers' ); // phpcs:ignore ?> <?php echo (int) count( $curr ); ?> Modules</span>
						<span><?php echo sol_icon( 'play-circle' ); // phpcs:ignore ?> <?php echo (int) $topics; ?> Video Topics</span>
						<span><?php echo sol_icon( 'award' ); // phpcs:ignore ?> Certificate Included</span>
					</p>
					<h3>What You'll Learn</h3>
					<?php echo sol_checks( sol_list( get_field( 'outcomes', $id ) ), 'sol-checks--cols-2 sol-checks--sm' ); // phpcs:ignore ?>
					<h3>Course Curriculum</h3>
					<p class="sol-muted"><?php echo (int) count( $curr ); ?> modules · <?php echo (int) $topics; ?> video topics · 10 min per topic</p>
					<div class="sol-accordion">
						<?php foreach ( $curr as $n => $m ) : ?>
							<?php $tps = sol_list( sol_get( $m, 'topics', array() ) ); ?>
							<details class="sol-accordion__item" <?php echo 0 === $n ? 'open' : ''; ?>>
								<summary><span class="sol-course__num"><?php echo (int) $n + 1; ?></span> <?php echo esc_html( sol_get( $m, 'title' ) ); ?> <small><?php echo (int) count( $tps ); ?> topics</small><?php echo sol_icon( 'chevron-down', 'sol-accordion__chev' ); // phpcs:ignore ?></summary>
								<ul class="sol-course__topics"><?php foreach ( $tps as $t ) : ?><li><?php echo sol_icon( 'play-circle' ); // phpcs:ignore ?> <span><?php echo esc_html( $t ); ?></span> <small>10 min</small></li><?php endforeach; ?></ul>
							</details>
						<?php endforeach; ?>
					</div>
				</div>
				<aside class="sol-course__aside">
					<div class="sol-course__card">
						<?php echo sol_image( $thumb, 'medium_large', get_the_title( $id ), 'sol-course__thumb' ); // phpcs:ignore ?>
						<p class="sol-muted"><?php echo esc_html( get_field( 'level', $id ) . ' · ' . get_field( 'badge', $id ) ); ?></p>
						<?php if ( $price ) : ?><p class="sol-course__price">AUD $<?php echo esc_html( number_format_i18n( (float) $price ) ); ?></p><?php endif; ?>
						<p class="sol-muted">One-time payment · Lifetime access · No hidden fees</p>
						<?php echo sol_button( get_field( 'enrol_link', $id ), 'primary' ); // phpcs:ignore ?>
						<?php echo sol_button( get_field( 'buy_link', $id ), 'outline' ); // phpcs:ignore ?>
						<a class="sol-link-btn" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">Ask a Question</a>
					</div>
				</aside>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( sol_get( $s, 'disclaimer' ) ) : ?><p class="sol-note sol-note--center"><?php echo esc_html( $s['disclaimer'] ); ?></p><?php endif; ?>
</div>
</section>
