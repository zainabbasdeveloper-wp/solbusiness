<?php
/**
 * Site footer for every page (replaces any Elementor theme-builder footer).
 * Layout: brand + contact on the left, a one-line invitation and a compact newsletter field on the right,
 * link columns (accordions on phones) and a bottom bar with legal links. Styles: assets/css/sol-footer.css.
 */

defined( 'ABSPATH' ) || exit;

$company  = sol_opt( 'company_name', get_bloginfo( 'name' ) );
$columns  = sol_rows( sol_opt( 'footer_columns', array() ) );
$phone    = sol_opt( 'phone' );
$phone_l  = preg_replace( '/[^0-9+]/', '', (string) sol_opt( 'phone_link', $phone ) );
$email    = sol_opt( 'email' );
$address  = sol_opt( 'address' );
$hours    = sol_opt( 'business_hours' );
$abn      = sol_opt( 'abn' );
$cta_h    = sol_opt( 'footer_cta_heading' );
$copy     = str_replace( '{year}', gmdate( 'Y' ), sol_opt( 'footer_copyright', '© {year} ' . $company ) );

// Legal links move from the columns to the bottom bar (they still come from the site settings).
$legal = array();
$widest = -1;
$most   = 10; // only a column with more than 10 links gets the two-column layout
foreach ( $columns as $i => $col ) {
	$keep = array();
	foreach ( sol_rows( sol_get( $col, 'links', array() ) ) as $l ) {
		if ( preg_match( '/privacy|terms|accessib|refund/i', (string) sol_get( $l, 'label' ) ) ) {
			$legal[] = $l;
		} else {
			$keep[] = $l;
		}
	}
	$columns[ $i ]['links'] = $keep;
	if ( count( $keep ) > $most ) {
		$most   = count( $keep );
		$widest = $i;
	}
}
?>
</div><!-- sol-main -->
<footer class="sol-footer">
	<div class="sol-container sol-footer__top">
		<div class="sol-footer__brand">
			<?php
			$logo = sol_opt( 'logo' );
			if ( $logo ) {
				echo '<a class="sol-footer__logo-link" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr( $company ) . ' home">' . sol_image( $logo, 'medium', $company, 'sol-footer__logo' ) . '</a>'; // phpcs:ignore
			}
			?>
			<p class="sol-footer__tagline"><?php echo esc_html( sol_opt( 'tagline' ) ); ?></p>
			<ul class="sol-footer__reach">
				<?php if ( $phone ) : ?><li><a href="tel:<?php echo esc_attr( $phone_l ); ?>"><span><?php echo sol_icon( 'phone' ); // phpcs:ignore ?></span><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
				<?php if ( $email ) : ?><li><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><span><?php echo sol_icon( 'mail' ); // phpcs:ignore ?></span><?php echo esc_html( antispambot( $email ) ); ?></a></li><?php endif; ?>
				<?php if ( $address ) : ?><li><span><?php echo sol_icon( 'map-pin' ); // phpcs:ignore ?></span><?php echo esc_html( $address ); ?></li><?php endif; ?>
				<?php if ( $hours ) : ?><li><span><?php echo sol_icon( 'clock' ); // phpcs:ignore ?></span><?php echo esc_html( $hours ); ?></li><?php endif; ?>
			</ul>
		</div>

		<div class="sol-footer__side">
			<?php
			$cta_l = sol_opt( 'footer_cta_link' );
			if ( $cta_h && $cta_l ) :
				?>
				<a class="sol-footer__invite" href="<?php echo esc_url( sol_url( sol_get( $cta_l, 'url' ) ) ); ?>">
					<span><?php echo esc_html( trim( str_replace( '|', ' ', $cta_h ) ) ); ?></span>
					<strong><?php echo esc_html( sol_get( $cta_l, 'title', 'Get Started' ) ); ?><?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></strong>
				</a>
			<?php endif; ?>
			<?php if ( sol_opt( 'newsletter_enabled', false ) ) : ?>
				<form class="sol-form sol-newsletter" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sol-form data-success="You're subscribed. Look out for practical NDIS and business updates from SOL in your inbox.">
					<?php sol_form_hidden( 'newsletter' ); ?>
					<label for="sol-news-email"><?php echo esc_html( sol_opt( 'newsletter_label', 'Newsletter' ) ); ?></label>
					<div class="sol-newsletter__row">
						<input id="sol-news-email" type="email" name="email" required placeholder="you@business.com.au" autocomplete="email">
						<button type="submit" aria-label="Subscribe"><?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></button>
					</div>
					<small><?php echo esc_html( sol_opt( 'newsletter_note' ) ); ?></small>
					<p class="sol-form__status" role="status" aria-live="polite"></p>
				</form>
			<?php endif; ?>
		</div>
	</div>

	<nav class="sol-container sol-footer__nav" aria-label="Footer">
		<?php
		foreach ( $columns as $i => $col ) :
			$links = $col['links'];
			if ( ! $links ) {
				continue;
			}
			?>
			<details class="sol-footer__col<?php echo $i === $widest ? ' sol-footer__col--wide' : ''; ?>" data-sol-fcol open>
				<summary><h3><?php echo esc_html( sol_get( $col, 'title' ) ); ?></h3><?php echo sol_icon( 'chevron-down' ); // phpcs:ignore ?></summary>
				<ul>
					<?php foreach ( $links as $l ) : ?>
						<li><a href="<?php echo esc_url( sol_url( sol_get( $l, 'url' ) ) ); ?>"><?php echo esc_html( sol_get( $l, 'label' ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endforeach; ?>
	</nav>

	<div class="sol-footer__bottom">
		<div class="sol-container sol-footer__bottom-inner">
			<p><?php echo esc_html( $copy ); ?></p>
			<ul class="sol-footer__legal">
				<?php if ( $abn ) : ?><li>ABN <?php echo esc_html( $abn ); ?></li><?php endif; ?>
				<?php foreach ( $legal as $l ) : ?>
					<li><a href="<?php echo esc_url( sol_url( sol_get( $l, 'url' ) ) ); ?>"><?php echo esc_html( sol_get( $l, 'label' ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</footer>
<script>
/* Footer link columns: open on tablets and desktops, tap-to-open accordions on phones. */
(function () {
	var mq = window.matchMedia('(min-width: 768px)');
	var cols = document.querySelectorAll('[data-sol-fcol]');
	function sync() { cols.forEach(function (d) { d.open = mq.matches; }); }
	cols.forEach(function (d) { d.querySelector('summary').addEventListener('click', function (e) { if (mq.matches) { e.preventDefault(); } }); });
	sync();
	if (mq.addEventListener) { mq.addEventListener('change', sync); }
})();
</script>

<?php
$wa = sol_opt( 'whatsapp_url' );
if ( $wa && sol_opt( 'floating_whatsapp', false ) ) :
	?>
	<a class="sol-float-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp"><svg class="sol-float-wa__logo" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg></a>
<?php endif; ?>
<?php if ( sol_opt( 'back_to_top', false ) ) : ?>
	<button type="button" class="sol-totop" aria-label="Back to top" data-sol-totop hidden><svg class="sol-totop__ring" viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="22" pathLength="100"/></svg><?php echo sol_icon( 'arrow-up' ); // phpcs:ignore ?></button>
	<script>
	/* Back-to-top ring fills as the page scrolls. */
	(function () {
		var b = document.querySelector('[data-sol-totop]');
		if (!b) { return; }
		function p() { var h = document.documentElement.scrollHeight - window.innerHeight; b.style.setProperty('--p', h > 0 ? Math.min(100, Math.round(window.scrollY / h * 100)) : 0); }
		window.addEventListener('scroll', p, { passive: true });
		p();
	})();
	</script>
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
