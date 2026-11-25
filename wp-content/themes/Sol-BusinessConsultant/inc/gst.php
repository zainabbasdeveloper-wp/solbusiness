<?php
/**
 * GST at checkout (Appearance → SOL Setup → "GST at checkout").
 *
 * SOL is registered for GST. Package prices on the site are shown "+GST", so WooCommerce adds 10% at checkout:
 * $350 + $35 GST = $385. Course prices are shown "incl. GST" (Level 1 $325), so when GST is turned on their
 * stored price becomes the ex-GST amount (325 / 1.1) and the customer still pays exactly $325, with $29.55 GST
 * shown on the tax invoice.
 *
 * "Turn on GST" sets: taxes on, prices entered ex-GST, GST based on the shop's address, prices shown including
 * GST, a 10% "GST" rate for Australia (all countries are taxed at the shop's address), and the course prices.
 * "Turn off GST" puts every setting and course price back as it was.
 */

defined( 'ABSPATH' ) || exit;

const SOL_GST_BACKUP = 'sol_gst_backup';

/** WooCommerce settings for GST (option => value). */
function sol_gst_settings() {
	return array(
		'woocommerce_calc_taxes'          => 'yes',
		'woocommerce_prices_include_tax'  => 'no',
		'woocommerce_tax_based_on'        => 'base',
		'woocommerce_tax_display_shop'    => 'incl',
		'woocommerce_tax_display_cart'    => 'incl',
		'woocommerce_tax_total_display'   => 'single',
		'woocommerce_tax_round_at_subtotal' => 'no',
	);
}

/** Course product IDs (shown "incl. GST"). */
function sol_gst_course_ids() {
	$seed = function_exists( 'sol_seed' ) ? sol_seed() : array();
	$ids  = array();
	foreach ( (array) ( $seed['courses'] ?? array() ) as $c ) {
		$ids[] = (int) $c['product_id'];
	}
	return array_values( array_filter( (array) apply_filters( 'sol_gst_inclusive_products', $ids ) ) );
}

/** True when GST is switched on by this tool (or taxes are on with a 10% rate). */
function sol_gst_on() {
	return 'yes' === get_option( 'woocommerce_calc_taxes' ) && is_array( get_option( SOL_GST_BACKUP ) );
}

/** ex-GST amount for a GST-inclusive price, kept to 4 decimals so the total rounds back exactly. */
function sol_gst_ex( $incl ) {
	return '' === (string) $incl ? '' : (string) round( (float) $incl / 1.1, 4 );
}

function sol_gst_turn_on() {
	if ( ! class_exists( 'WC_Tax' ) || is_array( get_option( SOL_GST_BACKUP ) ) ) {
		return 0;
	}
	$backup = array( 'options' => array(), 'products' => array(), 'rate' => 0 );
	foreach ( sol_gst_settings() as $name => $value ) {
		$backup['options'][ $name ] = get_option( $name, null );
		update_option( $name, $value );
	}
	// A 10% GST rate (standard class) unless there's already one for Australia.
	$has = false;
	foreach ( WC_Tax::get_rates_for_tax_class( '' ) as $r ) {
		if ( in_array( (string) $r->tax_rate_country, array( 'AU', '' ), true ) && abs( (float) $r->tax_rate - 10 ) < 0.001 ) {
			$has = true;
		}
	}
	if ( ! $has ) {
		$backup['rate'] = (int) WC_Tax::_insert_tax_rate( array(
			'tax_rate_country'  => 'AU',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'GST',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 1,
			'tax_rate_order'    => 0,
			'tax_rate_class'    => '',
		) );
	}
	// Courses keep their GST-inclusive total.
	$n = 0;
	foreach ( sol_gst_course_ids() as $id ) {
		$p = wc_get_product( $id );
		if ( ! $p ) {
			continue;
		}
		$backup['products'][ $id ] = array( 'regular' => $p->get_regular_price( 'edit' ), 'sale' => $p->get_sale_price( 'edit' ), 'tax_status' => $p->get_tax_status( 'edit' ) );
		$p->set_regular_price( sol_gst_ex( $p->get_regular_price( 'edit' ) ) );
		$p->set_sale_price( sol_gst_ex( $p->get_sale_price( 'edit' ) ) );
		$p->set_tax_status( 'taxable' );
		$p->save();
		++$n;
	}
	update_option( SOL_GST_BACKUP, $backup, false );
	wc_delete_product_transients();
	return $n;
}

function sol_gst_turn_off() {
	$backup = get_option( SOL_GST_BACKUP );
	if ( ! is_array( $backup ) ) {
		return;
	}
	foreach ( (array) $backup['options'] as $name => $value ) {
		if ( null === $value ) {
			delete_option( $name );
		} else {
			update_option( $name, $value );
		}
	}
	if ( ! empty( $backup['rate'] ) && class_exists( 'WC_Tax' ) ) {
		WC_Tax::_delete_tax_rate( (int) $backup['rate'] );
	}
	foreach ( (array) $backup['products'] as $id => $old ) {
		$p = wc_get_product( $id );
		if ( $p ) {
			$p->set_regular_price( $old['regular'] );
			$p->set_sale_price( $old['sale'] );
			$p->set_tax_status( $old['tax_status'] );
			$p->save();
		}
	}
	delete_option( SOL_GST_BACKUP );
	wc_delete_product_transients();
}

add_action( 'admin_post_sol_gst', function () {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'sol_gst' );
	$on = ! empty( $_POST['on'] );
	if ( $on ) {
		sol_gst_turn_on();
	} else {
		sol_gst_turn_off();
	}
	wp_safe_redirect( admin_url( 'themes.php?page=sol-setup&sol_gst=' . ( $on ? 'on' : 'off' ) . '#sol-gst' ) );
	exit;
} );

/** The "GST at checkout" box on SOL Setup. */
function sol_gst_box() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$on   = sol_gst_on();
	$done = isset( $_GET['sol_gst'] ) ? sanitize_key( wp_unslash( $_GET['sol_gst'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div id="sol-gst" class="card" style="max-width:760px;padding:12px 20px;margin:16px 0">
		<h2 style="margin-top:8px">GST at checkout</h2>
		<?php if ( 'on' === $done ) : ?><div class="notice notice-success inline"><p>GST is on. Packages are charged their price + 10% GST; courses are still charged their shown price, with GST included.</p></div><?php endif; ?>
		<?php if ( 'off' === $done ) : ?><div class="notice notice-success inline"><p>GST is off again. Every setting and course price is back as it was.</p></div><?php endif; ?>
		<?php if ( $on ) : ?>
			<p style="color:#008a20"><strong>● On.</strong> A $350 package is charged $385 ($350 + $35 GST). A $325 course is charged $325 (includes $29.55 GST). Receipts show the GST.</p>
		<?php elseif ( 'yes' === get_option( 'woocommerce_calc_taxes' ) ) : ?>
			<p>Taxes are already on in WooCommerce → Settings → Tax (set up there by hand). Check that the 10% rate exists and that course prices are entered ex-GST.</p>
		<?php else : ?>
			<p style="color:#b32d2e"><strong>● Off.</strong> Packages say "+GST" but checkout adds none, so a $350 package is charged $350 and SOL pays the GST out of it.</p>
			<p>Turning GST on: packages are charged price + 10% GST; course prices are adjusted so customers still pay exactly $325 / $460 / $599 including GST; a 10% GST rate is added; prices in the shop and cart show GST included. You can turn it off again here, which puts everything back.</p>
		<?php endif; ?>
		<?php if ( $on || 'yes' !== get_option( 'woocommerce_calc_taxes' ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo $on ? 'Turn GST off and restore the previous settings and course prices?' : 'Turn on 10% GST at checkout?'; ?>');">
				<input type="hidden" name="action" value="sol_gst">
				<input type="hidden" name="on" value="<?php echo $on ? '' : '1'; ?>">
				<?php wp_nonce_field( 'sol_gst' ); ?>
				<?php submit_button( $on ? 'Turn off GST' : 'Turn on GST (10%)', $on ? 'secondary' : 'primary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

// Shop, product and course cards: say the price includes GST (prices are shown including GST when it's on).
add_filter( 'woocommerce_get_price_suffix', function ( $suffix, $product ) {
	if ( '' !== trim( (string) $suffix ) || ! function_exists( 'wc_tax_enabled' ) || ! wc_tax_enabled() || 'incl' !== get_option( 'woocommerce_tax_display_shop' ) || ! $product || ! $product->is_taxable() ) {
		return $suffix;
	}
	return ' <small class="woocommerce-price-suffix">incl. GST</small>';
}, 10, 2 );
