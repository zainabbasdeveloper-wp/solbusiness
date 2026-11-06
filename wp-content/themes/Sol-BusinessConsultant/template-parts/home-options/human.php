<?php
/** Home page option D: Human — warm, personal, NDIS-first. */
defined( 'ABSPATH' ) || exit;

$d     = sol_ho_data();
$paths = array(
	array( 'tone' => 'peach', 'icon' => 'sprout', 'title' => "I'm starting a business", 'text' => 'Set up the right structure and get the basics in place from day one.', 'links' => array( array( 'Company & Business Registration', '/services/company-business-registration/' ), array( 'Bookkeeping & BAS', '/services/bookkeeping-bas-lodgement/' ), array( 'Website Development', '/services/website-development/' ) ), 'cta' => array( 'Start your business', '/services/company-business-registration/' ) ),
	array( 'tone' => 'sky', 'icon' => 'heart-handshake', 'title' => 'I want to become a registered NDIS provider', 'text' => 'Policies, evidence and audit preparation, with a team beside you all the way.', 'links' => array( array( 'NDIS Registration', '/services/ndis-registration/' ), array( 'NDIS Packages & Pricing', '/ndis-packages/' ), array( 'NDIS Readiness Quiz', '/readiness-quiz/' ) ), 'cta' => array( 'Find your NDIS package', '/get-started/' ) ),
	array( 'tone' => 'mint', 'icon' => 'trending-up', 'title' => "I'm growing and need more support", 'text' => 'Free up your time with admin help, payroll, marketing and automation.', 'links' => array( array( 'Payroll Services', '/services/payroll-services/' ), array( 'Admin & Back-Office Support', '/services/admin-back-office-support/' ), array( 'Search Engine Optimisation', '/services/search-engine-optimization/' ) ), 'cta' => array( 'See all services', '/services/' ) ),
);
get_header();
?>
<!-- 1. Hero -->
<section class="hod-hero">
	<span class="hod-blob hod-blob--1" aria-hidden="true"></span><span class="hod-blob hod-blob--2" aria-hidden="true"></span>
	<div class="ho-wrap hod-hero__grid">
		<div class="hod-hero__copy">
			<span class="hod-hello"><?php echo sol_icon( 'hand-heart' ); // phpcs:ignore ?> Friendly, local and here to help</span>
			<h1 class="hod-hero__title"><?php echo esc_html( $d['headline'][0] ); ?> <span>Moves You Forward.</span></h1>
			<p class="hod-hero__text"><?php echo esc_html( $d['intro'] ); ?></p>
			<div class="hod-btns">
				<a class="sol-btn sol-btn--primary hod-pill" href="#hod-paths">Find the right help <?php echo sol_icon( 'arrow-down', 'sol-btn__icon' ); // phpcs:ignore ?></a>
				<a class="sol-btn sol-btn--outline hod-pill" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Talk to Our Team</a>
			</div>
			<p class="hod-reply"><span class="hod-reply__dot"></span>We reply within one business day · <a href="<?php echo esc_url( $d['tel'] ); ?>"><?php echo esc_html( $d['phone'] ); ?></a></p>
		</div>
		<div class="hod-hero__art">
			<div class="hod-photo hod-photo--main"><?php echo sol_ho_img( 'ndis-registration-support-worker', 'Support worker assisting an NDIS participant with an exercise session', '', true ); // phpcs:ignore ?></div>
			<div class="hod-photo hod-photo--small"><?php echo sol_ho_img( 'about-consultant-conversation', 'SOL consultant in a friendly conversation with a client' ); // phpcs:ignore ?></div>
			<div class="hod-note hod-note--a"><span><?php echo sol_icon( 'calendar-heart' ); // phpcs:ignore ?></span><div><strong>Free 30-minute call</strong><small>No pressure, no jargon</small></div></div>
			<div class="hod-note hod-note--b"><span><?php echo sol_icon( 'map-pin' ); // phpcs:ignore ?></span><div><strong>Australian-owned</strong><small>Glenroy, Victoria</small></div></div>
		</div>
	</div>
</section>

<!-- 2. Who we help -->
<section class="hod-who">
	<div class="ho-wrap">
		<p class="hod-who__label">We work with</p>
		<ul class="hod-who__list">
			<?php foreach ( $d['audiences'] as $a ) : ?><li><?php echo sol_icon( $a[0] ); // phpcs:ignore ?><?php echo esc_html( $a[1] ); ?></li><?php endforeach; ?>
		</ul>
	</div>
</section>

<!-- 3. Where are you today? -->
<section id="hod-paths" class="ho-sec hod-paths">
	<div class="ho-wrap">
		<?php sol_ho_head( 'Where are you today?', 'Tell us where you are. We will show you the way.', 'Choose the option that sounds most like you.', 'ho-head--center' ); ?>
		<div class="hod-paths__grid" data-reveal-group>
			<?php foreach ( $paths as $p ) : ?>
				<article class="hod-path hod-path--<?php echo esc_attr( $p['tone'] ); ?>" data-reveal>
					<span class="hod-path__icon"><?php echo sol_icon( $p['icon'] ); // phpcs:ignore ?></span>
					<h3><?php echo esc_html( $p['title'] ); ?></h3>
					<p><?php echo esc_html( $p['text'] ); ?></p>
					<ul class="ho-links"><?php foreach ( $p['links'] as $l ) : ?><li><a href="<?php echo esc_url( home_url( $l[1] ) ); ?>"><?php echo esc_html( $l[0] ); ?> <?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></a></li><?php endforeach; ?></ul>
					<a class="hod-path__cta" href="<?php echo esc_url( home_url( $p['cta'][1] ) ); ?>"><?php echo esc_html( $p['cta'][0] ); ?> <?php echo sol_icon( 'arrow-right' ); // phpcs:ignore ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- 5. Journey -->
<section class="ho-sec hod-journey">
	<div class="ho-wrap">
		<?php sol_ho_head( 'How it works', 'Your journey with SOL', '', 'ho-head--center' ); ?>
		<div class="hod-journey__wrap">
			<svg class="hod-journey__path" viewBox="0 0 1000 200" preserveAspectRatio="none" aria-hidden="true"><path d="M20 150 C 200 20, 330 20, 500 110 S 800 200, 980 50" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="2 12" stroke-linecap="round"/></svg>
			<ol class="hod-journey__list" data-reveal-group>
				<?php foreach ( $d['steps'] as $i => $s ) : ?>
					<li data-reveal><span class="hod-journey__bubble"><?php echo sol_icon( $s[0] ); // phpcs:ignore ?><b><?php echo (int) $i + 1; ?></b></span><h3><?php echo esc_html( $s[1] ); ?></h3><p><?php echo esc_html( $s[2] ); ?></p></li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</section>

<!-- 6. NDIS roadmap -->
<section id="ndis" class="ho-sec hod-road">
	<div class="ho-wrap">
		<div class="hod-road__head">
			<?php sol_ho_head( 'For NDIS providers', 'Registration, one step at a time', 'For sole traders, small providers and allied health professionals preparing for the NDIS Commission audit.' ); ?>
			<a class="sol-btn sol-btn--dark hod-pill" href="<?php echo esc_url( home_url( '/get-started/' ) ); ?>">Find My NDIS Package <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
		</div>
		<ol class="hod-road__grid" data-reveal-group>
			<?php foreach ( $d['roadmap'] as $r ) : ?>
				<li data-reveal><span class="hod-road__icon"><?php echo sol_icon( $r['icon'] ? $r['icon'] : 'circle' ); // phpcs:ignore ?></span><small><?php echo esc_html( $r['label'] ); ?></small><h3><?php echo esc_html( $r['title'] ); ?></h3><p><?php echo esc_html( $r['text'] ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<!-- 7. Talk to a person -->
<section class="ho-sec hod-person">
	<div class="ho-wrap hod-person__grid">
		<div class="hod-person__photo" data-reveal><?php echo sol_ho_img( 'home-one-stop-partner', 'Client and consultant shaking hands after agreeing a plan' ); // phpcs:ignore ?></div>
		<div data-reveal>
			<span class="ho-eyebrow">Our commitment</span>
			<h2 class="ho-title">Real people. Plain English. No surprises.</h2>
			<ul class="hod-promise">
				<?php foreach ( $d['why'] as $w ) : ?><li><span><?php echo sol_icon( $w[0] ); // phpcs:ignore ?></span><div><strong><?php echo esc_html( $w[1] ); ?></strong><p><?php echo esc_html( $w[2] ); ?></p></div></li><?php endforeach; ?>
			</ul>
			<div class="hod-contact">
				<a href="<?php echo esc_url( $d['tel'] ); ?>"><?php echo sol_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( $d['phone'] ); ?></a>
				<a href="mailto:<?php echo esc_attr( antispambot( $d['email'] ) ); ?>"><?php echo sol_icon( 'mail' ); // phpcs:ignore ?><?php echo sol_soft_email( $d['email'] ); // phpcs:ignore ?></a>
				<a href="<?php echo esc_url( $d['whatsapp'] ); ?>" target="_blank" rel="noopener"><?php echo sol_icon( 'message-circle' ); // phpcs:ignore ?>WhatsApp</a>
			</div>
		</div>
	</div>
</section>

<!-- 8. Pricing -->
<section class="ho-sec hod-prices">
	<div class="ho-wrap">
		<?php sol_ho_head( 'NDIS registration packages', 'Clear, fixed prices', 'Every package starts with a free call, so you know exactly what you are paying for.', 'ho-head--center' ); ?>
		<?php sol_ho_prices(); ?>
	</div>
</section>

<!-- 10. FAQ -->
<section class="ho-sec hod-faq">
	<div class="ho-wrap ho-wrap--narrow">
		<?php sol_ho_head( 'Questions', 'You might be wondering', '', 'ho-head--center' ); ?>
		<div data-reveal><?php sol_ho_faqs(); ?></div>
	</div>
</section>

<!-- 11. Closing with consultation form -->
<section class="ho-sec hod-final" id="hod-book">
	<div class="ho-wrap hod-final__grid">
		<div class="hod-final__copy">
			<span class="ho-eyebrow">Free consultation</span>
			<h2 class="ho-title">Ready to take the next step?</h2>
			<p>Let's discuss your goals and find the right support for your business. Pick a time that suits you and we'll be in touch.</p>
			<ul class="hod-final__ticks">
				<li><?php echo sol_icon( 'check' ); // phpcs:ignore ?>Free 30-minute call</li>
				<li><?php echo sol_icon( 'check' ); // phpcs:ignore ?>Reply within one business day</li>
				<li><?php echo sol_icon( 'check' ); // phpcs:ignore ?>No obligation</li>
			</ul>
		</div>
		<div class="hod-final__form"><?php sol_render_form( 'consultation', array( 'service' => 'Home page consultation request' ) ); ?></div>
	</div>
</section>
<?php
sol_ho_switcher();
get_footer();
