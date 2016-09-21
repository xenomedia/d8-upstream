<?php

namespace Drupal\reroute_email\Tests;

use Drupal\reroute_email\RerouteEmailTestBase;

/**
 * When reroute email addresses field is not configured, attempt to use the site email address, otherwise use sendmail_from system variable.
 *
 * @group reroute_email
 */
class DefaultAddressTest extends RerouteEmailTestBase {

  public static $modules = ['reroute_email', 'dblog'];

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
// Add more permissions to access recent log messages in test.
    $this->permissions[] = 'access site reports';
    parent::setUp();
  }

  /**
   * Test reroute email address is set to site_mail, sendmail_from or empty.
   *
   * When reroute email addresses field is not configured and settings haven't
   * been configured yet, check if the site email address or the sendmail_from
   * system variable are properly used as fallbacks. Additionally, check that
   * emails are aborted and a watchdog entry logged if reroute email address is
   * set to an empty string.
   */
  public function testRerouteDefaultAddress() {

    $config = \Drupal::service('config.factory')->getEditable('reroute_email.settings');

    // Check default value for reroute_email_address when not conf  igured.
    // If system.site's 'mail' is not empty, it should be the default value.
    $site_mail = \Drupal::config('system.site')->get('mail');

    $this->assertTrue(isset($site_mail), t('Site mail is not empty: @site_mail.', array('@site_mail' => $site_mail)));

    // Programmatically enable email rerouting.
    $config->set(REROUTE_EMAIL_ENABLE, TRUE);
    $config->save();

    // Load the Reroute Email Settings form page. Ensure rerouting is enabled.
    $this->drupalGet("admin/config/development/reroute_email");
    $this->assertFieldChecked('edit-reroute-email-enable', 'Email rerouting was programmatically successfully enabled.');

    $reroute_email_enable = $config->get(REROUTE_EMAIL_ENABLE);
    $this->assertTrue($reroute_email_enable, 'Rerouting is enabled');

    // Check Email addresses field default value should be the value of system.site.mail.
    $this->assertFieldByName(REROUTE_EMAIL_ADDRESS, $site_mail, t('reroute_email_address default value on form is system.site.mail value: @site_mail.', array('@site_mail' => $site_mail)));

    // Ensure reroute_email_address is actually empty at this point.
    $reroute_email_address = $config->get(REROUTE_EMAIL_ADDRESS);
    $this->assertNull($reroute_email_address, 'Reroute email destination address is not configured.');

    // Submit a test email and check if it is rerouted to system.site.mail address.
    $this->drupalPostForm("admin/config/development/reroute_email/test", ['to' => 'to@example.com'], 'Send email');
    $this->assertText(t("Test email submitted for delivery from test form."));
    $mails = $this->drupalGetMails();
    $this->assert(count($mails) == 1, 'Exactly one email captured');
    $this->verboseEmail();
    // Check rerouted email is the site email address.
    $this->assertMail('to', $site_mail, t('Email was properly rerouted to site email address: @default_destination.', array('@default_destination' => $site_mail)));

    // Unset system.site.mail
    \Drupal::service('config.factory')
      ->getEditable('system.site')
      ->set('mail', NULL)
      ->save();

    // If sendmail_from is defined, try to test the default sendmail_from system variable.
    $system_email = ini_get('sendmail_from');
    // Fallback to default placeholder if no system variable configured.
    $site_mail = empty($system_email) ? REROUTE_EMAIL_ADDRESS_EMPTY_PLACEHOLDER : $system_email;

    // Reload the Reroute Email Settings form page.
    $this->drupalGet("admin/config/development/reroute_email");
    // Check Email addresses field default value should be system default.
    $this->assertFieldByName('reroute_email_address', $system_email, format_string('Site email address is not configured, Email addresses field defaults to system sendmail_from: <em>@default_destination</em>.', array('@default_destination' => $system_email)));

    // Submit a test email to check if it is rerouted to sendmail_from address.
    $this->drupalPostForm("admin/config/development/reroute_email/test", array('to' => "to@example.com"), t("Send email"));
    $this->assertText(t("Test email submitted for delivery from test form."));
    $mails = $this->drupalGetMails();
    $mail = end($mails);
    // Check rerouted email is the system sendmail_from email address.
    $this->assertMail('to', $site_mail, format_string('Email was properly rerouted to system sendmail_from email address: @default_destination.', array('@default_destination' => $site_mail)));

    // Configure reroute email address to be emtpy: ensure emails are aborted.
    $this->configureRerouteEmail('');

    // Make sure reroute_email_address variable is an empty string.

    $reroute_email_address = \Drupal::config('reroute_email.settings')
      ->get(REROUTE_EMAIL_ADDRESS);
    $this->assertTrue($reroute_email_address === '', 'Reroute email destination address is an empty string.');
    // Flush the Test Mail collector to ensure it is empty for this tests.
    \Drupal::state()->set('system.test_mail_collector', array());

    // Submit a test email to check if it is aborted.
    $this->drupalPostForm("admin/config/development/reroute_email/test", array('to' => "to@example.com"), t("Send email"));
    $mails = $this->drupalGetMails();
    $this->assertTrue(count($mails) == 0, 'Email sending was properly aborted because rerouting email address is an empty string.');
    // Check status message is displayed properly after email form submission.
    $this->assertPattern('/' . t('@message_id.*was aborted by reroute email', [
      '@message_id' => $mail['id']]) . '/', t('Status message displayed as expected to the user with the mail ID <em>(@message_id)</em> and a link to recent log entries.', array('@message_id' => $mail['id'])));

    // Check the watchdog entry logged with aborted email message.
    $this->drupalGet('admin/reports/dblog');
    // Check the link to the watchdog detailed message.
    $dblog_link = $this->xpath('//table[@id="admin-dblog"]/tbody/tr[contains(@class,"dblog-reroute-email")][1]/td[text()="reroute_email"]/following-sibling::td/a[contains(text(),"reroute_email")]');
    $link_label = (string) $dblog_link[0];
    $this->assertTrue(isset($dblog_link[0]), t('Logged a message in dblog: <em>@link</em>.', array('@link' => $link_label)));
    // Open the full view page of the log message found for reroute_email.
    $this->clickLink($link_label);

    // Recreate expected logged message based on email submitted previously.
    $mail['send'] = FALSE;
    $mail['body'] = array($mail['body'], NULL);
    // Ensure the correct email is logged with default 'to' placeholder.
    $mail['to'] = REROUTE_EMAIL_ADDRESS_EMPTY_PLACEHOLDER;
    $this->assertPattern( '/' . t('Aborted email sending for.*@message_id.*Detailed email data',
      ['@message_id' => $mail['id']]) . '/',
      t('The dblog entry recorded by Reroute Email contains a dump of the aborted email message <em>@message_id</em> and is formatted as expected.', ['@message_id' => $mail['id']]));
  }
}
