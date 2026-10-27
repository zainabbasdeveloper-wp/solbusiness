<?php
/**
 * Markup for cards, pricing packages and contact details.
 */

defined( 'ABSPATH' ) || exit;

/** One card in a card_grid. $c keys: icon, tag, subtitle, title, text, image, price, list, list_title, meta, link, link_2. */
function sol_card( $c, $style = 'bordered', $index = 0 ) {
	$link  = sol_link( sol_get( $c, 'link' ) );
	$link2 = sol_link( sol_get( $c, 'link_2' ) );
	$list  = is_array( sol_get( $c, 'list' ) ) ? sol_list( $c['list'] ) : array();
	$meta  = sol_rows( sol_get( $c, 'meta', array() ) );
	$whole = $link && 'numbered' === $style; // Whole card clickable.

	$html  = '<article class="sol-card sol-card--' . esc_attr( $style ) . '">';
	if ( $whole ) {
		$html .= '<a class="sol-card__cover" href="' . esc_url( $link['url'] ) . '"' . ( '_blank' === $link['target'] ? ' target="_blank" rel="noopener"' : '' ) . '>';
	}
	if ( sol_get( $c, 'image' ) ) {
		$html .= '<div class="sol-card__media">' . sol_image( $c['image'], 'medium_large', sol_get( $c, 'title' ) ) . '</div>';
	}
	$top = '';
	if ( sol_get( $c, 'icon' ) ) {
		$top .= '<span class="sol-card__icon">' . sol_icon( $c['icon'] ) . '</span>';
	}
	if ( 'numbered' === $style ) {
		$top .= '<span class="sol-card__num">' . esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) . '</span>';
	}
	if ( 'plain' === $style && sol_get( $c, 'tag' ) ) {
		$top .= '<span class="sol-card__num">' . esc_html( $c['tag'] ) . '</span>';
	} elseif ( sol_get( $c, 'tag' ) ) {
		$top .= '<span class="sol-tag">' . esc_html( $c['tag'] ) . '</span>';
	}
	if ( $top ) {
		$html .= '<div class="sol-card__top">' . $top . '</div>';
	}
	$html .= '<div class="sol-card__body">';
	if ( sol_get( $c, 'subtitle' ) ) {
		$html .= '<p class="sol-card__subtitle">' . esc_html( $c['subtitle'] ) . '</p>';
	}
	if ( sol_get( $c, 'title' ) ) {
		$html .= '<h3 class="sol-card__title">' . esc_html( $c['title'] ) . '</h3>';
	}
	if ( sol_get( $c, 'price' ) ) {
		$html .= '<p class="sol-card__price">' . esc_html( $c['price'] ) . '</p>';
	}
	if ( sol_get( $c, 'text' ) ) {
		$html .= '<div class="sol-card__text">' . sol_text( $c['text'] ) . '</div>';
	}
	if ( $list ) {
		if ( sol_get( $c, 'list_title' ) ) {
			$html .= '<h4 class="sol-card__list-title">' . esc_html( $c['list_title'] ) . '</h4>';
		}
		$html .= sol_checks( $list, 'sol-checks--sm' );
	}
	if ( $meta ) {
		$html .= '<dl class="sol-card__meta">';
		foreach ( $meta as $m ) {
			if ( '' === trim( (string) sol_get( $m, 'value' ) ) ) {
				continue;
			}
			$html .= '<div><dt>' . esc_html( sol_get( $m, 'label' ) ) . '</dt><dd>' . esc_html( $m['value'] ) . '</dd></div>';
		}
		$html .= '</dl>';
	}
	$html .= '</div>';
	if ( $whole ) {
		$html .= '<span class="sol-card__more">' . esc_html( $link['title'] ) . ' ' . sol_icon( 'arrow-right' ) . '</span></a>';
	} elseif ( $link || $link2 ) {
		$html .= '<div class="sol-card__links">';
		if ( $link ) {
			$html .= '<a class="sol-card__link" href="' . esc_url( $link['url'] ) . '"' . ( '_blank' === $link['target'] ? ' target="_blank" rel="noopener"' : '' ) . '>' . esc_html( $link['title'] ) . ' ' . sol_icon( 'arrow-right' ) . '</a>';
		}
		if ( $link2 ) {
			$html .= '<a class="sol-card__link sol-card__link--muted" href="' . esc_url( $link2['url'] ) . '">' . esc_html( $link2['title'] ) . '</a>';
		}
		$html .= '</div>';
	}
	return $html . '</article>';
}

/** One pricing package card. */
function sol_package( $p ) {
	$hl       = ! empty( $p['highlight'] );
	$features = sol_list( sol_get( $p, 'features', array() ) );
	$lines    = sol_rows( sol_get( $p, 'extra_lines', array() ) );

	$html = '<article class="sol-package' . ( $hl ? ' sol-package--highlight' : '' ) . '">';
	if ( sol_get( $p, 'badge' ) ) {
		$html .= '<span class="sol-package__badge">' . esc_html( $p['badge'] ) . '</span>';
	}
	$html .= '<div class="sol-package__head">';
	if ( sol_get( $p, 'icon' ) ) {
		$html .= '<span class="sol-card__icon">' . sol_icon( $p['icon'] ) . '</span>';
	}
	if ( sol_get( $p, 'category' ) ) {
		$html .= '<span class="sol-package__category">' . esc_html( $p['category'] ) . '</span>';
	}
	if ( sol_get( $p, 'tag' ) ) {
		$html .= '<span class="sol-tag">' . esc_html( $p['tag'] ) . '</span>';
	}
	$html .= '<h3 class="sol-package__name">' . esc_html( sol_get( $p, 'name' ) ) . '</h3>';
	if ( sol_get( $p, 'tagline' ) ) {
		$html .= '<p class="sol-package__tagline">' . esc_html( $p['tagline'] ) . '</p>';
	}
	$html .= '<p class="sol-package__price"><strong>' . esc_html( sol_get( $p, 'price' ) ) . '</strong>';
	if ( sol_get( $p, 'period' ) ) {
		$html .= '<span class="sol-package__period">' . esc_html( $p['period'] ) . '</span>';
	}
	if ( sol_get( $p, 'price_note' ) ) {
		$html .= '<span class="sol-package__note">' . esc_html( $p['price_note'] ) . '</span>';
	}
	$html .= '</p></div>';
	if ( $features ) {
		$html .= sol_checks( $features, 'sol-checks--sm' );
	}
	if ( $lines ) {
		$html .= '<dl class="sol-package__lines">';
		foreach ( $lines as $l ) {
			$html .= '<div><dt>' . esc_html( sol_get( $l, 'label' ) ) . '</dt><dd>' . esc_html( sol_get( $l, 'value' ) ) . '</dd></div>';
		}
		$html .= '</dl>';
	}
	$html .= '<div class="sol-package__cta">';
	$html .= sol_button( sol_get( $p, 'cta' ), $hl ? 'primary' : 'dark' );
	$html .= sol_button( sol_get( $p, 'cta_2' ), 'outline' );
	return $html . '</div></article>';
}

/** Phone / email / address list from site settings. */
function sol_contact_details( $dark = false ) {
	$phone = sol_opt( 'phone' );
	$tel   = preg_replace( '/[^0-9+]/', '', sol_opt( 'phone_link', $phone ) );
	$email = sol_opt( 'email' );
	$addr  = sol_opt( 'address' );
	$html  = '<ul class="sol-contact-list' . ( $dark ? ' sol-contact-list--dark' : '' ) . '">';
	if ( $email ) {
		$html .= '<li>' . sol_icon( 'mail' ) . '<div><span>Email</span><a href="mailto:' . esc_attr( antispambot( $email ) ) . '">' . esc_html( antispambot( $email ) ) . '</a></div></li>';
	}
	if ( $phone ) {
		$html .= '<li>' . sol_icon( 'phone' ) . '<div><span>Phone</span><a href="tel:' . esc_attr( $tel ) . '">' . esc_html( $phone ) . '</a></div></li>';
	}
	if ( $addr ) {
		$html .= '<li>' . sol_icon( 'map-pin' ) . '<div><span>Location</span><p>' . esc_html( $addr ) . '</p></div></li>';
	}
	return $html . '</ul>';
}
