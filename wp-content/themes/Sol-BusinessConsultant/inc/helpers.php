<?php
/**
 * Small helpers used by the templates.
 */

defined( 'ABSPATH' ) || exit;

/** Value from an array row, or a default. */
function sol_get( $row, $key, $default = '' ) {
	if ( ! is_array( $row ) || ! isset( $row[ $key ] ) || '' === $row[ $key ] || null === $row[ $key ] || false === $row[ $key ] ) {
		return $default;
	}
	return $row[ $key ];
}

/** Repeater rows as an array (SCF returns false/'' when empty). */
function sol_rows( $value ) {
	return is_array( $value ) ? array_values( array_filter( $value, 'is_array' ) ) : array();
}

/** Simple list repeater (rows of { item }) as strings. */
function sol_list( $value, $key = 'item' ) {
	$out = array();
	foreach ( sol_rows( $value ) as $row ) {
		$v = trim( (string) sol_get( $row, $key ) );
		if ( '' !== $v ) {
			$out[] = $v;
		}
	}
	return $out;
}

/** Site setting from the SOL options page. */
function sol_opt( $name, $default = '' ) {
	static $cache = array();
	if ( ! array_key_exists( $name, $cache ) ) {
		$cache[ $name ] = function_exists( 'get_field' ) ? get_field( $name, 'option' ) : null;
	}
	$v = $cache[ $name ];
	return ( null === $v || '' === $v || false === $v || array() === $v ) ? $default : $v;
}

/** Turn "/path" into a full site URL; leave absolute, mailto:, tel: and #anchors alone. */
function sol_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( 0 === strpos( $url, 'woo:' ) ) {
		return sol_woo_url( $url );
	}
	if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
		return home_url( $url );
	}
	return $url;
}

/**
 * Resolve WooCommerce link tokens:
 *   woo:sku:SKU / woo:id:123  -> add to cart and go to checkout
 *   woo:product:123           -> product page
 * Unknown products fall back to the shop page so buttons never break.
 */
function sol_woo_url( $token ) {
	$parts = explode( ':', $token, 3 );
	$kind  = isset( $parts[1] ) ? $parts[1] : '';
	$val   = isset( $parts[2] ) ? $parts[2] : '';
	if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return home_url( '/' );
	}
	$id      = 'sku' === $kind ? (int) wc_get_product_id_by_sku( $val ) : (int) $val;
	$product = $id ? wc_get_product( $id ) : null;
	if ( ! $product || ! $product->is_purchasable() ) {
		return wc_get_page_permalink( 'shop' );
	}
	if ( 'product' === $kind ) {
		return get_permalink( $id );
	}
	return add_query_arg( 'add-to-cart', $id, wc_get_checkout_url() );
}

/** Normalise an SCF link value (array) into [url, title, target]. */
function sol_link( $link ) {
	if ( ! is_array( $link ) || empty( $link['url'] ) ) {
		return null;
	}
	return array(
		'url'    => sol_url( $link['url'] ),
		'title'  => isset( $link['title'] ) && '' !== $link['title'] ? $link['title'] : $link['url'],
		'target' => isset( $link['target'] ) ? $link['target'] : '',
	);
}

/** <a> button from a link array. */
function sol_button( $link, $style = 'primary', $class = '', $arrow = true ) {
	$l = sol_link( $link );
	if ( ! $l ) {
		return '';
	}
	$style = preg_replace( '/[^a-z]/', '', (string) $style );
	$attrs = '';
	if ( '_blank' === $l['target'] ) {
		$attrs = ' target="_blank" rel="noopener noreferrer"';
	}
	$icon = $arrow && 'primary' === $style ? sol_icon( 'arrow-right', 'sol-btn__icon' ) : '';
	return sprintf(
		'<a class="sol-btn sol-btn--%1$s %2$s" href="%3$s"%4$s>%5$s%6$s</a>',
		esc_attr( $style ? $style : 'primary' ),
		esc_attr( $class ),
		esc_url( $l['url'] ),
		$attrs,
		esc_html( $l['title'] ),
		$icon
	);
}

/** Buttons repeater (rows of { link, style }). */
function sol_buttons( $rows, $class = '' ) {
	$html = '';
	foreach ( sol_rows( $rows ) as $row ) {
		$html .= sol_button( sol_get( $row, 'link' ), sol_get( $row, 'style', 'primary' ) );
	}
	return $html ? '<div class="sol-buttons ' . esc_attr( $class ) . '">' . $html . '</div>' : '';
}

/** Lucide icon placeholder; assets/js/sol.js swaps it for an SVG. */
function sol_icon( $name, $class = '' ) {
	$name = strtolower( trim( (string) $name ) );
	$name = preg_replace( '/[^a-z0-9-]/', '', $name );
	if ( '' === $name ) {
		return '';
	}
	return '<i data-lucide="' . esc_attr( $name ) . '" class="sol-icon ' . esc_attr( $class ) . '" aria-hidden="true"></i>';
}

/** Escaped heading: new lines become <br>, [words] become amber. */
function sol_heading( $text ) {
	$text = esc_html( trim( (string) $text ) );
	$text = preg_replace( '/\[(.+?)\]/', '<span class="sol-accent">$1</span>', $text );
	return nl2br( $text, false );
}

/** Escaped paragraph text with line breaks kept. */
function sol_text( $text ) {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return '';
	}
	$paras = preg_split( "/\n\s*\n/", $text );
	return implode( '', array_map( function ( $p ) {
		return '<p>' . nl2br( esc_html( trim( $p ) ), false ) . '</p>';
	}, $paras ) );
}

/** Rich text from a WYSIWYG field. */
function sol_rich( $html ) {
	$html = (string) $html;
	if ( '' === trim( $html ) ) {
		return '';
	}
	return wp_kses_post( do_shortcode( wpautop( $html ) ) );
}

/** Image tag from an attachment ID (or URL). */
function sol_image( $image, $size = 'large', $alt = '', $class = '', $eager = false ) {
	if ( ! $image ) {
		return '';
	}
	$attrs = array( 'class' => $class, 'loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async' );
	if ( $eager ) {
		$attrs['fetchpriority'] = 'high';
	}
	if ( '' !== $alt ) {
		$attrs['alt'] = $alt;
	}
	if ( is_numeric( $image ) ) {
		return wp_get_attachment_image( (int) $image, $size, false, $attrs );
	}
	if ( is_array( $image ) && isset( $image['ID'] ) ) {
		return wp_get_attachment_image( (int) $image['ID'], $size, false, $attrs );
	}
	return sprintf( '<img src="%s" alt="%s" class="%s" loading="%s" decoding="async">', esc_url( $image ), esc_attr( $alt ), esc_attr( $class ), $eager ? 'eager' : 'lazy' );
}

/** Opening <section> with shared settings (anchor, background). */
function sol_section_open( $s, $layout, $extra = '' ) {
	$id = sanitize_title( sol_get( $s, 'section_id' ) );
	$bg = preg_replace( '/[^a-z]/', '', sol_get( $s, 'background', 'white' ) );
	printf(
		'<section%s class="sol-section sol-%s sol-bg-%s %s">',
		$id ? ' id="' . esc_attr( $id ) . '"' : '',
		esc_attr( str_replace( '_', '-', $layout ) ),
		esc_attr( $bg ? $bg : 'white' ),
		esc_attr( $extra )
	);
}

/** Eyebrow + heading + intro block. */
function sol_section_head( $s, $tag = 'h2', $force_align = '' ) {
	$eyebrow = sol_get( $s, 'eyebrow' );
	$heading = sol_get( $s, 'heading' );
	$intro   = sol_get( $s, 'intro' );
	if ( ! $eyebrow && ! $heading && ! $intro ) {
		return;
	}
	$align = $force_align ? $force_align : sol_get( $s, 'align', 'left' );
	$tag   = in_array( $tag, array( 'h1', 'h2', 'h3' ), true ) ? $tag : 'h2';
	echo '<header class="sol-head sol-head--' . esc_attr( 'center' === $align ? 'center' : 'left' ) . '">';
	if ( $eyebrow ) {
		echo '<span class="sol-eyebrow">' . esc_html( $eyebrow ) . '</span>';
	}
	if ( $heading ) {
		echo '<' . $tag . ' class="sol-title">' . sol_heading( $heading ) . '</' . $tag . '>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<span class="sol-rule" aria-hidden="true"></span>';
	}
	if ( $intro ) {
		echo '<div class="sol-intro">' . sol_text( $intro ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</header>';
}

/** Ticked list. */
function sol_checks( $items, $class = '' ) {
	if ( ! $items ) {
		return '';
	}
	$html = '<ul class="sol-checks ' . esc_attr( $class ) . '">';
	foreach ( $items as $item ) {
		$html .= '<li>' . sol_icon( 'check-circle', 'sol-checks__icon' ) . '<span>' . esc_html( $item ) . '</span></li>';
	}
	return $html . '</ul>';
}

/** Value cell for comparison tables: yes = tick, no = dash, anything else = text. */
function sol_cell( $value ) {
	$v = trim( (string) $value );
	$l = strtolower( $v );
	if ( in_array( $l, array( 'yes', 'true', '✓' ), true ) ) {
		return sol_icon( 'check-circle-2', 'sol-yes' ) . '<span class="screen-reader-text">Included</span>';
	}
	if ( in_array( $l, array( 'no', 'false', '', '-', '—' ), true ) ) {
		return '<span class="sol-no" aria-label="Not included">—</span>';
	}
	return '<span class="sol-cell-text">' . esc_html( $v ) . '</span>';
}

/** URL inside the training app (LMS, student dashboard, client portal). */
function sol_app_url( $path = '' ) {
	$base = untrailingslashit( (string) sol_opt( 'app_url', 'https://training.solbusinessconsultant.com.au' ) );
	return $base . $path;
}

/** Service payment link (the WordPress service payment form page). */
function sol_payment_url( $type = '' ) {
	$url = sol_url( sol_opt( 'payment_page_url', '/sol-service-payment-form/' ) );
	return apply_filters( 'sol_service_payment_url', $url, $type );
}

/** Post ID whose fields service_* layouts should read. */
function sol_context_id( $set = null ) {
	static $id = 0;
	if ( null !== $set ) {
		$id = (int) $set;
	}
	return $id ? $id : get_the_ID();
}
