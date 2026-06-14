<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bw_register_license_cpt' ) ) {
	function bw_register_license_cpt() {
		$labels = [
			'name'               => __( 'Licenses', 'bw' ),
			'singular_name'      => __( 'License', 'bw' ),
			'menu_name'          => __( 'Licenses', 'bw' ),
			'name_admin_bar'     => __( 'License', 'bw' ),
			'add_new'            => __( 'Add New', 'bw' ),
			'add_new_item'       => __( 'Add New License', 'bw' ),
			'new_item'           => __( 'New License', 'bw' ),
			'edit_item'          => __( 'Edit License', 'bw' ),
			'view_item'          => __( 'View License', 'bw' ),
			'all_items'          => __( 'Licenses', 'bw' ),
			'search_items'       => __( 'Search Licenses', 'bw' ),
			'not_found'          => __( 'No licenses found.', 'bw' ),
			'not_found_in_trash' => __( 'No licenses found in Trash.', 'bw' ),
		];

		register_post_type(
			'bw_license',
			[
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => 'blackwork-site-settings',
				'show_in_admin_bar'   => true,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => [ 'title' ],
				'capability_type'     => 'post',
				'menu_position'       => 81,
				'menu_icon'           => 'dashicons-media-document',
			]
		);
	}
}
add_action( 'init', 'bw_register_license_cpt', 9 );
