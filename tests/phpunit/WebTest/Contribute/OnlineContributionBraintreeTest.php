<?php
/*
     Online contribution test for Braintree Payment Processor
*/

require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Contribute_OnlineContributionBraintreeTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testOnlineContributionAdd() {
    $this->webtestLogin();
   
    // We need a payment processor
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
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(10, 50);
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = FALSE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $friend = TRUE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $memPriceSetId = NULL;
    $isAddPaymentProcessor = FALSE;
    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
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
      $isAddPaymentProcessor
    );

    //logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId", "_qf_Main_upload-bottom");

    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $honorFirstName = 'In' . substr(sha1(rand()), 0, 4);
    $honorLastName = 'Hon' . substr(sha1(rand()), 0, 7);
    $honorEmail = $honorFirstName . "@example.com";
    $honorSortName = $honorLastName . ', ' . $honorFirstName;
    $honorDisplayName = 'Ms. ' . $honorFirstName . ' ' . $honorLastName;

    $this->type("email-5", $firstName . "@example.com");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    // Honoree Info
    $this->click("xpath=id('Main')/x:div[2]/x:fieldset/x:div[2]/x:div/x:label[text()='In Honor of']");
    $this->waitForElementPresent("honor_email");

    $this->select("honor_prefix_id", "label=Ms.");
    $this->type("honor_first_name", $honorFirstName);
    $this->type("honor_last_name", $honorLastName);
    $this->type("honor_email", $honorEmail);

    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
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
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");

    $this->type("sort_name", "$firstName $lastName");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ContributionView_cancel-bottom");

    //View Contribution Record and verify data
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Total Amount' => '100.00',
      'Contribution Status' => 'Completed',
      'In Honor of' => $honorDisplayName
    );
    $this->webtestVerifyTabularData($expected);

    // Check for Honoree contact created
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", $honorSortName);
    $this->typeKeys("css=input#sort_name_navigation", $honorSortName);

    // wait for result list
    $this->waitForElementPresent("css=div.ac_results-inner li");

    // visit contact summary page
    $this->click("css=div.ac_results-inner li");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is contact present?
    $this->assertTrue($this->isTextPresent("$honorDisplayName"), "Honoree contact not found.");

    }
  }

