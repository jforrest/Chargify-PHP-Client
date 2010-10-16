<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-subscriptions
class ChargifySubscription
{
	//******************************
	//**** INPUT ONLY VARIABLES ****
	//******************************
	var $customer_id;
	var $customer_reference;
	var $credit_card_attributes;
	var $customer_attributes;
	var $product_handle;
	var $product_id;
	var $coupon_code;
	var $next_billing_at;

	//******************************
	//** INPUT & OUTPUT VARIABLES **
	//******************************
	var $customer;
	var $credit_card;
	var $cancellation_message;
	var $components;
	
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************	
	var $product;
	var $id;
	var $state;
	var $balance_in_cents;
	var $current_period_started_at;
	var $current_period_ends_at;
	var $trial_started_at;
	var $trial_ended_at;
	var $activated_at;
	var $expires_at;
	var $created_at;
	var $updated_at;
	
	private $connector;
	public function __construct(SimpleXMLElement $subscription_xml_node = null, $test_mode = false)
	{
		$this->connector = new ChargifyConnector($test_mode);
		
		if ($subscription_xml_node) {
			//Load object dynamically and convert SimpleXMLElements into strings
			foreach($subscription_xml_node as $key => $element)
			{
			  	if($key == 'customer' || $key == 'customer_attributes') { 
		  			$this->customer = new ChargifyCustomer($element); 
			  	} else if($key == 'product') { 
			  		$this->product = new ChargifyProduct($element); 
			  	} else if($key == 'credit_card' || $key == 'credit_card_attributes') { 
			  		$this->credit_card = new ChargifyCreditCard($element);
			  	} elseif ($key == 'components') {
			  		$this->components = array();
			  		foreach ($element as $component) {
			  			$this->components[] = new ChargifyQuantityBasedComponent($component);
			  		}
			  	} else {
			  		$this->$key = (string)$element; 
			  	}
			}
		}
	}
	
	protected function format_timestamp($format, $timestamp)
	{
		$temp = explode('T', $timestamp);
		$temp = strtotime($temp[0]);
		
		return date($format, $temp);
	}
	
	public function getCurrentPeriodStart($date_format = NULL)
	{
		if($date_format == NULL) { 
			return $this->current_period_started_at; 
		} else { 
			return $this->format_timestamp($date_format, $this->current_period_started_at); 
		}
	}
	
	public function getCreatedAt($date_format = NULL)
	{
		if($date_format == NULL) { 
			return $this->created_at; 
		} else { 
			return $this->format_timestamp($date_format, $this->created_at); 
		}
	}
	
	public function getXML() {
		$xml = $this->getXMLObject();
	  	return $xml->asXML();
	}
	
	public function getXMLObject(&$xml = null) {
	  	if ($xml === null) {
			$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><subscription></subscription>");
	  	}
	  	foreach (get_object_vars($this) as $key=>$val) {
		  	if($key == 'customer' || $key == 'product' || $key == 'credit_card' || $key == 'credit_card_attributes' || $key == 'customer_attributes') { 
		  		if ($val) {
		  			$node = $xml->addChild($key);
		  			$val->getXMLObject($node);
		  		}
		  	} elseif ($key == 'components') {
		  		if (is_array($this->components)) {
			  		$node = $xml->addChild($key);
					foreach ($this->components as $component) {
						$component->getXMLObject($node);
					}
		  		}
	  		} elseif ($key != 'connector') {
	  			if ($val) {
	  				$xml->addChild($key,htmlentities($val, ENT_QUOTES));
	  			}
	  		}
	  	}
	  	return $xml;
	}
	
	public function getJSON() {
		return sprintf('{"subscription":%s}',json_encode($this->getXMLObject()));
	}
	
	public function create() {
		return $this->connector->createSubscription($this);
	}
	
	public function getAll($page = 1, $per_page = 2000) {
		return $this->connector->getSubscriptions($page, $per_page);
	}
	
	public function getByCustomerID($customer_id = null) {
		if ($customer_id == null) {
			$customer_id = $this->customer_id;
		}
		return $this->connector->getSubscriptionsByCustomerID($customer_id);
	}
	
	public function getByID($subscription_id = null) {
		if ($subscription_id == null) {
			$subscription_id = $this->id;
		}
		return $this->connector->getSubscriptionsByID($subscription_id);
	}
	
	public function updateProduct($chargify_product = null) {
		if ($chargify_product == null) {
			$chargify_product = $this->product;
		}
		return $this->connector->updateSubscriptionProduct($this->id, $chargify_product);
	}
	
	public function updateProductProrated($chargify_product = null) {
		if ($chargify_product == null) {
			$chargify_product = $this->product;
		}
		return $this->connector->updateSubscriptionProductProrated($this->id, $chargify_product);
	}	
	
	public function updateCreditCard($credit_card_attributes = null) {
		if ($credit_card_attributes == null) {
			$credit_card_attributes = $this->credit_card_attributes;
		}
		return $this->connector->updateSubscriptionCreditCard($this->id, $credit_card_attributes);
	}
	
	public function cancel($cancellation_message = null) {
		if ($cancellation_message == null) {
			$cancellation_message = $this->cancellation_message;
		}
		return $this->connector->cancelSubscription($this->id, $cancellation_message);
	}
	
	public function reactivate() {
		return $this->connector->reactivateSubscription($this->id);
	}
	
	public function resetBalance() {
		return $this->connector->resetSubscriptionBalance($this->id);
	}
}?>