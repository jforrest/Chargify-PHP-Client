<?php 
//Base class for Chargify Data Objects.
class ChargifyBase {
	public function getXMLObject(&$xml = null) {
	  	if ($xml === null) {
			$xml = simplexml_load_string(sprintf("<?xml version='1.0' encoding='utf-8'?><%s></%s>", $this->getName(), $this->getName()));
	  	}
	  	foreach (get_object_vars($this) as $key=>$val) {
	  		if ($key != 'connector') {
		  		if (is_object($val) && method_exists($val, "getXMLObject")) {
		  			$node = $xml->addChild($key);
		  			$val->getXMLObject($node);	  			
		  		} elseif ($val !== null) {
		  			$xml->addChild($key,htmlentities($val, ENT_QUOTES));
		  		}
	  		}
	  	}
	  	return $xml;	  	
	}
	
	public function getXML() {
		$xml = $this->getXMLObject();
		return $xml->asXML();
	}
	
	public function getJSON() {
		return sprintf('{"%s":%s}', $this->getName(), json_encode($this->getXMLObject()));
	}
}
?>