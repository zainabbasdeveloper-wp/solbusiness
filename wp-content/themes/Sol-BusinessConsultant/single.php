<?php
/**
 * Single blog post: article fields (takeaways, sections) around the post content.
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) {
	the_post();
	$id        = get_the_ID();
	$cats      = get_the_category( $id );
	$author    = get_field( 'post_author_label', $id ) ? get_field( 'post_author_label', $id ) : get_the_author();
	$date      = get_field( 'post_date_label', $id ) ? get_field( 'post_date_label', $id ) : get_the_date();
	$read      = get_field( 'post_read_time', $id );
	$takeaways = sol_list( get_field( 'post_takeaways', $id ) );
	$sections  = sol_rows( get_field( 'post_sections', $id ) );
	$blog      = get_option( 'page_for_posts' ) ? get_post( get_option( 'page_for_posts' ) ) : get_page_by_path( 'blog' );
	?>
	<article class="sol-section sol-bg-white sol-article">
		<div class="sol-container sol-container--narrow">
			<a class="sol-back" href="<?php echo esc_url( $blog ? get_permalink( $blog ) : home_url( '/' ) ); ?>"><?php echo sol_icon( 'arrow-left' ); // phpcs:ignore ?> Back to blog</a>
			<?php if ( $cats ) : ?><span class="sol-tag"><?php echo esc_html( $cats[0]->name ); ?></span><?php endif; ?>
			<h1 class="sol-article__title"><?php the_title(); ?></h1>
			<p class="sol-article__meta">
				<span><?php echo sol_icon( 'user' ); // phpcs:ignore ?> <?php echo esc_html( $author ); ?></span>
				<span><?php echo sol_icon( 'calendar' ); // phpcs:ignore ?> <?php echo esc_html( $date ); ?></span>
				<?php if ( $read ) : ?><span><?php echo sol_icon( 'clock' ); // phpcs:ignore ?> <?php echo esc_html( $read ); ?></span><?php endif; ?>
			</p>
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="sol-article__image"><?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?></div>
			<?php endif; ?>

			<?php if ( $takeaways ) : ?>
				<div class="sol-takeaways">
					<h2>Key takeaways</h2>
					<?php echo sol_checks( $takeaways ); // phpcs:ignore ?>
				</div>
			<?php endif; ?>

			<div class="sol-prose"><?php the_content(); ?></div>

			<?php foreach ( $sections as $sec ) : ?>
				<section class="sol-article__section">
					<?php if ( sol_get( $sec, 'heading' ) ) : ?><h2><?php echo esc_html( $sec['heading'] ); ?></h2><?php endif; ?>
					<?php echo sol_text( sol_get( $sec, 'content' ) ); // phpcs:ignore ?>
					<?php $bul = sol_list( sol_get( $sec, 'bullets', array() ) ); ?>
					<?php if ( $bul ) : ?>
						<ul><?php foreach ( $bul as $b ) : ?><li><?php echo esc_html( $b ); ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>

			<p class="sol-article__note">This article is general information only. For advice tailored to your business or provider registration pathway, contact SOL Business Consultant.</p>
		</div>
	</article>
	<?php
	sol_render_row( array(
		'acf_fc_layout' => 'cta_banner',
		'style'         => 'dark',
		'heading'       => 'Explore practical SOL use cases',
		'text'          => 'See how portal, CRM, documents, tickets, and automation can fit together.',
		'buttons'       => array( array( 'link' => array( 'url' => '/use-cases', 'title' => 'View Use Cases', 'target' => '' ), 'style' => 'primary' ) ),
	) );
}
get_footer();
