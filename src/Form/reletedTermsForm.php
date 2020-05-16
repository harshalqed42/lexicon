<?php

namespace Drupal\lexicon\Form;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure lexicon admin configuration settings.
 */
class reletedTermsForm extends ConfigFormBase {

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
    return 'lexicon_terms';
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
    $form = [];
    $config = $this->config(static::SETTINGS);
    $vids = $config->get('lexicon_vids', []);
    $vids = array_filter($vids);
    $vocs = taxonomy_vocabulary_get_names();
    $vids_setup = FALSE;
    $form['vids'] = [
      '#type' => 'value',
      '#value' => $vids,
    ];
    $config = $this->config(static::SETTINGS);
    foreach ($vids as $vid) {
      // Don't create form-items for vocabularies that have not been setup as
      // Lexicon vocabularies.
      if (!empty($vid)) {
        $vids_setup = TRUE;
        // Until appropriate fields have been found there is no setup for related
        // terms, synonyms or image.
        $related_terms_setup = $synonyms_setup = $image_setup = FALSE;

        // Create the option "none" for each select option.
        $options_related_terms = $options_synonyms = $options_image = [
          '' => t('none'),
        ];

        // Get all the instances of fields related to the current vocabulary.
        $instances = \Drupal::entityManager()->getFieldDefinitions('taxonomy_term', $vid);

        foreach ($instances as $field) {
          if ($field instanceof FieldConfig) {

            switch ($field->getType()) {
              // If the type is "taxonomy_term_refernence" then it might be a
              // field for "Related terms".
              case "entity_reference":
                $options_related_terms[$field->get('field_name')] = $field->get('label');
                $related_terms_setup = TRUE;
                break;

              // If the type is "text" then it might be a field for "Synonyms".
              case "string":
                $options_synonyms[$field->get('field_name')] = $field->get('label');
                $synonyms_setup = TRUE;
                break;

              // If the type is "image" then it might be a field for "Image".
              case "image":
                $options_image[$field->get('field_name')] = $field->get('label');
                $image_setup = TRUE;
                break;

              default:
                break;
            }
          }
        }

        $vocabulary = Vocabulary::load($vid);
        $vocabulary_name = $vocabulary->get('name');

        if ($related_terms_setup || $synonyms_setup || $image_setup) {
          $form['related_terms_and_synonyms_and_image_' . $vid] = [
            '#type' => 'fieldset',
            '#title' => t('Related terms, synonyms and image settings for ' . $vocabulary_name),
            '#collapsible' => TRUE,
          ];
        }
        if ($related_terms_setup) {
          $form['related_terms_and_synonyms_and_image_' . $vid]['lexicon_related_terms_field_' . $vid] = [
            '#type' => 'select',
            '#options' => $options_related_terms,
            '#title' => t('Field for related terms'),
            '#default_value' => $config->get('lexicon_related_terms_field_' . $vid, ''),
            '#description' => t('Determines if related terms are shown and which field is used for the related terms. The default value is : <em>none</em>.'),
            '#prefix' => '<div class="lexicon_related_terms">',
            '#suffix' => '</div>',
            '#required' => FALSE,
          ];
        }
        else {
          drupal_set_message(t('No fields for related terms for the vocabulary <em> %vocabulary_name </em> were found. Until you set up at least one related terms field of the type "Term reference" for the vocabulary, no field can be selected.', ['%vocabulary_name' => $vocabulary_name]));
        }

        if ($synonyms_setup) {
          $form['related_terms_and_synonyms_and_image_' . $vid]['lexicon_synonyms_field_' . $vid] = [
            '#type' => 'select',
            '#options' => $options_synonyms,
            '#title' => t('Field for synonyms'),
            '#default_value' => $config->get('lexicon_synonyms_field_' . $vid, ''),
            '#description' => t('Determines if synonyms are shown and which field is used for synonyms. The default value is :') . ' <em>none</em>.',
            '#prefix' => '<div class="lexicon_synonyms">',
            '#suffix' => '</div>',
            '#required' => FALSE,
          ];
        }
        else {
          drupal_set_message(t('No fields for synonyms for the vocabulary <em> %vocabulary_name </em> were found. Until you set up at least one synonyms field of the type "Text" for the vocabulary, no field can be selected.', ['%vocabulary_name' => $vocabulary_name]));
        }
        if ($image_setup) {
          $form['related_terms_and_synonyms_and_image_' . $vid]['lexicon_image_field_' . $vid] = [
            '#type' => 'select',
            '#options' => $options_image,
            '#title' => t('Field for image'),
            '#default_value' => $config->get('lexicon_image_field_' . $vid, ''),
            '#description' => t('Determines if images are shown and which field is used as the image. The default value is :') . ' <em>none</em>.',
            '#prefix' => '<div class="lexicon_image">',
            '#suffix' => '</div>',
            '#required' => FALSE,
          ];
        }
        else {
          drupal_set_message(t('No fields for image for the vocabulary <em> %vocabulary_name </em> were found. Until you set up at least one image field of the type "Image" for the vocabulary, no field can be selected.', ['%vocabulary_name' => $vocabulary_name]));
        }
      }
    }

    if ($vids_setup && ($related_terms_setup || $synonyms_setup || $image_setup)) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save configuration'),
        '#weight' => 5,
      ];
    }
    else {
      if (!$vids_setup) {
        drupal_set_message(t('No vocabularies were found. Until you set up, and select, at least one vocabulary for Lexicon, no settings can be entered.'));
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vids = $form_state->getValues()['vids'];

    foreach ($vids as $vid) {
      if (!empty($vid)) {
        $this->configFactory->getEditable(static::SETTINGS)
          ->set('lexicon_related_terms_field_' . $vid, $form_state->getValues()['lexicon_related_terms_field_' . $vid])
          ->set('lexicon_synonyms_field_' . $vid, $form_state->getValues()['lexicon_synonyms_field_' . $vid])
          ->set('lexicon_image_field_' . $vid, $form_state->getValues()['lexicon_image_field_' . $vid])
          ->save();
      }
    }
    parent::submitForm($form, $form_state);
  }

}
