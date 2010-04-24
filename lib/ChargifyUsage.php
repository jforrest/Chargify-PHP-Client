<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-metered-components

class ChargifyUsage extends ChargifyBase 
{
	//******************************
	//** INPUT & OUTPUT VARIABLES **
	//******************************
	var $quantity;
	var $memo;
	
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************	
	var $id;
	
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
		return "usage";
	}
	
	public function create($subscription_id, $component_id) {
		return $this->connector->createMeteredComponent($subscription_id, $component_id, $this);
	}
	
	public function getAll($subscription_id, $component_id) {
		return $this->connector->getAllMeteredComponents($subscription_id, $component_id);
	}
	
	public function getAllByProductFamily($product_family_id) {
		return $this->connector->getAllMeteredComponentsByProductFamily($product_family_id);
	}
}?>