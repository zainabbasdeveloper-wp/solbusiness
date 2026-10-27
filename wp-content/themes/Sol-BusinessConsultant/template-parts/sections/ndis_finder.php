<?php
/** Layout: ndis_finder — entity + groups → package with payment options (JS in sol.js). */
defined( 'ABSPATH' ) || exit;

$entities = sol_rows( sol_get( $s, 'entity_types', array() ) );
$states   = sol_list( sol_get( $s, 'states', array() ) );
$groups   = sol_rows( sol_get( $s, 'groups', array() ) );
$modules  = sol_rows( sol_get( $s, 'modules', array() ) );
$packages = array();
foreach ( sol_rows( sol_get( $s, 'packages', array() ) ) as $p ) {
	$opts = array();
	foreach ( sol_rows( sol_get( $p, 'payment_options', array() ) ) as $o ) {
		$opts[] = array( 'label' => (string) sol_get( $o, 'label' ), 'amount' => (float) sol_get( $o, 'amount', 0 ), 'url' => esc_url_raw( sol_url( sol_get( $o, 'url' ) ) ) );
	}
	$packages[] = array(
		'tier'     => (int) sol_get( $p, 'tier', 1 ),
		'name'     => (string) sol_get( $p, 'name' ),
		'pathway'  => (string) sol_get( $p, 'pathway' ),
		'bestFor'  => (string) sol_get( $p, 'best_for' ),
		'priceEx'  => (float) sol_get( $p, 'price_ex_gst', 0 ),
		'gst'      => (float) sol_get( $p, 'gst', 0 ),
		'total'    => (float) sol_get( $p, 'total', 0 ),
		'features' => sol_list( sol_get( $p, 'features', array() ) ),
		'options'  => $opts,
	);
}
$items = array();
foreach ( $groups as $g ) {
	$items[] = array( 'code' => (string) sol_get( $g, 'code' ), 'tier' => (int) sol_get( $g, 'tier', 1 ), 'companyTier' => (int) sol_get( $g, 'company_tier', sol_get( $g, 'tier', 1 ) ), 'entityDependent' => ! empty( $g['entity_dependent'] ) );
}
foreach ( $modules as $m ) {
	$items[] = array( 'code' => (string) sol_get( $m, 'code' ), 'tier' => (int) sol_get( $m, 'tier', 1 ), 'companyTier' => (int) sol_get( $m, 'tier', 1 ), 'manual' => ! empty( $m['manual_review'] ) );
}
$config  = array( 'packages' => $packages, 'items' => $items );
$consult = sol_get( $s, 'consult_link' );

sol_section_open( $s, 'ndis_finder' );
?>
<div class="sol-container sol-container--mid">
	<?php sol_section_head( $s ); ?>
	<form class="sol-finder" data-sol-finder novalidate>
		<script type="application/json" data-sol-finder-config><?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ); ?></script>

		<fieldset class="sol-finder__step" data-finder-step="1">
			<legend>1. Tell us about your business</legend>
			<p class="sol-muted">Entity type changes the audit pathway for Community Nursing and Therapeutic Supports.</p>
			<label class="sol-field"><span>Legal entity type *</span>
				<select name="entity" required>
					<option value="">Select entity type</option>
					<?php foreach ( $entities as $e ) : ?><option value="<?php echo esc_attr( sol_get( $e, 'value' ) ); ?>"><?php echo esc_html( sol_get( $e, 'label' ) ); ?></option><?php endforeach; ?>
				</select>
			</label>
			<div class="sol-field"><span>Australian states of operation *</span>
				<div class="sol-chips"><?php foreach ( $states as $st ) : ?><label class="sol-chip"><input type="checkbox" name="states[]" value="<?php echo esc_attr( $st ); ?>"><span><?php echo esc_html( $st ); ?></span></label><?php endforeach; ?></div>
			</div>
			<p class="sol-form__error" data-finder-error hidden></p>
			<button type="button" class="sol-btn sol-btn--primary" data-finder-next>Select registration groups</button>
		</fieldset>

		<fieldset class="sol-finder__step" data-finder-step="2" hidden>
			<legend>2. Choose registration groups</legend>
			<p class="sol-muted">Select every support your organisation intends to deliver.</p>
			<input type="search" class="sol-input" placeholder="Search registration groups" data-finder-search aria-label="Search registration groups">
			<div class="sol-finder__groups">
				<?php foreach ( $groups as $g ) : ?>
					<label class="sol-finder__group" data-finder-item>
						<input type="checkbox" name="groups[]" value="<?php echo esc_attr( sol_get( $g, 'code' ) ); ?>">
						<span class="sol-finder__code"><?php echo esc_html( sol_get( $g, 'code' ) ); ?></span>
						<span class="sol-finder__name"><?php echo esc_html( sol_get( $g, 'name' ) ); ?>
							<?php if ( ! empty( $g['entity_dependent'] ) ) : ?><small>Audit pathway depends on legal entity type.</small><?php endif; ?>
						</span>
						<span class="sol-tag">Tier <?php echo ! empty( $g['entity_dependent'] ) ? '1/2' : (int) sol_get( $g, 'tier', 1 ); ?></span>
					</label>
				<?php endforeach; ?>
				<?php foreach ( $modules as $m ) : ?>
					<label class="sol-finder__group" data-finder-item>
						<input type="checkbox" name="groups[]" value="<?php echo esc_attr( sol_get( $m, 'code' ) ); ?>">
						<span class="sol-finder__code"><?php echo esc_html( sol_get( $m, 'code' ) ); ?></span>
						<span class="sol-finder__name"><?php echo esc_html( sol_get( $m, 'name' ) ); ?>
							<?php if ( ! empty( $m['manual_review'] ) ) : ?><small>Requires manual confirmation before quotation.</small><?php endif; ?>
						</span>
						<span class="sol-tag">Module</span>
					</label>
				<?php endforeach; ?>
			</div>
			<?php if ( sol_get( $s, 'retainer_text' ) ) : ?>
				<label class="sol-check"><input type="checkbox" name="retainer" value="1"> <span><?php echo esc_html( $s['retainer_text'] ); ?></span></label>
			<?php endif; ?>
			<p class="sol-form__error" data-finder-error hidden></p>
			<div class="sol-buttons">
				<button type="button" class="sol-btn sol-btn--outline" data-finder-back>Back</button>
				<button type="button" class="sol-btn sol-btn--primary" data-finder-calc>Calculate my package</button>
			</div>
		</fieldset>

		<div class="sol-finder__step sol-finder__result" data-finder-step="3" hidden aria-live="polite">
			<span class="sol-pill">Package calculated</span>
			<h3 data-finder-name tabindex="-1"></h3>
			<p class="sol-muted" data-finder-pathway></p>
			<div class="sol-grid sol-grid--2">
				<div><p class="sol-label">What is included</p><ul class="sol-checks sol-checks--sm" data-finder-features></ul></div>
				<div class="sol-finder__price">
					<p class="sol-label">Fixed package fee</p>
					<p class="sol-finder__total" data-finder-total></p>
					<p class="sol-muted" data-finder-gst></p>
					<p class="sol-muted" data-finder-count></p>
				</div>
			</div>
			<div class="sol-notice sol-notice--warning" data-finder-manual hidden><p><?php echo esc_html( sol_get( $s, 'manual_review_text' ) ); ?></p></div>
			<div class="sol-finder__pay" data-finder-pay>
				<p class="sol-label">Choose payment amount</p>
				<div data-finder-options></div>
				<a class="sol-btn sol-btn--primary" href="#" data-finder-pay-link>Continue to secure payment <?php echo sol_icon( 'lock', 'sol-btn__icon' ); // phpcs:ignore ?></a>
			</div>
			<?php if ( sol_get( $s, 'fee_note' ) ) : ?><div class="sol-notice"><p><strong>Important fee information.</strong> <?php echo esc_html( $s['fee_note'] ); ?></p></div><?php endif; ?>
			<div class="sol-buttons">
				<button type="button" class="sol-btn sol-btn--outline" data-finder-restart>Change selection</button>
				<?php echo sol_button( $consult, 'outline', '', false ); // phpcs:ignore ?>
			</div>
		</div>
	</form>
</div>
</section>
