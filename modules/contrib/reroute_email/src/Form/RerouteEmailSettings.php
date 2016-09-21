<?php
namespace Drupal\reroute_email\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class RerouteEmailSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reroute_email_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('reroute_email.settings');

    foreach (['reroute_email_enable', 'reroute_email_address', 'reroute_email_enable_message'] as $variable) {
      $config->set($variable, $form_state->getValue($variable));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['reroute_email.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
$form[REROUTE_EMAIL_ENABLE] = array(
    '#type'          => 'checkbox',
    '#title'         => 'Enable rerouting',
    '#default_value' => \Drupal::config('reroute_email.settings')->get(REROUTE_EMAIL_ENABLE),
    '#description'   => 'Check this box if you want to enable email rerouting. Uncheck to disable rerouting.',
  );

    $default_address = \Drupal::config('reroute_email.settings')->get(REROUTE_EMAIL_ADDRESS);
    if (empty($default_address)) {
      $default_address = \Drupal::config('system.site')->get('mail');
    }
$form[REROUTE_EMAIL_ADDRESS] = array(
    '#type'          => 'textfield',
    '#title'         => 'Email addresses',
    '#default_value' => $default_address,
    '#description'   => 'Provide a space, comma, or semicolon-delimited list of email addresses to pass through. Every destination email address which is not on this list will be rerouted to the first address on the list.<br/> If the field is empty and no value is provided, <strong>all outgoing emails would be aborted</strong> and the email would be recorded in the <a href="@dblog">recent log entries</a>.', array('#dblog' => \Drupal\Core\Url::fromRoute('dblog.overview')),
    '#states' => array(
      'visible' => array(':input[name=reroute_email_enable]' => array('checked' => TRUE)),
    ),
  );

$form[REROUTE_EMAIL_ENABLE_MESSAGE] = array(
    '#type' => 'checkbox',
    '#title' => 'Show rerouting description in mail body',
    '#default_value' => \Drupal::config('reroute_email.settings')->get(REROUTE_EMAIL_ENABLE_MESSAGE),
    '#description' => 'Check this box if you want a message to be inserted into the email body when the mail is being rerouted. Otherwise, SMTP headers will be used to describe the rerouting. If sending rich-text email, leave this unchecked so that the body of the email will not be disturbed.',
    '#states' => array(
      'visible' => array(':input[name=reroute_email_enable]' => array('checked' => TRUE)),
    ),
  );
    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['reroute_email_enable']) == TRUE) {
      // Allow splitting emails by space, comma, semicolon.
      $addresslist = preg_split(REROUTE_EMAIL_EMAIL_SPLIT_RE, $form_state->getValue(['reroute_email_address']), -1, PREG_SPLIT_NO_EMPTY);
      foreach ($addresslist as $address) {
        if (!valid_email_address($address)) {
          $form_state->setErrorByName('reroute_email_address', t('@address is not a valid email address', [
            '@address' => $address
            ]));
        }
      }
    }
  }

}
