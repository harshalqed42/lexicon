<?php
namespace Drupal\lexicon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure lexicon admin configuration settings.
 */
class lexiconAlphabetForm extends ConfigFormBase {

  /**
   * Config settings.

   *
   * @var string
   */
  const SETTINGS = 'lexicon.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lexicon_alphabet';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $config = $this->config(static::SETTINGS);

    $form['alphabet'] = array(
      '#type' => 'textarea',
      '#title' => t('Enter all the letters of your alphabet, in the correct order, and in lower case.'),
      '#default_value' => implode(' ', $config->get('alphabet')),
      //   '#default_value' => implode(' ', variable_get('lexicon_alphabet', range('a', 'z'))),
      '#description' => t('Separate the letters by a blank.'),
      '#rows' => 1,
    );
  
    $form['digits'] = array(
      '#type' => 'textarea',
      '#title' => t('Enter all the digits of your alphabet, in the correct order.'),
      '#default_value' => implode(' ', $config->get('digits')),
      //   '#default_value' => implode(' ', variable_get('lexicon_digits', range('0', '9'))),
      '#description' => t("Separate the digits by a blank. If you don't want terms to start with digits, leave this blank."),
      '#rows' => 1,
    );
  
    $form['suppress_unused'] = array(
      '#type' => 'checkbox',
      '#title' => t('Suppress unused letters?'),
      '#default_value' => $config->get('suppress_unused'),
      //   '#default_value' => variable_get('lexicon_suppress_unused', FALSE),
      '#description' => t('This will cause unused letters to be omitted from the alphabar.'),
    );
  
    $ab_seps = array(
      ' ' => t('none'),
      '|' => t('vertical bar (pipe)'),
      '&bull;' => t('bullet'),
      '&#8211;' => t('en-dash (&#8211;)'),
      '&#8212;' => t('em-dash (&#8212;)'),
      '_' => t('underscore'),
    );
    $form['alphabar_separator'] = array(
      '#type' => 'radios',
      '#options' => $ab_seps,
      '#title' => t('Alphabar separator'),
      '#default_value' => $config->get('alphabar_separator'),
      //   '#default_value' => variable_get('lexicon_alphabar_separator', '|'),
      '#description' => t('This is the character that will separate the letters in the alphabar.'),
      '#prefix' => '<div class="lexicon_radios">',
      '#suffix' => '</div>',
    );
  
    $form['alphabar_instruction'] = array(
      '#type' => 'textarea',
      '#title' => t('Alphabar instruction'),
      '#default_value' => $config->get('alphabar_instruction'),
      //   '#default_value' => variable_get('lexicon_alphabar_instruction', _lexicon_alphabar_instruction_default()),
      '#description' => t('This is the text that will appear immediately below the alphabar.'),
      '#rows' => 1,
    );
  
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#weight' => 5,
    );
  
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
    // Set the submitted configuration setting.
      ->set('alphabet', explode(" ", $form_state->getValue('alphabet')))
      ->set('digits', explode(" ", $form_state->getValue('digits')))
      ->set('suppress_unused', $form_state->getValue('suppress_unused'))
      ->set('alphabar_separator', $form_state->getValue('alphabar_separator'))
      ->set('alphabar_instruction', $form_state->getValue('alphabar_instruction'))
      ->save();

    parent::submitForm($form, $form_state); 
  }
}