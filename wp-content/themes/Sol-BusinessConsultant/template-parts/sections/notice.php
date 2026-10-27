<?php
/** Layout: notice — slim information / disclaimer bar. */
defined( 'ABSPATH' ) || exit;

if ( ! sol_get( $s, 'text' ) ) {
	return;
}
$style = preg_replace( '/[^a-z]/', '', sol_get( $s, 'style', 'info' ) );
?>
<div class="sol-notice-bar sol-notice-bar--<?php echo esc_attr( $style ); ?>">
	<div class="sol-container"><p><?php echo sol_icon( 'warning' === $style ? 'alert-triangle' : 'info' ); // phpcs:ignore ?> <span><?php echo esc_html( $s['text'] ); ?></span></p></div>
</div>
