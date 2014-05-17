<?php

/******************************************************************************************

Jason Forrest, 2010

Reference Documentation: http://support.chargify.com/faqs/api/api-authentication

******************************************************************************************/
class ChargifyConnector
{
	protected $api_key;

	/**
	 * your chargify domain, e.g. if you have [your-domain].chargify.com, then enter "your-domain" only.
	 *
	 * @var string
	 */
	protected $domain;

	protected $active_api_key;

	protected $username;
	protected $password;

	/**
	 *
	 * @param string $api_key
	 * @param string $domain
	 */
	public function __construct($api_key, $domain = null) {
	  $this->domain   = $domain;
    $this->api_key  = $api_key;
    $this->username = $api_key;
    $this->password = 'x';
	}

	private function sendRequest($uri, $format = 'XML', $method = 'GET', $data = '') {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://" . $this->domain . ".chargify.com" . $uri);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($format == 'XML') {
	        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	            'Content-Type: application/xml',
	            'Accept: application/xml'
	        ));
        } else {
	        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	            'Content-Type: application/json',
	            'Accept: application/json'
	        ));
        }

        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);

        $method = strtoupper($method);
        if($method == 'POST')
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    	else if ($method == 'PUT')
    	{
	        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	}
        else if($method != 'GET')
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = new StdClass();
        $result->response = curl_exec($ch);
        $result->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result->meta = curl_getinfo($ch);

        $curl_error = ($result->code > 0 ? null : curl_error($ch) . ' (' . curl_errno($ch) . ')');

        curl_close($ch);

        if ($curl_error) {
            throw new ChargifyConnectionException('An error occurred while connecting to Chargify: ' . $curl_error);
        }

        return $result;
	}

	/****************************************************
	 *********       CUSTOMER FUNCTIONS       ***********
	 ****************************************************/

	public function retrieveAllCustomers($page_num = 1, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
	  	$base_url = '/customers' . $extension;

  		$customers = $this->sendRequest($base_url . '?page=' . $page_num, $format);
	    return $customers->response;
	}

	public function retrieveCustomerByID($id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/customers/{$id}" . $extension;

		$customer = $this->sendRequest($base_url, $format);
		if ($customer->code == 200) {
			return $customer->response;
		} elseif ($customer->code == 404) {
			$errors = $this->_createSimpleXmlElement($customer->response);
			throw new ChargifyNotFoundException($customer->code, $errors);
		}
	}

	public function retrieveCustomerByReferenceID($reference_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/customers/lookup{$extension}?reference=". urlencode($reference_id);

		$customer = $this->sendRequest($base_url, $format);
		if ($customer->code == 200) {
			return $customer->response;
		} elseif ($customer->code == 404) {
			throw new ChargifyNotFoundException($customer->code, array());
		}
	}

	public function requestCreateCustomer($customerRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/customers" . $extension;

		$customer = $this->sendRequest($base_url, $format, 'POST', $customerRequest);

		if ($customer->code == 201) { //CREATED
			return $customer->response;
		} elseif ($customer->code == 422) { //UNPROCESSABLE ENTITY
			$errors = $this->_createSimpleXmlElement($customer->response);
			throw new ChargifyValidationException($customer->code, $errors);
		}
	}

	public function createCustomer($chargify_customer) {
		$xml = $this->requestCreateCustomer($chargify_customer->getXML());
		$customer = $this->_createSimpleXmlElement($xml);
		return new ChargifyCustomer($this, $customer);
	}

	public function requestUpdateCustomer($customer_id, $customerRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/customers/{$customer_id}" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'PUT', $customerRequest);

		if ($xml->code == 200) { //CREATED
			return $xml->response;
		} elseif ($xml->code == 422) { //UNPROCESSABLE ENTITY
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		} elseif ($xml->code == 404) { //NOT FOUND
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyNotFoundException($xml->code, $errors);
		}
	}

	public function updateCustomer($chargify_customer) {
		$xml = $this->requestUpdateCustomer($chargify_customer->id, $chargify_customer->getXML());
		$customer = $this->_createSimpleXmlElement($xml);
		return new ChargifyCustomer($this, $customer);
	}

	//delete isn't supported yet.
	public function deleteCustomer($customer_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/customers/{$customer_id}" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'DELETE');

		if ($xml->code == 403) { //FORBIDDEN
			throw new ChargifyException('DELETE is not supported through the Chargify API.',$xml->code);
		} else {
			return true;
		}
	}

	public function getAllCustomers($page_num = 1)
	{
	    $xml = $this->retrieveAllCustomers($page_num);
	    $all_customers = $this->_createSimpleXmlElement($xml);
	    $customer_objects = array();

	    foreach($all_customers as $customer)
	    {
	      $temp_customer = new ChargifyCustomer($this, $customer);
	      array_push($customer_objects, $temp_customer);
	    }

	    return $customer_objects;
	}

	public function getCustomerByID($id)
	{
	    $xml = $this->retrieveCustomerByID($id);
	    $customer_xml_node = $this->_createSimpleXmlElement($xml);
	    $customer = new ChargifyCustomer($this, $customer_xml_node);

	    return $customer;
	}

	public function getCustomerByReferenceID($reference_id) {
	    $xml = $this->retrieveCustomerByReferenceID($reference_id);
	    $customer_xml_node = $this->_createSimpleXmlElement($xml);
	    $customer = new ChargifyCustomer($this, $customer_xml_node);

	    return $customer;
	}

	/****************************************************
	 ************     PRODUCT FUNCTIONS     *************
	 ****************************************************/

	public function retrieveAllProducts($format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
  		$base_url = "/products" . $extension;

	  	$products = $this->sendRequest($base_url, $format);
		return $products->response;
	}

	public function retrieveProductByID($product_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
  		$base_url = "/products/{$product_id}" . $extension;

	  	$product = $this->sendRequest($base_url, $format);
		return $product->response;
	}

	public function retrieveProductByHandle($product_handle, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
  		$base_url = "/products/handle/{$product_handle}" . $extension;

	  	$product = $this->sendRequest($base_url, $format);
		return $product->response;
	}

	public function getAllProducts()
	{
	    $xml = $this->retrieveAllProducts();
	    $all_products = $this->_createSimpleXmlElement($xml);
	    $product_objects = array();

	    foreach($all_products as $product)
	    {
	      $temp_product = new ChargifyProduct($this, $product);
	      array_push($product_objects, $temp_product);
	    }

	    return $product_objects;
	}

	public function getProductByID($product_id)
	{
	    $xml = $this->retrieveProductByID($product_id);
	    $product = $this->_createSimpleXmlElement($xml);
	    return new ChargifyProduct($this, $product);
	}

	public function getProductByHandle($product_handle)
	{
	    $xml = $this->retrieveProductByHandle($product_handle);
	    $product = $this->_createSimpleXmlElement($xml);
	    return new ChargifyProduct($this, $product);
	}


	/****************************************************
	 ************     COUPON FUNCTIONS     **************
	 ****************************************************/


	function retrieveCouponByID($product_family_id, $coupon_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
  		$base_url = "/product_families/{$product_family_id}/coupons/{$coupon_id}" . $extension;

	  	$coupon = $this->sendRequest($base_url, $format);
	  	if ($coupon->code == 200) {
	  		return $coupon->response;
	  	} elseif ($coupon->code == 404) {
	  		throw new ChargifyNotFoundException(404, "Coupon id: [{$coupon_id}] was not found.");
	  	}
	}

	function retrieveCouponByCode($product_family_id, $coupon_code, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
  		$base_url = "/product_families/{$product_family_id}/coupons/find" . $extension;

  		$parameters = "?code=".urlencode($coupon_code);

	  	$coupon = $this->sendRequest($base_url.$parameters, $format);
	  	if ($coupon->code == 200) {
	  		return $coupon->response;
	  	} elseif ($coupon->code == 404) {
	  		throw new ChargifyNotFoundException(404, "Coupon code: [{$coupon_code}] was not found.");
	  	}
	}

	function getCouponByID($product_family_id, $coupon_id) {
	    $xml = $this->retrieveCouponByID($product_family_id, $coupon_id);
	    $coupon = $this->_createSimpleXmlElement($xml);
	    return new ChargifyCoupon($this, $coupon);
	}

	function getCouponByCode($product_family_id, $coupon_code) {
	    $xml = $this->retrieveCouponByCode($product_family_id, $coupon_code);
	    $coupon = $this->_createSimpleXmlElement($xml);
	    return new ChargifyCoupon($this, $coupon);
	}

	/****************************************************
	 ************     CREDIT FUNCTIONS     **************
	 ****************************************************/


	function requestCreateCredit($subscription_id, $creditRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/credits" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'POST', $creditRequest);

		if ($xml->code == 201) { //CREATED
			return $xml->response;
		} elseif ($xml->code == 422) { //UNPROCESSABLE ENTITY
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		} elseif ($xml->code == 404) { //NOT FOUND
			throw new ChargifyNotFoundException(404, "Subscription id: [{$subscription_id}] was not found.");
		}
	}

	public function createCredit($subscription_id, $chargify_credit) {
		$xml = $this->requestCreateCredit($subscription_id, $chargify_credit->getXML());
		$credit = $this->_createSimpleXmlElement($xml);
		return new ChargifyCredit($this, $credit);
	}

	/****************************************************
	 *********     SUBSCRIPTION FUNCTIONS     ***********
	 ****************************************************/

	public function retrieveSubscriptions($page = 1, $per_page = 2000, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$params = $this->getOptionParams(array('page'=>$page,'per_page'=>$per_page));
  		$base_url = "/subscriptions" . $extension . $params;

	  	$customer = $this->sendRequest($base_url, $format);
	    return $customer->response;
	}

	public function retrieveSubscriptionsByCustomerID($id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
  		$base_url = "/customers/{$id}/subscriptions" . $extension;

	  	$customer = $this->sendRequest($base_url, $format);
	    return $customer->response;
	}

	public function retrieveSubscriptionsByID($id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
  		$base_url = "/subscriptions/{$id}" . $extension;

  		$customer = $this->sendRequest($base_url, $format);
	    return $customer->response;
	}

	public function requestCreateSubscription($subscriptionRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'POST', $subscriptionRequest);

		if ($xml->code == 201) { //CREATED
			return $xml->response;
		} elseif ($xml->code == 422) { //UNPROCESSABLE ENTITY
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function createSubscription($chargify_subscription) {
		$xml = $this->requestCreateSubscription($chargify_subscription->getXML());
		$subscription = $this->_createSimpleXmlElement($xml);
		return new ChargifySubscription($this, $subscription);
	}

	public function requestUpdateSubscription($subscription_id, $subscriptionRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}" . $extension;
		$xml = $this->sendRequest($base_url, $format, 'PUT', $subscriptionRequest);

		if ($xml->code == 200) {
			return $xml->response;
		} else {
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function requestUpdateSubscriptionProrated($subscription_id, $migrationRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/migrations" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'POST', $migrationRequest);

		if ($xml->code == 200) { //SUCCESS
			return $xml->response;
		} else {
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function updateSubscriptionProduct($subscription_id, $chargify_product) {
		$chargify_subscription = new ChargifySubscription($this);
		$chargify_subscription->product_handle = $chargify_product->handle;
		$chargify_subscription->product_id = $chargify_product->id;

		$xml = $this->requestUpdateSubscription($subscription_id, $chargify_subscription->getXML());
		$subscription = $this->_createSimpleXmlElement($xml);
		return new ChargifySubscription($this, $subscription);
	}

	public function updateSubscriptionProductProrated($subscription_id, $chargify_product, $include_trial = false, $include_initial_charge = false) {
		$chargify_migration = new ChargifyMigration();
		$chargify_migration->product_handle = $chargify_product->handle;
		$chargify_migration->product_id = $chargify_product->id;
		$chargify_migration->include_trial = $include_trial;
		$chargify_migration->include_initial_charge = $include_initial_charge;

		$xml = $this->requestUpdateSubscriptionProrated($subscription_id, $chargify_migration->getXML());
		$subscription = $this->_createSimpleXmlElement($xml);
		return new ChargifySubscription($this, $subscription);
	}

	public function updateSubscriptionCreditCard($subscription_id, $chargify_credit_card) {
		$chargify_subscription = new ChargifySubscription($this);
		$chargify_subscription->credit_card_attributes = $chargify_credit_card;

		$xml = $this->requestUpdateSubscription($subscription_id, $chargify_subscription->getXML());
		$subscription = $this->_createSimpleXmlElement($xml);
		return new ChargifySubscription($this, $subscription);
	}

	public function requestCancelSubscription($subscription_id, $subscriptionRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}" . $extension;
		$xml = $this->sendRequest($base_url, $format, 'DELETE', $subscriptionRequest);

		if ($xml->code == 200) { //SUCCESS
			return true;
		} else {
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function cancelSubscription($subscription_id, $cancellation_message, $format = 'XML') {
		$chargify_subscription = new ChargifySubscription($this);
		$chargify_subscription->cancellation_message = $cancellation_message;
		return $this->requestCancelSubscription($subscription_id, $chargify_subscription->getXML());
	}

	public function requestReactivateSubscription($subscription_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/reactivate" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'PUT');

		if ($xml->code == 200) {
			return $xml->response;
		} else {
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function reactivateSubscription($subscription_id) {
		$xml = $this->requestReactivateSubscription($subscription_id);
		$subscription = $this->_createSimpleXmlElement($xml);
		return new ChargifySubscription($this, $subscription);
	}

	public function requestResetSubscriptionBalance($subscription_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/reset_balance" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'PUT');

		if ($xml->code == 200) {
			return $xml->response;
		} else {
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function resetSubscriptionBalance($subscription_id) {
		$xml = $this->requestResetSubscriptionBalance($subscription_id);
		$subscription = $this->_createSimpleXmlElement($xml);
		return new ChargifySubscription($this, $subscription);
	}

	public function getSubscriptions($page = 1, $per_page = 2000) {
	    $xml = $this->retrieveSubscriptions($page, $per_page);
	    $subscriptions = $this->_createSimpleXmlElement($xml);
	    $subscription_objects = array();

	    foreach($subscriptions as $subscription)
	    {
	      $temp_sub = new ChargifySubscription($this, $subscription);
	      array_push($subscription_objects, $temp_sub);
	    }

	    return $subscription_objects;
	}

	public function getSubscriptionsByCustomerID($id)
	{
	    $xml = $this->retrieveSubscriptionsByCustomerID($id);
	    $subscriptions = $this->_createSimpleXmlElement($xml);
	    $subscription_objects = array();

	    foreach($subscriptions as $subscription)
	    {
	      $temp_sub = new ChargifySubscription($this, $subscription);
	      array_push($subscription_objects, $temp_sub);
	    }

	    return $subscription_objects;
	}

	public function getSubscriptionsByID($id)
	{
	    $xml = $this->retrieveSubscriptionsByID($id);
	    $subscription = $this->_createSimpleXmlElement($xml);

	    return new ChargifySubscription($this, $subscription);
	}

	/****************************************************
	 ************     CHARGE FUNCTIONS     **************
	 ****************************************************/

	public function requestCreateCharge($subscription_id, $chargeRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/charges" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'POST', $chargeRequest);

		if ($xml->code == 201) { //CREATED
			return $xml->response;
		} elseif ($xml->code == 422) { //UNPROCESSABLE ENTITY
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		} elseif ($xml->code == 404) { //NOT FOUND
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyNotFoundException($xml->code, $errors);
		}
	}

	public function createCharge($subscription_id, $chargify_charge) {
		$xml = $this->requestCreateCharge($subscription_id, $chargify_charge->getXML());
		$charge = $this->_createSimpleXmlElement($xml);
		return new ChargifyCharge($this, $charge);
	}

	public function createChargeByAmount($subscription_id, $amount, $memo) {
		$chargify_charge = new ChargifyCharge($this);
		$chargify_charge->amount = $amount;
		$chargify_charge->memo = $memo;

		return $this->createCharge($subscription_id, $chargify_charge);
	}

	public function createChargeByAmountInCents($subscription_id, $amount_in_cents, $memo) {
		$chargify_charge = new ChargifyCharge($this);
		$chargify_charge->amount_in_cents = $amount_in_cents;
		$chargify_charge->memo = $memo;

		return $this->createCharge($subscription_id, $chargify_charge);
	}

	/****************************************************
	 **********     COMPONENT FUNCTIONS     *************
	 ****************************************************/
	public function retrieveAllMeteredComponents($subscription_id, $component_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/components/{$component_id}/usages" . $extension;

		$components = $this->sendRequest($base_url, $format);
		return $components->response;
	}

	public function retrieveAllMeteredComponentsByProductFamily($product_family_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/product_families/{$product_family_id}/components" . $extension;

		$components = $this->sendRequest($base_url, $format);
		return $components->response;
	}

	public function requestCreateMeteredComponent($subscription_id, $component_id, $componentRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/components/{$component_id}/usages" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'POST', $componentRequest);

		if ($xml->code == 200) { //CREATED
			return $xml->response;
		} elseif ($xml->code == 422) { //UNPROCESSABLE ENTITY
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function createMeteredComponent($subscription_id, $component_id, $chargify_usage) {
		$xml = $this->requestCreateMeteredComponent($subscription_id, $component_id, $chargify_usage->getXML());
		$usage = $this->_createSimpleXmlElement($xml);
		return new ChargifyUsage($this, $usage);
	}

	public function retrieveAllQuantityBasedComponents($subscription_id, $component_id, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/components/{$component_id}" . $extension;

		$components = $this->sendRequest($base_url, $format);
		return $components->response;
	}

	public function requestUpdateQuantityBasedComponent($subscription_id, $component_id, $componentRequest, $format = 'XML') {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$base_url = "/subscriptions/{$subscription_id}/components/{$component_id}" . $extension;

		$xml = $this->sendRequest($base_url, $format, 'PUT', $componentRequest);

		if ($xml->code == 200) { //SUCCESS
			return $xml->response;
		} elseif ($xml->code == 422) { //UNPROCESSABLE ENTITY
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function updateQuantityBasedComponent($subscription_id, $component_id, $chargify_component) {
		$xml = $this->requestUpdateQuantityBasedComponent($subscription_id, $component_id, $chargify_component->getXML());
		$component = $this->_createSimpleXmlElement($xml);
		return new ChargifyQuantityBasedComponent($this, $component);
	}

	public function createQuantityBasedComponent($subscription_id, $component_id, $chargify_component) {
		return $this->updateQuantityBasedComponent($subscription_id, $component_id, $chargify_component);
	}

	public function getAllMeteredComponents($subscription_id, $component_id) {
		$xml = $this->retrieveAllMeteredComponents($subscription_id, $component_id);
		$all_metered_components = $this->_createSimpleXmlElement($xml);
		$component_objects = array();

		foreach ($all_metered_components as $metered_component) {
			$component_objects[] = new ChargifyUsage($this, $metered_component);
		}

	    return $component_objects;
	}

	public function getAllMeteredComponentsByProductFamily($product_family_id) {
		$xml = $this->retrieveAllMeteredComponentsByProductFamily($product_family_id);
		$all_metered_components = $this->_createSimpleXmlElement($xml);
		$component_objects = array();

		foreach ($all_metered_components as $metered_component) {
			$component_objects[] = new ChargifyUsage($this, $metered_component);
		}

	    return $component_objects;
	}

	public function getAllQuantityBasedComponents($subscription_id, $component_id) {
		$xml = $this->retrieveAllQuantityBasedComponents($subscription_id, $component_id);
		$quantity_based_component = $this->_createSimpleXmlElement($xml);
		return new ChargifyQuantityBasedComponent($this, $quantity_based_component);
	}

	/****************************************************
	 **********     TRANSACTION FUNCTIONS     ***********
	 ****************************************************/

	/**
	 * $options is an associative array, it's top-most level can contain the keys: kinds, since_id, max_id, since_date, until_date, page, and per_page
	 * kinds can be an array; since_date and until_date are strings formatted YYYY-MM-DD
	 *
	 * @param unknown_type $format
	 * @param unknown_type $options
	 * @return unknown
	 */
	public function retrieveAllTransactions($format = 'XML', $options = array()) {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$params = $this->getOptionParams($options);
		$base_url = "/transactions" . $extension. $params;

		$xml = $this->sendRequest($base_url, $format, 'GET');

		if ($xml->code == 200) { //SUCCESS
			return $xml->response;
		} else { //ERROR
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	public function retrieveTransactionsBySubscriptionID($subscription_id, $format='XML', $options=array()) {
		$extension = strtoupper($format) == 'XML' ? '.xml' : '.json';
		$params = $this->getOptionParams($options);
		$base_url = "/subscriptions/{$subscription_id}/transactions" . $extension. $params;

		$xml = $this->sendRequest($base_url, $format, 'GET');

		if ($xml->code == 200) { //SUCCESS
			return $xml->response;
		} else { //ERROR
			$errors = $this->_createSimpleXmlElement($xml->response);
			throw new ChargifyValidationException($xml->code, $errors);
		}
	}

	private function getOptionParams($options) {
		$params = '';
		$paramsArr = array();
		foreach ($options as $key => $val) {
			if ($key == 'kinds') {
				foreach ($val as $kind) {
					$paramsArr[] = "kinds[]={$kind}";
				}
			} else {
				$paramsArr[] = "{$key}={$val}";
			}
		}

		$params = implode("&", $paramsArr);
		if (!empty($params)) {
			$params = "?".$params;
		}

		return $params;
	}

	public function getAllTransactions($options = array()) {
		$xml = $this->retrieveAllTransactions('XML', $options);
		$result = array();
		$transactions = $this->_createSimpleXmlElement($xml);
		foreach($transactions as $key => $element)
		{
			$result[] = new ChargifyTransaction($this, $element);
		}
		return $result;
	}

	public function getTransactionsBySubscriptionID($subscription_id, $options = array()) {
		$xml = $this->retrieveTransactionsBySubscriptionID($subscription_id, 'XML', $options);
		$result = array();
		$transactions = $this->_createSimpleXmlElement($xml);
		foreach($transactions as $key => $element)
		{
			$result[] = new ChargifyTransaction($this, $element);
		}
		return $result;
	}

	/**
	 *
	 * @param ChargifyConnector $connector
	 *
	 * @return void
	 */
	public static function setDefault(ChargifyConnector $connector)
	{
    self::$_defaultConnector = $connector;
	}

	/**
	 *
	 * @throws ChargifyException
	 *
	 * @return ChargifyConnector
	 */
	public static function getDefault()
	{
	  if (!self::$_defaultConnector) {
	    throw new ChargifyException('The default connector should be set before any other use');
	  }

	  return self::$_defaultConnector;
	}
	
	/**
	 * 
	 * @param string xml
	 * 
	 * @throws ChargifyException if not valid exception
	 * 
	 * @return SimpleXMLElement 
	 */
	protected function _createSimpleXmlElement($xml)
	{
	  try {
	    return new SimpleXMLElement($xml);
	  } catch (Exception $e) {
	    throw new ChargifyException('Not valid xml provided, instead of it a string given: `'.$xml.'`', null, $e);
	  }
	}
}