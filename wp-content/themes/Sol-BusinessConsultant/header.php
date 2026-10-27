<?php
/**
 * Site header for every page (replaces Hello Elementor / Elementor headers): top bar, menu with services mega menu, mobile drawer.
 */

defined( 'ABSPATH' ) || exit;

$logo      = sol_opt( 'logo' );
$company   = sol_opt( 'company_name', get_bloginfo( 'name' ) );
$nav       = sol_rows( sol_opt( 'nav_items', array() ) );
$mega      = sol_rows( sol_opt( 'mega_menu', array() ) );
$promo     = sol_opt( 'mega_promo', array() );
$buttons   = sol_opt( 'header_buttons', array() );
$portals   = sol_rows( sol_opt( 'portal_links', array() ) );
$top_text  = sol_opt( 'topbar_text' );
$top_link  = sol_opt( 'topbar_link' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="sol-skip" href="#sol-main">Skip to content</a>
<header class="sol-header" data-sol-header>
	<?php if ( $top_text ) : ?>
		<div class="sol-topbar">
			<div class="sol-container sol-topbar__inner">
				<span><?php echo esc_html( $top_text ); ?></span>
				<?php
				$tl = sol_link( $top_link );
				if ( $tl ) :
					?>
					<a href="<?php echo esc_url( $tl['url'] ); ?>"><?php echo esc_html( $tl['title'] ); ?> &rarr;</a>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="sol-nav">
		<div class="sol-container sol-nav__inner">
			<a class="sol-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $company ); ?> home">
				<?php
				if ( $logo ) {
					echo sol_image( $logo, 'medium', $company, 'sol-logo__img', true ); // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo '<span class="sol-logo__text">' . esc_html( $company ) . '</span>';
				}
				?>
			</a>

			<nav class="sol-menu" aria-label="Main">
				<ul class="sol-menu__list">
					<?php foreach ( $nav as $item ) : ?>
						<?php if ( 'mega' === sol_get( $item, 'type' ) && $mega ) : ?>
							<li class="sol-menu__item sol-menu__item--mega">
								<button type="button" class="sol-menu__link" aria-expanded="false" data-sol-mega-toggle>
									<?php echo esc_html( sol_get( $item, 'label', 'Services' ) ); ?> <?php echo sol_icon( 'chevron-down', 'sol-menu__chev' ); // phpcs:ignore ?>
								</button>
								<div class="sol-mega" data-sol-mega>
									<div class="sol-mega__grid">
										<?php foreach ( $mega as $col ) : ?>
											<div class="sol-mega__col">
												<p class="sol-mega__title"><?php echo esc_html( sol_get( $col, 'label' ) ); ?>
													<?php if ( sol_get( $col, 'badge' ) ) : ?><span class="sol-badge"><?php echo esc_html( $col['badge'] ); ?></span><?php endif; ?>
												</p>
												<?php foreach ( sol_rows( sol_get( $col, 'items', array() ) ) as $link ) : ?>
													<a class="sol-mega__link" href="<?php echo esc_url( sol_url( sol_get( $link, 'url' ) ) ); ?>">
														<span class="sol-mega__icon"><?php echo sol_icon( sol_get( $link, 'icon', 'circle' ) ); // phpcs:ignore ?></span>
														<span><strong><?php echo esc_html( sol_get( $link, 'label' ) ); ?></strong>
															<?php if ( sol_get( $link, 'desc' ) ) : ?><small><?php echo esc_html( $link['desc'] ); ?></small><?php endif; ?>
														</span>
													</a>
												<?php endforeach; ?>
											</div>
										<?php endforeach; ?>
										<?php if ( is_array( $promo ) && sol_get( $promo, 'title' ) ) : ?>
											<div class="sol-mega__promo">
												<span class="sol-eyebrow">Get Started</span>
												<p class="sol-mega__promo-title"><?php echo esc_html( $promo['title'] ); ?></p>
												<p><?php echo esc_html( sol_get( $promo, 'text' ) ); ?></p>
												<?php echo sol_button( sol_get( $promo, 'link' ), 'primary' ); // phpcs:ignore ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							</li>
						<?php else : ?>
							<li class="sol-menu__item"><a class="sol-menu__link" href="<?php echo esc_url( sol_url( sol_get( $item, 'url' ) ) ); ?>"><?php echo esc_html( sol_get( $item, 'label' ) ); ?></a></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</nav>

			<div class="sol-nav__actions">
				<?php foreach ( $portals as $p ) : ?>
					<a class="sol-nav__portal" href="<?php echo esc_url( sol_url( sol_get( $p, 'url' ) ) ); ?>"><?php echo esc_html( sol_get( $p, 'label' ) ); ?></a>
				<?php endforeach; ?>
				<?php echo sol_buttons( $buttons, 'sol-nav__buttons' ); // phpcs:ignore ?>
			</div>

			<button type="button" class="sol-burger" aria-label="Open menu" aria-expanded="false" aria-controls="sol-drawer" data-sol-burger>
				<?php echo sol_icon( 'menu' ); // phpcs:ignore ?>
			</button>
		</div>
	</div>

	<div class="sol-drawer" id="sol-drawer" hidden data-sol-drawer>
		<div class="sol-drawer__inner">
			<?php foreach ( $nav as $item ) : ?>
				<?php if ( 'mega' === sol_get( $item, 'type' ) && $mega ) : ?>
					<details class="sol-drawer__group">
						<summary><?php echo esc_html( sol_get( $item, 'label', 'Services' ) ); ?></summary>
						<?php foreach ( $mega as $col ) : ?>
							<p class="sol-drawer__label"><?php echo esc_html( sol_get( $col, 'label' ) ); ?></p>
							<?php foreach ( sol_rows( sol_get( $col, 'items', array() ) ) as $link ) : ?>
								<a href="<?php echo esc_url( sol_url( sol_get( $link, 'url' ) ) ); ?>"><?php echo esc_html( sol_get( $link, 'label' ) ); ?></a>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</details>
				<?php else : ?>
					<a class="sol-drawer__link" href="<?php echo esc_url( sol_url( sol_get( $item, 'url' ) ) ); ?>"><?php echo esc_html( sol_get( $item, 'label' ) ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php foreach ( $portals as $p ) : ?>
				<a class="sol-drawer__link" href="<?php echo esc_url( sol_url( sol_get( $p, 'url' ) ) ); ?>"><?php echo esc_html( sol_get( $p, 'label' ) ); ?></a>
			<?php endforeach; ?>
			<?php echo sol_buttons( $buttons, 'sol-drawer__buttons' ); // phpcs:ignore ?>
		</div>
	</div>
</header>
<div id="sol-main" class="sol-main">
