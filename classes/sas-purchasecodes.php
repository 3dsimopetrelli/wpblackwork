<?php
// not load directly
if ( ! defined( "ABSPATH" ) ) {
	die( "You shouldnt be here" );
}

require_once "classes.utils.php";
require_once "classes.wpapi.purchasecodes.php";

class SAS_pcodes extends WP_Widget {

	private $_objApiPurchaseCode = null;

	public function __construct() {
		$_objApiPurchaseCode = SAS_wpapi_purchasecodes::instance();
	}
}

$__objSASpcodes = new SAS_pcodes();


?>