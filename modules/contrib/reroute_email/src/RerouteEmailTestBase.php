<?php

namespace Drupal\reroute_email;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for reroute_email.
 *
 * @group reroute_email
 */
class RerouteEmailTestBase extends WebTestBase  {

  public static $modules = ['reroute_email'];

  /**
   * User object to perform site browsing.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * Reroute email destination address used for the tests.
   *
   * @var string
   */
  protected $rerouteDestination = "rerouted@example.com";

  /**
   * Original email address used for the tests.
   *
   * @var string
   */
  protected $originalDestination = "original@example.com";

  /**
   * Permissions required by the user to perform the tests.
   *
   * @var array
   */
  protected $permissions = array(
    'administer reroute email',
  );

  /**
   * Enable modules and create user with specific permissions.
   */
  public function setUp() {
    parent::setUp();

    // Authenticate test user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Helper function to configure Reroute Email Settings.
   *
   * @param string $reroute_destination
   *   (optional) The email address to which emails should be rerouted.
   *   Defaults to $this->rerouteDestination if set to NULL.
   * @param bool $reroute_email_enable
   *   (optional) Set to TRUE to enable email Rerouting, defaults to TRUE.
   * @param bool $reroute_email_enable_message
   *   (optional) Set to TRUE to show rerouting description, defaults to TRUE.
   */
  public function configureRerouteEmail($reroute_destination = NULL, $reroute_email_enable = TRUE, $reroute_email_enable_message = TRUE) {
    // Initialize $reroute_destination by default if no value is provided.
    if (!isset($reroute_destination)) {
      $reroute_destination = $this->rerouteDestination;
    }
    // Configure to Reroute Email settings form.
    $post = array(
      'reroute_email_address' => $reroute_destination,
      'reroute_email_enable' => $reroute_email_enable,
      'reroute_email_enable_message' => $reroute_email_enable_message,
    );
    // Submit Reroute Email Settings form and check if it was successful.
    $this->drupalPostForm("admin/config/development/reroute_email", $post, t('Save configuration'));
    $this->assertText(t("The configuration options have been saved."));
  }

  /**
   * Assert whether the text "Originally to: @to_email" is found in email body.
   *
   * @param string $mail_body
   *   The email body in which the line of text should be searched for.
   * @param bool $message
   *   Message to display in test case results.
   * @param bool $original_destination
   *   (optional) The original email address to be found in rerouted email
   *   body. Defaults to $this->originalDestination if set to NULL.
   */
  public function assertOriginallyTo($mail_body, $message, $original_destination = NULL) {
    // Initialize $original_destination by default if no value is provided.
    if (!isset($original_destination)) {
      $original_destination = $this->originalDestination;
    }
    // Search in $mailbody for "Originally to: $original_destination".
    $search_for = t("Originally to: @to", array('@to' => $original_destination));
    $has_info = preg_match("/$search_for/", $mail_body);
    // Asserts whether searched text was found.
    $this->assertTrue($has_info, $message);
    $this->verbose(t('Email body was: <pre>@mail_body</pre>', array('@mail_body' => $mail_body)));
  }
}
