<?php
/** Layout: directory — searchable grouped links (e.g. NDIS services explorer). */
defined( 'ABSPATH' ) || exit;

$groups = sol_rows( sol_get( $s, 'groups', array() ) );
if ( ! $groups ) {
	return;
}
$total = 0;
foreach ( $groups as $g ) {
	$total += count( sol_rows( sol_get( $g, 'items', array() ) ) );
}

sol_section_open( $s, 'directory' );
?>
<div class="sol-container" data-sol-directory>
	<?php sol_section_head( $s ); ?>
	<?php if ( ! empty( $s['searchable'] ) ) : ?>
		<div class="sol-directory__tools">
			<input type="search" class="sol-input" placeholder="Search services…" aria-label="Search services" data-sol-directory-search>
			<div class="sol-filter" role="group" aria-label="Filter by category">
				<button type="button" class="is-active" data-sol-directory-group="*">All</button>
				<?php foreach ( $groups as $g ) : ?><button type="button" data-sol-directory-group="<?php echo esc_attr( sanitize_title( sol_get( $g, 'title' ) ) ); ?>"><?php echo esc_html( sol_get( $g, 'title' ) ); ?></button><?php endforeach; ?>
			</div>
			<p class="sol-muted" data-sol-directory-count data-total="<?php echo (int) $total; ?>"><?php echo (int) $total; ?> services across <?php echo (int) count( $groups ); ?> categories</p>
		</div>
	<?php endif; ?>
	<div class="sol-grid sol-grid--3">
		<?php foreach ( $groups as $g ) : ?>
			<?php $items = sol_rows( sol_get( $g, 'items', array() ) ); ?>
			<div class="sol-directory__group" data-group="<?php echo esc_attr( sanitize_title( sol_get( $g, 'title' ) ) ); ?>">
				<h3><?php echo esc_html( sol_get( $g, 'title' ) ); ?> <span class="sol-count"><?php echo (int) count( $items ); ?></span></h3>
				<ul>
					<?php foreach ( $items as $it ) : ?>
						<?php $u = sol_get( $it, 'url' ); ?>
						<li data-sol-directory-item>
							<?php if ( $u ) : ?>
								<a href="<?php echo esc_url( sol_url( $u ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( sol_get( $it, 'label' ) ); ?> <?php echo sol_icon( 'external-link', 'sol-ext' ); // phpcs:ignore ?></a>
							<?php else : ?>
								<span><?php echo esc_html( sol_get( $it, 'label' ) ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>
	<p class="sol-muted" data-sol-directory-empty hidden>No services found. Try a different search term or clear the filter.</p>
	<?php if ( sol_get( $s, 'note' ) ) : ?><p class="sol-note"><?php echo esc_html( $s['note'] ); ?></p><?php endif; ?>
</div>
</section>
