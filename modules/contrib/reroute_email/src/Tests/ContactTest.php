<?php

namespace Drupal\reroute_email\Tests;

use Drupal\reroute_email\RerouteEmailTestBase;

/**
 * Test Reroute Email module's ability to reroute mail sent from the Contact module form.
 *
 * @group reroute_email
 */
class ContactTest extends RerouteEmailTestBase {

  public static $modules = ['reroute_email', 'contact'];

  protected $confirmation_message;

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    // Add more permissions to be able to manipulate the contact forms.
    $this->permissions[] = 'administer contact forms';
    $this->permissions[] = 'access site-wide contact form';
    $this->confirmation_message = 'Your message has been sent.';

    parent::setUp();

    // Create a "feedback" contact form
    // Note that the 'message' was added in the 8.2.x series, and is not there
    // in 8.1.x, so this could fail against 8.1.x
    $this->drupalPostForm('admin/structure/contact/add', ['label' => 'feedback', 'id' => 'feedback', 'recipients' => $this->originalDestination, 'message' => $this->confirmation_message, 'selected' => TRUE], 'Save');
    $this->assertResponse(200, 'Contact form named "feedback" added');

    // Make sure that the flood controls don't break the test.
    \Drupal::service('config.factory')->getEditable('contact.settings')
      ->set('flood.limit', 1000)
      ->set('flood.interval', 60);
  }

  /**
   * Basic tests of email rerouting for emails sent through the Contact forms.
   *
   * The Core Contact email form is submitted several times with different
   * Email Rerouting settings: Rerouting enabled or disabled, Body injection
   * enabled or disabled, several recipients with or without whitelist.
   */
  public function testBasicNotification() {
    // Additional destination email address used for testing the whitelist.
    $additional_destination = "additional@example.com";

    // Configure to reroute normally to rerouted@example.com.
    $this->configureRerouteEmail();

    // Configure the contact settings to send to $original_destination.
    $this->drupalPostForm('admin/structure/contact/manage/feedback', array('recipients' => $this->originalDestination), t('Save'));

    // Go to the contact page and send an email.
    $post = array('subject[0][value]' => "Test test test", 'message[0][value]' => 'This is a test');
    $this->drupalPostForm("contact", $post, 'Send message');
    $this->assertResponse(200, 'Posted contact form successfully');

    $this->assertText($this->confirmation_message);
    $mails = $this->drupalGetMails();

    $mail = end($mails);
    $this->assertMail('to', $this->rerouteDestination, t("Email was rerouted to @address", array('@address' => $this->rerouteDestination)));

    // Check if original destination email address is in rerouted email body.
    $this->assertOriginallyTo($mail['body'], 'Found the correct "Originally to" line in the body');
    $this->assertTrue(strpos($mail['body'], 'Originally to') !== FALSE, 'Body does contain "Originally to"');

    // Now try sending to one of the additional email addresses that should
    // not be rerouted. Configure two email addresses in reroute form.
    // Body injection is still turned on.
    $this->configureRerouteEmail("{$this->rerouteDestination}, $additional_destination");

    // Configure the contact settings to point to the additional recipient.
    $this->drupalPostForm('admin/structure/contact/manage/feedback', array('recipients' => $additional_destination), t('Save'));

    // Go to the contact page and send an email.
    $post = array('subject[0][value]' => "Test test test", 'message[0][value]' => 'This is a test');
    $this->drupalPostForm("contact", $post, t("Send message"));
    $this->assertText($this->confirmation_message);
    $this->assertMail('to', $additional_destination, 'Email was not rerouted because destination was in whitelist');

    // Now change the configuration to disable reroute and set the default
    // email recipients (from system.site.mail)
    $this->configureRerouteEmail(NULL, FALSE);

    // Set the contact form to send to original_destination.
    $this->drupalPostForm('admin/structure/contact/manage/feedback', array('recipients' => $this->originalDestination), t('Save'));
    // Go to the contact page and send an email.
    $post = array('subject[0][value]' => "Test test test", 'message[0][value]' => 'This is a test');
    $this->drupalPostForm("contact", $post, t("Send message"));
    $this->assertText($this->confirmation_message);
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // Mail should not be rerouted - should go to $original_destination.
    $this->assertMail('to', $this->originalDestination, 'Mail not rerouted - sent to original destination.');
    $this->verbose(t("Email 'to' was: <pre>@mail_to</pre>", array('@mail_to' => $mail['to'])));

    // Configure to reroute without body injection.
    $this->configureRerouteEmail(NULL, TRUE, FALSE);

    // Go to the contact page and send an email.
    $post = array('subject[0][value]' => "Test test test", 'message[0][value]' => 'This is a test');
    $this->drupalPostForm("contact", $post, t("Send message"));
    $this->assertText($this->confirmation_message);
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // There should be nothing in the body except the contact message - no
    // body injection like 'Originally to'.
    $this->assertTrue(strpos($mail['body'], 'Originally to') === FALSE, 'Body does not contain "Originally to"');
    $this->assertTrue($mail['headers']['X-Rerouted-Original-To'] == $this->originalDestination, 'X-Rerouted-Original-To is correctly set to the original destination email');
  }
}
