<?php

// not load directly
if ( ! defined( "ABSPATH" ) ) {
	die( "You shouldnt be here" );
}


class SAS_wpapi_purchasecodes {

	private static $_instance;
	const PURCHASECODES_TAXONOMY = "purchasecodes";
	const META_DETAILS = "meta_details";

	public function __construct() {
		add_action("rest_api_init", array($this, "rest_api_init"));
		add_action("init", array($this, "init"));
		add_action("admin_init", array($this, "admin_init"));
		add_action("save_post", array($this, "save_post"));
	}

    public static function instance() {
    	if (null==self::$_instance) self::$_instance = new self();
    	return self::$_instance;
    }

	public function admin_init() {
		add_meta_box(self::META_DETAILS, esc_html__("Dettagli", "sasthemes"), array(&$this, "meta_details"), self::PURCHASECODES_TAXONOMY, "side", "low");
	}

	public function rest_api_init() {
		register_rest_route("sparrowandsnow/v1", "purchasecodes/insert", array(
			"methods" => WP_REST_Server::CREATABLE,
			"callback" => array($this, "purchasecode_insert"),
			"permission_callback" => __return_true(  )
		));
	}

	public function init() {
		$labels = array(
			"name" => esc_html__("Purchase Codes", "sasthemes"),
			"singular_name" => esc_html__("Purchase Codes", "sasthemes"),
			"add_new" => esc_html__("Add New Purchase Codes", "sasthemes"),
			"add_new_item" => esc_html__("Add New Purchase Codes", "sasthemes"),
			"edit_item" => esc_html__("Edit Purchase Codes", "sasthemes"),
			"new_item" => esc_html__("New Purchase Codes", "sasthemes"),
			"view_item" => esc_html__("View Purchase Codes", "sasthemes"),
			"search_items" => esc_html__("Search Purchase Codes", "sasthemes"),
			"not_found" =>  esc_html__("No Purchase Codes found", "sasthemes"),
			"not_found_in_trash" => esc_html__("No Purchase Codes found in Trash", "sasthemes"),
			"parent_item_colon" => ""
		);

		$args = array(
			"labels" => $labels,
			
			"public" => false,
			"publicly_queryable" => false, //Post privato
			"exclude_from_search" => true,
			
			"has_archive" => false,
			"query_var" => false,
		 

			"show_ui" => true,
			"capability_type" => "post",
			"show_in_nav_menus" => true,
			"hierarchical" => false,
			
			"menu_position" => 5,
			"menu_icon" => "dashicons-format-quote",
			"supports" => array("title","editor","thumbnail","excerpt","custom-fields"),
		);
		register_post_type(self::PURCHASECODES_TAXONOMY,$args);

	}

    public function purchasecode_insert($request) {
		global $wpdb;
		$rvalue = array();
		$categoria_id = 0;
		$rating_id = 0;
		$canale_id = 0;

		$nuovo = 0;
		$data = $request->get_params();

		$purchase_code = $data["purchase_code"];
		$email = $data["email"];
		$date_created = $data["date_created"];
		$date_expires = $data["date_expires"];
		$theme = $data["theme"];
		$domain = $data["domain"];

		$post = get_page_by_title($purchase_code, OBJECT, "post");

		if ($post) {
			$post_id = $post->ID;
		} else {
			$post_content = "purchase code: ".$purchase_code."\n";
			$post_content .= "email contatto: ".$email."\n";
			$post_content .= "dominio: ".$domain."\n";
			$post_content .= "tema: ".$theme."\n";
			$post_content .= "\n";
			$post_content .= "data creazione: ".$date_created."\n";
			$post_content .= "data fine supporto: ".$date_expires."\n";

			$post_id = wp_insert_post(array(
				"post_type" => self::PURCHASECODES_TAXONOMY,
				"post_title" => $purchase_code,
				"post_content" => $post_content,
				"post_status" => "publish",
				"post_name" => $purchase_code,
				"comment_status" => "closed",
				"ping_status" => "closed",
			));

			SAS_utils::setPostMeta($post_id,"purchasecode-email",$data["email"]);
			SAS_utils::setPostMeta($post_id,"purchasecode-date_created",$data["date_created"]);
			SAS_utils::setPostMeta($post_id,"purchasecode-date_expires",$data["date_expires"]);
			SAS_utils::setPostMeta($post_id,"purchasecode-domain",$data["domain"]);
			SAS_utils::setPostMeta($post_id,"purchasecode-theme",$data["theme"]);
		}

		$rvalue["post_id"] = $post_id;

    	return rest_ensure_response($rvalue);
    }

	public function meta_details() {
		global $post;

		if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE ) return $post_id;
		$custom = get_post_custom($post->ID);

		$email = SAS_utils::getPostMeta($custom,"purchasecode-email","");
		$date_created = SAS_utils::getPostMeta($custom,"purchasecode-date_created","");
		$date_expires = SAS_utils::getPostMeta($custom,"purchasecode-date_expires","");
		$domain = SAS_utils::getPostMeta($custom,"purchasecode-domain","");
		$theme = SAS_utils::getPostMeta($custom,"purchasecode-theme","");
	?>
		<p class="label"><label style="font-weight: bold;"><?php echo esc_html__("Email", "tiam") ?></label></p>
		<div class="purchasecode-email">
			<input type="text" name="purchasecode-email" id="purchasecode-email" value="<?php echo esc_attr($email); ?>"/>
		</div>

		<p class="label"><label style="font-weight: bold;"><?php echo esc_html__("Dominio", "tiam") ?></label></p>
		<div class="purchasecode-domain">
			<input type="text" name="purchasecode-domain" id="purchasecode-domain" value="<?php echo esc_attr($domain); ?>"/>
		</div>

		<p class="label"><label style="font-weight: bold;"><?php echo esc_html__("Tema", "tiam") ?></label></p>
		<div class="purchasecode-theme">
			<input type="text" name="purchasecode-theme" id="purchasecode-theme" value="<?php echo esc_attr($theme); ?>"/>
		</div>

		<p class="label"><label style="font-weight: bold;"><?php echo esc_html__("Data creazione", "tiam") ?></label></p>
		<div class="purchasecode-date_created">
			<input type="text" name="purchasecode-date_created" id="purchasecode-date_created" value="<?php echo esc_attr($date_created); ?>"/>
		</div>

		<p class="label"><label style="font-weight: bold;"><?php echo esc_html__("Data fine supporto", "tiam") ?></label></p>
		<div class="purchasecode-date_expires">
			<input type="text" name="purchasecode-date_expires" id="purchasecode-date_expires" value="<?php echo esc_attr($date_expires); ?>"/>
		</div>
    <?php
	}

	public function save_post(){
		global $post;

		if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE){
			return $post_id;
		} else if ($post) {
			$post_type = get_post_type($post->ID);

			if ($post_type==self::PURCHASECODES_TAXONOMY) {
				SAS_utils::updatePostMeta($post->ID,"purchasecode-email");
				SAS_utils::updatePostMeta($post->ID,"purchasecode-date_created");
				SAS_utils::updatePostMeta($post->ID,"purchasecode-date_expires");
				SAS_utils::updatePostMeta($post->ID,"purchasecode-domain");
				SAS_utils::updatePostMeta($post->ID,"purchasecode-theme");
			}
		}
	}

}


?>