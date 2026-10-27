<?php
/** Layout: quiz — scored assessment or service recommender (rendered by assets/js/sol.js). */
defined( 'ABSPATH' ) || exit;

$questions = array();
foreach ( sol_rows( sol_get( $s, 'questions', array() ) ) as $q ) {
	$opts = array();
	foreach ( sol_rows( sol_get( $q, 'options', array() ) ) as $o ) {
		$opts[] = array( 'label' => (string) sol_get( $o, 'label' ), 'score' => (float) sol_get( $o, 'score', 0 ), 'value' => (string) sol_get( $o, 'value' ) );
	}
	$questions[] = array( 'group' => (string) sol_get( $q, 'group' ), 'question' => (string) sol_get( $q, 'question' ), 'options' => $opts );
}
$results = array();
foreach ( sol_rows( sol_get( $s, 'results', array() ) ) as $r ) {
	$l         = sol_link( sol_get( $r, 'link' ) );
	$results[] = array(
		'key'         => (string) sol_get( $r, 'key' ),
		'min'         => (float) sol_get( $r, 'min_percent', 0 ),
		'title'       => (string) sol_get( $r, 'title' ),
		'subtitle'    => (string) sol_get( $r, 'subtitle' ),
		'description' => (string) sol_get( $r, 'description' ),
		'link'        => $l,
	);
}
if ( ! $questions ) {
	return;
}
$config = array(
	'mode'      => sol_get( $s, 'mode', 'score' ),
	'questions' => $questions,
	'results'   => $results,
	'lead'      => ! empty( $s['lead_capture'] ),
	'title'     => wp_strip_all_tags( sol_get( $s, 'heading' ) ),
);
$bullets = sol_list( sol_get( $s, 'bullets', array() ) );

sol_section_open( $s, 'quiz' );
?>
<div class="sol-container sol-container--mid">
	<?php sol_section_head( $s ); ?>
	<div class="sol-quiz" data-sol-quiz>
		<script type="application/json" data-sol-quiz-config><?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ); ?></script>
		<div class="sol-quiz__start" data-step="start">
			<?php if ( $bullets ) : ?><ul class="sol-pills sol-pills--center"><?php foreach ( $bullets as $b ) : ?><li><?php echo esc_html( $b ); ?></li><?php endforeach; ?></ul><?php endif; ?>
			<button type="button" class="sol-btn sol-btn--primary" data-sol-quiz-start><?php echo esc_html( sol_get( $s, 'start_label', 'Start' ) ); ?> <?php echo sol_icon( 'arrow-right', 'sol-btn__icon' ); // phpcs:ignore ?></button>
		</div>
		<div class="sol-quiz__question" data-step="question" hidden>
			<div class="sol-quiz__progress"><span data-sol-quiz-count></span><span class="sol-quiz__bar"><span data-sol-quiz-bar></span></span></div>
			<p class="sol-quiz__group" data-sol-quiz-group></p>
			<h3 class="sol-quiz__q" data-sol-quiz-q tabindex="-1"></h3>
			<div class="sol-quiz__options" data-sol-quiz-options></div>
			<button type="button" class="sol-link-btn" data-sol-quiz-back><?php echo sol_icon( 'arrow-left' ); // phpcs:ignore ?> Back</button>
		</div>
		<?php if ( $config['lead'] ) : ?>
			<div class="sol-quiz__lead" data-step="lead" hidden>
				<h3>Your results are ready!</h3>
				<p>Enter your details to view your personalised report.</p>
				<?php
				sol_render_form( 'quiz', array(
					'consent_text' => sol_get( $s, 'consent_text' ),
					'submit'       => 'View My Results',
					'privacy_text' => 'Your details are kept confidential and never sold.',
				) );
				?>
				<button type="button" class="sol-link-btn" data-sol-quiz-skip>Skip and show results</button>
			</div>
		<?php endif; ?>
		<div class="sol-quiz__result" data-step="result" hidden aria-live="polite">
			<p class="sol-eyebrow" data-sol-result-sub></p>
			<h3 class="sol-quiz__result-title" data-sol-result-title tabindex="-1"></h3>
			<p data-sol-result-desc></p>
			<p class="sol-quiz__score" data-sol-result-score></p>
			<div class="sol-quiz__breakdown" data-sol-result-breakdown></div>
			<div class="sol-buttons" data-sol-result-cta></div>
			<?php if ( sol_get( $s, 'note' ) ) : ?><p class="sol-note"><?php echo esc_html( $s['note'] ); ?></p><?php endif; ?>
			<button type="button" class="sol-link-btn" data-sol-quiz-restart>Start again</button>
		</div>
	</div>
</div>
</section>
