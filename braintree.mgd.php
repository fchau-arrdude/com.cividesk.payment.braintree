<?php

return array( 
     0 => 
  array (
    'module' => 'com.cividesk.payment.braintree',
    'name' => 'braintree',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'braintree',
      'title' => 'Braintree',
      'description' => 'Braintree Payment Processor',
      'class_name' => 'Payment_Braintree',
      'billing_mode' => 1,
      'user_name_label' => 'Merchant Id',
      'password_label' => 'Public Key',
      'signature_label' => 'Private Key',
      'url_site_default'=> NULL,
      'url_recur_default' => NULL,
      'url_site_test_default' => NULL,
      'url_recur_test_default' => NULL,
      'is_recur' => 1,
      'payment_type' => 1
    ),
    ),  
  );

?>
