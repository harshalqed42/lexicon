<?php

namespace Drupal\lexicon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure lexicon admin configuration settings.
 */
class lexiconTitleIntrosForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lexicon_intro';
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
    $vids = $config->get('lexicon_vids', []);
    $vids = array_filter($vids);
    $vids_setup = FALSE;

    $form['vids'] = [
      '#type' => 'value',
      '#value' => $vids,
    ];
    foreach ($vids as $vocabulary_name) {
      // Don't create form-items for vocabularies that have not been setup as
      // Lexicon vocabularies.
      if (!empty($vocabulary_name)) {
        $vids_setup = TRUE;

        $form['paths_and_titles_and_intros' . $vocabulary_name] = [
          '#type' => 'fieldset',
          '#title' => t('Path, title and intro settings for %vocabulary_name', ['%vocabulary_name' => $vocabulary_name]),
          '#collapsible' => TRUE,
        ];

        $form['paths_and_titles_and_intros' . $vocabulary_name]['lexicon_path_' . $vocabulary_name] = [
          '#type' => 'textfield',
          '#title' => t('The path of the lexicon for this vocabulary'),
          '#description' => t('Determines the path that is used for the lexicon page for this vocabulary. Default is: <em>%path</em>.', ['%path' => '/lexicon/' . $vocabulary_name]),
          '#required' => TRUE,
          '#default_value' => ($config->get('lexicon_path_' . $vocabulary_name) ? $config->get('lexicon_path_' . $vocabulary_name) : '/lexicon/' . $vocabulary_name),
        ];
        $form['paths_and_titles_and_intros' . $vocabulary_name]['lexicon_title_' . $vocabulary_name] = [
          '#type' => 'textfield',
          '#title' => t('The title of the lexicon for this vocabulary'),
          '#description' => t('Determines the title that is used for the lexicon page for this vocabulary. Default is: <em>%name</em>.', ['%name' => $vocabulary_name]),
          '#required' => TRUE,
          '#default_value' => ($config->get('lexicon_title_' . $vocabulary_name) ? $config->get('lexicon_title_' . $vocabulary_name) : $vocabulary_name),

        ];

        $introduction_text = '';
        $introduction_text_format = $config->get('lexicon_introduction_' . $vocabulary_name, NULL);
        if ($introduction_text_format != NULL) {
          $introduction_text = $introduction_text_format['value'];
        }
        $form['paths_and_titles_and_intros' . $vocabulary_name]['lexicon_introduction_' . $vocabulary_name] = [
          '#type' => 'text_format',
          '#title' => t('The optional introduction text for this vocabulary'),
          // '#default_value' => $introduction_text,
          '#description' => t('The optional introduction text that is displayed at the top of the Lexicon overview or, when the Lexicon is split over multiple pages, is shown on the Lexicon start page.', ['%name' => $vocabulary_name]),
          '#required' => FALSE,
          '#default_value' => ($config->get('lexicon_introduction_' . $vocabulary_name)) ? $config->get('lexicon_introduction_' . $vocabulary_name)['value'] : '',

        ];
      }
    }

    if ($vids_setup) {
      $form['lexicon_clear_menu_cache_on_submit'] = [
        '#type' => 'checkbox',
        '#title' => t('Clear the menu cache when settings are submitted.'),
        '#default_value' => TRUE,
        '#description' => t('Changes in the paths and titles are only visible when the menu cache is flushed. This setting ensures that the menu cache is flushed when the settings are submitted.'),
        '#prefix' => '<div class="lexicon_clear_menu_cache_on_submit">',
        '#suffix' => '</div>',
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save configuration'),
        '#weight' => 5,
      ];
    }
    else {
      drupal_set_message(t('No vocabularies were found. Until you set up, and select, at least one vocabulary for Lexicon, no settings can be entered.'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $vids = $form_state->getValue('vids');
    foreach ($vids as $vid) {
      if (!empty($vids)) {
        $this->configFactory->getEditable('lexicon.settings')
        // Set the submitted configuration setting.
          ->set('lexicon_path_' . $vid, $form_state->getValue('lexicon_path_' . $vid))
          ->set('lexicon_title_' . $vid, $form_state->getValue('lexicon_title_' . $vid))
          ->set('lexicon_introduction_' . $vid, $form_state->getValue('lexicon_introduction_' . $vid))
          ->save();
      }
    }
    // dump($form_state->getValue('lexicon_clear_menu_cache_on_submit'));
    // exit;.
    if ($form_state->getValue('lexicon_clear_menu_cache_on_submit') == 1) {
      _lexicon_clear_menu_cache();
      // _lexicon_clear_filter_cache(NULL, TRUE);
    }
    parent::submitForm($form, $form_state);
  }

}
