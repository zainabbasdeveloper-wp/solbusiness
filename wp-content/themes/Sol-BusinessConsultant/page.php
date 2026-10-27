<?php
/**
 * Default page template: cart, checkout, my account, and any page still built with
 * the block editor or Elementor. SOL pages use page-templates/sol-builder.php instead.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) {
	the_post();
	$elementor = get_post_meta( get_the_ID(), '_elementor_edit_mode', true ) && did_action( 'elementor/loaded' );
	?>
	<main id="primary" class="site-main sol-page-content<?php echo $elementor ? ' is-elementor' : ''; ?>">
		<?php if ( ! $elementor && ! is_front_page() ) : ?>
			<header class="sol-page-content__header"><h1 class="entry-title"><?php the_title(); ?></h1></header>
		<?php endif; ?>
		<div class="entry-content"><?php the_content(); ?></div>
		<?php wp_link_pages(); ?>
	</main>
	<?php
}
get_footer();
