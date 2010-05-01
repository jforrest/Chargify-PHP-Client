<?php
 
//Reference Documentation: http://support.chargify.com/faqs/technical/quantity-based-components

class ChargifyQuantityBasedComponent extends ChargifyBase 
{
	//******************************
	//** INPUT & OUTPUT VARIABLES **
	//******************************
	var $allocated_quantity;
	var $component_id;
	
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************	
	var $name;
	var $kind;
	var $subscription_id;
	var $pricing_scheme;
	var $unit_name;
		
	private $connector;
	public function __construct(SimpleXMLElement $usage_xml_node = null, $test_mode = false)
	{
		$this->connector = new ChargifyConnector($test_mode);
		if ($usage_xml_node) {
			//Load object dynamically and convert SimpleXMLElements into strings
			foreach($usage_xml_node as $key => $element) { 
				$this->$key = (string)$element; 
			}
		}
	}
	
	protected function getName() {
		return "component";
	}

	//this function has special needs, so needs to override the base implementation.
	public function getXMLObject(&$xml = null) {
		if ($xml === null) {
	  		$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><component></component>");
		} else {
			$node = $xml->addChild('component');
		}
	  	foreach (get_object_vars($this) as $key=>$val) {
	  		if ($key != 'connector' && $val !== null) {
	  			if (isset($node)) {
	  				$node->addChild($key, htmlentities($val, ENT_QUOTES));
	  			} else {
	  				$xml->addChild($key,htmlentities($val, ENT_QUOTES));
	  			}
	  		}
	  	}
	  	return $xml;
	}
	
	public function create($subscription_id, $component_id) {
		return $this->connector->createQuantityBasedComponent($subscription_id, $component_id, $this);
	}
	
	public function update($subscription_id, $component_id) {
		return $this->connector->updateQuantityBasedComponent($subscription_id, $component_id, $this);	
	}
	
	public function getAll($subscription_id, $component_id) {
		return $this->connector->getAllQuantityBasedComponents($subscription_id, $component_id);
	}

}?>