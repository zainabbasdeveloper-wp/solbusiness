<?php
/**
 * Site footer for every page (replaces any Elementor theme-builder footer).
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
				<form class="sol-form sol-newsletter" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sol-form data-success="You're subscribed. Look out for practical NDIS and business updates from SOL in your inbox.">
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
	<a class="sol-float-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"><svg class="sol-float-wa__logo" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg></a>
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
