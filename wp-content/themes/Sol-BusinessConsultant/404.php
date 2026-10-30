<?php
/**
 * Page not found: a dark stage with a large "404", site search, and quick links to popular pages.
 * Styles: assets/css/sol-404.css.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$phone   = sol_opt( 'phone' );
$phone_l = preg_replace( '/[^0-9+]/', '', (string) sol_opt( 'phone_link', $phone ) );
$book    = sol_url( '/book-consultation/' );
$popular = array(
	array( 'icon' => 'shield-check', 'title' => 'NDIS Registration', 'text' => 'Packages, audits and readiness support', 'url' => '/services/ndis-registration/' ),
	array( 'icon' => 'layout-grid', 'title' => 'All Services', 'text' => 'Business, compliance, marketing and more', 'url' => '/services/' ),
	array( 'icon' => 'badge-dollar-sign', 'title' => 'Pricing', 'text' => 'Clear packages with GST shown', 'url' => '/pricing/' ),
	array( 'icon' => 'clipboard-check', 'title' => 'Readiness Quiz', 'text' => 'A personalised plan in 5 minutes', 'url' => '/readiness-quiz/' ),
);
?>
<section class="sol-404" aria-labelledby="sol-404-title">
	<div class="sol-container sol-404__inner">
		<div class="sol-404__copy">
			<p class="sol-404__eyebrow"><span aria-hidden="true"></span>Error 404 · Page not found</p>
			<h1 class="sol-404__title" id="sol-404-title">This page has <em>moved on.</em></h1>
			<p class="sol-404__text">The link may be out of date, or the page now lives at a new address. Search the site, or pick up from one of the pages below.</p>

			<form class="sol-404__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="sol-404-q">Search the site</label>
				<?php echo sol_icon( 'search', 'sol-404__search-icon' ); // phpcs:ignore ?>
				<input id="sol-404-q" type="search" name="s" placeholder="Search services, courses, guides…" autocomplete="off">
				<button type="submit">Search</button>
			</form>

			<div class="sol-404__actions">
				<a class="sol-btn sol-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo sol_icon( 'house' ); // phpcs:ignore ?>Back to Home</a>
				<?php if ( $book ) : ?><a class="sol-btn sol-btn--light" href="<?php echo esc_url( $book ); ?>">Book a Consultation</a><?php endif; ?>
			</div>
		</div>

		<div class="sol-404__art" aria-hidden="true">
			<div class="sol-404__orbit"><i></i></div>
			<div class="sol-404__num"><span>4</span><span class="sol-404__zero"></span><span>4</span></div>
			<p class="sol-404__tag"><?php echo sol_icon( 'compass' ); // phpcs:ignore ?>Lost? We'll point the way.</p>
		</div>
	</div>

	<div class="sol-container">
		<div class="sol-404__links">
			<p class="sol-404__links-title">Popular destinations</p>
			<div class="sol-404__grid">
				<?php foreach ( $popular as $p ) : ?>
					<a class="sol-404__card" href="<?php echo esc_url( sol_url( $p['url'] ) ); ?>">
						<span class="sol-404__card-icon"><?php echo sol_icon( $p['icon'] ); // phpcs:ignore ?></span>
						<span class="sol-404__card-body">
							<strong><?php echo esc_html( $p['title'] ); ?></strong>
							<span><?php echo esc_html( $p['text'] ); ?></span>
						</span>
						<span class="sol-404__card-arrow"><?php echo sol_icon( 'arrow-up-right' ); // phpcs:ignore ?></span>
					</a>
				<?php endforeach; ?>
			</div>
			<?php if ( $phone ) : ?>
				<p class="sol-404__help">Still can't find it? Call <a href="tel:<?php echo esc_attr( $phone_l ); ?>"><?php echo esc_html( $phone ); ?></a> and we'll take you straight there.</p>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
