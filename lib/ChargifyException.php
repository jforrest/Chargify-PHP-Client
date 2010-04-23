<?php
class ChargifyException extends Exception {}

class ChargifyConnectionException extends ChargifyException {}

class ChargifyValidationException extends ChargifyException {
	var $errors;
	var $http_code;
	
	public function ChargifyValidationException($http_code, $error) {
		$this->http_code = $http_code;		

		$message = '';
		$this->errors = array();
		foreach ($error as $key=>$value) {
			if ($key == 'error') {
				$this->errors[] = $value;
				$message .= $value . ' ';
			}
		}

		parent::__construct($message, intval($http_code));
	}
}

class ChargifyNotFoundException extends ChargifyException {
	var $errors;
	var $http_code;
	
	public function ChargifyNotFoundException($http_code, $error) {
		$this->http_code = $http_code;		

		$message = '';
		$this->errors = array();
		foreach ($error as $key=>$value) {
			if ($key == 'error') {
				$this->errors[] = $value;
				$message .= $value . ' ';
			}
		}

		parent::__construct($message, intval($http_code));
	}	
}

class ChargifyError
{
	var $field;
	var $message;
}
?>