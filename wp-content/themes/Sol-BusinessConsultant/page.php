<?php
/**
 * Default page template: cart, checkout, my account, and any page still built with
 * the block editor or Elementor. SOL pages use page-templates/sol-builder.php instead.
 */

defined( 'ABSPATH' ) || exit;

$sol_woo_flow = function_exists( 'is_cart' ) && ( is_cart() || ( is_checkout() && ! is_order_received_page() ) );

get_header();
while ( have_posts() ) {
	the_post();
	$elementor = get_post_meta( get_the_ID(), '_elementor_edit_mode', true ) && did_action( 'elementor/loaded' );

	if ( $sol_woo_flow ) {
		sol_checkout_header();
	}
	$classes = 'site-main sol-page-content';
	$classes .= $elementor ? ' is-elementor' : '';
	$classes .= $sol_woo_flow ? ' sol-woo-flow' : '';
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		$classes .= ' sol-woo-account';
	}
	?>
	<main id="primary" class="<?php echo esc_attr( $classes ); ?>">
		<?php if ( ! $elementor && ! $sol_woo_flow && ! is_front_page() ) : ?>
			<header class="sol-page-content__header"><h1 class="entry-title"><?php the_title(); ?></h1></header>
		<?php endif; ?>
		<div class="entry-content"><?php the_content(); ?></div>
		<?php wp_link_pages(); ?>
	</main>
	<?php
}
get_footer();
