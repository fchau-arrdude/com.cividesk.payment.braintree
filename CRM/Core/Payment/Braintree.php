<?php
require_once 'vendor/braintree/braintree_php/lib/Braintree.php';

class CRM_Core_Payment_Braintree extends CRM_Core_Payment {
  CONST CHARSET = 'iso-8859-1';

  protected $_mode = NULL;

  protected $_params = array();

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Braintree');
    $environment = ($mode == "test" ? 'sandbox' : 'production');

    Braintree_Configuration::environment($environment);
    Braintree_Configuration::merchantId($paymentProcessor["user_name"]);
    Braintree_Configuration::publicKey($paymentProcessor["password"]);
    Braintree_Configuration::privateKey($paymentProcessor["signature"]);   
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL, $force = FALSE) {
    $processorName = $paymentProcessor['name'];

    if (CRM_Utils_Array::value($processorName, self::$_singleton) === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_Braintree($mode, $paymentProcessor);
    }

    return self::$_singleton[$processorName];
  }



 function doTransferCheckout(&$params,$component='contribute') {
     CRM_Core_Error::fatal(ts('Use direct billing instead of Transfer method.'));
  }
  /**
   * Submit a payment using Advanced Integration Method
   *
   * @param  array $params assoc array of input parameters for this transaction
   *
   * @return array the result in a nice formatted array (or an error object)
   * @public
   */
  function doDirectPayment(&$params) {

    // Let a $0 transaction pass
    if (empty($params['amount']) || $params['amount'] == 0) {
      return $params;
    }

    // Get proper entry URL for returning on error.
    $qfKey = $params['qfKey'];
    $parsed_url = parse_url($params['entryURL']);
    $url_path = substr($parsed_url['path'], 1);
    $params['stripe_error_url'] = $error_url = CRM_Utils_System::url(
      $url_path,
      $parsed_url['query'] . "&_qf_Main_display=1&qfKey={$qfKey}",
      FALSE, NULL, FALSE
    );

    $requestArray = $this->formRequestArray($params);
    $error_url = CRM_Utils_System::url($url_path, $parsed_url['query'] . "&_qf_Main_display=1&qfKey={$qfKey}", FALSE, NULL, FALSE);

    try {
      $result = Braintree_Transaction::sale($requestArray);
    } catch(Exception $e) {
      CRM_Core_Error::statusBounce("Oops! Looks like there was problem.  Payment Response:  <br /> {$e->message}", $error_url);
    }
    
	if ($result->success) {
      $params['trxn_id'] = $result->transaction->id;
      $params['gross_amount'] = $result->transaction->amount;
	} 
	else if ($result->transaction) {
      $errormsg = 'Transactions is not approved';
      CRM_Core_Error::statusBounce("Oops!  Looks like there was problem.  Payment Response: <br /> {$result->transaction->processorResponseCode}: {$result->message}", $error_url);

      return self::error($result->transaction->processorResponseCode, $result->message);
	} 
	else {
      $error = "Validation errors:<br/>";
      foreach ($result->errors->deepAll() as $e) {
        $error .= $e->message;
      }
      return self::error(9001, $error);
	}

    return $params;
  }

  function &error($errorCode = NULL, $errorMessage = NULL) {
    $e = CRM_Core_Error::singleton();

    if ($errorCode) {
      $e->push($errorCode, 0, NULL, $errorMessage);
    }
    else {
      $e->push(9001, 0, NULL, 'Unknown System Error.');
    }
    return $e;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('Merchant Id is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Public Key is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['signature'])) {
      $error[] = ts('Signature is not set for this payment processor');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    } else {
      return NULL;
    }
  }

  /*
   *   This function returns the request array
   *   @param  array $params assoc array of input parameters for this transaction
   *   @return Array
   */
  function formRequestArray($postArray){

    $requestArray = array(
      'amount'          => $postArray['amount'],
      'creditCard'      => array(
        'number'           => $postArray['credit_card_number'],
        'expirationMonth'  => $postArray['credit_card_exp_date']['M'],
        'expirationYear'   => $postArray['credit_card_exp_date']['Y'],
        'cvv'              => $postArray['cvv2']
      ),
    );

    // Allow usage of a merchant account ID (for charging in other currencies) - see README.md
    if (!empty($this->_paymentProcessor['subject'])) {
      $requestArray['merchantAccountId'] = $this->_paymentProcessor['subject'];
    }

    if (array_key_exists('first_name',$postArray)){
      $requestArray['customer'] = array(
        'firstName' => $postArray['first_name'],
        'lastName'  => $postArray['last_name']
      );

      if (array_key_exists('email-5',$postArray)) {
        $requestArray['customer']['email'] = $postArray['email-5'];
      }
    }
  
    if (array_key_exists('billing_first_name',$postArray)) {
	  $requestArray['billing'] = array(
        'firstName'         => $postArray['billing_first_name'],
		'lastName'          => $postArray['billing_last_name'],
		'streetAddress'     => $postArray['billing_street_address-5'],
		'locality' 	        => $postArray['billing_city-5'],
		'region'            => $postArray['billing_state_province-5'],
		'postalCode'        => $postArray['billing_state_province-5'],
		'countryCodeAlpha2' => $postArray['billing_country-5']
      );
    }

    return $requestArray;
  }

}