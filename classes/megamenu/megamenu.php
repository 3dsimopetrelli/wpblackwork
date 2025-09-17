<?php

class BW_Plugin_Builder_Megamenu {
    public static $instance;

    private $content = '';
    public $megamenu_id = 0;

    public static function getInstance() {
        if (!isset(self::$instance) && !(self::$instance instanceof BW_Plugin_Builder_Megamenu)) {
            self::$instance = new BW_Plugin_Builder_Megamenu();
        }
        return self::$instance;
    }

    public function __construct() {
        $this -> register_cpt();
        $this -> add_featured_image_col();
    }

    public function register_cpt() {
        $labels = array(
            'name'               => __('Megamenu', "sas"),
            'singular_name'      => __('Megamenu', "sas"),
            'add_new'            => __('Add New Megamenu', "sas"),
            'add_new_item'       => __('Add New Megamenu', "sas"),
            'edit_item'          => __('Edit Megamenu', "sas"),
            'new_item'           => __('New Megamenu', "sas"),
            'view_item'          => __('View Megamenu', "sas"),
            'search_items'       => __('Search Megamenus', "sas"),
            'not_found'          => __('No Megamenus found', "sas"),
            'not_found_in_trash' => __('No Megamenus found in Trash', "sas"),
            'parent_item_colon'  => __('Parent Megamenu:', "sas"),
            'menu_name'          => __('Megamenu Builder', "sas"),
        );

        $args = array(
            'labels'              => $labels,
            'hierarchical'        => true,
            'description'         => __('List Megamenu', "sas"),
            'supports'            => array('title', 'editor', 'thumbnail'),
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'query_var'           => true,
            'can_export'          => true,
            'rewrite'             => true,
            'capability_type'     => 'post'
        );
        register_post_type('megamenu_builder', $args);
    }

    public function add_featured_image_col() {
        add_filter('manage_megamenu_builder_posts_columns', [ $this, 'columns_head' ] );
        add_action('manage_megamenu_builder_posts_custom_column', [ $this, 'columns_content' ], 10, 2);
        add_filter('manage_megamenu_builder_posts_columns', [ $this, 'columns_order' ]);
        add_action('admin_head', [ $this, 'columns_width' ]);
    }

    public function get_featured_image($post_ID) {
        $post_thumbnail_id = get_post_thumbnail_id($post_ID);
        if ($post_thumbnail_id) {
            $post_thumbnail_img = wp_get_attachment_image_src($post_thumbnail_id, 'featured_preview');
            return $post_thumbnail_img[0];
        }
    }

    public function columns_head($defaults) {
        $defaults['featured_image'] = 'Featured Image';
        return $defaults;
    }
    
    public function columns_content($column_name, $post_ID) {
        if ($column_name == 'featured_image') {
            $post_featured_image = $this->get_featured_image($post_ID);
            if ($post_featured_image) {
                echo '<img src="' . $post_featured_image . '" />';
            }
        }
    }

    public function columns_order($columns) {
        $n_columns = array();
        $move = 'featured_image'; // what to move
        $before = 'title'; // move before this
        foreach($columns as $key => $value) {
            if ($key==$before){
            $n_columns[$move] = $move;
            }
            $n_columns[$key] = $value;
        }
        return $n_columns;
    }

    public function columns_width() {
        echo '<style type="text/css">';
        echo '.column-featured_image { width:200px !important; overflow:hidden }';
        echo '</style>';
    }

    public function get_megamenu_options() {
        if (!isset($this->megamenu_id->ID) || $this->megamenu_id->ID == 0) {
            return;
        }
        return get_post_meta( $this->megamenu_id->ID, 'sas_megamenu_options', true );
    }

}

BW_Plugin_Builder_Megamenu::getInstance();
