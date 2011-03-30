<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-coupons
 
class ChargifyCoupon extends ChargifyBase
{
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************
	var $amount_in_cents;
	var $code;
	var $created_at;
	var $description;
	var $end_date;
	var $id;
	var $name;
	var $percentage;
	var $product_family_id;
	var $start_date;
	var $updated_at;
	
	private $connector;
	
	public function __construct(SimpleXMLElement $cc_xml_node = null, $test_mode = false)
	{
		$this->connector = new ChargifyConnector($test_mode);
		if ($cc_xml_node) {
			//Load object dynamically and convert SimpleXMLElements into strings
			foreach($cc_xml_node as $key => $element) { $this->{str_ireplace("-","_",$key)} = (string)$element; }
		}
	}
	
	protected function getName() {
		return "coupon";
	}
	
	public function getByID($product_family_id = null, $coupon_id = null) {
		if ($product_family_id == null) { 
			$product_family_id = $this->product_family_id; 
		}
		if ($coupon_id == null) {
			$coupon_id = $this->id;
		}
		return $this->connector->getCouponByID($product_family_id, $coupon_id);		
	}
	
	function getByCode($product_family_id = null, $coupon_code = null) {
		if ($product_family_id == null) {
			$product_family_id = $this->product_family_id;
		}
		if ($coupon_code == null) {
			$coupon_code = $this->code;
		}
	    return $this->connector->getCouponByCode($product_family_id, $coupon_code);
	}
}?>