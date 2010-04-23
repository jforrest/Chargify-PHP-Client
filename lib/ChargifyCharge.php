<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-charges

class ChargifyCharge extends ChargifyBase 
{
	//******************************
	//**** INPUT ONLY VARIABLES ****
	//******************************
	var $amount;

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
		return "charge";
	}
	
	public function create($subscription_id) {
		return $this->connector->createCharge($subscription_id, $this);
	}
	
	public function createByAmount($subscription_id) {
		return $this->connector->createChargeByAmount($subscription_id, $this->amount, $this->memo);
	}
	
	public function createByAmountInCents($subscription_id) {
		return $this->connector->createChargeByAmountInCents($subscription_id, $this->amount_in_cents, $this->memo);
	}	
}