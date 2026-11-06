<?php
/** Home page option C: Bento — modern tiles, interactive explorer, portal preview, quiz and form on the page. */
defined( 'ABSPATH' ) || exit;

$d = sol_ho_data();
get_header();
?>
<!-- 1. Hero with tile mosaic -->
<section class="hoc-hero">
	<div class="ho-wrap hoc-hero__grid">
		<div class="hoc-hero__copy">
			<span class="hoc-tag"><b>Free</b> 30-minute consultation</span>
			<h1 class="hoc-hero__title"><?php echo esc_html( $d['headline'][0] ); ?> <span>Moves You Forward.</span></h1>
			<p class="hoc-hero__text"><?php echo esc_html( $d['intro'] ); ?></p>
			<div class="hoc-btns">
				<a class="sol-btn sol-btn--dark" href="#hoc-explore">Explore Our Services <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
				<a class="sol-btn sol-btn--outline" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Talk to Our Team</a>
			</div>
			<ul class="hoc-badges"><?php foreach ( $d['badges'] as $b ) : ?><li><?php echo sol_icon( $b[0] ); // phpcs:ignore ?><?php echo esc_html( $b[1] ); ?></li><?php endforeach; ?></ul>
		</div>
		<div class="hoc-mosaic" aria-hidden="true">
			<div class="hoc-tile hoc-tile--photo"><?php echo sol_ho_img( 'home-consultants-team', '', '', true ); // phpcs:ignore ?></div>
			<div class="hoc-tile hoc-tile--amber"><span class="hoc-tile__big"><span data-sol-count="12">12</span>+</span><span>Business services under one roof</span></div>
			<div class="hoc-tile hoc-tile--status">
				<p class="hoc-status__head"><span class="hoc-live"></span>NDIS registration pathway</p>
				<ul>
					<?php foreach ( array_slice( $d['roadmap'], 0, 4 ) as $i => $r ) : ?>
						<li class="<?php echo $i < 3 ? 'is-done' : ''; ?>"><?php echo sol_icon( $i < 3 ? 'check' : 'loader' ); // phpcs:ignore ?><?php echo esc_html( $r['title'] ); ?></li>
					<?php endforeach; ?>
				</ul>
				<small>Illustration of a typical pathway</small>
			</div>
			<div class="hoc-tile hoc-tile--dark"><span class="hoc-tile__big"><span data-sol-count="24">24</span>h</span><span>Response target</span></div>
		</div>
	</div>
</section>

<!-- 3. Service explorer -->
<section id="hoc-explore" class="ho-sec hoc-explore" data-ho-tabs>
	<div class="ho-wrap">
		<?php sol_ho_head( 'Service explorer', 'What do you need help with?', 'Pick an area to see the services inside it.', 'ho-head--center' ); ?>
		<div class="hoc-tabs" role="tablist" aria-label="Service areas">
			<?php foreach ( $d['areas'] as $i => $a ) : ?>
				<button type="button" role="tab" class="hoc-tabs__btn" data-ho-tab="<?php echo esc_attr( $a['key'] ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>" id="hoc-tab-<?php echo esc_attr( $a['key'] ); ?>" aria-controls="hoc-panel-<?php echo esc_attr( $a['key'] ); ?>">
					<span class="hoc-tabs__icon"><?php echo sol_icon( $a['icon'] ); // phpcs:ignore ?></span><?php echo esc_html( $a['title'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>
		<?php foreach ( $d['areas'] as $i => $a ) : ?>
			<div class="hoc-panel" role="tabpanel" id="hoc-panel-<?php echo esc_attr( $a['key'] ); ?>" aria-labelledby="hoc-tab-<?php echo esc_attr( $a['key'] ); ?>" data-ho-panel="<?php echo esc_attr( $a['key'] ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
				<div class="hoc-panel__media"><?php echo sol_ho_img( $a['img'], $a['alt'] ); // phpcs:ignore ?><span class="hoc-panel__count"><?php echo esc_html( count( $a['links'] ) ); ?> services</span></div>
				<div class="hoc-panel__body">
					<h3><?php echo esc_html( $a['title'] ); ?></h3>
					<p><?php echo esc_html( $a['text'] ); ?></p>
					<ul class="hoc-panel__links">
						<?php foreach ( $a['links'] as $l ) : ?><li><a href="<?php echo esc_url( home_url( $l[1] ) ); ?>"><span><?php echo esc_html( $l[0] ); ?></span><?php echo sol_icon( 'arrow-up-right' ); // phpcs:ignore ?></a></li><?php endforeach; ?>
					</ul>
					<a class="sol-btn sol-btn--dark" href="<?php echo esc_url( home_url( $a['url'] ) ); ?>">Explore <?php echo esc_html( $a['short'] ); ?> <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<!-- 4. Bento: why SOL -->
<section class="ho-sec hoc-bento">
	<div class="ho-wrap">
		<div class="hoc-bento__grid" data-reveal-group>
			<div class="hoc-b hoc-b--lead" data-reveal>
				<span class="ho-eyebrow">Why SOL</span>
				<h2 class="ho-title">Clear advice.<br>No guesswork.</h2>
				<p>You always know what is happening, why, and what it costs.</p>
				<a class="sol-btn sol-btn--primary" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About SOL <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
			</div>
			<?php foreach ( $d['why'] as $i => $w ) : ?>
				<div class="hoc-b hoc-b--why hoc-b--w<?php echo (int) $i + 1; ?>" data-reveal><span class="hoc-icon"><?php echo sol_icon( $w[0] ); // phpcs:ignore ?></span><h3><?php echo esc_html( $w[1] ); ?></h3><p><?php echo esc_html( $w[2] ); ?></p></div>
			<?php endforeach; ?>
			<div class="hoc-b hoc-b--photo" data-reveal><?php echo sol_ho_img( 'home-one-stop-partner', 'Client and consultant shaking hands after agreeing a plan' ); // phpcs:ignore ?></div>
			<div class="hoc-b hoc-b--stat" data-reveal><span class="hoc-tile__big"><span data-sol-count="2">2</span></span><span>Dedicated portals: one for clients, one for students</span></div>
		</div>
	</div>
</section>

<!-- 5. How it works -->
<section class="ho-sec hoc-steps">
	<div class="ho-wrap">
		<?php sol_ho_head( 'How it works', 'Three steps. No surprises.', '', 'ho-head--center' ); ?>
		<ol class="hoc-steps__list" data-reveal-group>
			<?php foreach ( $d['steps'] as $i => $s ) : ?>
				<li data-reveal><span class="hoc-steps__num"><?php echo (int) $i + 1; ?></span><span class="hoc-icon"><?php echo sol_icon( $s[0] ); // phpcs:ignore ?></span><h3><?php echo esc_html( $s[1] ); ?></h3><p><?php echo esc_html( $s[2] ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<!-- 6. NDIS roadmap tabs -->
<section id="ndis" class="ho-sec hoc-road" data-ho-tabs>
	<div class="ho-wrap">
		<div class="hoc-road__grid">
			<div>
				<?php sol_ho_head( 'NDIS registration', 'Your registration roadmap', 'Tap each stage to see what happens and when.' ); ?>
				<div class="hoc-road__tabs" role="tablist" aria-label="NDIS registration stages">
					<?php foreach ( $d['roadmap'] as $i => $r ) : ?>
						<button type="button" role="tab" data-ho-tab="r<?php echo (int) $i; ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>"><small><?php echo esc_html( $r['label'] ); ?></small><?php echo esc_html( $r['title'] ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="hoc-road__panels">
				<?php foreach ( $d['roadmap'] as $i => $r ) : ?>
					<div class="hoc-road__panel" role="tabpanel" data-ho-panel="r<?php echo (int) $i; ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
						<span class="hoc-road__icon"><?php echo sol_icon( $r['icon'] ? $r['icon'] : 'circle' ); // phpcs:ignore ?></span>
						<small><?php echo esc_html( 'Stage ' . ( $i + 1 ) . ' of ' . count( $d['roadmap'] ) . ' · ' . $r['label'] ); ?></small>
						<h3><?php echo esc_html( $r['title'] ); ?></h3>
						<p><?php echo esc_html( $r['text'] ); ?></p>
						<span class="hoc-road__bar"><span style="width:<?php echo esc_attr( round( ( $i + 1 ) / max( 1, count( $d['roadmap'] ) ) * 100 ) ); ?>%"></span></span>
						<div class="hoc-btns">
							<a class="sol-btn sol-btn--primary" href="<?php echo esc_url( home_url( '/get-started/' ) ); ?>">Find My NDIS Package <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
							<a class="sol-btn sol-btn--light" href="<?php echo esc_url( home_url( '/services/ndis-registration/' ) ); ?>">NDIS Registration</a>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>

<!-- 7. Client portal preview -->
<section class="ho-sec hoc-portal">
	<div class="ho-wrap hoc-portal__grid">
		<div>
			<?php sol_ho_head( 'Client portal', 'Your project, visible at any time', 'After a service payment you get your own secure portal: progress, documents, requests and support in one place.' ); ?>
			<a class="sol-btn sol-btn--outline" href="<?php echo esc_url( home_url( '/after-payment/' ) ); ?>">How the portal works</a>
		</div>
		<figure class="hoc-window" data-reveal>
			<div class="hoc-window__bar"><span></span><span></span><span></span><b>Client portal</b></div>
			<div class="hoc-window__body">
				<?php
				$chips = array( 'Live', 'Upload', 'Open', 'Ready' );
				foreach ( $d['portal'] as $i => $p ) :
					?>
					<div class="hoc-window__row"><span class="hoc-icon"><?php echo sol_icon( $p['icon'] ? $p['icon'] : 'check' ); // phpcs:ignore ?></span><div><strong><?php echo esc_html( $p['title'] ); ?></strong><small><?php echo esc_html( $p['text'] ); ?></small></div><em><?php echo esc_html( $chips[ $i % 4 ] ); ?></em></div>
				<?php endforeach; ?>
			</div>
			<figcaption>Illustration of the client portal features</figcaption>
		</figure>
	</div>
</section>

<!-- 8. Quiz -->
<div class="hoc-quiz"><?php sol_ho_quiz( array( 'eyebrow' => 'Not sure where to start?', 'heading' => 'Find the right service in three questions', 'align' => 'center', 'background' => 'white' ) ); ?></div>

<!-- 9. Pricing -->
<section class="ho-sec hoc-prices">
	<div class="ho-wrap">
		<?php sol_ho_head( 'Pricing', 'Fixed-fee NDIS registration', 'Pick the package that matches your registration groups. Not sure? The package finder will tell you.', 'ho-head--center' ); ?>
		<?php sol_ho_prices(); ?>
	</div>
</section>

<!-- 10. FAQ -->
<section class="ho-sec hoc-faq">
	<div class="ho-wrap">
		<?php sol_ho_head( 'Questions', 'Good questions, straight answers', '', 'ho-head--center' ); ?>
		<div data-reveal><?php sol_ho_faqs( 'hoc-faq__grid' ); ?></div>
	</div>
</section>

<!-- 11. Closing with form -->
<section class="ho-sec hoc-final" id="hoc-contact">
	<div class="ho-wrap">
		<div class="hoc-final__card">
			<div class="hoc-final__copy">
				<span class="ho-eyebrow">Free consultation</span>
				<h2 class="ho-title">Ready to take the next step?</h2>
				<p>Let's discuss your goals and find the right support for your business. We reply within one business day.</p>
				<ul class="hoc-final__list">
					<li><?php echo sol_icon( 'phone' ); // phpcs:ignore ?><a href="<?php echo esc_url( $d['tel'] ); ?>"><?php echo esc_html( $d['phone'] ); ?></a></li>
					<li><?php echo sol_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( antispambot( $d['email'] ) ); ?>"><?php echo sol_soft_email( $d['email'] ); // phpcs:ignore ?></a></li>
					<li><?php echo sol_icon( 'message-circle' ); // phpcs:ignore ?><a href="<?php echo esc_url( $d['whatsapp'] ); ?>" target="_blank" rel="noopener">WhatsApp us</a></li>
				</ul>
			</div>
			<div class="hoc-final__form"><?php sol_render_form( 'contact', array( 'service' => 'Home page enquiry', 'submit' => 'Start a Conversation' ) ); ?></div>
		</div>
	</div>
</section>
<?php
sol_ho_switcher();
get_footer();
