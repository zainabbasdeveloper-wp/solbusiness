<?php
/**
 * Theme content updates after a git pull.
 *
 * seed/site.json carries a fingerprint for every page, reusable section, blog post, product,
 * course and site setting. WordPress remembers the fingerprint it last applied to each item
 * and a hash of the values it saved. After a pull:
 *   - items whose fingerprint changed are updated automatically,
 *   - unless someone edited that item in WordPress since; those are listed and kept,
 *   - new items are created,
 *   - the previous version of every updated item is backed up (Undo last update).
 * It runs on the first admin page load after a pull, from Appearance → SOL Setup, or `wp sol update`.
 */

defined( 'ABSPATH' ) || exit;

/** Unique key for a seed item. */
function sol_item_key( $item ) {
	return $item['post_type'] . ':' . $item['path'];
}

/** Hash of what is stored for a post item now (title, template, SCF fields), to detect edits. */
function sol_post_values_hash( $id, $item, $keys ) {
	$vals = array(
		'title'    => get_post_field( 'post_title', $id ),
		'template' => (string) get_post_meta( $id, '_wp_page_template', true ),
	);
	foreach ( array_keys( (array) ( $item['fields'] ?? array() ) ) as $name ) {
		$vals[ $name ] = get_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, $id, false );
	}
	return md5( wp_json_encode( $vals ) );
}

/** Hash of a site setting's stored value. */
function sol_option_values_hash( $name, $keys ) {
	return md5( wp_json_encode( get_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, 'option', false ) ) );
}

/** Remember which fingerprint was applied to a post and what it looked like right after. */
function sol_stamp_post( $id, $item, $keys ) {
	$seed = sol_seed();
	$key  = sol_item_key( $item );
	if ( isset( $seed['fingerprints'][ $key ] ) ) {
		update_post_meta( $id, '_sol_fp', $seed['fingerprints'][ $key ] );
	}
	update_post_meta( $id, '_sol_values_hash', sol_post_values_hash( $id, $item, $keys ) );
	update_post_meta( $id, '_sol_setup', 1 );
}

/** Same for a site setting. */
function sol_stamp_option( $name, $keys ) {
	$seed = sol_seed();
	$fp   = get_option( 'sol_fp_options', array() );
	$vh   = get_option( 'sol_values_options', array() );
	$fp   = is_array( $fp ) ? $fp : array();
	$vh   = is_array( $vh ) ? $vh : array();
	if ( isset( $seed['fingerprints'][ 'option:' . $name ] ) ) {
		$fp[ $name ] = $seed['fingerprints'][ 'option:' . $name ];
	}
	$vh[ $name ] = sol_option_values_hash( $name, $keys );
	update_option( 'sol_fp_options', $fp, false );
	update_option( 'sol_values_options', $vh, false );
}

/**
 * Decide what to do with one item.
 * $applied_fp: fingerprint stored when last applied ('' = set up before fingerprints existed).
 * Returns: current | update | edited | stamp
 */
function sol_update_state( $key, $applied_fp, $values_unchanged ) {
	$seed = sol_seed();
	$new  = isset( $seed['fingerprints'][ $key ] ) ? $seed['fingerprints'][ $key ] : '';
	if ( $applied_fp && $applied_fp === $new ) {
		return 'current';
	}
	if ( ! $applied_fp ) {
		// Set up before fingerprints: update only items the theme changed since its first version.
		return empty( $seed['history'][ $key ] ) ? 'stamp' : 'update';
	}
	return $values_unchanged ? 'update' : 'edited';
}

/** Has SOL Setup ever run on this site? */
function sol_setup_has_run() {
	if ( get_option( 'sol_setup_options_done' ) || get_option( 'sol_applied_build' ) ) {
		return true;
	}
	return (bool) get_posts( array( 'post_type' => array( 'page', 'sol_block' ), 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_sol_setup' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
}

/** Everything a theme update would change, grouped by state. */
function sol_updates_plan() {
	$seed = sol_seed();
	$keys = isset( $seed['field_keys'] ) ? $seed['field_keys'] : array();
	$plan = array();
	if ( ! $seed || ! function_exists( 'get_field' ) ) {
		return $plan;
	}

	foreach ( array( 'blocks', 'pages', 'services', 'posts' ) as $g ) {
		foreach ( (array) ( $seed[ $g ] ?? array() ) as $item ) {
			$key = sol_item_key( $item );
			$id  = sol_find_item( $item );
			if ( ! $id ) {
				$plan[] = array( 'key' => $key, 'kind' => 'post', 'item' => $item, 'id' => 0, 'state' => 'new', 'label' => $item['title'] );
				continue;
			}
			if ( ! get_post_meta( $id, '_sol_setup', true ) ) {
				continue; // Not managed by SOL Setup (e.g. an Elementor page never converted, an existing blog post).
			}
			$applied = (string) get_post_meta( $id, '_sol_fp', true );
			$same    = $applied && get_post_meta( $id, '_sol_values_hash', true ) === sol_post_values_hash( $id, $item, $keys );
			$plan[]  = array( 'key' => $key, 'kind' => 'post', 'item' => $item, 'id' => $id, 'state' => sol_update_state( $key, $applied, $same ), 'label' => get_the_title( $id ) );
		}
	}

	if ( get_option( 'sol_setup_options_done' ) ) {
		$fp = (array) get_option( 'sol_fp_options', array() );
		$vh = (array) get_option( 'sol_values_options', array() );
		foreach ( array_keys( (array) ( $seed['options'] ?? array() ) ) as $name ) {
			$key     = 'option:' . $name;
			$applied = isset( $fp[ $name ] ) ? (string) $fp[ $name ] : '';
			$same    = $applied && isset( $vh[ $name ] ) && $vh[ $name ] === sol_option_values_hash( $name, $keys );
			$plan[]  = array( 'key' => $key, 'kind' => 'option', 'name' => $name, 'state' => sol_update_state( $key, $applied, $same ), 'label' => 'Site setting: ' . $name );
		}
	}

	if ( class_exists( 'WooCommerce' ) ) {
		foreach ( (array) ( $seed['products'] ?? array() ) as $p ) {
			$id = (int) wc_get_product_id_by_sku( $p['sku'] );
			if ( ! $id ) {
				continue; // Products are only created by a full setup run.
			}
			$key     = 'product:' . $p['sku'];
			$applied = (string) get_post_meta( $id, '_sol_fp', true );
			$same    = $applied && get_post_meta( $id, '_sol_values_hash', true ) === sol_product_values_hash( $id );
			$plan[]  = array( 'key' => $key, 'kind' => 'product', 'data' => $p, 'id' => $id, 'state' => sol_update_state( $key, $applied, $same ), 'label' => $p['name'] );
		}
		foreach ( (array) ( $seed['courses'] ?? array() ) as $c ) {
			$pid = (int) $c['product_id'];
			if ( 'product' !== get_post_type( $pid ) || ! get_field( 'level', $pid ) ) {
				continue;
			}
			$key     = 'course:' . $pid;
			$applied = (string) get_post_meta( $pid, '_sol_fp_course', true );
			$same    = $applied && get_post_meta( $pid, '_sol_values_hash_course', true ) === sol_course_values_hash( $pid, $c, $keys );
			$plan[]  = array( 'key' => $key, 'kind' => 'course', 'data' => $c, 'id' => $pid, 'state' => sol_update_state( $key, $applied, $same ), 'label' => 'Course details: ' . get_the_title( $pid ) );
		}
	}
	return $plan;
}

function sol_product_values_hash( $id ) {
	$p = wc_get_product( $id );
	return $p ? md5( wp_json_encode( array( $p->get_name(), $p->get_regular_price(), $p->get_short_description(), $p->get_description() ) ) ) : '';
}

function sol_course_values_hash( $pid, $c, $keys ) {
	$vals = array();
	foreach ( array_keys( $c['fields'] ) as $name ) {
		$vals[ $name ] = get_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, $pid, false );
	}
	return md5( wp_json_encode( $vals ) );
}

/**
 * Apply theme updates.
 * $force: keys of edited items to overwrite anyway.
 */
function sol_apply_theme_updates( $force = array() ) {
	$seed   = sol_seed();
	$keys   = isset( $seed['field_keys'] ) ? $seed['field_keys'] : array();
	$report = array( 'log' => array(), 'errors' => array(), 'updated' => array(), 'created' => array(), 'kept' => array() );
	if ( ! $seed || ! function_exists( 'update_field' ) ) {
		$report['errors'][] = 'Seed file missing or Secure Custom Fields not active.';
		return $report;
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	wp_raise_memory_limit( 'admin' );

	$plan    = sol_updates_plan();
	$ids     = sol_seed_ids();
	$fp_opts = (array) get_option( 'sol_fp_options', array() );
	$backup = array( 'time' => time(), 'build' => get_option( 'sol_applied_build' ), 'items' => array() );

	// Create new items first so references to them resolve.
	foreach ( $plan as $row ) {
		if ( 'new' !== $row['state'] ) {
			continue;
		}
		$id = sol_create_seed_post( $row['item'], $report );
		if ( $id ) {
			$ids[ $row['key'] ] = $id;
			sol_apply_post_item( $id, $row['item'], $keys, $ids, $report );
			$report['created'][] = $row['item']['title'];
			$backup['items'][]   = array( 'key' => $row['key'], 'kind' => 'created', 'id' => $id );
		}
	}

	foreach ( $plan as $row ) {
		$state = $row['state'];
		if ( 'edited' === $state && in_array( $row['key'], (array) $force, true ) ) {
			$state = 'update';
		}
		if ( 'edited' === $state ) {
			$report['kept'][] = $row['label'];
			continue;
		}
		if ( 'current' === $state || 'new' === $state ) {
			continue;
		}

		if ( 'post' === $row['kind'] ) {
			if ( 'update' === $state ) {
				$backup['items'][] = sol_backup_post( $row['id'], $row['item'], $keys, $row['key'] );
				sol_apply_post_item( $row['id'], $row['item'], $keys, $ids, $report );
				$report['updated'][] = $row['item']['title'];
			} else {
				sol_stamp_post( $row['id'], $row['item'], $keys );
			}
		} elseif ( 'option' === $row['kind'] ) {
			if ( 'update' === $state ) {
				$k                 = isset( $keys[ $row['name'] ] ) ? $keys[ $row['name'] ] : $row['name'];
				$backup['items'][] = array( 'key' => $row['key'], 'kind' => 'option', 'name' => $row['name'], 'fp' => $fp_opts[ $row['name'] ] ?? '', 'value' => get_field( $k, 'option', false ) );
				update_field( $k, sol_setup_resolve( $seed['options'][ $row['name'] ], $ids, $report ), 'option' );
				$report['updated'][] = $row['label'];
			}
			sol_stamp_option( $row['name'], $keys );
		} elseif ( 'product' === $row['kind'] ) {
			if ( 'update' === $state ) {
				$p                 = wc_get_product( $row['id'] );
				$backup['items'][] = array( 'key' => $row['key'], 'kind' => 'product', 'id' => $row['id'], 'fp' => get_post_meta( $row['id'], '_sol_fp', true ), 'value' => array( $p->get_name(), $p->get_regular_price(), $p->get_short_description(), $p->get_description() ) );
				sol_save_package_product( $row['data'], $row['id'] );
				$report['updated'][] = $row['label'];
			}
			update_post_meta( $row['id'], '_sol_fp', $seed['fingerprints'][ $row['key'] ] ?? '' );
			update_post_meta( $row['id'], '_sol_values_hash', sol_product_values_hash( $row['id'] ) );
		} elseif ( 'course' === $row['kind'] ) {
			if ( 'update' === $state ) {
				$vals = array();
				foreach ( array_keys( $row['data']['fields'] ) as $name ) {
					$vals[ $name ] = get_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, $row['id'], false );
				}
				$backup['items'][] = array( 'key' => $row['key'], 'kind' => 'course', 'id' => $row['id'], 'fp' => get_post_meta( $row['id'], '_sol_fp_course', true ), 'value' => $vals );
				foreach ( $row['data']['fields'] as $name => $value ) {
					update_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, sol_setup_resolve( $value, $ids, $report ), $row['id'] );
				}
				$report['updated'][] = $row['label'];
			}
			update_post_meta( $row['id'], '_sol_fp_course', $seed['fingerprints'][ $row['key'] ] ?? '' );
			update_post_meta( $row['id'], '_sol_values_hash_course', sol_course_values_hash( $row['id'], $row['data'], $keys ) );
		}
	}

	update_option( 'sol_applied_build', $seed['build'] ?? '', false );
	if ( $backup['items'] ) {
		update_option( 'sol_last_update_backup', $backup, false );
	}
	$report['log'][] = sprintf(
		'Theme updates: %d updated, %d created, %d edited in WordPress and kept.',
		count( $report['updated'] ),
		count( $report['created'] ),
		count( $report['kept'] )
	);
	return $report;
}

/** IDs of every seed post item that exists, for resolving references. */
function sol_seed_ids() {
	$seed = sol_seed();
	$ids  = array();
	foreach ( array( 'blocks', 'pages', 'services', 'posts' ) as $g ) {
		foreach ( (array) ( $seed[ $g ] ?? array() ) as $item ) {
			$id = sol_find_item( $item );
			if ( $id ) {
				$ids[ sol_item_key( $item ) ] = $id;
			}
		}
	}
	return $ids;
}

/** Save the current version of a post item so the update can be undone. */
function sol_backup_post( $id, $item, $keys, $key ) {
	// A WordPress revision as well, for pages and posts (SCF stores field values with it).
	if ( post_type_supports( get_post_type( $id ), 'revisions' ) ) {
		add_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
		wp_save_post_revision( $id );
		remove_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
	}
	$vals = array();
	foreach ( array_keys( (array) ( $item['fields'] ?? array() ) ) as $name ) {
		$vals[ $name ] = get_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, $id, false );
	}
	return array(
		'key'      => $key,
		'kind'     => 'post',
		'id'       => $id,
		'title'    => get_post_field( 'post_title', $id ),
		'template' => get_post_meta( $id, '_wp_page_template', true ),
		'fp'       => get_post_meta( $id, '_sol_fp', true ),
		'value'    => $vals,
	);
}

/** Put back everything the last theme update changed. */
function sol_undo_last_update() {
	$report = array( 'log' => array(), 'errors' => array() );
	$backup = get_option( 'sol_last_update_backup' );
	$keys   = sol_seed()['field_keys'] ?? array();
	if ( ! is_array( $backup ) || empty( $backup['items'] ) ) {
		$report['errors'][] = 'There is no theme update to undo.';
		return $report;
	}
	$n = 0;
	foreach ( $backup['items'] as $b ) {
		switch ( $b['kind'] ) {
			case 'post':
				wp_update_post( array( 'ID' => $b['id'], 'post_title' => $b['title'] ) );
				update_post_meta( $b['id'], '_wp_page_template', $b['template'] );
				foreach ( $b['value'] as $name => $raw ) {
					update_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, $raw, $b['id'] );
				}
				update_post_meta( $b['id'], '_sol_fp', $b['fp'] );
				delete_post_meta( $b['id'], '_sol_values_hash' );
				break;
			case 'created':
				wp_trash_post( $b['id'] );
				break;
			case 'option':
				update_field( isset( $keys[ $b['name'] ] ) ? $keys[ $b['name'] ] : $b['name'], $b['value'], 'option' );
				$fp = (array) get_option( 'sol_fp_options', array() );
				$vh = (array) get_option( 'sol_values_options', array() );
				$fp[ $b['name'] ] = $b['fp'] ?? '';
				unset( $vh[ $b['name'] ] );
				update_option( 'sol_fp_options', $fp, false );
				update_option( 'sol_values_options', $vh, false );
				break;
			case 'product':
				$p = wc_get_product( $b['id'] );
				if ( $p ) {
					$p->set_name( $b['value'][0] );
					$p->set_regular_price( $b['value'][1] );
					$p->set_short_description( $b['value'][2] );
					$p->set_description( $b['value'][3] );
					$p->save();
				}
				update_post_meta( $b['id'], '_sol_fp', $b['fp'] ?? '' );
				delete_post_meta( $b['id'], '_sol_values_hash' );
				break;
			case 'course':
				foreach ( $b['value'] as $name => $raw ) {
					update_field( isset( $keys[ $name ] ) ? $keys[ $name ] : $name, $raw, $b['id'] );
				}
				update_post_meta( $b['id'], '_sol_fp_course', $b['fp'] ?? '' );
				delete_post_meta( $b['id'], '_sol_values_hash_course' );
				break;
		}
		++$n;
	}
	delete_option( 'sol_last_update_backup' );
	// Next admin visit offers the update again, now treating these items as edited.
	update_option( 'sol_applied_build', 'undone-' . time(), false );
	update_option( 'sol_updates_paused', 1, false );
	$report['log'][] = sprintf( '%d items restored to how they were before the last theme update. Automatic updates are paused until you apply them again on this page.', $n );
	return $report;
}

/* ------------------------------------------------------------------ */
/* Automatic run after a pull                                          */
/* ------------------------------------------------------------------ */

/** Apply theme updates once per pulled build, on an administrator's dashboard visit. */
function sol_maybe_auto_update() {
	if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) || ! function_exists( 'update_field' ) ) {
		return;
	}
	if ( get_option( 'sol_updates_paused' ) || ! apply_filters( 'sol_auto_apply_theme_updates', true ) ) {
		return;
	}
	$seed = sol_seed();
	if ( empty( $seed['build'] ) || get_option( 'sol_applied_build' ) === $seed['build'] || ! sol_setup_has_run() ) {
		return;
	}
	// One run at a time.
	if ( get_transient( 'sol_updating' ) ) {
		return;
	}
	set_transient( 'sol_updating', 1, 5 * MINUTE_IN_SECONDS );
	$report = sol_apply_theme_updates();
	delete_transient( 'sol_updating' );
	set_transient( 'sol_update_notice', $report, DAY_IN_SECONDS );
}
add_action( 'admin_init', 'sol_maybe_auto_update' );

/** Notice after an automatic update. */
function sol_render_update_notice() {
	$report = get_transient( 'sol_update_notice' );
	if ( ! $report || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	delete_transient( 'sol_update_notice' );
	$changed = array_merge( $report['updated'], $report['created'] );
	if ( ! $changed && ! $report['kept'] && ! $report['errors'] ) {
		return;
	}
	$url = admin_url( 'themes.php?page=sol-setup' );
	echo '<div class="notice notice-' . ( $report['kept'] || $report['errors'] ? 'warning' : 'success' ) . ' is-dismissible"><p><strong>SOL theme content updated.</strong> ';
	if ( $changed ) {
		echo esc_html( count( $changed ) . ' item(s) updated: ' . implode( ', ', array_slice( $changed, 0, 12 ) ) . ( count( $changed ) > 12 ? '…' : '' ) . '.' ) . ' ';
	}
	if ( $report['kept'] ) {
		echo esc_html( count( $report['kept'] ) . ' item(s) were edited in WordPress and kept as they are: ' . implode( ', ', array_slice( $report['kept'], 0, 8 ) ) . '.' ) . ' ';
	}
	foreach ( $report['errors'] as $e ) {
		echo '<br>' . esc_html( $e );
	}
	echo ' <a href="' . esc_url( $url ) . '">Review or undo in SOL Setup</a></p></div>';
}
add_action( 'admin_notices', 'sol_render_update_notice' );
