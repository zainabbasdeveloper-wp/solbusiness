<?php
/** Home page option A: Executive — dark, cinematic, confident. */
defined( 'ABSPATH' ) || exit;

$d = sol_ho_data();
get_header();
?>
<!-- 1. Hero -->
<section class="hoa-hero" data-ho-glow>
	<?php echo sol_ho_img( 'home-consultants-team', '', 'hoa-hero__bg', true ); // phpcs:ignore ?>
	<div class="ho-wrap hoa-hero__grid">
		<div class="hoa-hero__copy">
			<span class="hoa-pill"><span class="hoa-pill__dot"></span>Australian business consultants</span>
			<h1 class="hoa-hero__title"><?php echo esc_html( $d['headline'][0] ); ?> <span>Moves You Forward.</span></h1>
			<p class="hoa-hero__text"><?php echo esc_html( $d['intro'] ); ?></p>
			<div class="hoa-hero__btns">
				<a class="sol-btn sol-btn--primary" href="#hoa-services">Explore Our Services <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
				<a class="sol-btn sol-btn--light" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Talk to Our Team</a>
			</div>
			<ul class="hoa-badges">
				<?php foreach ( $d['badges'] as $b ) : ?><li><?php echo sol_icon( $b[0] ); // phpcs:ignore ?><?php echo esc_html( $b[1] ); ?></li><?php endforeach; ?>
			</ul>
		</div>
		<aside class="hoa-glass hoa-start" aria-label="Where do you need help?">
			<p class="hoa-start__label">Where do you need help?</p>
			<?php foreach ( $d['areas'] as $a ) : ?>
				<a class="hoa-start__item" href="<?php echo esc_url( home_url( $a['url'] ) ); ?>">
					<span class="hoa-start__icon"><?php echo sol_icon( $a['icon'] ); // phpcs:ignore ?></span>
					<span><strong><?php echo esc_html( $a['title'] ); ?></strong><small><?php echo esc_html( count( $a['links'] ) . ' services' ); ?></small></span>
					<?php echo sol_icon( 'arrow-up-right', 'hoa-start__arrow' ); // phpcs:ignore ?>
				</a>
			<?php endforeach; ?>
			<a class="hoa-start__call" href="<?php echo esc_url( $d['tel'] ); ?>"><?php echo sol_icon( 'phone' ); // phpcs:ignore ?> Prefer to talk? <b><?php echo esc_html( $d['phone'] ); ?></b></a>
		</aside>
	</div>
	<div class="ho-wrap">
		<dl class="hoa-stats hoa-glass">
			<?php foreach ( $d['stats'] as $s ) : ?>
				<div class="ho-stat"><dt><span data-sol-count="<?php echo esc_attr( $s[0] ); ?>"><?php echo esc_html( $s[0] ); ?></span><?php echo esc_html( $s[1] ); ?></dt><dd><?php echo esc_html( $s[2] ); ?></dd></div>
			<?php endforeach; ?>
		</dl>
	</div>
</section>

<!-- 2. Platforms -->
<section class="hoa-platforms" aria-label="Systems we work with">
	<div class="ho-wrap">
		<p class="hoa-platforms__label">Familiar with the systems Australian providers use every day</p>
	</div>
	<div class="ho-marquee">
		<ul class="ho-marquee__track">
			<?php foreach ( array( false, true ) as $dup ) : ?>
				<?php foreach ( $d['platforms'] as $p ) : ?><li<?php echo $dup ? ' aria-hidden="true"' : ''; ?>><?php echo esc_html( $p ); ?></li><?php endforeach; ?>
			<?php endforeach; ?>
		</ul>
	</div>
	<div class="ho-wrap"><p class="hoa-platforms__note">Names shown for context only. They do not imply endorsement or partnership.</p></div>
</section>

<!-- 3. Services -->
<section id="hoa-services" class="ho-sec hoa-services">
	<div class="ho-wrap">
		<div class="hoa-split-head">
			<?php sol_ho_head( 'What we do', 'Three practices.<br>One accountable team.' ); ?>
			<p class="hoa-split-head__aside" data-reveal>Pick the area closest to what you need. Every engagement starts with a free consultation and a written scope.</p>
		</div>
		<div class="hoa-cards" data-reveal-group>
			<?php foreach ( $d['areas'] as $i => $a ) : ?>
				<article class="hoa-card" data-reveal tabindex="0">
					<?php echo sol_ho_img( $a['img'], $a['alt'], 'hoa-card__img' ); // phpcs:ignore ?>
					<div class="hoa-card__body">
						<span class="hoa-card__num">0<?php echo (int) $i + 1; ?></span>
						<span class="hoa-card__icon"><?php echo sol_icon( $a['icon'] ); // phpcs:ignore ?></span>
						<h3 class="hoa-card__title"><?php echo esc_html( $a['title'] ); ?></h3>
						<p class="hoa-card__text"><?php echo esc_html( $a['text'] ); ?></p>
						<div class="hoa-card__more">
							<ul class="ho-links">
								<?php foreach ( array_slice( $a['links'], 0, 4 ) as $l ) : ?><li><a href="<?php echo esc_url( home_url( $l[1] ) ); ?>"><?php echo esc_html( $l[0] ); ?> <?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></a></li><?php endforeach; ?>
							</ul>
							<a class="hoa-card__cta" href="<?php echo esc_url( home_url( $a['url'] ) ); ?>">Explore <?php echo esc_html( $a['short'] ); ?> <?php echo sol_icon( 'arrow-up-right' ); // phpcs:ignore ?></a>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- 4. Why SOL -->
<section class="ho-sec hoa-why">
	<div class="ho-wrap hoa-why__grid">
		<div class="hoa-why__lead" data-reveal>
			<span class="ho-eyebrow">Why SOL</span>
			<h2 class="ho-title">Clear advice.<br>No guesswork.</h2>
			<p class="ho-intro">You always know what is happening, why, and what it costs, from the first call to the finished work.</p>
			<a class="sol-btn sol-btn--light" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About SOL</a>
		</div>
		<div class="hoa-why__items" data-reveal-group>
			<?php foreach ( $d['why'] as $w ) : ?>
				<div class="hoa-glass hoa-why__item" data-reveal data-ho-glow>
					<span class="hoa-why__icon"><?php echo sol_icon( $w[0] ); // phpcs:ignore ?></span>
					<h3><?php echo esc_html( $w[1] ); ?></h3>
					<p><?php echo esc_html( $w[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- 5. How it works -->
<section class="ho-sec hoa-steps">
	<div class="ho-wrap">
		<?php sol_ho_head( 'How it works', 'Three steps from first call to finished work', '', 'ho-head--center' ); ?>
		<ol class="hoa-steps__list" data-reveal-group>
			<?php foreach ( $d['steps'] as $i => $s ) : ?>
				<li data-reveal>
					<span class="hoa-steps__num"><?php echo (int) $i + 1; ?></span>
					<span class="hoa-steps__icon"><?php echo sol_icon( $s[0] ); // phpcs:ignore ?></span>
					<h3><?php echo esc_html( $s[1] ); ?></h3>
					<p><?php echo esc_html( $s[2] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
		<div class="hoa-center"><a class="sol-btn sol-btn--primary" href="<?php echo esc_url( home_url( '/book-consultation/' ) ); ?>">Book a Free Consultation <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a></div>
	</div>
</section>

<!-- 6. NDIS roadmap (light) -->
<section id="ndis" class="ho-sec hoa-roadmap">
	<div class="ho-wrap hoa-roadmap__grid">
		<div class="hoa-roadmap__media" data-reveal>
			<?php echo sol_ho_img( 'ndis-roadmap-support-at-home', 'Support worker helping an NDIS participant at home' ); // phpcs:ignore ?>
			<div class="hoa-chip"><span><?php echo sol_icon( 'check' ); // phpcs:ignore ?></span><div><strong>Audit Ready</strong><small>Policies, evidence and coaching</small></div></div>
		</div>
		<div>
			<?php sol_ho_head( 'NDIS registration', 'Your path to NDIS registration, simplified', 'For sole traders, small providers and allied health professionals preparing for the NDIS Commission audit.' ); ?>
			<ol class="hoa-timeline" data-reveal-group>
				<?php foreach ( $d['roadmap'] as $r ) : ?>
					<li data-reveal><span class="hoa-timeline__dot"><?php echo sol_icon( $r['icon'] ? $r['icon'] : 'circle' ); // phpcs:ignore ?></span><div><small><?php echo esc_html( $r['label'] ); ?></small><h3><?php echo esc_html( $r['title'] ); ?></h3><p><?php echo esc_html( $r['text'] ); ?></p></div></li>
				<?php endforeach; ?>
			</ol>
			<div class="hoa-row-btns">
				<a class="sol-btn sol-btn--dark" href="<?php echo esc_url( home_url( '/get-started/' ) ); ?>">Find My NDIS Package <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
				<a class="sol-btn sol-btn--outline" href="<?php echo esc_url( home_url( '/services/ndis-registration/' ) ); ?>">NDIS Registration Service</a>
			</div>
		</div>
	</div>
</section>

<!-- 7. Pricing -->
<section class="ho-sec hoa-prices">
	<div class="ho-wrap">
		<?php sol_ho_head( 'Transparent pricing', 'Fixed-fee NDIS registration', 'No surprises: you see the price before any paid work begins.', 'ho-head--center' ); ?>
		<?php sol_ho_prices(); ?>
	</div>
</section>

<!-- 8. Easy Compliance -->
<section class="ho-sec hoa-life">
	<div class="ho-wrap">
		<div class="hoa-split-head">
			<?php sol_ho_head( 'Powered by Easy Compliance', 'Compliance that keeps you audit-ready, all year' ); ?>
			<p class="hoa-split-head__aside" data-reveal>Easy Compliance is a service, not just software. Our team works with you at every step, from scattered documents to a clear audit-readiness plan.</p>
		</div>
		<ol class="hoa-life__grid" data-reveal-group>
			<?php foreach ( $d['lifecycle'] as $i => $s ) : ?>
				<li class="hoa-glass" data-reveal data-ho-glow>
					<div class="hoa-life__top"><span class="hoa-why__icon"><?php echo sol_icon( $s['icon'] ? $s['icon'] : 'check' ); // phpcs:ignore ?></span><b>0<?php echo (int) $i + 1; ?></b></div>
					<h3><?php echo esc_html( $s['title'] ); ?></h3>
				</li>
			<?php endforeach; ?>
		</ol>
		<div class="hoa-row-btns">
			<a class="sol-btn sol-btn--primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Get Audit-Ready <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
			<a class="sol-btn sol-btn--light" href="https://sol.easycompliance.org" target="_blank" rel="noopener">Open the platform</a>
		</div>
	</div>
</section>

<!-- 10. FAQ -->
<section class="ho-sec hoa-faq">
	<div class="ho-wrap hoa-faq__grid">
		<div data-reveal>
			<span class="ho-eyebrow">Questions</span>
			<h2 class="ho-title">Answers before you ask</h2>
			<p class="ho-intro">Still unsure? Our team replies within one business day.</p>
			<div class="hoa-contact">
				<a href="<?php echo esc_url( $d['tel'] ); ?>"><?php echo sol_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( $d['phone'] ); ?></a>
				<a href="mailto:<?php echo esc_attr( antispambot( $d['email'] ) ); ?>"><?php echo sol_icon( 'mail' ); // phpcs:ignore ?><?php echo sol_soft_email( $d['email'] ); // phpcs:ignore ?></a>
				<span><?php echo sol_icon( 'map-pin' ); // phpcs:ignore ?><?php echo esc_html( $d['address'] ); ?></span>
			</div>
		</div>
		<div data-reveal><?php sol_ho_faqs(); ?></div>
	</div>
</section>

<!-- 11. Closing -->
<section class="hoa-final">
	<?php echo sol_ho_img( 'home-one-stop-partner', '', 'hoa-final__bg' ); // phpcs:ignore ?>
	<div class="ho-wrap hoa-final__inner" data-reveal>
		<span class="hoa-pill"><span class="hoa-pill__dot"></span>Free 30-minute consultation</span>
		<h2 class="hoa-final__title">Ready to take the next step?</h2>
		<p>Let's discuss your goals and find the right support for your business.</p>
		<div class="hoa-hero__btns">
			<a class="sol-btn sol-btn--primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Start a Conversation <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
			<a class="sol-btn sol-btn--light" href="<?php echo esc_url( $d['whatsapp'] ); ?>" target="_blank" rel="noopener"><?php echo sol_icon( 'message-circle', 'sol-btn__icon' ); // phpcs:ignore ?> WhatsApp Us</a>
		</div>
	</div>
</section>
<?php
sol_ho_switcher();
get_footer();
