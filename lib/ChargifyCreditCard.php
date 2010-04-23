<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-subscriptions
 
class ChargifyCreditCard extends ChargifyBase
{
	//******************************
	//**** INPUT ONLY VARIABLES ****
	//******************************		
	var $full_number;
	var $cvv;
	var $billing_address;
	var $billing_city;
	var $billing_state;
	var $billing_zip;
	var $billing_country;	
	
	//******************************	
	//** INPUT & OUTPUT VARIABLES **
	//******************************
	var $expiration_month;
	var $expiration_year;
	var $first_name;
	var $last_name;
		
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************
	var $card_type;
	var $masked_card_number;	

	
	public function __construct(SimpleXMLElement $cc_xml_node = null)
	{
		if ($cc_xml_node) {
			//Load object dynamically and convert SimpleXMLElements into strings
			foreach($cc_xml_node as $key => $element) { $this->$key = (string)$element; }
		}
	}
	
	protected function getName() {
		return "credit_card";
	}	
}?>