<?php
/** Layout: comparison_table — columns plus grouped rows; values separated by "|". */
defined( 'ABSPATH' ) || exit;

$cols   = sol_rows( sol_get( $s, 'columns', array() ) );
$groups = sol_rows( sol_get( $s, 'groups', array() ) );
$n      = count( $cols );
if ( ! $n ) {
	return;
}

// With headline features, show them as cards and keep the full table behind "Show all features".
$has_highlights = false;
foreach ( $cols as $c ) {
	if ( sol_list( sol_get( $c, 'highlights', array() ) ) ) {
		$has_highlights = true;
	}
}

sol_section_open( $s, 'comparison_table' );
?>
<div class="sol-container">
	<?php sol_section_head( $s ); ?>
	<?php if ( $has_highlights ) : ?>
		<div class="sol-grid sol-grid--<?php echo (int) min( 4, $n ); ?> sol-compare-cards">
			<?php foreach ( $cols as $c ) : ?>
				<article class="sol-package<?php echo ! empty( $c['highlight'] ) ? ' sol-package--highlight' : ''; ?>">
					<?php if ( sol_get( $c, 'badge' ) ) : ?><span class="sol-package__badge"><?php echo esc_html( $c['badge'] ); ?></span><?php endif; ?>
					<div class="sol-package__head">
						<h3 class="sol-package__name"><?php echo esc_html( sol_get( $c, 'name' ) ); ?></h3>
						<?php if ( sol_get( $c, 'tagline' ) ) : ?><p class="sol-package__tagline"><?php echo esc_html( $c['tagline'] ); ?></p><?php endif; ?>
					</div>
					<?php echo sol_checks( sol_list( sol_get( $c, 'highlights', array() ) ), 'sol-checks--sm' ); // phpcs:ignore ?>
					<div class="sol-package__cta"><?php echo sol_button( sol_get( $c, 'link' ), ! empty( $c['highlight'] ) ? 'primary' : 'dark' ); // phpcs:ignore ?></div>
				</article>
			<?php endforeach; ?>
		</div>
		<details class="sol-compare-more">
			<summary><?php echo sol_icon( 'chevron-down', 'sol-accordion__chev' ); // phpcs:ignore ?> Show all features</summary>
	<?php endif; ?>
	<div class="sol-table-wrap" tabindex="0">
		<table class="sol-table">
			<thead>
				<tr>
					<th scope="col" class="sol-table__first"><?php echo esc_html( sol_get( $s, 'first_column_label' ) ); ?></th>
					<?php foreach ( $cols as $c ) : ?>
						<th scope="col" class="<?php echo ! empty( $c['highlight'] ) ? 'is-highlight' : ''; ?>">
							<?php if ( sol_get( $c, 'badge' ) ) : ?><span class="sol-package__badge sol-package__badge--inline"><?php echo esc_html( $c['badge'] ); ?></span><?php endif; ?>
							<span class="sol-table__name"><?php echo esc_html( sol_get( $c, 'name' ) ); ?></span>
							<?php if ( sol_get( $c, 'price' ) ) : ?><span class="sol-table__price"><?php echo esc_html( $c['price'] ); ?></span><?php endif; ?>
							<?php if ( sol_get( $c, 'tagline' ) ) : ?><span class="sol-table__tagline"><?php echo esc_html( $c['tagline'] ); ?></span><?php endif; ?>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $groups as $g ) : ?>
					<?php if ( sol_get( $g, 'category' ) ) : ?>
						<tr class="sol-table__group"><th scope="rowgroup" colspan="<?php echo (int) $n + 1; ?>"><?php echo esc_html( $g['category'] ); ?></th></tr>
					<?php endif; ?>
					<?php foreach ( sol_rows( sol_get( $g, 'rows', array() ) ) as $r ) : ?>
						<?php $vals = array_map( 'trim', explode( '|', (string) sol_get( $r, 'values' ) ) ); ?>
						<tr>
							<th scope="row"><?php echo esc_html( sol_get( $r, 'label' ) ); ?></th>
							<?php for ( $i = 0; $i < $n; $i++ ) : ?>
								<td class="<?php echo ! empty( $cols[ $i ]['highlight'] ) ? 'is-highlight' : ''; ?>"><?php echo sol_cell( isset( $vals[ $i ] ) ? $vals[ $i ] : '' ); // phpcs:ignore ?></td>
							<?php endfor; ?>
						</tr>
					<?php endforeach; ?>
				<?php endforeach; ?>
				<?php if ( sol_get( $s, 'footer_label' ) ) : ?>
					<tr class="sol-table__footer">
						<th scope="row"><?php echo esc_html( $s['footer_label'] ); ?></th>
						<?php foreach ( $cols as $c ) : ?>
							<td><?php echo sol_button( sol_get( $c, 'link' ), ! empty( $c['highlight'] ) ? 'primary' : 'outline', 'sol-btn--sm', false ); // phpcs:ignore ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php if ( $has_highlights ) : ?>
		</details>
	<?php endif; ?>
	<?php if ( sol_get( $s, 'note' ) ) : ?><p class="sol-note"><?php echo esc_html( $s['note'] ); ?></p><?php endif; ?>
</div>
</section>
