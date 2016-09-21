<?php

namespace Drupal\reroute_email\Tests;

use Drupal\reroute_email\RerouteEmailTestBase;

/**
 * Test Reroute Email's form for sending a test email.
 *
 * @group reroute_email
 */
class TestEmailTest extends RerouteEmailTestBase {

  /**
   * Basic tests for reroute_email Test Email form.
   *
   * Check if submitted form values are properly submitted and rerouted.
   * Test Subject, To, Cc, Bcc and Body submitted values, form validation,
   * default values, and submission with invalid email addresses.
   */
  public function testFormTestEmail() {

    // Configure to reroute normally to rerouted@example.com.
    $this->configureRerouteEmail();

    // Check Subject field default value.
    $this->drupalGet("admin/config/development/reroute_email/test");
    $this->assertFieldByName('subject', t("Reroute Email Test"), 'The expected default value was found for the Subject field.');

    // Submit the Test Email form to send an email to be rerouted.
    $post = array(
      'to' => "to@example.com",
      'cc' => "cc@example.com",
      'bcc' => "bcc@example.com",
      'subject' => "Test Reroute Email Test Email Form",
      'body' => 'Testing email rerouting and the Test Email form',
    );
    $this->drupalPostForm("admin/config/development/reroute_email/test", $post, t("Send email"));
    $this->assertText(t("Test email submitted for delivery from test form."));
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // Check rerouted email to.
    $this->assertMail('to', $this->rerouteDestination, format_string('To email address was rerouted to @address.', array('@address' => $this->rerouteDestination)));

    // Check the To passed through the Test Email Form.
    $this->assertOriginallyTo($mail['body'], 'Found submitted "To" email address in the body', $post['to']);

    // Check the Cc and Bcc headers are the ones submitted through the form.
    $this->assertTrue($mail['headers']['X-Rerouted-Original-Cc'] == $post['cc'], format_string('X-Rerouted-Original-Cc is correctly set to submitted value: @address', array('@address' => $post['cc'])));
    $this->assertTrue($mail['headers']['X-Rerouted-Original-Bcc'] == $post['bcc'], format_string('X-Rerouted-Original-Cc is correctly set to submitted value: @address', array('@address' => $post['bcc'])));
    // Check the Subject and Body field values can be found in rerouted email.
    $this->assertMail('subject', $post['subject'], format_string('Subject is correctly set to submitted value: @subject', array('@subject' => $post['subject'])));
    $this->assertFalse(strpos($mail['body'], $post['body']) === FALSE, 'Body contains the value submitted through the form');

    // Check required To field.
    $this->drupalPostForm("admin/config/development/reroute_email/test", array('to' => ''), t("Send email"));
    $this->assertText(t("To field is required."));

    // Test form submission with email rerouting and invalid email addresses.
    $post = array(
      'to' => "To address invalid format",
      'cc' => "Cc address invalid format",
      'bcc' => "Bcc address invalid format",
    );
    $this->drupalPostForm("admin/config/development/reroute_email/test", $post, t("Send email"));
    // Successful submission with email rerouting enabled.
    $this->assertText(t("Test email submitted for delivery from test form."));
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // Check rerouted email to.
    $this->assertMail('to', $this->rerouteDestination, format_string('To email address was rerouted to @address.', array('@address' => $this->rerouteDestination)));

    // Check the To passed through the Test Email Form.
    $this->assertOriginallyTo($mail['body'], 'Found submitted "To" email address in the body', $post['to']);

    // Check the Cc and Bcc headers are the ones submitted through the form.
    $this->assertTrue($mail['headers']['X-Rerouted-Original-Cc'] == $post['cc'], format_string('X-Rerouted-Original-Cc is correctly set to submitted value: @address', array('@address' => $post['cc'])));
    $this->assertTrue($mail['headers']['X-Rerouted-Original-Bcc'] == $post['bcc'], format_string('X-Rerouted-Original-Cc is correctly set to submitted value: @address', array('@address' => $post['bcc'])));

    // Now change the configuration to disable reroute and submit the Test
    // Email form with the same invalid email address values.
    $this->configureRerouteEmail(NULL, FALSE);

    // Submit the test email form again with previously used invalid addresses.
    $this->drupalPostForm("admin/config/development/reroute_email/test", $post, t("Send email"));
    // Check invalid email addresses are still passed to the mail system.
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // Check rerouted email to.
    $this->assertMail('to', $post['to'], format_string('To email address is correctly set to submitted value: @address.', array('@address' => $post['to'])));
    $this->verbose(t('Sent email values: <pre>@mail</pre>', array('@mail' => var_export($mail, TRUE))));
    // Check the Cc and Bcc headers are the ones submitted through the form.
    $this->assertTrue($mail['headers']['Cc'] == $post['cc'], format_string('Cc is correctly set to submitted value: @address', array('@address' => $post['cc'])));
    $this->assertTrue($mail['headers']['Bcc'] == $post['bcc'], format_string('Bcc is correctly set to submitted value: @address', array('@address' => $post['bcc'])));
  }
}
