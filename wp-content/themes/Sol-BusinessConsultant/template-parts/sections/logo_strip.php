<?php
/** Layout: logo_strip — scrolling platform names / logos with trust pills. */
defined( 'ABSPATH' ) || exit;

$logos = sol_rows( sol_get( $s, 'logos', array() ) );
$pills = sol_list( sol_get( $s, 'pills', array() ) );
$anim  = ! empty( $s['animate'] );

$pill = function ( $l ) {
	$img = sol_get( $l, 'image' );
	$inner = $img ? sol_image( $img, 'medium', str_replace( '|', ' ', sol_get( $l, 'name' ) ) ) : wp_kses( str_replace( '|', '<br>', esc_html( sol_get( $l, 'name' ) ) ), array( 'br' => array() ) );
	return '<li class="sol-logo-pill">' . $inner . '</li>';
};

sol_section_open( $s, 'logo_strip' );
?>
<div class="sol-container"><?php sol_section_head( $s ); ?></div>
<?php if ( $logos ) : ?>
	<div class="sol-marquee<?php echo $anim ? ' sol-marquee--animate' : ''; ?>">
		<ul class="sol-marquee__track">
			<?php
			foreach ( $logos as $l ) {
				echo $pill( $l ); // phpcs:ignore
			}
			if ( $anim ) {
				foreach ( $logos as $l ) {
					echo str_replace( '<li ', '<li aria-hidden="true" ', $pill( $l ) ); // phpcs:ignore
				}
			}
			?>
		</ul>
	</div>
<?php endif; ?>
<?php if ( $pills ) : ?>
	<div class="sol-container"><ul class="sol-pills sol-pills--center"><?php foreach ( $pills as $p ) : ?><li><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
</section>
