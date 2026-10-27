<?php
/**
 * Site footer for every page (replaces Hello Elementor / Elementor footers).
 */

defined( 'ABSPATH' ) || exit;

$company  = sol_opt( 'company_name', get_bloginfo( 'name' ) );
$columns  = sol_rows( sol_opt( 'footer_columns', array() ) );
$phone    = sol_opt( 'phone' );
$phone_l  = sol_opt( 'phone_link', $phone );
$email    = sol_opt( 'email' );
$web      = sol_opt( 'website_label' );
$address  = sol_opt( 'address' );
$abn      = sol_opt( 'abn' );
$cta_h    = sol_opt( 'footer_cta_heading' );
$copy     = str_replace( '{year}', gmdate( 'Y' ), sol_opt( 'footer_copyright', '© {year} ' . $company ) );
?>
</div><!-- sol-main -->
<footer class="sol-footer">
	<?php if ( $cta_h ) : ?>
		<div class="sol-footer__cta">
			<div class="sol-container sol-footer__cta-inner">
				<div>
					<h2 class="sol-footer__cta-title"><?php echo wp_kses( str_replace( '|', '<br>', esc_html( $cta_h ) ), array( 'br' => array() ) ); ?></h2>
					<p><?php echo esc_html( sol_opt( 'footer_cta_text' ) ); ?></p>
				</div>
				<?php echo sol_button( sol_opt( 'footer_cta_link' ), 'primary' ); // phpcs:ignore ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="sol-container sol-footer__main">
		<div class="sol-footer__brand">
			<?php
			$logo = sol_opt( 'logo' );
			if ( $logo ) {
				echo sol_image( $logo, 'medium', $company, 'sol-footer__logo' ); // phpcs:ignore
			}
			?>
			<p><?php echo esc_html( sol_opt( 'tagline' ) ); ?></p>
			<?php if ( sol_opt( 'newsletter_enabled', false ) ) : ?>
				<form class="sol-form sol-newsletter" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sol-form>
					<?php sol_form_hidden( 'newsletter' ); ?>
					<label for="sol-news-email"><?php echo esc_html( sol_opt( 'newsletter_label', 'Newsletter' ) ); ?></label>
					<div class="sol-newsletter__row">
						<input id="sol-news-email" type="email" name="email" required placeholder="you@business.com.au" autocomplete="email">
						<button class="sol-btn sol-btn--primary" type="submit">Join</button>
					</div>
					<small><?php echo esc_html( sol_opt( 'newsletter_note' ) ); ?></small>
					<p class="sol-form__status" role="status" aria-live="polite"></p>
				</form>
			<?php endif; ?>
		</div>
		<div class="sol-footer__cols">
			<?php foreach ( $columns as $col ) : ?>
				<div class="sol-footer__col">
					<h3><?php echo esc_html( sol_get( $col, 'title' ) ); ?></h3>
					<ul>
						<?php foreach ( sol_rows( sol_get( $col, 'links', array() ) ) as $l ) : ?>
							<li><a href="<?php echo esc_url( sol_url( sol_get( $l, 'url' ) ) ); ?>"><?php echo esc_html( sol_get( $l, 'label' ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="sol-container sol-footer__contact">
		<?php if ( $phone ) : ?><span>Phone: <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone_l ) ); ?>"><?php echo esc_html( $phone ); ?></a></span><?php endif; ?>
		<?php if ( $email ) : ?><span>Email: <a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></span><?php endif; ?>
		<?php if ( $web ) : ?><span>Web: <a href="<?php echo esc_url( 'https://' . preg_replace( '#^https?://#', '', $web ) ); ?>"><?php echo esc_html( $web ); ?></a></span><?php endif; ?>
		<?php if ( $address ) : ?><span>Location: <?php echo esc_html( $address ); ?></span><?php endif; ?>
		<?php if ( $abn ) : ?><span>ABN: <?php echo esc_html( $abn ); ?></span><?php endif; ?>
	</div>
	<div class="sol-footer__bottom"><div class="sol-container"><p><?php echo esc_html( $copy ); ?></p></div></div>
</footer>

<?php
$wa = sol_opt( 'whatsapp_url' );
if ( $wa && sol_opt( 'floating_whatsapp', false ) ) :
	?>
	<a class="sol-float-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"><?php echo sol_icon( 'message-circle' ); // phpcs:ignore ?></a>
<?php endif; ?>
<?php if ( sol_opt( 'back_to_top', false ) ) : ?>
	<button type="button" class="sol-totop" aria-label="Back to top" data-sol-totop hidden><?php echo sol_icon( 'arrow-up' ); // phpcs:ignore ?></button>
<?php endif; ?>
<?php if ( sol_opt( 'cookie_enabled', false ) ) : ?>
	<div class="sol-cookie" data-sol-cookie hidden role="dialog" aria-live="polite" aria-label="Cookie notice">
		<p><?php echo esc_html( sol_opt( 'cookie_text' ) ); ?>
			<?php if ( sol_opt( 'cookie_link' ) ) : ?><a href="<?php echo esc_url( sol_url( sol_opt( 'cookie_link' ) ) ); ?>">Privacy Policy</a><?php endif; ?>
		</p>
		<button type="button" class="sol-btn sol-btn--primary" data-sol-cookie-accept>OK</button>
	</div>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
