<?php
/*
    Online Membership create and signup test for Braintree Payment Processor
*/

require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Member_OnlineMembershipBraintreeCreateTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function _testOnlineMembershipSignup($pageId, $memTypeId, $firstName, $lastName, $payLater, $hash, $otherAmount = FALSE) {
    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId", "_qf_Main_upload-bottom");
    // Select membership type 1
    $this->waitForElementPresent("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label");
    if ($memTypeId != 'No thank you') {
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'$memTypeId')]");
    }

    else {
      $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label[contains(text(),'$memTypeId')]");

    }
    if (!$otherAmount) {
      $this->click("xpath=//div[@class='crm-section contribution_amount-section']/div[2]//span/label[text()='No thank you']");
    }
    else {
      $this->type("xpath=//div[@class='content other_amount-content']/input", $otherAmount);
    }
    if ($payLater) {
      $this->click("xpath=//div[@class='crm-section payment_processor-section']/div[2]//label[text()='Pay later label {$hash}']");
    }
    $this->type("email-5", $firstName . "@example.com");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");
    if (!$payLater) {
      //Credit Card Info
      $this->select("credit_card_type", "value=Visa");
      $this->type("credit_card_number", "4111111111111111");
      $this->type("cvv2", "111");
      $this->select("credit_card_exp_date[M]", "value=1");
      $this->select("credit_card_exp_date[Y]", "value=2020");

      //Billing Info
      $this->type("billing_first_name", $firstName . "billing");
      $this->type("billing_last_name", $lastName . "billing");
      $this->type("billing_street_address-5", "15 Main St.");
      $this->type(" billing_city-5", "San Jose");
      $this->select("billing_country_id-5", "value=1228");
      $this->select("billing_state_province_id-5", "value=1004");
      $this->type("billing_postal_code-5", "94129");
    }
    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function testOnlineMembershipCreateWithContribution() {
    //login with admin credentials & make sure we do have required permissions.
    $permissions = array("edit-1-make-online-contributions", "edit-1-profile-listings-and-forms");
    $this->changePermissions($permissions);

    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
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

    $amountSection = TRUE;
    $payLater = FALSE;
    $allowOtherAmmount = TRUE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = TRUE;
    $memPriceSetId = NULL;
    $friend = FALSE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $isSeparatePayment = FALSE;
    $membershipsRequired = FALSE;
    $fixedAmount = FALSE;
    $contributionTitle = "Title $hash";
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $contributionTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp,
      FALSE,
      FALSE,
      $isSeparatePayment,
      TRUE,
      $allowOtherAmmount,
      TRUE,
      'Donation',
      $fixedAmount,
      $membershipsRequired
    );
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    //logout
    $this->webtestLogout();

    $this->_testOnlineMembershipSignup($pageId, 'No thank you', $firstName, $lastName, FALSE, $hash, 50);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search","reset=1", "contribution_date_low");

    $this->type("sort_name", "$firstName $lastName");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ContributionView_cancel-bottom");

    //View Contribution Record and verify data
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => '50.00',
      'Contribution Status' => 'Completed',
      'Received Into' => 'Deposit Bank Account',
      'Source' => "Online Contribution: $contributionTitle",
      'Online Contribution Page' => $contributionTitle,
    );
    $this->webtestVerifyTabularData($expected);
  }
}
