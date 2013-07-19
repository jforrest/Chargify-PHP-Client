<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-adjustment

class ChargifyAdjustment extends ChargifyBase
{
	//******************************
	//**** INPUT ONLY VARIABLES ****
	//******************************
	var $amount;
    var $adjustment_method;

	//******************************
	//** INPUT & OUTPUT VARIABLES **
	//******************************	
	var $memo;

	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************	
	var $success;
	var $amount_in_cents;

	private $connector;
	public function __construct(SimpleXMLElement $product_xml_node = null, $test_mode = false)
	{
		$this->connector = new ChargifyConnector($test_mode);
		if ($product_xml_node) {
	    //Load object dynamically and convert SimpleXMLElements into strings
	    foreach($product_xml_node as $key => $element) { 
				$this->$key = (string)$element; 
	    }
		}
	}
	
	protected function getName() {
		return "adjustment";
	}
	
	public function create($subscription_id) {
		return $this->connector->createAdjustment($subscription_id, $this);
	}
	
	public function createByAmount($subscription_id) {
		return $this->connector->createAdjustmentByAmount($subscription_id, $this->amount, $this->memo);
	}
}
