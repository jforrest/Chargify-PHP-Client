<?php
 
//Reference Documentation: http://support.chargify.com/faqs/api/api-prorated-upgrades-downgrades

class ChargifyMigration
{
	//******************************
	//**** INPUT ONLY VARIABLES ****
	//******************************
	var $product_handle;
	var $product_id;
	var $include_trial;
	var $include_initial_charge;

	public function __construct(SimpleXMLElement $product_xml_node = null)
	{
		if ($product_xml_node) {
	    //Load object dynamically and convert SimpleXMLElements into strings
	    foreach($product_xml_node as $key => $element) { 
				$this->$key = (string)$element; 
	    }
		}
	}

	//thanks to Andrew Watson (https://github.com/andrewwatson) for helping improve this functionality.
	public function getXML() {
		if ($this->product_id) {
			return sprintf("<?xml version='1.0' encoding='utf-8'?><migration><product_id>%s</product_id><include_trial>%s</include_trial><include_initial_charge>%s</include_initial_charge></migration>",
				$this->product_id, $this->include_trial ? '1' : '0', $this->include_initial_charge ? '1' : '0');
		} elseif ($this->product_handle) {
			return sprintf("<?xml version='1.0' encoding='utf-8'?><migration><product_handle>%s</product_handle><include_trial>%s</include_trial><include_initial_charge>%s</include_initial_charge></migration>",
				$this->product_handle, $this->include_trial ? '1' : '0', $this->include_initial_charge ? '1' : '0');
		}
	}
	
	public function getXMLObject() {
		return simplexml_load_string($this->getXML());
	}
	
	public function getJSON() {
		if ($this->product_id) {
			return '{"migration": {
						"product_id": "'.$this->product_id.'"
						"include_trial": "'.($this->include_trial ? '1' : '0').'"
						"include_initial_charge": "'.($this->include_initial_charge ? '1' : '0').'"
					}}';
		} elseif ($this->product_handle) {
			return '{"migration": {
						"product_handle": "'.$this->product_handle.'"
						"include_trial": "'.($this->include_trial ? '1' : '0').'"
						"include_initial_charge": "'.($this->include_initial_charge ? '1' : '0').'"
					}}';
		}		
	}
}