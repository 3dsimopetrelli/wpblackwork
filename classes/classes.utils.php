<?php


// not load directly
if ( ! defined( "ABSPATH" ) ) {
	die( "You shouldnt be here" );
}

class BW_utils {

	const UTILS_OPTIONSNAME = "saspcodes-settings";

	public static function getPostMeta($custom,$variable,$default) {
		$rvalue = $default;
		if ($custom && isset($custom[$variable]) && isset($custom[$variable][0])) {
			$rvalue = $custom[$variable][0];
		}
		return $rvalue;
	}

	public static function updatePostMeta($post_id,$variable) {
		if (isset($_POST[$variable])) {
			update_post_meta($post_id, $variable, $_POST[$variable]);
		}
	}

	public static function setPostMeta($post_id,$variable,$value) {
		update_post_meta($post_id, $variable, $value);
	}

	public static function getsettings() {
		$rvalue = array();

		if (get_option(self::UTILS_OPTIONSNAME)) {
			$rvalue = get_option(self::UTILS_OPTIONSNAME);
		}

		if (!isset($rvalue["table-programmazione-version"])) {
			$rvalue["table-programmazione-version"] = 0;
		}
		if (!isset($rvalue["table-immagini-version"])) {
			$rvalue["table-immagini-version"] = 0;
		}

		return $rvalue;
	}

	public static function setsettings($settings) {
		update_option(self::UTILS_OPTIONSNAME, $settings);
	}

	public static function sanitizeslug($slug) {
		$slug = str_replace("&amp;","",$slug);
		$slug = str_replace("|amp;","",$slug);
		$slug = preg_replace("/[^A-Za-z0-9 ]/", "-", $slug);
		while (strpos($slug, "  ")) {
			$slug = str_replace("  "," ",$slug);
		}
		$slug = trim($slug);
		$slug = str_replace(" ","-",$slug);
		while (strpos($slug, "--")) {
			$slug = str_replace("--","-",$slug);
		}


		if (substr($slug, -1)=="-") {
			$slug = substr($slug, 0, -1);
		}
		if (substr($slug, 0, 1)=="-") {
			$slug = substr($slug, 1);
		}

		$slug = strtolower($slug);

		return $slug;
	}

	public static function sanitizegeneral($valore) {
		//$valore = str_replace("|","&",$valore);
		$valore = urldecode($valore);
		return $valore;
	}

	public static function sanitizetitolo($titolo) {
		if (strpos($titolo, " --")>0) {
			$titolo = substr($titolo, 0, strpos($titolo, " --"));
		}
		if (strpos($titolo, " (")>0) {
			$titolo = substr($titolo, 0, strpos($titolo, " ("));
		}
		$titolo = str_replace("- Primatv","",$titolo);
		$titolo = str_replace("- Vm14","",$titolo);
		$titolo = str_replace("- 1^TV","",$titolo);
		$titolo = str_replace("- 1 ^ TV","",$titolo);

		return $titolo;
	}

    public static function term_insert($term_description, $taxonomy) {
    	$rvalue = 0;
    	$slug = BW_utils::sanitizeslug($term_description);

    	$term_description = ucfirst($term_description);

		$term = term_exists($term_description, $taxonomy);

    	if (!$term) {
			$term = wp_insert_term($term_description, $taxonomy, array(
				"description" => $term_description,
				"slug" => $slug
			));
    	}
		if ($term) {
			$rvalue = $term["term_id"];
		}

    	return $rvalue;
    }
}


?>