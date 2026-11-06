<?php
/** Home page option B: Editorial — light, spacious, magazine-like. */
defined( 'ABSPATH' ) || exit;

$d     = sol_ho_data();
$words = array_map( function ( $a ) {
	return $a[1];
}, $d['audiences'] );
get_header();
?>
<!-- 1. Hero with photo collage -->
<section class="hob-hero">
	<div class="ho-wrap">
		<div class="hob-hero__head">
			<span class="ho-eyebrow">Australian business consultants · Glenroy, Victoria</span>
			<h1 class="hob-hero__title"><?php echo esc_html( $d['headline'][0] ); ?><br><em>Moves You Forward.</em></h1>
			<div class="hob-hero__row">
				<p class="hob-hero__text"><?php echo esc_html( $d['intro'] ); ?></p>
				<div class="hob-hero__btns">
					<a class="sol-btn sol-btn--dark" href="#hob-services">Explore Our Services <?php echo sol_icon( 'arrow-down', 'sol-btn__icon' ); // phpcs:ignore ?></a>
					<a class="sol-btn sol-btn--outline" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Talk to Our Team</a>
				</div>
			</div>
		</div>
		<div class="hob-collage">
			<?php foreach ( $d['areas'] as $i => $a ) : ?>
				<a class="hob-collage__item hob-collage__item--<?php echo (int) $i + 1; ?>" href="<?php echo esc_url( home_url( $a['url'] ) ); ?>">
					<?php echo sol_ho_img( $a['img'], $a['alt'], '', 0 === $i ); // phpcs:ignore ?>
					<span class="hob-collage__cap"><b>0<?php echo (int) $i + 1; ?></b><?php echo esc_html( $a['title'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<dl class="hob-stats">
			<?php foreach ( $d['stats'] as $s ) : ?>
				<div class="ho-stat"><dt><span data-sol-count="<?php echo esc_attr( $s[0] ); ?>"><?php echo esc_html( $s[0] ); ?></span><?php echo esc_html( $s[1] ); ?></dt><dd><?php echo esc_html( $s[2] ); ?></dd></div>
			<?php endforeach; ?>
		</dl>
	</div>
</section>

<!-- 2. Rotating audience statement -->
<section class="hob-say">
	<div class="ho-wrap">
		<p class="hob-say__text" data-reveal>We help <span class="hob-say__word" data-ho-rotate="<?php echo esc_attr( wp_json_encode( $words ) ); ?>"><?php echo esc_html( $words[0] ); ?></span> get set up, stay compliant and grow with confidence.</p>
	</div>
</section>

<!-- 3. Numbered service stories -->
<section id="hob-services" class="ho-sec hob-services">
	<div class="ho-wrap">
		<?php sol_ho_head( 'What we do', 'Three areas of expertise', 'One Australian team, scoped clearly and priced before any paid work begins.' ); ?>
		<div class="hob-rows">
			<?php foreach ( $d['areas'] as $i => $a ) : ?>
				<article class="hob-row" data-reveal>
					<span class="hob-row__num">0<?php echo (int) $i + 1; ?></span>
					<div class="hob-row__main">
						<h3 class="hob-row__title"><a href="<?php echo esc_url( home_url( $a['url'] ) ); ?>"><?php echo esc_html( $a['title'] ); ?></a></h3>
						<p class="hob-row__text"><?php echo esc_html( $a['text'] ); ?></p>
						<ul class="hob-chips">
							<?php foreach ( $a['links'] as $l ) : ?><li><a href="<?php echo esc_url( home_url( $l[1] ) ); ?>"><?php echo esc_html( $l[0] ); ?></a></li><?php endforeach; ?>
						</ul>
					</div>
					<a class="hob-row__media" href="<?php echo esc_url( home_url( $a['url'] ) ); ?>" aria-label="<?php echo esc_attr( 'Explore ' . $a['title'] ); ?>">
						<?php echo sol_ho_img( $a['img'], '' ); // phpcs:ignore ?>
						<span class="hob-row__go"><?php echo sol_icon( 'arrow-up-right' ); // phpcs:ignore ?></span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- 4. Commitment statement -->
<section class="ho-sec hob-quote">
	<div class="ho-wrap ho-wrap--narrow">
		<span class="ho-eyebrow" data-reveal>Our commitment</span>
		<p class="hob-quote__text" data-reveal>You'll always know <mark>what we're doing</mark>, <mark>why it matters</mark>, and <mark>what it costs</mark>.</p>
		<p class="hob-quote__sub" data-reveal>Plain-English advice, a written scope and price before any paid work, and a reply within one business day.</p>
	</div>
</section>

<!-- 5. How it works -->
<section class="ho-sec hob-steps">
	<div class="ho-wrap">
		<?php sol_ho_head( 'How it works', 'Simple from the very first call' ); ?>
		<ol class="hob-steps__list" data-reveal-group>
			<?php foreach ( $d['steps'] as $i => $s ) : ?>
				<li data-reveal><span class="hob-steps__num">0<?php echo (int) $i + 1; ?></span><h3><?php echo esc_html( $s[1] ); ?></h3><p><?php echo esc_html( $s[2] ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<!-- 6. Feature story: NDIS -->
<section id="ndis" class="ho-sec hob-story">
	<div class="ho-wrap hob-story__grid">
		<figure class="hob-story__media" data-reveal>
			<?php echo str_replace( 'loading="lazy"', 'loading="eager"', sol_ho_img( 'ndis-roadmap-support-at-home', 'Support worker helping an NDIS participant at home' ) ); // phpcs:ignore -- pinned column: lazy loading can skip it ?>
			<figcaption>NDIS registration · Featured service</figcaption>
		</figure>
		<div class="hob-story__copy">
			<?php sol_ho_head( 'Featured', 'NDIS registration, made manageable', 'Registration means policies, evidence and an audit. We guide you through each step and help your team feel ready on audit day.' ); ?>
			<ol class="hob-road" data-reveal-group>
				<?php foreach ( $d['roadmap'] as $r ) : ?>
					<li data-reveal><small><?php echo esc_html( $r['label'] ); ?></small><div><h3><?php echo esc_html( $r['title'] ); ?></h3><p><?php echo esc_html( $r['text'] ); ?></p></div></li>
				<?php endforeach; ?>
			</ol>
			<div class="hob-btns">
				<a class="sol-btn sol-btn--dark" href="<?php echo esc_url( home_url( '/services/ndis-registration/' ) ); ?>">Explore NDIS Registration <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
				<a class="sol-btn sol-btn--text" href="<?php echo esc_url( home_url( '/readiness-quiz/' ) ); ?>">Take the readiness quiz <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
			</div>
		</div>
	</div>
</section>

<!-- 7. Compliance lifecycle (swipe) -->
<section class="ho-sec hob-life" data-ho-scroller>
	<div class="ho-wrap">
		<div class="hob-life__head">
			<?php sol_ho_head( 'Powered by Easy Compliance', 'The compliance lifecycle', 'From first call to a clear audit-readiness plan, and support every day after.' ); ?>
			<div class="ho-arrows"><button type="button" data-ho-prev aria-label="Previous"><?php echo sol_icon( 'arrow-left' ); // phpcs:ignore ?></button><button type="button" data-ho-next aria-label="Next"><?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></button></div>
		</div>
		<ol class="hob-life__track" data-ho-track>
			<?php foreach ( $d['lifecycle'] as $i => $s ) : ?>
				<li class="hob-life__card"><b>0<?php echo (int) $i + 1; ?></b><span class="hob-icon"><?php echo sol_icon( $s['icon'] ? $s['icon'] : 'check' ); // phpcs:ignore ?></span><h3><?php echo esc_html( $s['title'] ); ?></h3><p><?php echo esc_html( $s['text'] ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<!-- 8. Why SOL -->
<section class="ho-sec hob-why">
	<div class="ho-wrap">
		<?php sol_ho_head( 'Why SOL', 'Clear advice. No guesswork.', '', 'ho-head--center' ); ?>
		<div class="hob-why__grid" data-reveal-group>
			<?php foreach ( $d['why'] as $w ) : ?>
				<div class="hob-why__item" data-reveal><span class="hob-icon"><?php echo sol_icon( $w[0] ); // phpcs:ignore ?></span><h3><?php echo esc_html( $w[1] ); ?></h3><p><?php echo esc_html( $w[2] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- 9. Pricing -->
<div class="hob-pricing"><?php sol_ho_pricing(); ?></div>

<!-- 10. Client portal -->
<section class="ho-sec hob-portal">
	<div class="ho-wrap hob-portal__grid">
		<div>
			<?php sol_ho_head( 'Client portal', 'Everything in one secure place', 'After a service payment, follow progress, share documents and get help from your own portal.' ); ?>
			<a class="sol-btn sol-btn--text" href="<?php echo esc_url( home_url( '/after-payment/' ) ); ?>">How the portal works <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
		</div>
		<ul class="hob-portal__list" data-reveal-group>
			<?php foreach ( $d['portal'] as $p ) : ?>
				<li data-reveal><span class="hob-icon"><?php echo sol_icon( $p['icon'] ? $p['icon'] : 'check' ); // phpcs:ignore ?></span><div><h3><?php echo esc_html( $p['title'] ); ?></h3><p><?php echo esc_html( $p['text'] ); ?></p></div></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<!-- 11. FAQ -->
<section class="ho-sec hob-faq">
	<div class="ho-wrap ho-wrap--narrow">
		<?php sol_ho_head( 'Questions', 'Common questions', '', 'ho-head--center' ); ?>
		<div data-reveal><?php sol_ho_faqs(); ?></div>
	</div>
</section>

<!-- 12. Closing -->
<section class="hob-final">
	<div class="ho-wrap">
		<span class="ho-eyebrow">Ready to take the next step?</span>
		<h2 class="hob-final__title" data-reveal>Let's talk.</h2>
		<div class="hob-final__grid" data-reveal>
			<a class="hob-final__link" href="<?php echo esc_url( $d['tel'] ); ?>"><small>Call</small><?php echo esc_html( $d['phone'] ); ?></a>
			<a class="hob-final__link" href="mailto:<?php echo esc_attr( antispambot( $d['email'] ) ); ?>"><small>Email</small><?php echo sol_soft_email( $d['email'] ); // phpcs:ignore ?></a>
			<a class="sol-btn sol-btn--primary hob-final__btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Start a Conversation <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
		</div>
	</div>
</section>
<?php
sol_ho_switcher();
get_footer();
