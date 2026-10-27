<?php
/** Layout: reusable_block — renders the sections of a Reusable Section post. */
defined( 'ABSPATH' ) || exit;

$block_id = (int) sol_get( $s, 'block' );
$depth    = isset( $GLOBALS['sol_block_depth'] ) ? (int) $GLOBALS['sol_block_depth'] : 0;
if ( ! $block_id || 'sol_block' !== get_post_type( $block_id ) || 'publish' !== get_post_status( $block_id ) || $depth > 3 ) {
	return;
}
$GLOBALS['sol_block_depth'] = $depth + 1;
foreach ( sol_rows( get_field( 'page_sections', $block_id ) ) as $i => $row ) {
	if ( 'reusable_block' === sol_get( $row, 'acf_fc_layout' ) && (int) sol_get( $row, 'block' ) === $block_id ) {
		continue;
	}
	sol_render_row( $row, $i );
}
$GLOBALS['sol_block_depth'] = $depth;
