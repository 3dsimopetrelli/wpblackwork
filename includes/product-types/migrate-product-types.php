<?php
/**
 * Product Types Migration Script
 *
 * This script migrates existing products from the old product type slug
 * to the new one:
 * - digitalassets → digital_assets
 *
 * Usage:
 * 1. Include this file once in your WordPress admin
 * 2. Call bw_migrate_product_types_once() function
 * 3. Remove this file after migration
 *
 * @package BWElementorWidgets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrate products from old product type slugs to new ones.
 *
 * This function should be run ONCE after deploying the new product type implementation.
 * It migrates:
 * - digitalassets → digital_assets
 *
 * @return array Migration results with counts.
 */
function bw_migrate_product_types_once() {
	global $wpdb;

	$results = array(
		'digital_assets_migrated' => 0,
		'errors'                  => array(),
	);

	// Check if WooCommerce is active
	if ( ! function_exists( 'wc_get_product' ) ) {
		$results['errors'][] = 'WooCommerce is not active';
		return $results;
	}

	// Ensure new terms exist
	if ( ! term_exists( 'digital_assets', 'product_type' ) ) {
		wp_insert_term( 'digital_assets', 'product_type' );
	}

	// Find all products with the old 'digitalassets' type
	$old_type_products = $wpdb->get_col(
		"SELECT p.ID
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
		INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
		INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
		WHERE p.post_type = 'product'
		AND tt.taxonomy = 'product_type'
		AND t.slug = 'digitalassets'"
	);

	// Migrate each product
	foreach ( $old_type_products as $product_id ) {
		// Set new taxonomy term
		wp_set_object_terms( $product_id, 'digital_assets', 'product_type', false );

		// Update meta
		update_post_meta( $product_id, '_product_type', 'digital_assets' );

		// Clear caches
		clean_post_cache( $product_id );
		wc_delete_product_transients( $product_id );

		$results['digital_assets_migrated']++;
	}

	// Also check for products that have the meta but not the taxonomy
	$meta_only_products = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = 'product'
			AND pm.meta_key = '_product_type'
			AND pm.meta_value = %s",
			'digitalassets'
		)
	);

	foreach ( $meta_only_products as $product_id ) {
		// Set new taxonomy term
		wp_set_object_terms( $product_id, 'digital_assets', 'product_type', false );

		// Update meta
		update_post_meta( $product_id, '_product_type', 'digital_assets' );

		// Clear caches
		clean_post_cache( $product_id );
		wc_delete_product_transients( $product_id );

		$results['digital_assets_migrated']++;
	}

	return $results;
}

/**
 * Admin notice to run migration.
 * Uncomment this section to show an admin notice prompting to run the migration.
 */
/*
function bw_product_types_migration_notice() {
	// Check if migration has been run
	if ( get_option( 'bw_product_types_migrated', false ) ) {
		return;
	}

	// Only show to admins
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong>BW Elementor Widgets:</strong>
			<?php esc_html_e( 'Product types have been updated. Please run the migration to update existing products.', 'bw' ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bw-migrate-product-types' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Run Migration', 'bw' ); ?>
			</a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'bw_product_types_migration_notice' );

function bw_register_migration_page() {
	add_submenu_page(
		null, // Hidden from menu
		__( 'Migrate Product Types', 'bw' ),
		__( 'Migrate Product Types', 'bw' ),
		'manage_options',
		'bw-migrate-product-types',
		'bw_migration_page_callback'
	);
}
add_action( 'admin_menu', 'bw_register_migration_page' );

function bw_migration_page_callback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bw' ) );
	}

	// Run migration if confirmed
	if ( isset( $_POST['bw_confirm_migration'] ) && check_admin_referer( 'bw_migrate_products', 'bw_migration_nonce' ) ) {
		$results = bw_migrate_product_types_once();
		update_option( 'bw_product_types_migrated', true );

		echo '<div class="notice notice-success"><p>';
		printf(
			esc_html__( 'Migration completed! %d products migrated to digital_assets.', 'bw' ),
			absint( $results['digital_assets_migrated'] )
		);
		echo '</p></div>';

		if ( ! empty( $results['errors'] ) ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Errors:', 'bw' ) . ' ' . esc_html( implode( ', ', $results['errors'] ) );
			echo '</p></div>';
		}
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Migrate Product Types', 'bw' ); ?></h1>
		<p><?php esc_html_e( 'This will migrate existing products from the old product type slugs to the new ones:', 'bw' ); ?></p>
		<ul>
			<li><code>digitalassets</code> → <code>digital_assets</code></li>
		</ul>
		<form method="post">
			<?php wp_nonce_field( 'bw_migrate_products', 'bw_migration_nonce' ); ?>
			<p>
				<button type="submit" name="bw_confirm_migration" class="button button-primary">
					<?php esc_html_e( 'Run Migration', 'bw' ); ?>
				</button>
			</p>
		</form>
	</div>
	<?php
}
*/
