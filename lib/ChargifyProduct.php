<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-products

class ChargifyProduct extends ChargifyBase 
{
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************	
	var $price_in_cents;
	var $name;
	var $handle;
	var $description;
	var $id;
	var $product_family;
	var $accounting_code;
	var $interval_unit;
	var $interval;
	var $initial_charge_in_cents;
	var $trial_price_in_cents;
	var $trial_interval;
	var $trial_interval_unit;
	var $expiration_interval;
	var $expiration_interval_unit;
	var $return_url;
	var $return_params;
	var $require_credit_card;
	var $request_credit_card;
	var $created_at;
	var $updated_at;
 	var $archived_at;
 	
 	private $connector;
	public function __construct(SimpleXMLElement $product_xml_node = null, $test_mode = false)
	{
		$this->connector = new ChargifyConnector($test_mode);
		if ($product_xml_node) {
	    //Load object dynamically and convert SimpleXMLElements into strings
	    foreach($product_xml_node as $key => $element) { 
			if($key == 'product_family') { 
				$this->product_family = new ChargifyProductFamily($element); 
			} else { 
				$this->$key = (string)$element; 
			}
	    }
		}
	}

  	public function getPriceInDollars() { return number_format($this->price_in_cents / 100, 0); }

  	protected function getName() {
  		return "product";
  	}
  	
  	public function getAllProducts() {
  		return $this->connector->getAllProducts();
  	}
  	
  	public function getByID() {
		return $this->connector->getProductByID($this->id);
  	}
  	
  	public function getByHandle() {
  		return $this->connector->getProductByHandle($this->handle);
  	}
}