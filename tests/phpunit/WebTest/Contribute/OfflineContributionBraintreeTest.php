<?php

/*
     Offline contribution test for Braintree Payment Processor
*/

require_once 'CiviTest/CiviSeleniumTestCase.php';

class WebTest_Contribute_OfflineContributionBraintreeTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

 

  function testDeductibleAmount() {
    $this->webtestLogin();

    $processorName = "Webtest Braintree" . substr(sha1(rand()), 0, 7);
    $processorType = 'Braintree';
    $processorSettings = array(
        'test_user_name' => 'qvtn6yk594nbxsyw',
        'test_password' => 'g55wdxm36pb8yy5m',
        'test_signature' => 'b92f264fd7b17d0f01893ff52777135c',
        'user_name' => 'qvtn6yk594nbxsyw',
        'password' => 'g55wdxm36pb8yy5m',
        'signature' => 'b92f264fd7b17d0f01893ff52777135c',
    );
    $this->webtestAddPaymentProcessor($processorName,$processorType,$processorSettings);
 

    $this->openCiviPage("admin/contribute/managePremiums", "action=add&reset=1");
    $premiumName = 'test Premium' . substr(sha1(rand()), 0, 7);
    $this->addPremium($premiumName, 'SKU', 3, 12, NULL, NULL);

    $firstName = 'John'.substr(sha1(rand()), 0, 7);
    $lastName = 'Dsouza'.substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName);

    //scenario 1 : is_deductible = 0 and non deductible amount is entered
    $scenario1 = array(
      'financial_type' => 'Campaign Contribution',
      'total_amount' => 111,
      'non_deductible_amount' => 15
    );
    $this->_doOfflineContribution($scenario1, $firstName, $lastName, $processorName);

    $checkScenario1 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Campaign Contribution',
      'Total Amount' => 111,
      'Non-deductible Amount' => 15
    );
    $this->_verifyAmounts($checkScenario1);

    //scenario 2 : is_deductible = TRUE and premium is set and premium is greater than total amount
    $scenario2 = array(
      'financial_type' => 'Donation',
      'total_amount' => 10,
      'premium' => "{$premiumName} ( SKU )"
    );
    $this->_doOfflineContribution($scenario2, $firstName, $lastName, $processorName);

    $checkScenario2 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => 10,
      'Non-deductible Amount' => 10
    );
    $this->_verifyAmounts($checkScenario2);

    //scenario 3 : is_deductible = TRUE and premium is set and premium is less than total amount
    $scenario3 = array(
      'financial_type' => 'Donation',
      'total_amount' => 123,
      'premium' => "{$premiumName} ( SKU )"
    );
    $this->_doOfflineContribution($scenario3, $firstName, $lastName, $processorName);

    $checkScenario3 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => 123,
      'Non-deductible Amount' => 12
    );
    $this->_verifyAmounts($checkScenario3);



    //scenario 5 : is_deductible = FALSE, non_deductible_amount = the total amount
    $scenario5 = array(
      'financial_type' => 'Campaign Contribution',
      'total_amount' => 555,
    );
    $this->_doOfflineContribution($scenario5, $firstName, $lastName, $processorName);

    $checkScenario5 = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Campaign Contribution',
      'Total Amount' => 555,
      'Non-deductible Amount' => 555
    );
    $this->_verifyAmounts($checkScenario5);
  }

  //common function for doing offline contribution
  function _doOfflineContribution($params, $firstName, $lastName, $processorName) {

    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Submit Credit Card Contribution");
    $this->click("link=Submit Credit Card Contribution");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // since we don't have live credentials we will switch to test mode
    $url = $this->getLocation();
    //$url = str_replace('mode=live', 'mode=test', $url);
    $this->open($url);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // start filling out contribution form
    $this->waitForElementPresent('payment_processor_id');
    $this->select('payment_processor_id', "label={$processorName}");

    // select financial type
    $this->select("financial_type_id", "label={$params['financial_type']}");

    // total amount
    $this->type("total_amount", "{$params['total_amount']}");

    // enter credit card info on form
    $this->webtestAddCreditCardDetails();

    // billing address
    $this->webtestAddBillingDetails($firstName, NULL, $lastName);

    if ($nonDeductibleAmt = CRM_Utils_Array::value('non_deductible_amount', $params)) {
      $this->click("AdditionalDetail");
      $this->waitForElementPresent("thankyou_date");
      $this->type("note", "This is a test note.");
      $this->type("non_deductible_amount", "{$nonDeductibleAmt}");
    }

    if (CRM_Utils_Array::value('premium', $params)) {
      //Premium section
      $this->click("Premium");
      $this->waitForElementPresent("fulfilled_date");
      $this->select("product_name[0]", "label={$params['premium']}");
    }
    // Clicking save.
    $this->click("_qf_Contribution_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The contribution record has been processed."), "Status message didn't show up after saving!");
  }

  //common function for verifing total_amount, and non_deductible_amount
  function _verifyAmounts($verifyData) {
    $this->waitForElementPresent( "xpath=//div[@id='Contributions']//table//tbody/tr[1]/td[8]/span/a[text()='View']" );
    $this->click( "xpath=//div[@id='Contributions']//table/tbody/tr[1]/td[8]/span/a[text()='View']" );
    $this->waitForPageToLoad($this->getTimeoutMsec());

    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    $this->click("_qf_ContributionView_cancel-top");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
}
