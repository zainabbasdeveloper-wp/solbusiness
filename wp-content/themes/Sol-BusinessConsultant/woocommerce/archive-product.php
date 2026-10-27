<?php
/**
 * Shop and product category pages in the SOL Training Academy style.
 * Overrides woocommerce/templates/archive-product.php
 *
 * @package Sol-BusinessConsultant
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$title = is_shop() ? 'NDIS Support Coordinator|[Training Courses]' : wp_strip_all_tags( woocommerce_page_title( false ) );
$text  = is_shop()
	? 'Three structured levels designed to take you from foundational knowledge to advanced practice. Self-paced, online, and built specifically for Australian NDIS professionals.'
	: wp_strip_all_tags( term_description() );
?>
<section class="sol-shop-hero">
	<div class="sol-container sol-container--mid">
		<span class="sol-pill">Online LMS Training</span>
		<h1 class="sol-shop-hero__title"><?php echo sol_heading( str_replace( '|', "\n", $title ) ); // phpcs:ignore ?></h1>
		<?php if ( $text ) : ?><p class="sol-shop-hero__text"><?php echo esc_html( $text ); ?></p><?php endif; ?>
		<div class="sol-shop-hero__stats">
			<?php foreach ( array( array( 'graduation-cap', '3 Levels', 'Structured Learning' ), array( 'users', '100% Online', 'Learn Anywhere' ), array( 'award', 'Certificate', 'Upon Completion' ), array( 'clock', 'Self-Paced', 'Lifetime Access' ) ) as $st ) : ?>
				<div><?php echo sol_icon( $st[0] ); // phpcs:ignore ?><strong><?php echo esc_html( $st[1] ); ?></strong><span><?php echo esc_html( $st[2] ); ?></span></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="sol-section sol-bg-chalk sol-shop">
	<div class="sol-container">
		<?php
		do_action( 'woocommerce_before_main_content' );
		woocommerce_output_all_notices();

		if ( woocommerce_product_loop() ) :
			?>
			<div class="sol-shop__bar">
				<?php woocommerce_result_count(); ?>
				<?php woocommerce_catalog_ordering(); ?>
			</div>
			<div class="sol-grid sol-grid--3 sol-course-cards">
				<?php
				while ( have_posts() ) {
					the_post();
					sol_course_card( get_the_ID() );
				}
				?>
			</div>
			<div class="sol-pagination"><?php woocommerce_pagination(); ?></div>
		<?php else : ?>
			<?php do_action( 'woocommerce_no_products_found' ); ?>
		<?php endif; ?>
		<?php do_action( 'woocommerce_after_main_content' ); ?>
	</div>
</section>

<?php
sol_render_row( array(
	'acf_fc_layout' => 'cta_banner',
	'style'         => 'dark',
	'eyebrow'       => 'Not sure which level?',
	'heading'       => 'Talk to the SOL training team',
	'text'          => 'We can recommend the right starting level for you or your team, and arrange team or enterprise packages.',
	'buttons'       => array(
		array( 'link' => array( 'url' => '/book-consultation/', 'title' => 'Book a Free Consultation', 'target' => '' ), 'style' => 'primary' ),
		array( 'link' => array( 'url' => '/services/ndis-support-coordination-training/', 'title' => 'Team & Enterprise Training', 'target' => '' ), 'style' => 'light' ),
	),
) );

get_footer( 'shop' );
