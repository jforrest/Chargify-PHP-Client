<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-credits
 
class ChargifyCredit extends ChargifyBase
{
	//******************************	
	//** INPUT & OUTPUT VARIABLES **
	//******************************
	var $amount;
	var $amount_in_cents;
	var $memo;
	
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************
	var $success;
	var $id;
	var $ending_balance_in_cents;
	var $type;
	var $subscription_id;
	var $product_id;
	var $created_at;

	private $connector;
	public function __construct(SimpleXMLElement $cc_xml_node = null, $test_mode = false)
	{
		$this->connector = new ChargifyConnector($test_mode);
		if ($cc_xml_node) {
			//Load object dynamically and convert SimpleXMLElements into strings
			foreach($cc_xml_node as $key => $element) { $this->$key = (string)$element; }
		}
	}
	
	protected function getName() {
		return "credit";
	}	
}?>