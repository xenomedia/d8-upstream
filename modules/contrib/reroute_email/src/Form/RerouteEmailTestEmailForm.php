<?php
namespace Drupal\reroute_email\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class RerouteEmailTestEmailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reroute_email_test_email_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return [
      'addresses' => [
        '#type' => 'fieldset',
        '#description' => t('Email addresses are not validated: any valid or invalid email address format could be submitted.'),
        'to' => [
          '#type' => 'textfield',
          '#title' => t('To'),
          '#required' => TRUE,
        ],
        'cc' => [
          '#type' => 'textfield',
          '#title' => t('Cc'),
        ],
        'bcc' => [
          '#type' => 'textfield',
          '#title' => t('Bcc'),
        ],
      ],
      'subject' => [
        '#type' => 'textfield',
        '#title' => t('Subject'),
        '#default_value' => t('Reroute Email Test'),
      ],
      'body' => [
        '#type' => 'textarea',
        '#title' => t('Body'),
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => t('Send email'),
      ],
    ];
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $from = "somewhere@example.com";
    $to = $form_state->getValue(['to']);
    $param_keys = ['cc', 'bcc', 'subject', 'body'];
    $params = array_intersect_key($form_state->getValues(), array_flip($param_keys));
    $langcode = \Drupal::languageManager()->getDefaultLanguage();

    // Send email with drupal_mail.
    $message =  \Drupal::service('plugin.manager.mail')->mail('reroute_email', 'test_email_form', $to, $langcode, $params, $from);

    if (!empty($message['result'])) {
      drupal_set_message(t("Test email submitted for delivery from test form."));
    }
  }

}
