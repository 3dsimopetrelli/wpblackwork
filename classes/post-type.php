<?php


function sparrow_post_type()  {

//=========================  SHOWCASE POST-TYPE ==========================


$labels = array(
	'name' => __('Showcase', 'sparrow'),
	'singular_name' => __('Showcase', 'sparrow'),
	'add_new' => __('Add Showcase', 'sparrow'),
	'add_new_item' => __('Add Showcase', 'sparrow'),
	'edit_item' => __('Edit Showcase', 'sparrow'),
	'new_item' => __('New Showcase', 'sparrow'),
	'view_item' => __('View Showcase', 'sparrow'),
	'search_items' => __('Search Showcase', 'sparrow'),
	'not_found' =>  __('No Showcase found', 'sparrow'),
	'not_found_in_trash' => __('No Showcase found in Trash', 'sparrow'),
	'parent_item_colon' => ''
);

$args = array(
	'label'               => __( 'Showcase', 'sparrow' ),
	'description'         => __( 'A word from our customers', 'sparrow' ),
	'labels'              => $labels,
	'supports'            => array( 'title','editor','thumbnail' ),
	
	'show_ui'             => true,
	'show_in_menu'        => true,
	'show_in_nav_menus'   => true,
	'show_in_admin_bar'   => true,
	'menu_position'       => 5,
	
	'hierarchical'        => true,
	'public'              => true,
    'exclude_from_search' => true,
	'can_export'          => true,
	'has_archive'         => true,
	'publicly_queryable'  => true,
	
	//'public' => true,
	//'capability_type'     => 'post',
	'taxonomies' => array('showcase_tax','category'),

);


register_post_type('showcase',$args);
register_taxonomy('showcase_categories',array('showcase'),

	array(
		'hierarchical' => true,
		"label" => __( "Showcase Cat", 'sparrow'),
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'showcase_categories' ),
		
		'show_in_nav_menus' => true,
	));



//=========================  SERVICE POST-TYPE ==========================


$labels = array(
	'name' => __('Services', 'sparrow'),
	'singular_name' => __('Service', 'sparrow'),
	'add_new' => __('Add Service', 'sparrow'),
	'add_new_item' => __('Add Service', 'sparrow'),
	'edit_item' => __('Edit Service', 'sparrow'),
	'new_item' => __('New Service', 'sparrow'),
	'view_item' => __('View Service', 'sparrow'),
	'search_items' => __('Search Service', 'sparrow'),
	'not_found' =>  __('No Service found', 'sparrow'),
	'not_found_in_trash' => __('No Service found in Trash', 'sparrow'),
	'parent_item_colon' => ''
);

$args = array(
	'label'               => __( 'Services', 'sparrow' ),
	'description'         => __( 'Our Services', 'sparrow' ),
	'labels'              => $labels,
	'supports'            => array( 'title','editor','thumbnail' ),
	
	'show_ui'             => true,
	'show_in_menu'        => true,
	'show_in_nav_menus'   => true,
	'show_in_admin_bar'   => true,
	'menu_position'       => 5,
	
	'hierarchical'        => true,
	'public'              => true,
    'exclude_from_search' => false,
	'can_export'          => true,
	'has_archive'         => true,
	'publicly_queryable'  => true,
	
	//'public' => true,
	'capability_type'     => 'post',
	'taxonomies' => array('services_tax','category'),

);

register_post_type('services',$args);


register_taxonomy('services_categories',array('services'),

	array(
		'hierarchical' => true,
		"label" => __( "Services Cat", 'sparrow'),
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'services_categories' ),
		'show_in_nav_menus' => true,
	));



//=========================  Doc Grapyc POST-TYPE ==========================

$labels = array(
	'name' => __('Help', 'sparrow'),
	'singular_name' => __('Help', 'sparrow'),
	'add_new' => __('Add Help', 'sparrow'),
	'add_new_item' => __('Add Help', 'sparrow'),
	'edit_item' => __('Edit Help', 'sparrow'),
	'new_item' => __('New Help', 'sparrow'),
	'view_item' => __('View Help', 'sparrow'),
	'search_items' => __('Search Help', 'sparrow'),
	'not_found' =>  __('No Help found', 'sparrow'),
	'not_found_in_trash' => __('No Help found in Trash', 'sparrow'),
	'parent_item_colon' => ''
);

$args = array(
		'label'               => __( 'Help', 'sparrow' ),
		'description'         => __( 'Mini Documentations', 'sparrow' ),
		'labels'              => $labels,
		'show_in_rest' => true,
		'supports'            => array( 'title', 'tags', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields', ),
		
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 6,
		
		'hierarchical'        => true,
		'public'              => true,
          'exclude_from_search' => false,
		'can_export'          => true,
		'has_archive'         => true,
		'publicly_queryable'  => true,

		'capability_type'     => 'post',
		'taxonomies' => array('help_tax','category'),

	);


register_post_type('help',$args);
register_taxonomy('help_categories',array('help'),

	array(
		'hierarchical' => true,
		"label" => __( "Help Cat", 'sparrow'),
		'show_ui' => true,
		'query_var' => true,
		'rewrite' => array( 'slug' => 'help_categories' ),
		
		'show_in_nav_menus' => true,
	));

/* end */

} 
add_action('init', 'sparrow_post_type', 0 );
?>