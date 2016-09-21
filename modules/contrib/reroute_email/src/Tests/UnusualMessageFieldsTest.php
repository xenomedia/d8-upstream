<?php
namespace Drupal\reroute_email\Tests;

use Drupal\reroute_email\RerouteEmailTestBase;

/**
 * Test handling of
 * - message body passed as a string
 * - Cc/Bcc header keys with an unexpected case.
 *
 * @group reroute_email
 */
class UnusualMessageFieldsTest extends RerouteEmailTestBase {

  public static $modules = ['reroute_email', 'reroute_email_test', 'dblog'];

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    // Add more permissions to access recent log messages in test.
    $this->permissions[] = 'access site reports';
    // Include hidden test helper sub-module.
    parent::setUp();
  }

  /**
   * Test handling of message body as a string and header keys' robustness.
   *
   * A test email is sent by the reroute_email_test module with a string for
   * the body of the email message and Cc/Bcc header keys with an unexpected
   * case. Test if Reroute Email handles message's body properly when it is a
   * string and captures all Cc/Bcc header keys independently of the case.
   */
  public function testBodyStringRobustHeaders() {
    // Initialize Cc and Bcc keys with a special case.
    $test_cc_key = 'cC';
    $test_bcc_key = 'bCc';

    // Configure to reroute normally to rerouted@example.com.
    $this->configureRerouteEmail();

    // Print test email values for comparing values on test results page.
    $test_message = array(
      'to' => $this->originalDestination,
      'params' => array(
        'body' => "Test Message body is a string.",
        'headers' => array(
          'test_cc_key' => $test_cc_key,
          'test_bcc_key' => $test_bcc_key,
          $test_cc_key => "test_cc_key@example.com",
          $test_bcc_key => "test_bcc_key@example.com",
        ),
      ),
    );
    // Send test helper sub-module's email.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    \Drupal::getContainer()
      ->get('plugin.manager.mail')
      ->mail('reroute_email_test', 'test_reroute_email', $test_message['to'], $langcode, $test_message['params']);
    $this->verbose(t('Test email message values: <pre>@test_message</pre>', array('@test_message' => var_export($test_message, TRUE))));

    $mails = $this->drupalGetMails();
    $mail = end($mails);
// Check rerouted email to.
    $this->assertMail('to', $this->rerouteDestination, format_string('To email address was rerouted to @address.', array('@address' => $this->rerouteDestination)));

    // Check if original destination email address is in rerouted email body.
    $this->assertOriginallyTo($mail['body'], 'Found the correct "Originally to" line in the body');

    // Check if test message body is found although provided as a string.
    $this->assertTrue(strpos($mail['body'], $test_message['params']['body']) !== FALSE, 'Email body contains original message body although it was provided as a string.');

    // Check the watchdog entry logged by reroute_email_test_mail_alter.
    $this->drupalGet('admin/reports/dblog');
    $this->assertRaw(t('A String was detected in the body'), 'Recorded in recent log messages: a String was detected in the body.');

    // Test the robustness of the CC and BCC keys in headers.
    $this->assertTrue($mail['headers']['X-Rerouted-Original-Cc'] == $test_message['params']['headers'][$test_cc_key], format_string('X-Rerouted-Original-Cc is correctly set to @test_cc_address, although Cc header message key provided was: @test_cc_key', array(
      '@test_cc_address' => $test_message['params']['headers'][$test_cc_key],
      '@test_cc_key' => $test_cc_key
    )));
    $this->assertTrue($mail['headers']['X-Rerouted-Original-Bcc'] == $test_message['params']['headers'][$test_bcc_key], format_string('X-Rerouted-Original-Bcc is correctly set to @test_bcc_address, although Bcc header message key provided was: @test_bcc_key', array(
      '@test_bcc_address' => $test_message['params']['headers'][$test_bcc_key],
      '@test_bcc_key' => $test_bcc_key
    )));
  }
}
