<?php
/**
 * Site header for every page (replaces any Elementor theme-builder header): top bar, main menu with
 * the services mega menu and dropdowns, mobile drawer. Menu items come from sol_menu_data() (inc/menu.php).
 */

defined( 'ABSPATH' ) || exit;

$logo     = sol_opt( 'logo' );
$company  = sol_opt( 'company_name', get_bloginfo( 'name' ) );
$menu     = sol_menu_data();
$promo    = sol_opt( 'mega_promo', array() );
$buttons  = sol_opt( 'header_buttons', array() );
$portals  = sol_rows( sol_opt( 'portal_links', array() ) );
$top_text = sol_opt( 'topbar_text' );
$top_link = sol_opt( 'topbar_link' );
$phone    = sol_opt( 'phone' );
$phone_l  = preg_replace( '/[^0-9+]/', '', (string) sol_opt( 'phone_link', $phone ) );

$sol_target = function ( $l ) {
	return ! empty( $l['target'] ) ? ' target="_blank" rel="noopener"' : '';
};
$sol_is_cur = function ( $l ) {
	return ! empty( $l['current'] ) || sol_menu_is_current( isset( $l['url'] ) ? $l['url'] : '' );
};
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
					<?php foreach ( $menu as $mi => $item ) : ?>
						<?php if ( 'mega' === $item['type'] && ! empty( $item['columns'] ) ) : ?>
							<?php $mid = 'sol-mega-' . $mi; ?>
							<li class="sol-menu__item sol-menu__item--mega" data-sol-drop>
								<button type="button" class="sol-menu__link" aria-expanded="false" aria-controls="<?php echo esc_attr( $mid ); ?>" data-sol-drop-toggle>
									<?php echo esc_html( $item['label'] ); ?> <?php echo sol_icon( 'chevron-down', 'sol-menu__chev' ); // phpcs:ignore ?>
								</button>
								<div class="sol-mega" id="<?php echo esc_attr( $mid ); ?>" data-sol-drop-panel data-sol-mega>
									<div class="sol-mega__wrap">
										<div class="sol-mega__tabs" role="tablist" aria-label="<?php echo esc_attr( $item['label'] ); ?> categories">
											<?php foreach ( $item['columns'] as $ci => $col ) : ?>
												<button type="button" class="sol-mega__tab<?php echo 0 === $ci ? ' is-active' : ''; ?>" role="tab" id="<?php echo esc_attr( $mid . '-t' . $ci ); ?>" aria-controls="<?php echo esc_attr( $mid . '-p' . $ci ); ?>" aria-selected="<?php echo 0 === $ci ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $ci ? '0' : '-1'; ?>" data-sol-mega-tab>
													<span class="sol-mega__tab-icon"><?php echo sol_icon( $col['icon'] ? $col['icon'] : 'layers' ); // phpcs:ignore ?></span>
													<span class="sol-mega__tab-text">
														<strong><?php echo esc_html( $col['label'] ); ?></strong>
														<?php if ( $col['desc'] ) : ?><small><?php echo esc_html( $col['desc'] ); ?></small><?php endif; ?>
													</span>
													<?php if ( $col['badge'] ) : ?><span class="sol-mega__flag"><?php echo esc_html( $col['badge'] ); ?></span><?php endif; ?>
													<span class="sol-mega__count" aria-hidden="true"><?php echo (int) count( $col['items'] ); ?></span>
												</button>
											<?php endforeach; ?>
										</div>

										<div class="sol-mega__panels">
											<?php foreach ( $item['columns'] as $ci => $col ) : ?>
												<div class="sol-mega__panel<?php echo 0 === $ci ? ' is-active' : ''; ?>" role="tabpanel" id="<?php echo esc_attr( $mid . '-p' . $ci ); ?>" aria-labelledby="<?php echo esc_attr( $mid . '-t' . $ci ); ?>">
													<div class="sol-mega__panel-head">
														<p class="sol-mega__panel-title"><?php echo esc_html( $col['label'] ); ?></p>
														<?php
														$all = $col['url'] ? $col['url'] : $item['url'];
														if ( $all ) :
															?>
															<a class="sol-mega__all" href="<?php echo esc_url( $all ); ?>">View all <?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></a>
														<?php endif; ?>
													</div>
													<div class="sol-mega__links">
														<?php foreach ( $col['items'] as $li => $link ) : ?>
															<a class="sol-mega__link<?php echo $sol_is_cur( $link ) ? ' is-current' : ''; ?>" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo $sol_target( $link ); // phpcs:ignore ?> style="--i:<?php echo (int) $li; ?>">
																<span class="sol-mega__icon"><?php echo sol_icon( $link['icon'] ? $link['icon'] : 'circle-dot' ); // phpcs:ignore ?></span>
																<span class="sol-mega__text">
																	<strong><?php echo esc_html( $link['label'] ); ?><?php if ( $link['badge'] ) : ?> <span class="sol-mega__flag"><?php echo esc_html( $link['badge'] ); ?></span><?php endif; ?></strong>
																	<?php if ( $link['desc'] ) : ?><small><?php echo esc_html( $link['desc'] ); ?></small><?php endif; ?>
																</span>
																<?php echo sol_icon( 'arrow-up-right', 'sol-mega__go' ); // phpcs:ignore ?>
															</a>
														<?php endforeach; ?>
													</div>
												</div>
											<?php endforeach; ?>
										</div>

										<?php if ( is_array( $promo ) && sol_get( $promo, 'title' ) ) : ?>
											<aside class="sol-mega__promo">
												<span class="sol-mega__promo-glow" aria-hidden="true"></span>
												<span class="sol-mega__promo-kick">Get started</span>
												<p class="sol-mega__promo-title"><?php echo esc_html( $promo['title'] ); ?></p>
												<?php if ( sol_get( $promo, 'text' ) ) : ?><p class="sol-mega__promo-text"><?php echo esc_html( $promo['text'] ); ?></p><?php endif; ?>
												<?php echo sol_button( sol_get( $promo, 'link' ), 'primary', 'sol-btn--sm' ); // phpcs:ignore ?>
												<?php if ( $phone ) : ?>
													<a class="sol-mega__call" href="tel:<?php echo esc_attr( $phone_l ); ?>"><?php echo sol_icon( 'phone' ); // phpcs:ignore ?><span>Prefer to talk?<b><?php echo esc_html( $phone ); ?></b></span></a>
												<?php endif; ?>
											</aside>
										<?php endif; ?>
									</div>
								</div>
							</li>
						<?php elseif ( 'dropdown' === $item['type'] && ! empty( $item['children'] ) ) : ?>
							<li class="sol-menu__item sol-menu__item--dd" data-sol-drop>
								<button type="button" class="sol-menu__link" aria-expanded="false" data-sol-drop-toggle>
									<?php echo esc_html( $item['label'] ); ?> <?php echo sol_icon( 'chevron-down', 'sol-menu__chev' ); // phpcs:ignore ?>
								</button>
								<div class="sol-dd" data-sol-drop-panel>
									<?php if ( $item['url'] ) : ?>
										<a class="sol-dd__link sol-dd__link--top" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $sol_target( $item ); // phpcs:ignore ?>><?php echo esc_html( $item['label'] ); ?> overview</a>
									<?php endif; ?>
									<?php foreach ( $item['children'] as $li => $c ) : ?>
										<a class="sol-dd__link<?php echo $sol_is_cur( $c ) ? ' is-current' : ''; ?>" href="<?php echo esc_url( $c['url'] ); ?>"<?php echo $sol_target( $c ); // phpcs:ignore ?> style="--i:<?php echo (int) $li; ?>">
											<?php if ( $c['icon'] ) : ?><span class="sol-mega__icon"><?php echo sol_icon( $c['icon'] ); // phpcs:ignore ?></span><?php endif; ?>
											<span class="sol-mega__text"><strong><?php echo esc_html( $c['label'] ); ?></strong><?php if ( $c['desc'] ) : ?><small><?php echo esc_html( $c['desc'] ); ?></small><?php endif; ?></span>
										</a>
									<?php endforeach; ?>
								</div>
							</li>
						<?php else : ?>
							<li class="sol-menu__item"><a class="sol-menu__link<?php echo $sol_is_cur( $item ) ? ' is-current' : ''; ?>" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $sol_target( $item ); // phpcs:ignore ?><?php echo $sol_is_cur( $item ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a></li>
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
			<?php foreach ( $menu as $item ) : ?>
				<?php if ( 'mega' === $item['type'] && ! empty( $item['columns'] ) ) : ?>
					<details class="sol-drawer__group">
						<summary><?php echo esc_html( $item['label'] ); ?> <?php echo sol_icon( 'chevron-down', 'sol-drawer__chev' ); // phpcs:ignore ?></summary>
						<?php foreach ( $item['columns'] as $col ) : ?>
							<details class="sol-drawer__sub">
								<summary>
									<span class="sol-mega__icon"><?php echo sol_icon( $col['icon'] ? $col['icon'] : 'layers' ); // phpcs:ignore ?></span>
									<span><?php echo esc_html( $col['label'] ); ?></span>
									<span class="sol-mega__count" aria-hidden="true"><?php echo (int) count( $col['items'] ); ?></span>
								</summary>
								<?php foreach ( $col['items'] as $link ) : ?>
									<a href="<?php echo esc_url( $link['url'] ); ?>"<?php echo $sol_target( $link ); // phpcs:ignore ?>><?php echo esc_html( $link['label'] ); ?></a>
								<?php endforeach; ?>
							</details>
						<?php endforeach; ?>
					</details>
				<?php elseif ( 'dropdown' === $item['type'] && ! empty( $item['children'] ) ) : ?>
					<details class="sol-drawer__group">
						<summary><?php echo esc_html( $item['label'] ); ?> <?php echo sol_icon( 'chevron-down', 'sol-drawer__chev' ); // phpcs:ignore ?></summary>
						<?php if ( $item['url'] ) : ?><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?> overview</a><?php endif; ?>
						<?php foreach ( $item['children'] as $c ) : ?>
							<a href="<?php echo esc_url( $c['url'] ); ?>"<?php echo $sol_target( $c ); // phpcs:ignore ?>><?php echo esc_html( $c['label'] ); ?></a>
						<?php endforeach; ?>
					</details>
				<?php else : ?>
					<a class="sol-drawer__link" href="<?php echo esc_url( $item['url'] ); ?>"<?php echo $sol_target( $item ); // phpcs:ignore ?>><?php echo esc_html( $item['label'] ); ?></a>
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
