<?php
/**
 * SEO and social sharing.
 *
 * Every page has a 1200x630 JPG share card in assets/images/share/ (its photo, the SOL logo and the
 * page title). That card is used for og:image and twitter:image, so links shared in Slack, Facebook,
 * LinkedIn, WhatsApp and X always show a picture.
 *
 * With Yoast SEO active: Yoast keeps titles, descriptions, canonical URLs, sitemaps and schema. The
 * theme only supplies the share image (unless one is chosen by hand in Yoast's Social tab), sets the
 * Australian locale and adds the business details to Yoast's organisation schema.
 * Without an SEO plugin: the theme prints the full set of tags itself.
 */

defined( 'ABSPATH' ) || exit;

/** True when an SEO plugin handles titles, descriptions and social tags. */
function sol_seo_plugin() {
	if ( defined( 'WPSEO_VERSION' ) ) {
		return 'yoast';
	}
	if ( defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
		return 'other';
	}
	return '';
}

/** Share card name for the current request: home, about, services--payroll-services, post--slug… */
function sol_share_card_key() {
	if ( is_front_page() ) {
		return 'home';
	}
	if ( is_home() ) {
		return 'blog';
	}
	if ( ! is_singular() ) {
		return '';
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return '';
	}
	if ( 'page' === $post->post_type ) {
		return str_replace( '/', '--', get_page_uri( $post ) );
	}
	if ( 'post' === $post->post_type ) {
		return 'post--' . $post->post_name;
	}
	return '';
}

/** Social image for the current request: url, width, height, type, alt. */
function sol_share_image() {
	static $img = null;
	if ( null !== $img ) {
		return $img;
	}
	$alt = sol_opt( 'company_name', 'SOL Business Consultant' );
	$key = sol_share_card_key();
	$rel = '';
	if ( $key && preg_match( '/^[a-z0-9-]+$/', $key ) && file_exists( SOL_DIR . 'assets/images/share/' . $key . '.jpg' ) ) {
		$rel = 'assets/images/share/' . $key . '.jpg';
		$alt = is_front_page() ? $alt . ': NDIS registration, training and business consulting across Australia' : wp_strip_all_tags( single_post_title( '', false ) ) . ' | ' . $alt;
	}
	// Pages without a card: their featured image when it is a JPG or PNG (some apps skip WebP).
	if ( ! $rel && is_singular() && has_post_thumbnail() ) {
		$id   = get_post_thumbnail_id();
		$mime = get_post_mime_type( $id );
		$src  = wp_get_attachment_image_src( $id, 'large' );
		if ( $src && in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) ) {
			$img = array(
				'url'    => $src[0],
				'width'  => (int) $src[1],
				'height' => (int) $src[2],
				'type'   => $mime,
				'alt'    => get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: get_the_title(),
			);
			return $img = apply_filters( 'sol_share_image', $img );
		}
	}
	if ( ! $rel ) {
		$rel = 'assets/images/share/default.jpg';
	}
	$img = array(
		'url'    => SOL_URI . $rel . '?v=' . filemtime( SOL_DIR . $rel ),
		'width'  => 1200,
		'height' => 630,
		'type'   => 'image/jpeg',
		'alt'    => $alt,
	);
	return $img = apply_filters( 'sol_share_image', $img );
}

/** og:image + twitter:image tags. */
function sol_print_share_image() {
	$i = sol_share_image();
	if ( empty( $i['url'] ) ) {
		return;
	}
	$tags = array(
		'og:image'            => $i['url'],
		'og:image:secure_url' => set_url_scheme( $i['url'], 'https' ),
		'og:image:width'      => $i['width'],
		'og:image:height'     => $i['height'],
		'og:image:type'       => $i['type'],
		'og:image:alt'        => $i['alt'],
	);
	foreach ( $tags as $p => $v ) {
		printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $p ), esc_attr( $v ) );
	}
	printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $i['url'] ) );
	printf( '<meta name="twitter:image:alt" content="%s" />' . "\n", esc_attr( $i['alt'] ) );
}

/** The Yoast image set by hand for this page (Social tab), if any. */
function sol_yoast_custom_image() {
	$id = is_singular() ? get_queried_object_id() : ( is_home() ? (int) get_option( 'page_for_posts' ) : 0 );
	if ( is_front_page() && 'page' === get_option( 'show_on_front' ) ) {
		$id = (int) get_option( 'page_on_front' );
	}
	return $id && get_post_meta( $id, '_yoast_wpseo_opengraph-image', true );
}

/* ---------- Yoast SEO ---------- */

// Swap Yoast's image tags for the share card (it often picks a portrait or WebP image, or none).
add_filter( 'wpseo_frontend_presenters', function ( $presenters ) {
	if ( ! apply_filters( 'sol_seo_share_image', true ) || sol_yoast_custom_image() ) {
		return $presenters;
	}
	$GLOBALS['sol_seo_swap_image'] = true;
	return array_values( array_filter( (array) $presenters, function ( $p ) {
		$c = is_object( $p ) ? get_class( $p ) : '';
		return ! in_array( $c, array( 'Yoast\WP\SEO\Presenters\Open_Graph\Image_Presenter', 'Yoast\WP\SEO\Presenters\Twitter\Image_Presenter' ), true );
	} ) );
} );
add_action( 'wp_head', function () {
	if ( ! empty( $GLOBALS['sol_seo_swap_image'] ) ) {
		echo "<!-- SOL share image -->\n";
		sol_print_share_image();
	}
}, 2 );

// Australian audience.
add_filter( 'wpseo_og_locale', function ( $locale ) {
	return 'en_US' === $locale ? 'en_AU' : $locale;
} );

// The brand name, not the WordPress site title.
add_filter( 'wpseo_opengraph_site_name', function ( $name ) {
	return sol_opt( 'company_name', $name );
} );

// Business details on Yoast's organisation schema (shows in Google's knowledge panel and rich results).
add_filter( 'wpseo_schema_organization', function ( $data ) {
	$biz = sol_business_schema();
	unset( $biz['url'], $biz['image'] ); // keep Yoast's own url and logo
	return array_merge( $data, $biz, array( '@type' => array( 'Organization', 'ProfessionalService' ) ) );
} );
add_filter( 'wpseo_schema_website', function ( $data ) {
	if ( empty( $data['description'] ) ) {
		$data['description'] = wp_strip_all_tags( sol_opt( 'tagline', get_bloginfo( 'description' ) ) );
	}
	$data['inLanguage'] = 'en-AU';
	return $data;
} );

/* ---------- Shared schema ---------- */

/** Name, contact and location details from SOL Site Settings. */
function sol_business_schema() {
	$name = sol_opt( 'company_name', 'SOL Business Consultant' );
	$d    = array(
		'name'        => $name,
		'legalName'   => apply_filters( 'sol_legal_name', 'Sol Business Consultant Pty Ltd' ),
		'url'         => home_url( '/' ),
		'description' => wp_strip_all_tags( sol_opt( 'tagline' ) ),
		'telephone'   => sol_opt( 'phone' ),
		'email'       => sol_opt( 'email' ),
		'image'       => SOL_URI . 'assets/images/share/default.jpg',
		'priceRange'  => '$$',
		'areaServed'  => array( '@type' => 'Country', 'name' => 'Australia' ),
		'address'     => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'Glenroy',
			'addressRegion'   => 'VIC',
			'postalCode'      => '3046',
			'addressCountry'  => 'AU',
		),
		'openingHoursSpecification' => array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
			'opens'     => '09:00',
			'closes'    => '17:00',
		),
		'contactPoint' => array(
			'@type'             => 'ContactPoint',
			'contactType'       => 'customer service',
			'telephone'         => sol_opt( 'phone' ),
			'email'             => sol_opt( 'email' ),
			'areaServed'        => 'AU',
			'availableLanguage' => 'English',
		),
	);
	if ( sol_opt( 'abn' ) ) {
		$d['taxID'] = 'ABN ' . sol_opt( 'abn' );
	}
	return apply_filters( 'sol_business_schema', array_filter( $d ) );
}

function sol_print_schema( $data ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}

// Service pages: a Service item linked to the business.
add_action( 'wp_head', function () {
	if ( ! is_singular( 'page' ) || SOL_TPL_SERVICE !== get_page_template_slug() ) {
		return;
	}
	$id = get_queried_object_id();
	$desc = get_post_meta( $id, '_yoast_wpseo_metadesc', true );
	sol_print_schema( array_filter( array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Service',
		'name'        => get_the_title( $id ),
		'serviceType' => get_the_title( $id ),
		'description' => $desc ? $desc : get_the_excerpt( $id ),
		'url'         => get_permalink( $id ),
		'image'       => sol_share_image()['url'],
		'areaServed'  => array( '@type' => 'Country', 'name' => 'Australia' ),
		'provider'    => array( '@type' => 'ProfessionalService', '@id' => home_url( '/#organization' ), 'name' => sol_opt( 'company_name', 'SOL Business Consultant' ), 'url' => home_url( '/' ) ),
	) ) );
}, 30 );

/* ---------- No SEO plugin: the theme prints everything ---------- */

/** Meta description: the SEO description saved by the setup tool, else the excerpt, else the tagline. */
function sol_meta_description() {
	$id = 0;
	if ( is_front_page() && 'page' === get_option( 'show_on_front' ) ) {
		$id = (int) get_option( 'page_on_front' );
	} elseif ( is_home() ) {
		$id = (int) get_option( 'page_for_posts' );
	} elseif ( is_singular() ) {
		$id = get_queried_object_id();
	}
	$d = $id ? get_post_meta( $id, '_yoast_wpseo_metadesc', true ) : '';
	if ( ! $d && is_singular() && has_excerpt( $id ) ) {
		$d = get_the_excerpt( $id );
	}
	if ( ! $d && ( is_category() || is_tag() || is_tax() ) ) {
		$d = term_description();
	}
	if ( ! $d ) {
		$d = sol_opt( 'tagline', get_bloginfo( 'description' ) );
	}
	return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $d ) ) );
}

add_action( 'wp', function () {
	if ( is_admin() || sol_seo_plugin() ) {
		return;
	}
	// Page titles from the SEO title saved by the setup tool.
	add_filter( 'pre_get_document_title', function ( $title ) {
		$id = is_front_page() && 'page' === get_option( 'show_on_front' ) ? (int) get_option( 'page_on_front' ) : ( is_singular() ? get_queried_object_id() : 0 );
		$t  = $id ? get_post_meta( $id, '_yoast_wpseo_title', true ) : '';
		return ( $t && false === strpos( $t, '%%' ) ) ? $t : $title;
	} );
	// Keep search results and empty pages out of Google.
	add_filter( 'wp_robots', function ( $robots ) {
		if ( is_search() || is_404() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		} else {
			$robots['max-image-preview'] = 'large';
		}
		return $robots;
	} );
	add_action( 'wp_head', function () {
		$title = wp_get_document_title();
		$desc  = sol_meta_description();
		$url   = is_singular() ? get_permalink() : ( is_front_page() ? home_url( '/' ) : '' );
		if ( ! $url ) {
			global $wp;
			$url = home_url( add_query_arg( array(), $wp->request ? trailingslashit( $wp->request ) : '/' ) );
		}
		echo "<!-- SOL SEO -->\n";
		if ( $desc ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
		}
		if ( ! is_singular() && ! is_search() && ! is_404() ) {
			printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) ); // singular pages get WordPress's own canonical.
		}
		$og = array(
			'og:locale'    => 'en_AU',
			'og:type'      => is_singular( 'post' ) ? 'article' : 'website',
			'og:title'     => $title,
			'og:description' => $desc,
			'og:url'       => $url,
			'og:site_name' => sol_opt( 'company_name', get_bloginfo( 'name' ) ),
		);
		if ( is_singular( 'post' ) ) {
			$og['article:published_time'] = get_the_date( 'c' );
			$og['article:modified_time']  = get_the_modified_date( 'c' );
		}
		foreach ( array_filter( $og ) as $p => $v ) {
			printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $p ), esc_attr( $v ) );
		}
		sol_print_share_image();
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );
		if ( $desc ) {
			printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $desc ) );
		}

		// Schema: the business and website on every page, plus an article on blog posts.
		$org         = array_merge( array( '@type' => array( 'Organization', 'ProfessionalService' ), '@id' => home_url( '/#organization' ) ), sol_business_schema() );
		$org['logo'] = SOL_URI . 'assets/images/sol-logo.jpg';
		$graph       = array(
			$org,
			array(
				'@type'       => 'WebSite',
				'@id'         => home_url( '/#website' ),
				'url'         => home_url( '/' ),
				'name'        => sol_opt( 'company_name', get_bloginfo( 'name' ) ),
				'description' => wp_strip_all_tags( sol_opt( 'tagline' ) ),
				'inLanguage'  => 'en-AU',
				'publisher'   => array( '@id' => home_url( '/#organization' ) ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => home_url( '/?s={search_term_string}' ),
					'query-input' => 'required name=search_term_string',
				),
			),
		);
		if ( is_singular( 'post' ) ) {
			$graph[] = array(
				'@type'            => 'BlogPosting',
				'headline'         => get_the_title(),
				'description'      => $desc,
				'image'            => sol_share_image()['url'],
				'datePublished'    => get_the_date( 'c' ),
				'dateModified'     => get_the_modified_date( 'c' ),
				'mainEntityOfPage' => get_permalink(),
				'author'           => array( '@id' => home_url( '/#organization' ) ),
				'publisher'        => array( '@id' => home_url( '/#organization' ) ),
				'inLanguage'       => 'en-AU',
			);
		}
		sol_print_schema( array( '@context' => 'https://schema.org', '@graph' => $graph ) );
		echo "<!-- / SOL SEO -->\n";
	}, 1 );
} );
