# com.cividesk.payment.braintree

CiviCRM extension for Braintree Payments (https://www.braintreepayments.com).

This extension is a heavy refactoring of the original extension at https://github.com/vivekarora/braintree, which also includes some changes from the fork at https://github.com/wildcardcorp/braintree. All the credits go to these original authors.

This refactoring was necessary because the extension did not conform to CiviCRM's best practices and was no longer supported by the original author. The release number has been bumped to 2.0 for this 'rebirth', and the extension is now listed in the CiviCRM extensions directory.

Cividesk now supports this extension, and it is used in production by Cividesk and several of its customers.

## Compatibility and requirements

This extension is compatible with CiviCRM versions 4.2 to 4.6.

The included [Braintree library](https://github.com/braintree/braintree_php) (v3.18.0, Nov. 2016) requires the following:
* PHP 5.4+
* the curl, dom, hash, openssl and xmlwriter PHP extensions

## Installation and configuration

This extension is not yet available for automated distribution, so you need to do a manual install.

If you have shell access on your server:

1. log into your server and go to your CiviCRM extensions directory
2. git clone https://github.com/cividesk/com.cividesk.payment.braintree

If you only have ftp access to your server:

1. use the green 'Clone or download' button to download a local copy of the extension
2. upload the entire com.cividesk.payment.braintree directory to the CiviCRM extensions directory on your server

then in CivICRM:

3. go to the Administer >> System Settings >> Manage Extensions, click Refresh then enable the extension
4. go to System Settings >> Payment Processors, select Braintree from Payment processor type dropdown, fill the Merchant Id, Public Key and Private Key credentials which you do get from your account in Braintree. Note that it is recommended to also get test credentials from https://sandbox.braintreegateway.com in order to be able to test your contribution pages without charging your credit card.
5. create a contrinution page and select Braintree as the payment processor

If you are processing payment in multiple currencies, you can create additional Braintree payment processor instances with the same credentials, and then, in the database, change the 'subject' field in the 'civicrm_payment_processor' table to reflect the Braintree Merchant Account ID for these other currencies.

## Limitations and future improvements

* compatibility with CiviCRM 4.7 (only testing needed)
* PCI compliance is complicated since CiviCRM (and this extension) process the form that collects credit card data. The extension could be improved to use the [Braintree Client SDK](https://developers.braintreepayments.com/guides/client-sdk/setup/javascript/v3) and only process tokens, which would be more secure and greatly simplify PCI compliance.

## Unit testing (unmaintained)

Developers can run the test suite as such:

1. Go to the tests folder of the extention.
2. Copy All files inside the com.cividesk.payment.braintree/tests/phpunit/WebTest/Cotribute and paste it in civicrm(Your Civicrm module directory)/tests/phpunit/WebTest/Contribute
3. Copy All files inside the com.cividesk.payment.braintree/tests/phpunit/WebTest/Event and paste it in civicrm(Your Civicrm module directory)/tests/phpunit/WebTest/Event
4. Copy All files inside the com.cividesk.payment.braintree/tests/phpunit/WebTest/Member and paste it in civicrm(Your Civicrm module directory)/tests/phpunit/WebTest/Member
5. cd path/to/civicrm/packages/SeleniumRC 
6. sh selenium.sh

Now you are all set to run the web tests.

To run 

Online Contribution tests

       scripts/phpunit -uUSERNAME -pPASSWORD -hHOST -bTESTDBNAME WebTest_Contribute_OnlineContributionBraintreeTest

Offline Contribution tests

        scripts/phpunit -uUSERNAME -pPASSWORD -hHOST -bTESTDBNAME WebTest_Contribute_OfflineContributionBraintreeTest


 Event creation and Online registration tests

        scripts/phpunit -uUSERNAME -pPASSWORD -hHOST -bTESTDBNAME WebTest_Event_AddEventBraintreeTest


 Event creation and Ofline  add participants tests

       	 scripts/phpunit -uUSERNAME -pPASSWORD -hHOST -bTESTDBNAME WebTest_Event_AddParticipationBraintreeTest


 Online membership signup

         scripts/phpunit -uUSERNAME -pPASSWORD -hHOST -bTESTDBNAME  WebTest_Member_OnlineMembershipBraintreeCreateTest

 Offline membership payment and autorenew
 
          scripts/phpunit -uUSERNAME -pPASSWORD -hHOST -bTESTDBNAME WebTest_Member_OfflineAutoRenewBraintreeMembershipTest
