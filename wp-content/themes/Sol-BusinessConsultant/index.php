<?php
/**
 * Fallback for archives, categories, search results and anything without a more specific template.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main sol-page-content sol-archive">
	<header class="sol-page-content__header">
		<h1 class="entry-title">
			<?php
			if ( is_search() ) {
				/* translators: %s: search term */
				printf( esc_html__( 'Search results for “%s”', 'sol-business-consultant' ), esc_html( get_search_query() ) );
			} elseif ( is_archive() ) {
				echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
			} else {
				echo esc_html( get_bloginfo( 'name' ) );
			}
			?>
		</h1>
		<?php the_archive_description( '<div class="sol-archive__desc">', '</div>' ); ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="sol-grid sol-grid--3">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<a class="sol-card sol-card--post" href="<?php the_permalink(); ?>">
					<?php if ( has_post_thumbnail() ) : ?><div class="sol-card__media"><?php the_post_thumbnail( 'medium_large' ); ?></div><?php endif; ?>
					<div class="sol-card__body">
						<h2 class="sol-card__title"><?php the_title(); ?></h2>
						<p class="sol-card__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
					</div>
					<span class="sol-card__more"><?php esc_html_e( 'Read', 'sol-business-consultant' ); ?> <?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></span>
				</a>
			<?php endwhile; ?>
		</div>
		<div class="sol-pagination"><?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?></div>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found. Try a different search.', 'sol-business-consultant' ); ?></p>
		<?php get_search_form(); ?>
	<?php endif; ?>
</main>
<?php
get_footer();
