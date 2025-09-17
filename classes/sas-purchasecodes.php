<?php
// not load directly
if ( ! defined( "ABSPATH" ) ) {
	die( "You shouldnt be here" );
}

require_once "classes.utils.php";
require_once "classes.wpapi.purchasecodes.php";

class BW_pcodes extends WP_Widget {

	private $_objApiPurchaseCode = null;

	public function __construct() {
		$_objApiPurchaseCode = BW_wpapi_purchasecodes::instance();
	}
}

$__objBWpcodes = new BW_pcodes();


?>