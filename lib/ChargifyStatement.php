<?php

/******************************************************************************************

Bobby Kostadinov, 2013

Reference Documentation: http://docs.chargify.com/api-statements

******************************************************************************************/
 
class ChargifyStatement extends ChargifyBase
{
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************
	var $id ;
	var $subscription_id;
	var $opened_at;
	var $closed_at;
	var $settled_at;
	var $text_view;
	var $basic_html_view;
	var $html_view;
	var $future_payments;
	var $starting_balance_in_cents;
	var $ending_balance_in_cents;
	var $customer_first_name;
	var $customer_last_name;
	var $customer_organization;
	var $customer_shipping_address;
	var $customer_shipping_address2;
	var $customer_shipping_city;
	var $customer_shipping_state;
	var $customer_shipping_country;
	var $customer_shipping_zip;
	var $transactions;
	var $events ;
	var $created_at;
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

	
	public function getAllBySubscriptionId($subscription_id = null) {
		if ($subscription_id == null) {
			$subscription_id = $this->subscription_id;
		}
		return $this->connector->getAllStatementsBySubscriptionId($subscription_id);
	}
	
}