<?php
/** Layout: resource_download — pick a checklist, leave name + email; file link shown/emailed. */
defined( 'ABSPATH' ) || exit;

$resources = sol_rows( sol_get( $s, 'resources', array() ) );
if ( ! $resources ) {
	return;
}
sol_section_open( $s, 'resource_download' );
?>
<div class="sol-container sol-container--mid">
	<?php sol_section_head( $s ); ?>
	<div class="sol-form-card">
		<h3>Download a preparation checklist</h3>
		<p class="sol-muted">Choose a resource and enter your details to request it.</p>
		<form class="sol-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sol-form data-success="Thanks, your request is in. The SOL team will email your checklist and suggested next step within one business day.">
			<?php sol_form_hidden( 'resource' ); ?>
			<div class="sol-resource-list">
				<?php foreach ( $resources as $i => $r ) : ?>
					<label class="sol-resource">
						<input type="radio" name="selection" value="<?php echo esc_attr( sol_get( $r, 'key', 'resource-' . $i ) ); ?>" <?php checked( 0, $i ); ?>>
						<span><strong><?php echo esc_html( sol_get( $r, 'title' ) ); ?></strong><small><?php echo esc_html( sol_get( $r, 'text' ) ); ?></small></span>
					</label>
				<?php endforeach; ?>
			</div>
			<div class="sol-form__row">
				<label class="sol-field"><span>Name *</span><input type="text" name="name" required autocomplete="name"></label>
				<label class="sol-field"><span>Email *</span><input type="email" name="email" required autocomplete="email"></label>
			</div>
			<?php if ( sol_get( $s, 'consent_text' ) ) : ?>
				<label class="sol-check"><input type="checkbox" name="marketing_consent" value="1"> <span><?php echo esc_html( $s['consent_text'] ); ?></span></label>
			<?php endif; ?>
			<button type="submit" class="sol-btn sol-btn--primary"><?php echo sol_icon( 'download', 'sol-btn__icon' ); // phpcs:ignore ?> <?php echo esc_html( sol_get( $s, 'button_label', 'Request Checklist' ) ); ?></button>
			<p class="sol-form__status" role="status" aria-live="polite"></p>
		</form>
	</div>
</div>
</section>
