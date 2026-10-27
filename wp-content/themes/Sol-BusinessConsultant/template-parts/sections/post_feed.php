<?php
/** Layout: post_feed — blog posts, services, courses or engagement examples. */
defined( 'ABSPATH' ) || exit;

$type    = sol_get( $s, 'post_type', 'post' );
$allowed = array( 'post' );
if ( ! in_array( $type, $allowed, true ) ) {
	return;
}
$q = new WP_Query( array(
	'post_type'           => $type,
	'posts_per_page'      => max( 1, min( 60, (int) sol_get( $s, 'count', 9 ) ) ),
	'ignore_sticky_posts' => false,
	'no_found_rows'       => true,
	'orderby'             => 'post' === $type ? 'date' : array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
	'order'               => 'post' === $type ? 'DESC' : 'ASC',
) );
if ( ! $q->have_posts() ) {
	return;
}
$featured = ! empty( $s['featured_first'] ) && 'post' === $type;
$filters  = ! empty( $s['show_filters'] ) && 'post' === $type;
$posts    = $q->posts;
$cats     = array();

$meta = function ( $p ) {
	$author = get_field( 'post_author_label', $p->ID );
	$date   = get_field( 'post_date_label', $p->ID );
	$read   = get_field( 'post_read_time', $p->ID );
	$out    = '<p class="sol-post__meta">';
	$out   .= '<span>' . sol_icon( 'user' ) . ' ' . esc_html( $author ? $author : get_the_author_meta( 'display_name', $p->post_author ) ) . '</span>';
	$out   .= '<span>' . sol_icon( 'calendar' ) . ' ' . esc_html( $date ? $date : get_the_date( '', $p ) ) . '</span>';
	if ( $read ) {
		$out .= '<span>' . sol_icon( 'clock' ) . ' ' . esc_html( $read ) . '</span>';
	}
	return $out . '</p>';
};

sol_section_open( $s, 'post_feed' );
?>
<div class="sol-container" data-sol-filter-scope>
	<?php sol_section_head( $s ); ?>
	<?php
	if ( $featured ) :
		$f = array_shift( $posts );
		$c = get_the_category( $f->ID );
		?>
		<a class="sol-post-featured" href="<?php echo esc_url( get_permalink( $f ) ); ?>">
			<div class="sol-post-featured__body">
				<span class="sol-pill"><?php echo esc_html( $c ? $c[0]->name . ' guide' : 'Featured' ); ?></span>
				<h2><?php echo esc_html( get_the_title( $f ) ); ?></h2>
				<p><?php echo esc_html( get_the_excerpt( $f ) ); ?></p>
				<?php echo $meta( $f ); // phpcs:ignore ?>
				<span class="sol-card__link">Read article <?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></span>
			</div>
			<div class="sol-post-featured__media"><?php echo get_the_post_thumbnail( $f, 'large' ); ?></div>
		</a>
	<?php endif; ?>

	<?php
	if ( $filters ) {
		foreach ( $posts as $p ) {
			foreach ( get_the_category( $p->ID ) as $c ) {
				$cats[ $c->slug ] = $c->name;
			}
		}
	}
	if ( count( $cats ) > 1 ) :
		?>
		<div class="sol-filter" role="group" aria-label="Filter by category">
			<button type="button" class="is-active" data-sol-filter="*">All</button>
			<?php foreach ( $cats as $slug => $name ) : ?><button type="button" data-sol-filter="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></button><?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="sol-grid sol-grid--3">
		<?php foreach ( $posts as $p ) : ?>
			<?php
			$c       = get_the_category( $p->ID );
			$slugs   = implode( ' ', wp_list_pluck( $c, 'slug' ) );
			$thumb   = get_post_thumbnail_id( $p );
			$excerpt = get_the_excerpt( $p );
			?>
			<a class="sol-card sol-card--post" href="<?php echo esc_url( get_permalink( $p ) ); ?>" data-sol-filter-item="<?php echo esc_attr( $slugs ); ?>">
				<?php if ( $thumb ) : ?><div class="sol-card__media"><?php echo sol_image( $thumb, 'medium_large', get_the_title( $p ) ); // phpcs:ignore ?></div><?php endif; ?>
				<div class="sol-card__body">
					<?php if ( $c ) : ?><span class="sol-tag"><?php echo esc_html( $c[0]->name ); ?></span><?php endif; ?>
					<h3 class="sol-card__title"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
					<p class="sol-card__text"><?php echo esc_html( wp_trim_words( (string) $excerpt, 28 ) ); ?></p>
					<?php
					if ( 'post' === $type ) {
						echo $meta( $p ); // phpcs:ignore
					}
					?>
				</div>
				<span class="sol-card__more">Read <?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php echo sol_buttons( sol_get( $s, 'buttons', array() ), 'sol-buttons--after sol-buttons--center' ); // phpcs:ignore ?>
</div>
</section>
<?php
wp_reset_postdata();
