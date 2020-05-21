<?php

namespace Drupal\lexicon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure lexicon admin configuration settings.
 */
class lexiconAlphabetForm extends ConfigFormBase {

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
    return ['lexicon.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $config = $this->config('lexicon.settings');

    $form['lexicon_alphabet'] = [
      '#type' => 'textarea',
      '#title' => t('Enter all the letters of your alphabet, in the correct order, and in lower case.'),
      '#default_value' => implode(' ', !empty($config->get('lexicon_alphabet')) ? $config->get('lexicon_alphabet') : range('a', 'z')),
      '#description' => t('Separate the letters by a blank.'),
      '#rows' => 1,
    ];

    $form['lexicon_digits'] = [
      '#type' => 'textarea',
      '#title' => t('Enter all the digits of your alphabet, in the correct order.'),
      '#default_value' => implode(' ', !empty($config->get('lexicon_digits')) ? $config->get('lexicon_digits') : range(0, 9)),
      '#description' => t("Separate the digits by a blank. If you don't want terms to start with digits, leave this blank."),
      '#rows' => 1,
    ];

    $form['suppress_unused'] = [
      '#type' => 'checkbox',
      '#title' => t('Suppress unused letters?'),
      '#default_value' => $config->get('lexicon_suppress_unused'),
      '#description' => t('This will cause unused letters to be omitted from the alphabar.'),
    ];

    $ab_seps = [
      ' ' => t('none'),
      '|' => t('vertical bar (pipe)'),
      '&bull;' => t('bullet'),
      '&#8211;' => t('en-dash (&#8211;)'),
      '&#8212;' => t('em-dash (&#8212;)'),
      '_' => t('underscore'),
    ];
    $form['alphabar_separator'] = [
      '#type' => 'radios',
      '#options' => $ab_seps,
      '#title' => t('Alphabar separator'),
      '#default_value' => $config->get('lexicon_alphabar_separator'),
      '#description' => t('This is the character that will separate the letters in the alphabar.'),
      '#prefix' => '<div class="lexicon_radios">',
      '#suffix' => '</div>',
    ];

    $form['alphabar_instruction'] = [
      '#type' => 'textarea',
      '#title' => t('Alphabar instruction'),
      '#default_value' => $config->get('lexicon_alphabar_instruction'),
      '#description' => t('This is the text that will appear immediately below the alphabar.'),
      '#rows' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('lexicon.settings')
    // Set the submitted configuration setting.
      ->set('lexicon_alphabet', explode(" ", $form_state->getValue('lexicon_alphabet')))
      ->set('lexicon_digits', explode(" ", $form_state->getValue('lexicon_digits')))
      ->set('lexicon_suppress_unused', $form_state->getValue('suppress_unused'))
      ->set('lexicon_alphabar_separator', $form_state->getValue('alphabar_separator'))
      ->set('lexicon_alphabar_instruction', $form_state->getValue('alphabar_instruction'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
