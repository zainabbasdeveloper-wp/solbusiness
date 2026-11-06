<?php
/** Home page options: overview for reviewers (/home-options/). */
defined( 'ABSPATH' ) || exit;

$opts  = sol_ho_options();
$extra = array(
	'executive' => array( 'img' => 'home-consultants-team', 'best' => 'Looking established and premium; strong first impression for larger clients', 'mood' => 'Bold, dark, cinematic', 'interact' => 'Hover-reveal service cards, moving platform strip, counting numbers', 'points' => array( 'Full-photo hero with glass panels', 'Service cards that reveal their services', 'Compliance lifecycle on a dark band', 'Two-column FAQ' ) ),
	'editorial' => array( 'img' => 'about-consultant-conversation', 'best' => 'Calm, trustworthy, easy reading; feels like a consultancy, not software', 'mood' => 'Light, airy, typographic', 'interact' => 'Rotating audience word, hover photos on service rows, swipeable lifecycle', 'points' => array( 'Big headline with a three-photo collage', 'Numbered service stories', 'Large commitment statement', 'Swipeable compliance cards' ) ),
	'bento'     => array( 'img' => 'website-development-wireframes', 'best' => 'Showing breadth and modern tools; visitors explore and compare', 'mood' => 'Modern, colourful, structured', 'interact' => 'Service explorer tabs, NDIS roadmap tabs, quiz, enquiry form on the page', 'points' => array( 'Tile hero with live-style status card', 'Service explorer with tabs', 'Client portal preview', 'Quiz and enquiry form on the page' ) ),
	'human'     => array( 'img' => 'ndis-registration-support-worker', 'best' => 'NDIS providers and small owners who want a friendly, personal partner', 'mood' => 'Warm, soft, personal', 'interact' => '"Where are you today?" pathways, journey path, consultation form on the page', 'points' => array( '"Where are you today?" pathway cards', 'Illustrated three-step journey', 'Talk-to-a-person contact card', 'Consultation form on the page' ) ),
);

get_header();
?>
<section class="hoi-hero">
	<div class="ho-wrap">
		<span class="ho-eyebrow">Home page redesign · for review</span>
		<h1 class="hoi-hero__title">Four directions for the new SOL home page</h1>
		<p class="hoi-hero__text">Each option is a complete, working home page built with SOL's real services, pricing, NDIS roadmap and contact details. Open each one, scroll to the end, and try it on your phone. Use the bar at the bottom of every page to switch between options.</p>
		<ul class="hoi-hero__notes">
			<li><?php echo sol_icon( 'eye-off' ); // phpcs:ignore ?> Private preview: hidden from Google and not in the site menu</li>
			<li><?php echo sol_icon( 'smartphone' ); // phpcs:ignore ?> Every option works on phone, tablet and desktop</li>
			<li><?php echo sol_icon( 'mouse-pointer-click' ); // phpcs:ignore ?> Buttons, pricing tabs, quiz and forms all work</li>
		</ul>
	</div>
</section>

<section class="hoi-options">
	<div class="ho-wrap">
		<div class="hoi-grid">
			<?php foreach ( $opts as $slug => $o ) : ?>
				<?php $x = $extra[ $slug ]; ?>
				<article class="hoi-card hoi-card--<?php echo esc_attr( $slug ); ?>">
					<a class="hoi-card__preview" href="<?php echo esc_url( home_url( '/home-options/' . $slug . '/' ) ); ?>" aria-label="<?php echo esc_attr( 'Open option ' . $o['letter'] . ': ' . $o['name'] ); ?>">
						<?php echo sol_ho_img( $x['img'], '', 'hoi-card__img' ); // phpcs:ignore ?>
						<span class="hoi-card__mock" aria-hidden="true"><span></span><span></span><span></span></span>
						<span class="hoi-card__letter"><?php echo esc_html( $o['letter'] ); ?></span>
					</a>
					<div class="hoi-card__body">
						<h2 class="hoi-card__name">Option <?php echo esc_html( $o['letter'] ); ?>: <?php echo esc_html( $o['name'] ); ?></h2>
						<p class="hoi-card__idea"><?php echo esc_html( $o['idea'] ); ?></p>
						<div class="hoi-card__palette" aria-label="Colour palette">
							<?php foreach ( $o['palette'] as $hex ) : ?><span style="background:<?php echo esc_attr( $hex ); ?>" title="<?php echo esc_attr( $hex ); ?>"></span><?php endforeach; ?>
						</div>
						<ul class="hoi-card__points"><?php foreach ( $x['points'] as $p ) : ?><li><?php echo sol_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
						<a class="sol-btn sol-btn--dark hoi-card__btn" href="<?php echo esc_url( home_url( '/home-options/' . $slug . '/' ) ); ?>">Open option <?php echo esc_html( $o['letter'] ); ?> <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="hoi-compare">
	<div class="ho-wrap">
		<?php sol_ho_head( 'Side by side', 'How the options compare' ); ?>
		<div class="hoi-table-wrap">
			<table class="hoi-table">
				<thead><tr><th scope="col"></th><?php foreach ( $opts as $o ) : ?><th scope="col"><b><?php echo esc_html( $o['letter'] ); ?></b> <?php echo esc_html( $o['name'] ); ?></th><?php endforeach; ?></tr></thead>
				<tbody>
					<tr><th scope="row">Mood</th><?php foreach ( $extra as $x ) : ?><td><?php echo esc_html( $x['mood'] ); ?></td><?php endforeach; ?></tr>
					<tr><th scope="row">Best for</th><?php foreach ( $extra as $x ) : ?><td><?php echo esc_html( $x['best'] ); ?></td><?php endforeach; ?></tr>
					<tr><th scope="row">Interactive parts</th><?php foreach ( $extra as $x ) : ?><td><?php echo esc_html( $x['interact'] ); ?></td><?php endforeach; ?></tr>
				</tbody>
			</table>
		</div>
		<p class="hoi-foot">Every option uses the same content: SOL's three service areas, why SOL, how it works, the NDIS roadmap, pricing, Easy Compliance, client portal, FAQs and contact options. Only the design and layout change. Parts can be mixed, for example the hero from one option with the services from another.</p>
	</div>
</section>
<?php
sol_ho_switcher();
get_footer();
