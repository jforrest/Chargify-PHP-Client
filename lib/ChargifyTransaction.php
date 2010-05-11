<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-transactions

class ChargifyTransaction extends ChargifyBase
{
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************	
	var $type;
	var $id;
	var $amount_in_cents;
	var $created_at;
	var $ending_balance_in_cents;
	var $memo;
	var $subscription_id;
	var $product_id;
	var $success;	

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
		return "transaction";
	}
	
	public function getAll($options = array()) {
		return $this->connector->getAllTransactions($options);
	}
	
	public function getBySubscriptionID($subscription_id, $options = array()) {
		return $this->connector->getTransactionsBySubscriptionID($subscription_id, $options);
	}
}