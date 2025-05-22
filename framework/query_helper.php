<?php 

function sas_get_posts($settings, $paged) {
	$enable_navigation = $settings['enable_navigation'];

	$exclude_posts = ($settings['exclude_posts']) ? explode(',', $settings['exclude_posts']) : [];

	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => 1,
		'posts_per_page'      => $settings['posts_per_page'],
		'orderby'             => $settings['orderby'],
		'order'               => $settings['order'],
		'paged'               => $paged,
		'post__not_in'        => $exclude_posts,
	);

	if(isset($settings['category_name'])) {
		$args['category_name'] = $settings["category_name"];
	}

	if($enable_navigation == 'none') {
		$args['offset'] = $settings['blog_posts_offset'];
	}

	if ( 'by_name' === $settings['source'] and !empty($settings['post_categories']) ) {	
				  
		$args['tax_query'][] = array(
			'taxonomy'           => 'category',
			'field'              => 'slug',
			'terms'              => $settings['post_categories'],
			'post__not_in'       => $exclude_posts,
		);
		if($enable_navigation == 'none') {
			$args['tax_query']['offset'] = $settings['blog_posts_offset'];
		}
	}

	$wp_query = new \WP_Query($args);
	
	return $wp_query;
}
	
	
function wordpress_post_ajax_load() {
	$current_page = $_REQUEST['current_page'];
	$settings = array(
					'blog_thumbnail' => $_REQUEST['blog_thumbnail'],
					'blog_title' => $_REQUEST['blog_title'],
					'blog_categories' => $_REQUEST['blog_categories'],
					'blog_content' => $_REQUEST['blog_content'],
					'blog_author' => $_REQUEST['blog_author'],
					'blog_date' => $_REQUEST['blog_date'],
					'blog_comments' => $_REQUEST['blog_comments'],
					'blog_button' => $_REQUEST['blog_button'],
					'enable_navigation' => $_REQUEST['enable_navigation'],
					'enable_load_more' =>  $_REQUEST['enable_load_more'],
					'exclude_posts' => $_REQUEST['exclude_posts'],
					'posts_per_page' => $_REQUEST['posts_per_page'],
					'orderby' => $_REQUEST['orderby'],
					'order' => $_REQUEST['order'],
					'blog_posts_offset' => $_REQUEST['blog_posts_offset'],
					'post_categories' => $_REQUEST['post_categories'],
					'source' => $_REQUEST['source'],
					'image_size' => $_REQUEST['image_size'],
					'blog_type' => $_REQUEST['blog_type'],
					'preview_words' => $_REQUEST['preview_words']
				);


	//TODO: questa e` la funzione che fisicamente torna i post di istagram
	//bisogna copiarla per far tornare i posts di WP
	//vedi helper di Istagram
	//$wp_posts = get_posts($current_page);
	$wp_posts = sas_get_posts($settings, $current_page);
	$output = '';
	
	ob_start();

	if ( $wp_posts->have_posts() ) : 
		while ( $wp_posts->have_posts() ) : 
			$wp_posts->the_post();
			if ($_REQUEST['blog_type'] == 'grid'){
				include plugin_dir_path( __DIR__ ) .'widgets/content/blog-templates/grid.php';
			}
			else if ($_REQUEST['blog_type'] == 'classic') {
				include plugin_dir_path( __DIR__ ) .'widgets/content/blog-templates/classic.php';
			}
			else if ($_REQUEST['blog_type'] == 'list') {
				include plugin_dir_path( __DIR__ ) .'widgets/content/blog-templates/list.php';
			}
			
		endwhile;
	endif; 
	
	$output = ob_get_clean();

	echo $output;

	die();
}
	
		
		

add_action('wp_ajax_nopriv_wordpress_post_ajax_load', 'wordpress_post_ajax_load');
add_action('wp_ajax_wordpress_post_ajax_load', 'wordpress_post_ajax_load');


?>