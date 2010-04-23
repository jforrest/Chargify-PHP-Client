<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-products

class ChargifyProductFamily extends ChargifyBase 
{
	//******************************
	//*** OUTPUT ONLY VARIABLES ****
	//******************************
	var $accounting_code;
	var $description;
	var $handle;
	var $id;
	var $name;
	
	public function __construct(SimpleXMLElement $product_xml_node = null)
	{
		if ($product_xml_node) {
			//Load object dynamically and convert SimpleXMLElements into strings
			foreach($product_xml_node as $key => $element) { 
				$this->$key = (string)$element; 
			}
		}
	}

	protected function getName() {
		return "product_family";
	}
}?>