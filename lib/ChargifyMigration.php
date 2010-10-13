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

	public function getXML() {
		if ($this->product_id) {
			return "<?xml version='1.0' encoding='utf-8'?><migration><product_id>{$this->product_id}</migration></product_id>";
		} elseif ($this->product_handle) {
			return "<?xml version='1.0' encoding='utf-8'?><migration><product_handle>{$this->product_handle}</migration></product_handle>";
		}
	}
	
	public function getXMLObject() {
		return simplexml_load_string($this->getXML());
	}
	
	public function getJSON() {
		if ($this->product_id) {
			return '{"product_id": "'.$this->product_id.'"}';
		} elseif ($this->product_handle) {
			return '{"product_handle": "'.$this->product_handle.'"}';
		}		
	}
}
