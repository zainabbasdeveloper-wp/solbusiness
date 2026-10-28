<?php
/**
 * Appearance → SOL Setup
 * Applies the training-site design and content to this WordPress site from seed/site.json:
 *   - switches existing pages (home, about, services/…) to the SOL templates and fills their sections
 *   - creates pages that don't exist yet
 *   - creates the WooCommerce package products (by SKU) and adds course details to the training products
 *   - saves site settings (header, menu, footer) and optional blog posts
 * The previous page template is remembered so "Restore Elementor" can switch pages back.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_theme_page( 'SOL Setup', 'SOL Setup', 'manage_options', 'sol-setup', 'sol_setup_page' );
	add_submenu_page( SOL_OPTIONS_SLUG, 'SOL Setup', 'Setup & Import', 'manage_options', 'sol-setup', 'sol_setup_page' );
}, 20 );

function sol_seed() {
	static $seed = null;
	if ( null === $seed ) {
		$file = SOL_DIR . 'seed/site.json';
		$seed = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$seed = is_array( $seed ) ? $seed : array();
	}
	return $seed;
}

/** Find a page/post by its path (e.g. "services/payroll-services"). */
function sol_find_item( $item ) {
	if ( 'page' === $item['post_type'] ) {
		$p = get_page_by_path( $item['path'], OBJECT, 'page' );
		return $p ? (int) $p->ID : 0;
	}
	$found = get_posts( array( 'post_type' => $item['post_type'], 'name' => basename( $item['path'] ), 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	return $found ? (int) $found[0] : 0;
}

/** Plan: what setup would do for each page. */
function sol_setup_plan() {
	$seed = sol_seed();
	$rows = array();
	foreach ( array_merge( (array) ( $seed['pages'] ?? array() ), (array) ( $seed['services'] ?? array() ), (array) ( $seed['blocks'] ?? array() ) ) as $item ) {
		$id  = sol_find_item( $item );
		$tpl = $id && 'page' === $item['post_type'] ? get_page_template_slug( $id ) : '';
		if ( ! $id ) {
			$state = 'new';
		} elseif ( 'sol_block' === $item['post_type'] ) {
			$state = 'exists';
		} elseif ( $tpl === $item['template'] ) {
			$state = 'done';
		} else {
			$state = get_post_meta( $id, '_elementor_edit_mode', true ) ? 'elementor' : 'switch';
		}
		$rows[] = array( 'item' => $item, 'id' => $id, 'state' => $state );
	}
	return $rows;
}

function sol_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$report = null;
	if ( isset( $_POST['sol_setup_run'] ) ) {
		check_admin_referer( 'sol_setup' );
		$report = sol_run_setup( array(
			'options'   => ! empty( $_POST['opt_options'] ),
			'pages'     => ! empty( $_POST['opt_pages'] ),
			'products'  => ! empty( $_POST['opt_products'] ),
			'posts'     => ! empty( $_POST['opt_posts'] ),
			'overwrite' => ! empty( $_POST['opt_overwrite'] ),
		) );
	} elseif ( isset( $_POST['sol_updates_apply'] ) ) {
		check_admin_referer( 'sol_setup' );
		$force = isset( $_POST['force'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['force'] ) ) : array();
		delete_option( 'sol_updates_paused' );
		$report = sol_apply_theme_updates( $force );
	} elseif ( isset( $_POST['sol_updates_undo'] ) ) {
		check_admin_referer( 'sol_setup' );
		$report = sol_undo_last_update();
	} elseif ( isset( $_POST['sol_setup_reapply'] ) ) {
		check_admin_referer( 'sol_setup' );
		$keys = isset( $_POST['reapply'] ) ? array_map( function ( $k ) {
			return preg_replace( '#[^a-z0-9_:/-]#', '', strtolower( (string) $k ) );
		}, (array) wp_unslash( $_POST['reapply'] ) ) : array();
		$report = $keys
			? sol_run_setup( array( 'options' => false, 'products' => false, 'posts' => false, 'pages' => true, 'reapply' => $keys ) )
			: array( 'log' => array(), 'errors' => array( 'Tick at least one page or reusable section to re-apply.' ) );
	} elseif ( isset( $_POST['sol_setup_restore'] ) ) {
		check_admin_referer( 'sol_setup' );
		$report = sol_restore_templates();
	}
	$scf   = function_exists( 'update_field' );
	$woo   = class_exists( 'WooCommerce' );
	$plan  = sol_setup_plan();
	$label = array( 'new' => 'Will be created', 'elementor' => 'Elementor page → switched to SOL template (Elementor data kept)', 'switch' => 'Switched to SOL template', 'done' => 'Already using SOL template', 'exists' => 'Already created' );
	?>
	<div class="wrap">
		<h1>SOL Setup</h1>
		<p>Applies the SOL Training Academy design and content to this site. Pages keep their URLs. Checkout stays on WooCommerce; after payment, customers continue on the training site.</p>
		<?php if ( ! $scf ) : ?><div class="notice notice-error"><p>Install and activate <strong>Secure Custom Fields</strong> first.</p></div><?php endif; ?>
		<?php if ( ! $woo ) : ?><div class="notice notice-warning"><p>WooCommerce is not active, so products and checkout buttons will be skipped.</p></div><?php endif; ?>
		<?php if ( $woo && 'yes' !== get_option( 'woocommerce_calc_taxes' ) ) : ?><div class="notice notice-warning"><p>WooCommerce taxes are off. Package prices are entered ex-GST, so turn on taxes (10% GST) under <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings' ) ); ?>">WooCommerce → Settings</a> before taking payments.</p></div><?php endif; ?>

		<?php if ( $report ) : ?>
			<div class="notice notice-<?php echo $report['errors'] ? 'warning' : 'success'; ?>"><p><strong>Done.</strong></p>
				<ul style="list-style:disc;margin-left:20px">
					<?php foreach ( $report['log'] as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
					<?php foreach ( $report['errors'] as $line ) : ?><li style="color:#b32d2e"><?php echo esc_html( $line ); ?></li><?php endforeach; ?>
				</ul>
				<p><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">View site</a></p>
			</div>
			<?php $plan = sol_setup_plan(); ?>
		<?php endif; ?>

		<?php sol_render_updates_panel( $scf ); ?>

		<h2>Full setup</h2>
		<form method="post">
			<?php wp_nonce_field( 'sol_setup' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Apply</th><td>
					<label><input type="checkbox" name="opt_options" value="1" checked> Site settings: logo, top bar, menu + services mega menu, footer, contact details</label><br>
					<label><input type="checkbox" name="opt_pages" value="1" checked> Pages and service pages (table below) + reusable sections</label><br>
					<label><input type="checkbox" name="opt_products" value="1" checked <?php disabled( ! $woo ); ?>> WooCommerce package products (NDIS, training, automation, finance, marketing) and course details on the 3 training products</label><br>
					<label><input type="checkbox" name="opt_posts" value="1" checked> Blog posts from the training site (only ones that don't exist yet)</label>
				</td></tr>
				<tr><th scope="row">Already set up</th><td>
					<label><input type="checkbox" name="opt_overwrite" value="1"> Overwrite pages, products and settings that were already set up (resets your SCF edits)</label>
				</td></tr>
			</table>
			<p>
				<?php submit_button( 'Run setup', 'primary', 'sol_setup_run', false, $scf ? array() : array( 'disabled' => 'disabled' ) ); ?>
				<?php submit_button( 'Restore Elementor templates', 'secondary', 'sol_setup_restore', false, array( 'onclick' => "return confirm('Switch every page set up here back to its previous template?');" ) ); ?>
			</p>
		</form>

		<h2>Pages and reusable sections</h2>
		<p>To refresh only some items with the latest theme content (for example after a theme update), tick them and press <strong>Re-apply selected</strong>. This resets their SCF fields to the theme defaults; everything else is left alone.</p>
		<form method="post">
		<?php wp_nonce_field( 'sol_setup' ); ?>
		<table class="widefat striped" style="max-width:1100px">
			<thead><tr><td class="check-column"><input type="checkbox" onclick="document.querySelectorAll('.sol-reapply').forEach(function(c){c.checked=this.checked}.bind(this))" aria-label="Select all"></td><th>Page</th><th>URL</th><th>Template</th><th>What setup does</th></tr></thead>
			<tbody>
				<?php foreach ( $plan as $r ) : ?>
					<?php $rkey = $r['item']['post_type'] . ':' . $r['item']['path']; ?>
					<tr>
						<th scope="row" class="check-column"><?php if ( $r['id'] ) : ?><input type="checkbox" class="sol-reapply" name="reapply[]" value="<?php echo esc_attr( $rkey ); ?>" aria-label="<?php echo esc_attr( 'Re-apply ' . $r['item']['title'] ); ?>"><?php endif; ?></th>
						<td><?php echo $r['id'] ? '<a href="' . esc_url( get_edit_post_link( $r['id'] ) ) . '">' . esc_html( get_the_title( $r['id'] ) ) . '</a>' : esc_html( $r['item']['title'] ); ?></td>
						<td><?php echo 'sol_block' === $r['item']['post_type'] ? '<em>Reusable section</em>' : '<code>/' . esc_html( $r['item']['path'] ) . '/</code>'; ?></td>
						<td><?php echo esc_html( 'sol_block' === $r['item']['post_type'] ? '—' : ( SOL_TPL_SERVICE === $r['item']['template'] ? 'SOL Service Page' : 'SOL Page Builder' ) ); ?></td>
						<td><?php echo esc_html( $label[ $r['state'] ] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p><?php submit_button( 'Re-apply selected', 'secondary', 'sol_setup_reapply', false, $scf ? array() : array( 'disabled' => 'disabled' ) ); ?></p>
		</form>
		<p class="description">Pages not in this list (cart, checkout, my account, policies you keep in Elementor…) keep their content and get the new header and footer.</p>
	</div>
	<?php
}

/** Run setup. Also: wp eval 'print_r( sol_run_setup() );' */
function sol_run_setup( $opts = array() ) {
	$opts   = wp_parse_args( $opts, array( 'options' => true, 'pages' => true, 'products' => true, 'posts' => true, 'overwrite' => false, 'reapply' => array() ) );
	$seed   = sol_seed();
	$report = array( 'log' => array(), 'errors' => array() );
	if ( ! $seed || ! function_exists( 'update_field' ) ) {
		$report['errors'][] = 'Seed file missing or Secure Custom Fields not active.';
		return $report;
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	wp_raise_memory_limit( 'admin' );
	$keys = isset( $seed['field_keys'] ) ? $seed['field_keys'] : array();
	$ids  = array();

	// Products first, so checkout links resolve.
	if ( $opts['products'] && class_exists( 'WooCommerce' ) ) {
		sol_setup_products( $seed, $opts, $keys, $report );
	}

	$groups = array();
	if ( $opts['pages'] ) {
		$groups = array( 'blocks', 'pages', 'services' );
	}
	if ( $opts['posts'] ) {
		$groups[] = 'posts';
	}

	// The /services/ parent must exist before its children.
	$services_parent = get_page_by_path( 'services' );

	// Pass 1: make sure every item exists.
	$fresh = array();
	foreach ( $groups as $g ) {
		foreach ( (array) ( $seed[ $g ] ?? array() ) as $item ) {
			$id = sol_find_item( $item );
			if ( ! $id ) {
				$id = sol_create_seed_post( $item, $report );
				if ( ! $id ) {
					continue;
				}
				$fresh[ $id ] = true;
			}
			$ids[ $item['post_type'] . ':' . $item['path'] ] = (int) $id;
		}
	}

	// Pass 2: templates, fields, SEO, images.
	$counts = array();
	foreach ( $groups as $g ) {
		foreach ( (array) ( $seed[ $g ] ?? array() ) as $item ) {
			$key = $item['post_type'] . ':' . $item['path'];
			if ( empty( $ids[ $key ] ) ) {
				continue;
			}
			$id       = $ids[ $key ];
			$is_fresh = isset( $fresh[ $id ] );
			$set_up   = get_post_meta( $id, '_sol_setup', true );
			if ( ! $is_fresh && 'posts' === $g ) {
				$counts[ $g ]['kept'] = ( $counts[ $g ]['kept'] ?? 0 ) + 1;
				continue; // Never touch existing blog posts.
			}
			if ( ! $is_fresh && $set_up && ! $opts['overwrite'] && ! in_array( $key, (array) $opts['reapply'], true ) ) {
				$counts[ $g ]['kept'] = ( $counts[ $g ]['kept'] ?? 0 ) + 1;
				continue;
			}
			sol_apply_post_item( $id, $item, $keys, $ids, $report );
			$counts[ $g ][ $is_fresh ? 'created' : 'updated' ] = ( $counts[ $g ][ $is_fresh ? 'created' : 'updated' ] ?? 0 ) + 1;
		}
	}
	$names = array( 'blocks' => 'Reusable sections', 'pages' => 'Pages', 'services' => 'Service pages', 'posts' => 'Blog posts' );
	foreach ( $counts as $g => $c ) {
		$bits = array();
		foreach ( array( 'created', 'updated', 'kept' ) as $k ) {
			if ( ! empty( $c[ $k ] ) ) {
				$bits[] = $c[ $k ] . ' ' . ( 'kept' === $k ? 'left as they were' : $k );
			}
		}
		$report['log'][] = $names[ $g ] . ': ' . implode( ', ', $bits );
	}

	if ( $opts['options'] && ! empty( $seed['options'] ) ) {
		if ( $opts['overwrite'] || ! get_option( 'sol_setup_options_done' ) ) {
			foreach ( $seed['options'] as $name => $value ) {
				update_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, sol_setup_resolve( $value, $ids, $report ), 'option' );
				sol_stamp_option( $name, $keys );
			}
			update_option( 'sol_setup_options_done', 1, false );
			$report['log'][] = 'Site settings saved (header, menu, footer, contact details).';
		} else {
			$report['log'][] = 'Site settings already saved earlier — left as they are.';
		}
	}

	if ( $opts['pages'] && ! empty( $seed['build'] ) ) {
		update_option( 'sol_applied_build', $seed['build'], false );
		delete_option( 'sol_updates_paused' );
	}

	$home = get_page_by_path( 'home' );
	if ( $opts['pages'] && $home && 'page' !== get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home->ID );
		$report['log'][] = 'Front page set to "Home".';
	}
	return $report;
}

/** WooCommerce products by SKU + course details on the existing training products. */
function sol_setup_products( $seed, $opts, $keys, &$report ) {
	$made = 0;
	$kept = 0;
	foreach ( (array) ( $seed['products'] ?? array() ) as $p ) {
		$id = (int) wc_get_product_id_by_sku( $p['sku'] );
		if ( $id && ! $opts['overwrite'] ) {
			++$kept;
			continue;
		}
		$pid = sol_save_package_product( $p, $id );
		if ( $pid ) {
			update_post_meta( $pid, '_sol_fp', $seed['fingerprints'][ 'product:' . $p['sku'] ] ?? '' );
			update_post_meta( $pid, '_sol_values_hash', sol_product_values_hash( $pid ) );
		}
		++$made;
	}
	$report['log'][] = sprintf( 'WooCommerce packages: %d created/updated, %d already existed.', $made, $kept );

	$done = 0;
	foreach ( (array) ( $seed['courses'] ?? array() ) as $c ) {
		$pid = (int) $c['product_id'];
		if ( 'product' !== get_post_type( $pid ) ) {
			$report['errors'][] = "Training product #$pid not found — course details skipped. Edit the product IDs in _dev/build/content/helpers.mjs (WOO) and rebuild.";
			continue;
		}
		if ( get_field( 'level', $pid ) && ! $opts['overwrite'] ) {
			continue;
		}
		foreach ( $c['fields'] as $name => $value ) {
			update_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, sol_setup_resolve( $value, array(), $report ), $pid );
		}
		update_post_meta( $pid, '_sol_fp_course', $seed['fingerprints'][ 'course:' . $pid ] ?? '' );
		update_post_meta( $pid, '_sol_values_hash_course', sol_course_values_hash( $pid, $c, $keys ) );
		++$done;
	}
	$report['log'][] = sprintf( 'Course details added to %d training products.', $done );
}

/** Create a page / post / reusable section from the seed. Returns its ID or 0. */
function sol_create_seed_post( $item, &$report ) {
	$parent = 0;
	if ( false !== strpos( $item['path'], '/' ) ) {
		$pp     = get_page_by_path( dirname( $item['path'] ) );
		$sp     = get_page_by_path( 'services' );
		$parent = $pp ? (int) $pp->ID : ( $sp ? (int) $sp->ID : 0 );
	}
	$postarr = array(
		'post_type'    => $item['post_type'],
		'post_name'    => basename( $item['path'] ),
		'post_title'   => $item['title'],
		'post_status'  => 'publish',
		'post_parent'  => $parent,
		'menu_order'   => isset( $item['menu_order'] ) ? (int) $item['menu_order'] : 0,
		'post_content' => isset( $item['content'] ) ? $item['content'] : '',
		'post_excerpt' => isset( $item['excerpt'] ) ? $item['excerpt'] : '',
	);
	if ( ! empty( $item['date'] ) ) {
		$postarr['post_date'] = $item['date'];
	}
	$id = wp_insert_post( wp_slash( $postarr ), true );
	if ( is_wp_error( $id ) ) {
		$report['errors'][] = "Could not create {$item['path']}: " . $id->get_error_message();
		return 0;
	}
	return (int) $id;
}

/** Apply one seed item to a post: template, title, SCF fields, SEO, image, category. Then record its fingerprint. */
function sol_apply_post_item( $id, $item, $keys, $ids, &$report ) {
	if ( ! empty( $item['template'] ) ) {
		$prev = get_post_meta( $id, '_wp_page_template', true );
		if ( $prev !== $item['template'] && ! get_post_meta( $id, '_sol_previous_template', true ) ) {
			update_post_meta( $id, '_sol_previous_template', $prev ? $prev : 'default' );
		}
		update_post_meta( $id, '_wp_page_template', $item['template'] );
	}
	// Keep the title in line with the training site (service cards show it).
	if ( ! empty( $item['title'] ) && get_post_field( 'post_title', $id ) !== $item['title'] ) {
		if ( ! empty( $item['template'] ) && ! get_post_meta( $id, '_sol_previous_title', true ) ) {
			update_post_meta( $id, '_sol_previous_title', get_post_field( 'post_title', $id ) );
		}
		wp_update_post( array( 'ID' => $id, 'post_title' => $item['title'] ) );
	}
	foreach ( (array) ( $item['fields'] ?? array() ) as $name => $value ) {
		update_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, sol_setup_resolve( $value, $ids, $report ), $id );
	}
	sol_setup_yoast( $id, $item );
	if ( ! empty( $item['featured_image'] ) ) {
		$att = sol_setup_image( substr( $item['featured_image'], 4 ), $report, isset( $item['featured_alt'] ) ? $item['featured_alt'] : '' );
		if ( $att ) {
			set_post_thumbnail( $id, $att );
		}
	}
	if ( ! empty( $item['category'] ) ) {
		$term = term_exists( $item['category'], 'category' );
		$term = $term ? $term : wp_insert_term( $item['category'], 'category' );
		if ( ! is_wp_error( $term ) ) {
			wp_set_post_categories( $id, array( (int) $term['term_id'] ) );
		}
	}
	sol_stamp_post( $id, $item, $keys );
}

/** Create or update one WooCommerce package product. Returns its ID. */
function sol_save_package_product( $p, $id = 0 ) {
	$product = $id ? wc_get_product( $id ) : new WC_Product_Simple();
	if ( ! $product ) {
		return 0;
	}
	$product->set_name( $p['name'] );
	$product->set_sku( $p['sku'] );
	$product->set_regular_price( (string) $p['price'] );
	$product->set_short_description( $p['short'] );
	$product->set_description( $p['features'] ? '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $p['features'] ) ) . '</li></ul>' : '' );
	$product->set_virtual( true );
	$product->set_sold_individually( true );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_tax_status( 'taxable' );
	$product->set_status( 'publish' );
	$term = term_exists( $p['category'], 'product_cat' );
	$term = $term ? $term : wp_insert_term( $p['category'], 'product_cat' );
	if ( ! is_wp_error( $term ) ) {
		$product->set_category_ids( array( (int) $term['term_id'] ) );
	}
	$product->save();
	if ( ! empty( $p['pricing_code'] ) ) {
		update_post_meta( $product->get_id(), '_sol_pricing_code', $p['pricing_code'] );
	}
	return (int) $product->get_id();
}

/**
 * Yoast title/description. Filled where empty, and updated where the current value is one
 * the theme set before, so SEO text written by hand in Yoast is never replaced.
 */
function sol_setup_yoast( $id, $item ) {
	if ( empty( $item['yoast'] ) ) {
		return;
	}
	$seed = sol_seed();
	$old  = $seed['yoast_history'][ sol_item_key( $item ) ] ?? array();
	foreach ( array( 'title' => '_yoast_wpseo_title', 'description' => '_yoast_wpseo_metadesc' ) as $k => $meta ) {
		if ( empty( $item['yoast'][ $k ] ) ) {
			continue;
		}
		$cur  = (string) get_post_meta( $id, $meta, true );
		$ours = (array) get_post_meta( $id, '_sol_yoast_' . $k, true );
		$prev = array();
		foreach ( (array) $old as $y ) {
			if ( ! empty( $y[ $k ] ) ) {
				$prev[] = $y[ $k ];
			}
		}
		if ( '' === $cur || in_array( $cur, $ours, true ) || in_array( $cur, $prev, true ) ) {
			update_post_meta( $id, $meta, $item['yoast'][ $k ] );
			update_post_meta( $id, '_sol_yoast_' . $k, array( $item['yoast'][ $k ] ) );
		}
	}
}

/** Replace img:…, ref:type:path and post:ID tokens. */
function sol_setup_resolve( $value, $ids, &$report ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			$value[ $k ] = sol_setup_resolve( $v, $ids, $report );
		}
		return $value;
	}
	if ( ! is_string( $value ) ) {
		return $value;
	}
	if ( 0 === strpos( $value, 'img:' ) ) {
		$att = sol_setup_image( substr( $value, 4 ), $report );
		return $att ? $att : '';
	}
	if ( 0 === strpos( $value, 'ref:' ) ) {
		$parts = explode( ':', $value, 3 );
		$k     = $parts[1] . ':' . $parts[2];
		if ( empty( $ids[ $k ] ) ) {
			$report['errors'][] = "Missing reference $value";
			return '';
		}
		return $ids[ $k ];
	}
	if ( preg_match( '/^post:(\d+)$/', $value, $m ) ) {
		return get_post( (int) $m[1] ) ? (int) $m[1] : '';
	}
	return $value;
}

/** Copy a bundled image into the Media Library once and reuse it afterwards. */
function sol_setup_image( $rel, &$report, $alt = '' ) {
	static $cache = array();
	$rel = ltrim( str_replace( '\\', '/', (string) $rel ), '/' );
	if ( isset( $cache[ $rel ] ) ) {
		return $cache[ $rel ];
	}
	$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_sol_seed_image', 'meta_value' => $rel ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	if ( $found ) {
		return $cache[ $rel ] = (int) $found[0];
	}
	$src = SOL_DIR . 'assets/images/' . $rel;
	if ( false !== strpos( $rel, '..' ) || ! file_exists( $src ) ) {
		$report['errors'][] = "Image not found: $rel";
		return $cache[ $rel ] = 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$upload = wp_upload_bits( basename( $rel ), null, (string) file_get_contents( $src ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $upload['error'] ) ) {
		$report['errors'][] = "Upload failed for $rel: " . $upload['error'];
		return $cache[ $rel ] = 0;
	}
	$type = wp_check_filetype( $upload['file'] );
	$id   = wp_insert_attachment( array(
		'post_mime_type' => $type['type'],
		'post_title'     => ucwords( str_replace( array( '-', '_' ), ' ', pathinfo( $rel, PATHINFO_FILENAME ) ) ),
		'post_status'    => 'inherit',
	), $upload['file'] );
	if ( is_wp_error( $id ) || ! $id ) {
		$report['errors'][] = "Could not add $rel to the Media Library.";
		return $cache[ $rel ] = 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_sol_seed_image', $rel );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt ? $alt : ucwords( str_replace( '-', ' ', pathinfo( $rel, PATHINFO_FILENAME ) ) ) );
	return $cache[ $rel ] = (int) $id;
}

/** Switch pages set up here back to the template they had before. */
function sol_restore_templates() {
	$report = array( 'log' => array(), 'errors' => array() );
	$pages  = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_sol_previous_template' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $pages as $id ) {
		update_post_meta( $id, '_wp_page_template', get_post_meta( $id, '_sol_previous_template', true ) );
		$old_title = get_post_meta( $id, '_sol_previous_title', true );
		if ( $old_title ) {
			wp_update_post( array( 'ID' => $id, 'post_title' => $old_title ) );
			delete_post_meta( $id, '_sol_previous_title' );
		}
		delete_post_meta( $id, '_sol_previous_template' );
		delete_post_meta( $id, '_sol_setup' );
	}
	$report['log'][] = sprintf( '%d pages switched back to their previous template. Pages created by setup were left in place (unpublish them if you don\'t need them).', count( $pages ) );
	return $report;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'sol setup', function ( $args, $assoc ) {
		$r = sol_run_setup( array( 'overwrite' => ! empty( $assoc['overwrite'] ) ) );
		foreach ( $r['log'] as $l ) {
			WP_CLI::log( $l );
		}
		foreach ( $r['errors'] as $e ) {
			WP_CLI::warning( $e );
		}
		WP_CLI::success( 'SOL setup finished.' );
	} );
}

/** "Theme updates" panel on the SOL Setup screen. */
function sol_render_updates_panel( $scf ) {
	if ( ! $scf || ! sol_setup_has_run() ) {
		return;
	}
	$seed    = sol_seed();
	$plan    = sol_updates_plan();
	$pending = array_values( array_filter( $plan, function ( $r ) {
		return in_array( $r['state'], array( 'update', 'new', 'edited' ), true );
	} ) );
	$backup  = get_option( 'sol_last_update_backup' );
	$paused  = get_option( 'sol_updates_paused' );
	$labels  = array( 'update' => 'Will be updated', 'new' => 'Will be created', 'edited' => 'Edited in WordPress since setup: kept unless ticked' );
	?>
	<div class="card" style="max-width:1100px;margin:16px 0;padding:16px 20px">
		<h2 style="margin-top:0">Theme updates</h2>
		<?php if ( ! $pending ) : ?>
			<p><span class="dashicons dashicons-yes-alt" style="color:#00a32a"></span> Everything matches the current theme version. After the next <code>git pull</code>, changed pages, sections, products and settings are updated automatically the first time an administrator opens the dashboard.</p>
		<?php else : ?>
			<p><?php echo esc_html( $paused ? 'Automatic updates are paused after an undo. Review and apply below.' : 'This theme version changes the items below. They are applied automatically on the next dashboard visit, or right now with the button.' ); ?></p>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'sol_setup' ); ?>
			<?php if ( $pending ) : ?>
				<table class="widefat striped" style="margin:8px 0 12px">
					<thead><tr><th style="width:28px"></th><th>Item</th><th>What happens</th></tr></thead>
					<tbody>
						<?php foreach ( $pending as $r ) : ?>
							<tr>
								<td><?php if ( 'edited' === $r['state'] ) : ?><input type="checkbox" name="force[]" value="<?php echo esc_attr( $r['key'] ); ?>" aria-label="<?php echo esc_attr( 'Overwrite ' . $r['label'] ); ?>"><?php endif; ?></td>
								<td><?php echo esc_html( $r['label'] ); ?></td>
								<td><?php echo esc_html( $labels[ $r['state'] ] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button( 'Apply theme updates', 'primary', 'sol_updates_apply', false ); ?>
			<?php endif; ?>
			<?php if ( is_array( $backup ) && ! empty( $backup['items'] ) ) : ?>
				<?php submit_button( sprintf( 'Undo last update (%d items, %s)', count( $backup['items'] ), wp_date( 'j M Y H:i', $backup['time'] ) ), 'secondary', 'sol_updates_undo', false, array( 'onclick' => "return confirm('Put back the previous version of every item the last theme update changed?');" ) ); ?>
			<?php endif; ?>
		</form>
		<p class="description" style="margin-bottom:0">Before an item is updated its previous version is saved: <em>Undo last update</em> restores it, and pages also keep a WordPress revision.</p>
	</div>
	<?php
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	// wp sol update [--force-all]
	WP_CLI::add_command( 'sol update', function ( $args, $assoc ) {
		$force = array();
		if ( ! empty( $assoc['force-all'] ) ) {
			foreach ( sol_updates_plan() as $r ) {
				if ( 'edited' === $r['state'] ) {
					$force[] = $r['key'];
				}
			}
		}
		delete_option( 'sol_updates_paused' );
		$r = sol_apply_theme_updates( $force );
		foreach ( array_merge( $r['log'], array_map( function ( $x ) { return 'updated: ' . $x; }, $r['updated'] ), array_map( function ( $x ) { return 'kept (edited): ' . $x; }, $r['kept'] ) ) as $l ) {
			WP_CLI::log( $l );
		}
		foreach ( $r['errors'] as $e ) {
			WP_CLI::warning( $e );
		}
		WP_CLI::success( 'SOL theme content is up to date.' );
	} );
}
